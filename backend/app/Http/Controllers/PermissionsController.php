<?php

namespace BitApps\FM\Http\Controllers;

if (! \defined('ABSPATH')) {
    exit;
}

use BitApps\FM\Http\Requests\Permissions\AddUserPermissionRequest;
use BitApps\FM\Http\Requests\Permissions\DeleteUserPermissionRequest;
use BitApps\FM\Http\Requests\Permissions\PermissionsGetRequest;
use BitApps\FM\Http\Requests\Permissions\PermissionsUpdateRequest;
use BitApps\FM\Http\Requests\Permissions\SearchUserRequest;
use BitApps\FM\Plugin;
use BitApps\FM\Providers\PermissionsProvider;
use BitApps\FM\Vendor\BitApps\WPKit\Http\Response;
use WP_User_Query;

final class PermissionsController
{
    public PermissionsProvider $permissionProvider;

    public function __construct()
    {
        $this->permissionProvider = Plugin::instance()->permissions();
    }

    public function get(PermissionsGetRequest $request)
    {
        return Response::success(
            [
                'permissions' => $this->permissionProvider->permissions,
                'roles'       => $this->permissionProvider->allRoles(),
                'users'       => array_values($this->permissionProvider->permittedUsers()),
                'commands'    => $this->permissionProvider->allCommands(),
                'fileTypes'   => ['text', 'image', 'application', 'video', 'audio', 'php', 'javascript', 'css'],
                'wpRoot'      => ABSPATH,
            ]
        );
    }

    public function update(PermissionsUpdateRequest $request)
    {
        if ($this->permissionProvider->updatePermissionSetting($request->validated())) {
            return Response::success([])->message('Permission updated successfully');
        }

        return Response::error([])->message('failed to update permission');
    }

    public function searchUser(SearchUserRequest $request)
    {
        $paged       = $request->has('page') && $request->page > 0 ? $request->page : 1;
        $per_page    = 50;
        $args        = [
            'search'         => '*' . esc_attr($request->search) . '*',
            'search_columns' => ['user_login', 'display_name', 'user_email'],
            'number'         => $per_page,
            'paged'          => $paged,
            'exclude'        => Plugin::instance()->permissions()->permittedUserIds(),
        ];

        $users       = new WP_User_Query($args);
        $total_users = $users->get_total();
        $results     = [];
        $pages       = ceil($total_users / $per_page);

        if (!empty($users->get_results())) {
            foreach ($users->get_results() as $user) {
                $results[] = [
                    'ID'                   => $user->ID,
                    'display_name'         => $user->display_name,
                    'user_login'           => $user->user_login,
                    'user_email'           => $user->user_email,
                ];
            }
        }

        return Response::success(['users' => $results, 'total' => $total_users, 'pages' => $pages, 'current' => $paged]);
    }

    public function addPermissionByUser(AddUserPermissionRequest $request)
    {
        if (Plugin::instance()->permissions()->addByUser($request->id, ['path' => $request->path, 'commands' => $request->commands])) {
            return Response::success([])->message(__('Permission granted to user', 'file-manager'));
        }

        return Response::error([])->message(__('Unable to add user permission', 'file-manager'));
    }

    public function deletePermissionByUser(DeleteUserPermissionRequest $request)
    {
        if (Plugin::instance()->permissions()->removeByUser($request->id)) {
            return Response::success([])->message(__('User permission revoked', 'file-manager'));
        }

        return Response::error([])->message(__('Unable to revoke user permission', 'file-manager'));
    }
}
