# Bit File Manager developer hooks

Five filters let a site owner change who can use the file manager, which folders they see and what they may do there, from a small PHP snippet (theme `functions.php`, a must-use plugin or a code snippets plugin). No plugin settings change is needed.

| Filter | Decides |
| --- | --- |
| [`bitapps_fm_can_access_frontend`](#bitapps_fm_can_access_frontend) | Whether the visitor may use the file manager outside the admin File Manager screen |
| [`bitapps_fm_file_roots`](#bitapps_fm_file_roots) | Which folders (volumes) a guest or non-admin user sees |
| [`bitapps_fm_can_run_command`](#bitapps_fm_can_run_command) | Whether a command (upload, download, delete, ...) may run |
| [`bitapps_fm_file_access`](#bitapps_fm_file_access) | Per-file `read` / `write` / `locked` / `hidden` attributes |
| [`bitapps_fm_toolbar_commands`](#bitapps_fm_toolbar_commands) | Which toolbar and context-menu buttons are shown |

Every filter can both grant and deny, and every filter receives the request context as its last argument.

## How a request flows

The file manager talks to the server through `admin-ajax.php`, never through the REST API:

| Screen | `action` | Nonce | Who passes |
| --- | --- | --- | --- |
| Admin File Manager (wp-admin) | `bitapps_fm_connector` | `wp_rest` | Logged-in users |
| `[file-manager]` shortcode | `bitapps_fm_connector_front` | `bitapps_fm_public_nonce` | Anyone, guests included |

For every connector request:

1. **Dashboard shortcut.** A logged-in user with `manage_options` (filterable through `bitapps_fm_can_access_home`) calling the admin connector gets the site-root dashboard volume. None of the filters below except `bitapps_fm_can_run_command` and `bitapps_fm_file_access` run for that user.
2. **Gate.** Any other request is ended with "You don't have permission to access the file manager." unless `bitapps_fm_can_access_frontend` returns `true`. The shortcode itself renders the same message instead of the file manager.
3. **Volumes.** The guest volume (logged out) or the user's volumes from File Manager > Permissions go through `bitapps_fm_file_roots`. The plugin's allowed file types, upload size limit and hidden-file rules are then applied to every returned volume.
4. **Commands.** Every command except `open`, `search`, `subdirs`, `url`, `abort` and `callback` goes through `bitapps_fm_can_run_command`.
5. **Files.** Every listed file and folder goes through `bitapps_fm_file_access`.

`bitapps_fm_toolbar_commands` runs when a page that shows the file manager is rendered, not on connector requests.

## The `$context` argument

`$context` is `'frontend'` or `'admin'`:

| Request | `$context` |
| --- | --- |
| A frontend page showing the `[file-manager]` shortcode | `'frontend'` |
| Shortcode connector (`action=bitapps_fm_connector_front`) | `'frontend'` |
| Any wp-admin page | `'admin'` |
| Admin connector and every other admin-ajax request | `'admin'` |
| REST API requests | `'admin'` |

Check `$context` instead of `is_admin()`: admin-ajax requests always report `is_admin()` as `true`, including the shortcode's own requests.

`$context` only says which screen the request came from. It grants nothing. Any logged-in user can obtain a `wp_rest` nonce and call the admin connector, where `$context` is `'admin'`; non-admins there are still stopped by `bitapps_fm_can_access_frontend`. So **deny in that filter regardless of `$context`**, and limit the other filters to `'frontend'` when they should only change the shortcode.

## Filter reference

### bitapps_fm_can_access_frontend

```php
apply_filters( 'bitapps_fm_can_access_frontend', bool $allowed, WP_User $user, string $context ): bool
```

| Parameter | Description |
| --- | --- |
| `$allowed` | Default `true`. |
| `$user` | The current user. Guests get a `WP_User` with ID `0` and no roles. |
| `$context` | `'frontend'` or `'admin'`. |

Return `false` to hide the shortcode (it shows "You don't have permission to access the file manager." instead) and to reject every connector request except the admin dashboard shortcut. Users with `manage_options` on the admin File Manager screen never reach this filter, so a deny here cannot lock out site admins.

```php
// Logged-in users only.
add_filter( 'bitapps_fm_can_access_frontend', function ( $allowed, $user ) {
    return $allowed && $user->exists();
}, 10, 2 );
```

### bitapps_fm_file_roots

```php
apply_filters( 'bitapps_fm_file_roots', FileRoot[] $roots, WP_User $user, string $context ): FileRoot[]
```

| Parameter | Description |
| --- | --- |
| `$roots` | The volumes the plugin would use: the guest volume, or the user's volumes from File Manager > Permissions. |
| `$user` | The current user. |
| `$context` | `'frontend'` or `'admin'`. |

Return an array of `\BitApps\FM\Providers\FileManager\FileRoot`. Add, remove or replace entries; any path is allowed, including folders outside the WordPress root. Entries that are not a `FileRoot` are dropped, and a non-array return gives no volumes. Both trigger a `_doing_it_wrong` notice when `WP_DEBUG` is on.

```php
use BitApps\FM\Providers\FileManager\FileRoot;

new FileRoot( string $path, string $url, ?string $alias = null );
```

| Argument / method | Purpose |
| --- | --- |
| `$path` | Absolute folder path. It must exist and be readable by PHP; if no volume is readable, users see "There is no readable volume". |
| `$url` | Public URL of the folder, or `''` to serve files only through the permission-checked connector. Use `''` for private folders. |
| `$alias` | Name shown in the folder tree. |
| `setDisabled( string[] $commands )` | elFinder commands this volume refuses, whatever `bitapps_fm_can_run_command` says (`rm`, `rename`, `put`, `mkdir`, `mkfile`, `cut`, `copy`, `paste`, `duplicate`, `archive`, `extract`, `chmod`, ...). |
| `setOption( string $name, mixed $value )` | Any other [elFinder root option](https://github.com/Studio-42/elFinder/wiki/Connector-configuration-options-2.1#root-options). |

After the filter, the plugin sets each volume's upload MIME allow/deny lists, upload size limit, access-control callback and name validation from its settings, overwriting the same options set in the filter. Each volume also gets its own Trash volume when Trash is enabled in the settings.

```php
// Each logged-in user gets a private folder outside the web root.
add_filter( 'bitapps_fm_file_roots', function ( $roots, $user, $context ) {
    if ( 'frontend' !== $context || ! $user->exists() ) {
        return $roots;
    }
    $dir = '/srv/private-files/' . $user->ID;
    wp_mkdir_p( $dir );
    return array( new \BitApps\FM\Providers\FileManager\FileRoot( $dir, '', 'My files' ) );
}, 10, 3 );
```

### bitapps_fm_can_run_command

```php
apply_filters( 'bitapps_fm_can_run_command', bool $authorized, string $cmd, string[] $paths, WP_User $user, string $context ): bool
```

| Parameter | Description |
| --- | --- |
| `$authorized` | The plugin's verdict from File Manager > Permissions. |
| `$cmd` | Normalized command: `file` and `zipdl` arrive as `download`; `put` and `get` arrive as `edit`. Others keep their elFinder name (`ls`, `tree`, `info`, `size`, `parents`, `dim`, `upload`, `rm`, `rename`, `mkdir`, `mkfile`, `paste`, `duplicate`, `archive`, `extract`, `chmod`, ...). |
| `$paths` | Absolute paths of the command's targets. See the note on empty paths below. |
| `$user` | The current user. |
| `$context` | `'frontend'` or `'admin'`. |

The return value is cast to `bool` and is final: `true` runs the command (uploads are still scanned for scripts), `false` returns "You don't have permission to ... here." to the user.

Things to know:

- **Empty `$paths`.** Most commands are checked twice: first early with empty `$paths`, then with the resolved target paths. `file` (preview/download) skips the early check. `$paths` is also empty when the targets cannot be resolved, and elFinder rejects such a request anyway. A path-based grant must therefore also return `true` when `$paths` is empty.
- **Listing needs commands too.** `ls`, `tree`, `info` and `size` pass through this filter; deny them and folders cannot be listed. An allow-list usually also needs `parents` (reopening a deep folder) and `dim` (image dimensions). Both only read.
- **`edit` covers preview and save.** Text preview sends `get`, which arrives as `edit`; granting `edit` therefore also allows saving with `put`, unless the volume disables `put`.
- **Volumes still win.** A granted command does not run on a volume that lists it in `setDisabled()`.
- **Admins too.** This filter also runs on the admin File Manager screen. Check `$context` when a rule is meant for the shortcode only.

```php
// Nobody may delete through the shortcode.
add_filter( 'bitapps_fm_can_run_command', function ( $authorized, $cmd, $paths, $user, $context ) {
    return 'frontend' === $context && 'rm' === $cmd ? false : $authorized;
}, 10, 5 );
```

### bitapps_fm_file_access

```php
apply_filters( 'bitapps_fm_file_access', ?bool $result, string $attr, string $path, string $relPath, ?bool $isDir, string $context ): ?bool
```

| Parameter | Description |
| --- | --- |
| `$result` | The plugin's hidden-file (dotfile) decision, or `null` to keep elFinder's default. |
| `$attr` | `read`, `write`, `locked` or `hidden`. |
| `$path` | Absolute path of the file or folder. |
| `$relPath` | Path relative to the volume root, starting with `/` (the root itself is `/`). |
| `$isDir` | `true` for a folder, `false` for a file, `null` when unknown. |
| `$context` | `'frontend'` or `'admin'`. |

Return `true` or `false` to set the attribute, or `null` to keep the default. This runs for every attribute of every listed item, so keep it fast: no database queries or remote calls.

```php
// Hide backup files on the frontend.
add_filter( 'bitapps_fm_file_access', function ( $result, $attr, $path, $relPath, $isDir, $context ) {
    if ( 'frontend' === $context && 'hidden' === $attr && '.bak' === substr( $path, -4 ) ) {
        return true;
    }
    return $result;
}, 10, 6 );
```

### bitapps_fm_toolbar_commands

```php
apply_filters( 'bitapps_fm_toolbar_commands', string[] $commands, WP_User $user, string $context ): string[]
```

| Parameter | Description |
| --- | --- |
| `$commands` | The user's commands from File Manager > Permissions (`download`, `upload`, `rm`, `edit`, `rename`, `mkdir`, ...); `['*']` for users granted everything. |
| `$user` | The current user. |
| `$context` | `'frontend'` or `'admin'`. |

Return the commands whose buttons should show; every other managed command is hidden from the toolbar and context menu. Non-string entries are dropped and a non-array return hides them all; both trigger a `_doing_it_wrong` notice. When the list is not empty, the shortcode adds `sort`, `fullscreen`, `view` and `search`.

This only changes buttons. A hidden command can still be sent to the server, so block it with `bitapps_fm_can_run_command`; likewise, a shown button only works if that filter allows the command.

## Complete example: a board-only archive

Only users with the `board_member` role may use the shortcode. They see a single "Archief" folder where they can list, upload and download files but cannot delete, rename or edit them. Everyone else, guests included, gets "You don't have permission to access the file manager."

1. Create the folder (here `ABSPATH . 'archief'`, ideally outside the web root) and make it writable by PHP.
2. Enable the file types board members may upload in File Manager > Permissions.
3. Put `[file-manager]` on a page.
4. Add the snippet:

```php
function my_is_board_member( $user ) {
    return in_array( 'board_member', (array) $user->roles, true );
}

// No $context check here: admins on the File Manager screen never reach this filter.
add_filter( 'bitapps_fm_can_access_frontend', function ( $allowed, $user ) {
    return $allowed && my_is_board_member( $user );
}, 10, 2 );

add_filter( 'bitapps_fm_file_roots', function ( $roots, $user, $context ) {
    if ( 'frontend' !== $context || ! my_is_board_member( $user ) ) {
        return $roots;
    }
    $archief = new \BitApps\FM\Providers\FileManager\FileRoot( ABSPATH . 'archief', '', 'Archief' );
    $archief->setDisabled( array( 'rm', 'rename', 'edit', 'put', 'mkdir', 'mkfile', 'cut', 'copy', 'paste', 'duplicate', 'archive', 'extract', 'chmod' ) );
    return array( $archief );
}, 10, 3 );

add_filter( 'bitapps_fm_can_run_command', function ( $authorized, $cmd, $paths, $user, $context ) {
    if ( 'frontend' !== $context || ! my_is_board_member( $user ) ) {
        return $authorized;
    }
    return in_array( $cmd, array( 'ls', 'tree', 'info', 'size', 'parents', 'dim', 'upload', 'download' ), true );
}, 10, 5 );

add_filter( 'bitapps_fm_toolbar_commands', function ( $commands, $user, $context ) {
    if ( 'frontend' !== $context || ! my_is_board_member( $user ) ) {
        return $commands;
    }
    return array( 'upload', 'download' );
}, 10, 3 );
```

Board members on the frontend only get the Archief volume, so the command filter needs no path check. Outside the frontend the last three filters return the plugin's defaults untouched.

What each visitor gets:

| Visitor | Shortcode page | Admin connector |
| --- | --- | --- |
| Guest | Access-denied message | Not reachable (needs a login) |
| Subscriber without `board_member` | Access-denied message | Rejected |
| `board_member` | Archief only; upload and download | Their File Manager > Permissions volumes, unchanged |
| Administrator | Access-denied message (not a board member) | Full admin File Manager, unchanged |

## Troubleshooting

- **A filter seems ignored.** Check the accepted-args count in `add_filter()`: `$context` is the 3rd argument for `can_access_frontend`, `file_roots` and `toolbar_commands`, the 5th for `can_run_command` and the 6th for `file_access`.
- **"There is no readable volume".** The `FileRoot` path does not exist or PHP cannot read it.
- **A button shows but the action fails** (or the reverse): `bitapps_fm_toolbar_commands` and `bitapps_fm_can_run_command` disagree.
- **An upload is rejected with `errUploadMime`.** The file type is not enabled in File Manager > Permissions; the settings apply to filtered volumes too.
- **Wrong return types** are reported through `_doing_it_wrong`; turn on `WP_DEBUG` to see them.
