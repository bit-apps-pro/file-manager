<?php

namespace BitApps\FM\Providers;

use BitApps\FM\Plugin;

\defined('ABSPATH') or exit();
class MediaSynchronizer
{
    public $wpUploadBaseDirectory;

    function __construct()
    {
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $this->wpUploadBaseDirectory = wp_upload_dir()['basedir'];
    }

    // Triggers when a file is uploaded and initiates the uploading process for single or batch files.
    public function onFileUpload($cmd, &$result, $args, $elfinder, $volume)
    {
        if (!Plugin::instance()->preferences()->isWpMediaSyncEnabled() || empty($result['added'])) {
            return;
        }

        $images = [];
        foreach ($result['added'] as $added) {
            if (empty($added['hash']) || ($added['mime'] ?? '') === 'directory') {
                continue; // folder-structure uploads also add dir stats; sync files only
            }

            // Resolve the file elFinder actually saved (name basename-sanitized, collision-safe)
            // from its own hash — never the raw client filename in $args, which can carry a `../`
            // traversal. Confine on the FINAL path, not just the target directory.
            $path = $volume->getPath($added['hash']);
            if (!\is_string($path) || $path === '') {
                continue;
            }
            if (PermissionsProvider::realpathWithin($path, $this->wpUploadBaseDirectory) === null) {
                continue;
            }

            $name     = wp_basename($path);
            $images[] = [
                'name' => $name,
                'type' => wp_check_filetype($name, null),
                'path' => $path,
                'url'  => $this->abs_path_to_url($path),
            ];
        }

        if (!empty($images)) {
            $this->addMedia($images);
        }
    }

    private function addMedia($images)
    {
        foreach ($images as $image) {
            $attachment = [
                'post_mime_type' => $image['type']['type'],
                'post_title'     => sanitize_file_name($image['name']),
            ];
            $attachmentId  = wp_insert_attachment($attachment, $image['path']);
            $attachData    = wp_generate_attachment_metadata($attachmentId, $image['path']);
            wp_update_attachment_metadata($attachmentId, $attachData);
        }
    }

    /**
     * Converts an absolute file path to a URL. User can upload files to any directory
     * within the WordPress root, but only files within the wp-content/uploads directory
     * will be added to the media library. This function helps to generate the correct
     * URL for the uploaded file based on its absolute path.
     *
     * @param string $path The absolute file path to convert.
     *
     * @return string The corresponding URL for the given file path.
     */
    private function abs_path_to_url($path = '')
    {
        $url = str_replace(
            wp_normalize_path(untrailingslashit(ABSPATH)),
            site_url(),
            wp_normalize_path($path)
        );

        return esc_url_raw($url);
    }
}
