<?php

namespace BitApps\FM\Http\Controllers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


use BitApps\FM\Http\Requests\Settings\LangUpdateRequest;
use BitApps\FM\Http\Requests\Settings\SettingsRequest;
use BitApps\FM\Http\Requests\Settings\SettingsUpdateRequest;
use BitApps\FM\Http\Requests\Settings\ThemeUpdateRequest;
use BitApps\FM\Http\Requests\Settings\ToggleViewRequest;
use BitApps\FM\Plugin;
use BitApps\FM\Providers\PreferenceProvider;
use BitApps\FM\Vendor\BitApps\WPKit\Http\Response;

final class SettingsController
{
    public PreferenceProvider $preferenceProvider;

    public function __construct()
    {
        $this->preferenceProvider = Plugin::instance()->preferences();
    }

    public function get(SettingsRequest $request)
    {
        return Response::success(
            [
                'settings'  => $this->preferenceProvider->all(),
                'defaults'  => $this->preferenceProvider->defaultPrefs(),
                'themes'    => $this->preferenceProvider->getThemes(),
                'languages' => $this->preferenceProvider->getLanguages(),
            ]
        );
    }

    public function update(SettingsUpdateRequest $request)
    {
        $updatedSettings = $request->validated();

        $settingsService = Plugin::instance()->preferences();

        $settingsService->toggleWpMediaSync($updatedSettings['wp_media_sync']);
        $settingsService->setLinkPathVisibility($updatedSettings['show_url_path']);
        $settingsService->setVisibilityOfHiddenFile($updatedSettings['show_hidden_files']);
        $settingsService->setPermissionForTrashCreation($updatedSettings['create_trash_files_folders']);
        $settingsService->setPermissionForHiddenFolderCreation($updatedSettings['create_hidden_files_folders']);
        $settingsService->setRememberLastDir($updatedSettings['remember_last_dir']);
        $settingsService->setClearHistoryOnReload($updatedSettings['clear_history_on_reload']);
        $settingsService->setRootVolumeName($updatedSettings['root_folder_name']);
        $settingsService->setTheme($updatedSettings['theme']);
        $settingsService->setLang($updatedSettings['language']);
        $settingsService->setViewType($updatedSettings['default_view_type']);
        $settingsService->setRootPath($updatedSettings['root_folder_path']);
        $settingsService->setRootUrl($updatedSettings['root_folder_url']);
        $settingsService->setWidth($updatedSettings['size']['width']);
        $settingsService->setHeight($updatedSettings['size']['height']);
        $settingsService->setUiOptions($updatedSettings['display_ui_options']);

        if ($settingsService->saveOptions()) {
            return Response::success([])->message('Settings updated successfully');
        }

        return Response::error([])->message('failed to update settings');
    }

    public function toggleView(ToggleViewRequest $request)
    {
        $updatedSettings = $request->validated();

        $settingsService = Plugin::instance()->preferences();

        $settingsService->setViewType($updatedSettings['viewType']);

        if ($settingsService->saveOptions()) {
            return Response::success([])->message('View type updated successfully');
        }

        return Response::error([])->message('failed to update view type');
    }

    public function updateTheme(ThemeUpdateRequest $request)
    {
        $reqData = $request->validated();
        $prefs   = Plugin::instance()->preferences();
        $prefs->setTheme($reqData['theme']);
        if ($prefs->saveOptions()) {
            return Response::success([])->message(__('Theme updated successfully', 'file-manager'));
        }

        return Response::error([])->message(__('Failed to update theme', 'file-manager'));
    }

    public function getLanguages()
    {
        return Response::success(Plugin::instance()->preferences()->getLanguages());
    }

    public function updateLanguage(LangUpdateRequest $request)
    {
        $reqData = $request->validated();
        $prefs   = Plugin::instance()->preferences();
        $prefs->setLang($reqData['lang']);
        if ($prefs->saveOptions()) {
            return Response::success([])->message(__('Language updated successfully', 'file-manager'));
        }

        return Response::error([])->message(__('Failed to update language', 'file-manager'));
    }
}
