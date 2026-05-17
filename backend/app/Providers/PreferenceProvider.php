<?php

namespace BitApps\FM\Providers;

use BitApps\FM\Config;
use BitApps\FM\Plugin;
use BitApps\FM\Providers\FileManager\ClientOptions;
use BitApps\FM\Vendor\BitApps\WPKit\Http\RequestType;
use BitApps\FM\Vendor\BitApps\WPKit\Utils\Capabilities;

\defined('ABSPATH') || exit();
class PreferenceProvider
{
    public $preferences;

    private $_availableLang;

    private $_availableThemes;

    public function __construct()
    {
        $this->preferences = Config::getOption('preferences', $this->defaultPrefs());
        $this->fallback();
    }

    public function all()
    {
        return $this->preferences;
    }

    public function permissions()
    {
        return Plugin::instance()->permissions();
    }

    public function defaultPrefs()
    {
        return [
            'show_url_path'      => 'show',
            'language'           => 'en',
            'size'               => [
                'width'  => 'auto',
                'height' => 'auto',
            ],
            'default_view_type'  => 'icons',
            'display_ui_options' => [
                'toolbar',
                'places',
                'tree',
                'path',
                'stat',
            ],
            'root_folder_path'   => ABSPATH,
            'root_folder_url'    => Config::get('SITE_URL'),
        ];
    }

    /**
     * Saves pref
     * */
    public function saveOptions()
    {
        return Config::updateOption('preferences', $this->preferences, true);
    }

    /**
     * Returns all available themes
     *
     * @return array
     */
    public function themes()
    {
        if (isset($this->_availableThemes)) {
            return $this->_availableThemes;
        }

        $this->_availableThemes = [];
        $themeBase              = Config::get('ASSET_DIR') . DIRECTORY_SEPARATOR . 'themes';
        $themeDirs              = scandir($themeBase);

        foreach ($themeDirs as $theme) {
            if ($theme === '.' || $theme === '..') {
                continue;
            }

            $variants = scandir($themeBase . DIRECTORY_SEPARATOR . $theme);
            foreach ($variants as $variant) {
                if ($variant === '.' || $variant === '..') {
                    continue;
                }

                if (
                    is_readable(
                        $themeBase . DIRECTORY_SEPARATOR . $theme
                        . DIRECTORY_SEPARATOR . $variant . DIRECTORY_SEPARATOR
                         . $variant
                         . '.json'
                    )) {
                    $this->_availableThemes[$variant] = Config::get('ASSET_URI') . "/themes/{$theme}/{$variant}/{$variant}.json";
                }
            }
        }

        return $this->_availableThemes;
    }

    /**
     * Returns available themes as an array of Assoc-array
     *
     * @return array<int, array<string,string>>
     */
    public function getThemes()
    {
        $themes = [];
        foreach ($this->themes() as $key => $config) {
            $themes[] = [
                'key'   => $key,
                'title' => ucfirst(str_replace('-', ' ', $key)),
            ];
        }

        $themes[] = [
            'key'   => 'default',
            'title' => 'Default',
        ];

        return $themes;
    }

    /**
     * Returns selected theme from settings
     *
     * @return array
     */
    public function getTheme()
    {
        $theme = 'material-default';
        if (isset($this->preferences['theme'])) {
            $theme = esc_attr($this->preferences['theme']);
        }

        return $theme;
    }

    /**
     * Sets theme
     *
     * @param mixed $theme
     *
     * @return array
     */
    public function setTheme($theme)
    {
        $this->preferences['theme'] = $theme;
    }

    /**
     * Sets Link Path Visibility
     *
     * @param string $view
     */
    public function setLinkPathVisibility($view)
    {
        $this->preferences['show_url_path'] = $view;
    }

    /**
     * Returns selected show_url_path from settings
     *
     * @return string
     */
    public function isLinkPathVisibleInInfo()
    {
        $view = true;
        if (isset($this->preferences['show_url_path'])) {
            $view = \boolval($this->preferences['show_url_path']);
        }

        return esc_attr($view);
    }

    public function getDefaultLangCode()
    {
        if (get_locale() === 'en_US') {
            $code = 'en';
        } else {
            $code = get_locale();
        }

        return esc_attr($code);
    }

    public function availableLanguages()
    {
        if (!isset($this->_availableLang)) {
            $allLangs = [
                'LANG'  => __('Default', 'file-manager'),
                'aa'    => __('Afar', 'file-manager'),
                'ab'    => __('Abkhazian', 'file-manager'),
                'ae'    => __('Avestan', 'file-manager'),
                'af'    => __('Afrikaans', 'file-manager'),
                'ak'    => __('Akan', 'file-manager'),
                'am'    => __('Amharic', 'file-manager'),
                'an'    => __('Aragonese', 'file-manager'),
                'ar'    => __('Arabic', 'file-manager'),
                'as'    => __('Assamese', 'file-manager'),
                'av'    => __('Avaric', 'file-manager'),
                'ay'    => __('Aymara', 'file-manager'),
                'az'    => __('Azerbaijani', 'file-manager'),
                'ba'    => __('Bashkir', 'file-manager'),
                'be'    => __('Belarusian', 'file-manager'),
                'bg'    => __('Bulgarian', 'file-manager'),
                'bh'    => __('Bihari', 'file-manager'),
                'bi'    => __('Bislama', 'file-manager'),
                'bm'    => __('Bambara', 'file-manager'),
                'bn'    => __('Bengali', 'file-manager'),
                'bo'    => __('Tibetan', 'file-manager'),
                'br'    => __('Breton', 'file-manager'),
                'bs'    => __('Bosnian', 'file-manager'),
                'ca'    => __('Catalan', 'file-manager'),
                'ce'    => __('Chechen', 'file-manager'),
                'ch'    => __('Chamorro', 'file-manager'),
                'co'    => __('Corsican', 'file-manager'),
                'cr'    => __('Cree', 'file-manager'),
                'cs'    => __('Czech', 'file-manager'),
                'cu'    => __('Church Slavic', 'file-manager'),
                'cv'    => __('Chuvash', 'file-manager'),
                'cy'    => __('Welsh', 'file-manager'),
                'da'    => __('Danish', 'file-manager'),
                'de'    => __('German', 'file-manager'),
                'dv'    => __('Divehi', 'file-manager'),
                'dz'    => __('Dzongkha', 'file-manager'),
                'ee'    => __('Ewe', 'file-manager'),
                'el'    => __('Greek', 'file-manager'),
                'en'    => __('English', 'file-manager'),
                'eo'    => __('Esperanto', 'file-manager'),
                'es'    => __('Spanish', 'file-manager'),
                'et'    => __('Estonian', 'file-manager'),
                'eu'    => __('Basque', 'file-manager'),
                'fa'    => __('Persian', 'file-manager'),
                'ff'    => __('Fulah', 'file-manager'),
                'fi'    => __('Finnish', 'file-manager'),
                'fj'    => __('Fijian', 'file-manager'),
                'fo'    => __('Faroese', 'file-manager'),
                'fr'    => __('French', 'file-manager'),
                'fr_CA' => __('Française', 'file-manager'),
                'fy'    => __('Western Frisian', 'file-manager'),
                'ga'    => __('Irish', 'file-manager'),
                'gd'    => __('Scottish Gaelic', 'file-manager'),
                'gl'    => __('Galician', 'file-manager'),
                'gn'    => __('Guarani', 'file-manager'),
                'gu'    => __('Gujarati', 'file-manager'),
                'gv'    => __('Manx', 'file-manager'),
                'ha'    => __('Hausa', 'file-manager'),
                'he'    => __('Hebrew', 'file-manager'),
                'hi'    => __('Hindi', 'file-manager'),
                'ho'    => __('Hiri Motu', 'file-manager'),
                'hr'    => __('Croatian', 'file-manager'),
                'ht'    => __('Haitian', 'file-manager'),
                'hu'    => __('Hungarian', 'file-manager'),
                'hy'    => __('Armenian', 'file-manager'),
                'hz'    => __('Herero', 'file-manager'),
                'ia'    => __('Interlingua (International Auxiliary Language Association)', 'file-manager'),
                'id'    => __('Indonesian', 'file-manager'),
                'ie'    => __('Interlingue', 'file-manager'),
                'ig'    => __('Igbo', 'file-manager'),
                'ii'    => __('Sichuan Yi', 'file-manager'),
                'ik'    => __('Inupiaq', 'file-manager'),
                'io'    => __('Ido', 'file-manager'),
                'is'    => __('Icelandic', 'file-manager'),
                'it'    => __('Italian', 'file-manager'),
                'iu'    => __('Inuktitut', 'file-manager'),
                'ja'    => __('Japanese', 'file-manager'),
                'jp'    => __('Japanese', 'file-manager'),
                'jv'    => __('Javanese', 'file-manager'),
                'ka'    => __('Georgian', 'file-manager'),
                'kg'    => __('Kongo', 'file-manager'),
                'ki'    => __('Kikuyu', 'file-manager'),
                'kj'    => __('Kwanyama', 'file-manager'),
                'kk'    => __('Kazakh', 'file-manager'),
                'kl'    => __('Kalaallisut', 'file-manager'),
                'km'    => __('Khmer', 'file-manager'),
                'kn'    => __('Kannada', 'file-manager'),
                'ko'    => __('Korean', 'file-manager'),
                'kr'    => __('Kanuri', 'file-manager'),
                'ks'    => __('Kashmiri', 'file-manager'),
                'ku'    => __('Kurdish', 'file-manager'),
                'kv'    => __('Komi', 'file-manager'),
                'kw'    => __('Cornish', 'file-manager'),
                'ky'    => __('Kirghiz', 'file-manager'),
                'la'    => __('Latin', 'file-manager'),
                'lb'    => __('Luxembourgish', 'file-manager'),
                'lg'    => __('Ganda', 'file-manager'),
                'li'    => __('Limburgish', 'file-manager'),
                'ln'    => __('Lingala', 'file-manager'),
                'lo'    => __('Lao', 'file-manager'),
                'lt'    => __('Lithuanian', 'file-manager'),
                'lu'    => __('Luba-Katanga', 'file-manager'),
                'lv'    => __('Latvian', 'file-manager'),
                'mg'    => __('Malagasy', 'file-manager'),
                'mh'    => __('Marshallese', 'file-manager'),
                'mi'    => __('Maori', 'file-manager'),
                'mk'    => __('Macedonian', 'file-manager'),
                'ml'    => __('Malayalam', 'file-manager'),
                'mn'    => __('Mongolian', 'file-manager'),
                'mr'    => __('Marathi', 'file-manager'),
                'ms'    => __('Malay', 'file-manager'),
                'mt'    => __('Maltese', 'file-manager'),
                'my'    => __('Burmese', 'file-manager'),
                'na'    => __('Nauru', 'file-manager'),
                'nb'    => __('Norwegian Bokmal', 'file-manager'),
                'nd'    => __('North Ndebele', 'file-manager'),
                'ne'    => __('Nepali', 'file-manager'),
                'ng'    => __('Ndonga', 'file-manager'),
                'nl'    => __('Dutch', 'file-manager'),
                'nn'    => __('Norwegian Nynorsk', 'file-manager'),
                'no'    => __('Norwegian', 'file-manager'),
                'nr'    => __('South Ndebele', 'file-manager'),
                'nv'    => __('Navajo', 'file-manager'),
                'ny'    => __('Chichewa', 'file-manager'),
                'oc'    => __('Occitan', 'file-manager'),
                'oj'    => __('Ojibwa', 'file-manager'),
                'om'    => __('Oromo', 'file-manager'),
                'or'    => __('Oriya', 'file-manager'),
                'os'    => __('Ossetian', 'file-manager'),
                'pa'    => __('Panjabi', 'file-manager'),
                'pi'    => __('Pali', 'file-manager'),
                'pl'    => __('Polish', 'file-manager'),
                'ps'    => __('Pashto', 'file-manager'),
                'pt'    => __('Portuguese', 'file-manager'),
                'pt_BR' => __('Portuguese(Brazil)', 'file-manager'),
                'qu'    => __('Quechua', 'file-manager'),
                'rm'    => __('Raeto-Romance', 'file-manager'),
                'rn'    => __('Kirundi', 'file-manager'),
                'ro'    => __('Romanian', 'file-manager'),
                'ru'    => __('Russian', 'file-manager'),
                'rw'    => __('Kinyarwanda', 'file-manager'),
                'sa'    => __('Sanskrit', 'file-manager'),
                'sc'    => __('Sardinian', 'file-manager'),
                'sd'    => __('Sindhi', 'file-manager'),
                'se'    => __('Northern Sami', 'file-manager'),
                'sg'    => __('Sango', 'file-manager'),
                'si'    => __('Sinhala', 'file-manager'),
                'sk'    => __('Slovak', 'file-manager'),
                'sl'    => __('Slovenian', 'file-manager'),
                'sm'    => __('Samoan', 'file-manager'),
                'sn'    => __('Shona', 'file-manager'),
                'so'    => __('Somali', 'file-manager'),
                'sq'    => __('Albanian', 'file-manager'),
                'sr'    => __('Serbian', 'file-manager'),
                'ss'    => __('Swati', 'file-manager'),
                'st'    => __('Southern Sotho', 'file-manager'),
                'su'    => __('Sundanese', 'file-manager'),
                'sv'    => __('Swedish', 'file-manager'),
                'sw'    => __('Swahili', 'file-manager'),
                'ta'    => __('Tamil', 'file-manager'),
                'te'    => __('Telugu', 'file-manager'),
                'tg'    => __('Tajik', 'file-manager'),
                'th'    => __('Thai', 'file-manager'),
                'ti'    => __('Tigrinya', 'file-manager'),
                'tk'    => __('Turkmen', 'file-manager'),
                'tl'    => __('Tagalog', 'file-manager'),
                'tn'    => __('Tswana', 'file-manager'),
                'to'    => __('Tonga', 'file-manager'),
                'tr'    => __('Turkish', 'file-manager'),
                'ts'    => __('Tsonga', 'file-manager'),
                'tt'    => __('Tatar', 'file-manager'),
                'tw'    => __('Twi', 'file-manager'),
                'ty'    => __('Tahitian', 'file-manager'),
                'ug'    => __('Uighur', 'file-manager'),
                'ug_CN' => __('Uighur(China)', 'file-manager'),
                'uk'    => __('Ukrainian', 'file-manager'),
                'ur'    => __('Urdu', 'file-manager'),
                'uz'    => __('Uzbek', 'file-manager'),
                've'    => __('Venda', 'file-manager'),
                'vi'    => __('Vietnamese', 'file-manager'),
                'vo'    => __('Volapuk', 'file-manager'),
                'wa'    => __('Walloon', 'file-manager'),
                'wo'    => __('Wolof', 'file-manager'),
                'xh'    => __('Xhosa', 'file-manager'),
                'yi'    => __('Yiddish', 'file-manager'),
                'yo'    => __('Yoruba', 'file-manager'),
                'za'    => __('Zhuang', 'file-manager'),
                'zh'    => __('Chinese', 'file-manager'),
                'zh_CN' => __('Chinese(China)', 'file-manager'),
                'zh_TW' => __('Chinese(Taiwan)', 'file-manager'),
                'zu'    => __('Zulu', 'file-manager'),
            ];

            $dirs = scandir(Config::getFinderDirectory() . '/js/i18n');
            foreach ($dirs as $lang) {
                if (\in_array($lang, ['.', '..', 'help'])) {
                    continue;
                }

                $lang = str_replace(['elfinder.', '.js'], '', $lang);

                if (isset($allLangs[$lang])) {
                    $this->_availableLang[$lang] = $allLangs[$lang];
                }
            }
        }

        return $this->_availableLang;
    }

    /**
     * Returns available languages as an array of Assoc-array
     *
     * @return array<int, array<string,string>>
     */
    public function getLanguages()
    {
        $languages = [];
        foreach ($this->availableLanguages() as $code => $name) {
            $languages[] = compact('code', 'name');
        }

        return $languages;
    }

    public function getLangCode()
    {
        $selectedCode = $this->getDefaultLangCode();
        if (isset($this->preferences['language'])
        && \is_string($this->preferences['language'])
        && isset($this->availableLanguages()[$this->preferences['language']])
        ) {
            $selectedCode = esc_attr($this->preferences['language']);
        }

        return $selectedCode;
    }

    public function getLangUrl()
    {
        $langUrl = Config::getFinderUrl() . '/js/i18n/elfinder.' . $this->getLangCode() . '.js';

        if (!file_exists($langUrl)) {
            $langUrl = Config::getFinderUrl() . '/js/i18n/elfinder.LANG.js';
        }

        return esc_attr($langUrl);
    }

    public function setLang($lang)
    {
        $this->preferences['language']
            = isset($this->availableLanguages()[$lang])
        ? $lang
        : $this->getDefaultLangCode();
    }

    public function setRootPath($path)
    {
        $this->preferences['root_folder_path'] = $this->realPath($path);
    }

    public function getRootPath()
    {
        $defaultPath = Capabilities::check('manage_options')
         ? ABSPATH : $this->permissions()->getDefaultPublicRootPath();

        return isset($this->preferences['root_folder_path'])
        ? esc_attr($this->preferences['root_folder_path']) : $defaultPath;
    }

    public function realPath($path)
    {
        if (\is_null($path) || !\is_string($path)) {
            return $path;
        }

        // whether $path is unix or not
        $unipath  = \strlen($path) == 0 || $path[0] != '/';
        $prefixed = false;
        // attempts to detect if path is relative in which case, add cwd
        if (strpos($path, ':') === false && $unipath) {
            $path     = ABSPATH . $path;
            $prefixed = true;
        }

        // resolve path parts (single dot, double dot and double delimiters)
        $path      = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $parts     = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');
        $absolutes = [];
        foreach ($parts as $part) {
            if ($part == '.') {
                continue;
            }

            if ($part == '..') {
                array_pop($absolutes);
            } else {
                $absolutes[] = $part;
            }
        }

        $realpath = implode(DIRECTORY_SEPARATOR, $absolutes);
        if ($prefixed) {
            $realpath = DIRECTORY_SEPARATOR . $realpath;
        }

        // resolve any symlinks
        if (file_exists($realpath) && is_link($realpath)) {
            $realpath = readlink($realpath);
        }

        // put initial separator that could have been lost
        return $unipath ? $realpath : '/' . $realpath;
    }

    public function setRootUrl($url)
    {
        $this->preferences['root_folder_url'] = $url;
    }

    public function getRootUrl()
    {
        $defaultUrl = Capabilities::check('manage_options')
         ? Config::get('SITE_URL') : $this->permissions()->getDefaultPublicRootURL();

        return isset($this->preferences['root_folder_url'])
        ? $this->preferences['root_folder_url'] : $defaultUrl;
    }

    public function setRootVolumeName($name)
    {
        $this->preferences['root_folder_name'] = $name;
    }

    public function getRootVolumeName()
    {
        return isset($this->preferences['root_folder_name'])
        ? esc_attr($this->preferences['root_folder_name']) : basename($this->getRootPath());
    }

    public function setWidth($width)
    {
        $this->preferences['size']['width'] = $width;
    }

    public function getWidth()
    {
        return isset($this->preferences['size']['width']) && $this->preferences['size']['width']
        ? esc_attr($this->preferences['size']['width']) : 'auto';
    }

    public function setHeight($height)
    {
        $this->preferences['size']['height'] = $height;
    }

    public function getHeight()
    {
        if (!isset($this->preferences['size']['height'])) {
            return 'auto';
        }

        $height = esc_attr($this->preferences['size']['height']);

        return $height === '500' ? 'auto' : $height;
    }

    public function setVisibilityOfHiddenFile($visibility)
    {
        $this->preferences['show_hidden_files'] = $visibility;
    }

    public function getVisibilityOfHiddenFile()
    {
        return \array_key_exists('show_hidden_files', $this->preferences)
         && esc_attr($this->preferences['show_hidden_files']) ? true : false;
    }

    public function setPermissionForHiddenFolderCreation($permission)
    {
        $this->preferences['create_hidden_files_folders'] = $permission;
    }

    public function isHiddenFolderAllowed()
    {
        return \array_key_exists('create_hidden_files_folders', $this->preferences)
         && $this->preferences['create_hidden_files_folders'] ? true : false;
    }

    public function isWpMediaSyncEnabled()
    {
        return \array_key_exists('wp_media_sync', $this->preferences)
         && $this->preferences['wp_media_sync'] ? true : false;
    }

    public function toggleWpMediaSync($sync)
    {
        $this->preferences['wp_media_sync'] = (bool) $sync;
    }

    public function setPermissionForTrashCreation($permission)
    {
        $this->preferences['create_trash_files_folders'] = $permission;
    }

    public function isTrashAllowed()
    {
        return \array_key_exists('create_trash_files_folders', $this->preferences)
         && $this->preferences['create_trash_files_folders'] ? true : false;
    }

    public function setViewType($type)
    {
        $this->preferences['default_view_type'] = $type;
    }

    public function getViewType()
    {
        return isset($this->preferences['default_view_type']) ? $this->preferences['default_view_type'] : 'icons';
    }

    public function setRememberLastDir($remember)
    {
        $this->preferences['remember_last_dir'] = $remember;
    }

    public function getRememberLastDir()
    {
        return \array_key_exists('remember_last_dir', $this->preferences)
        && $this->preferences['remember_last_dir']
         ? esc_attr($this->preferences['remember_last_dir']) : false;
    }

    public function setClearHistoryOnReload($clearHistory)
    {
        $this->preferences['clear_history_on_reload'] = $clearHistory;
    }

    public function getClearHistoryOnReload()
    {
        return \array_key_exists('clear_history_on_reload', $this->preferences)
        && $this->preferences['clear_history_on_reload']
         ? $this->preferences['clear_history_on_reload'] : false;
    }

    public function setUiOptions($options)
    {
        $this->preferences['display_ui_options'] = $options;
    }

    public function getUiOptions()
    {
        $uiOptions = isset($this->preferences['display_ui_options'])
        ? array_map(
            function ($option) {
                return esc_attr($option);
            },
            $this->preferences['display_ui_options']
        ) : ['toolbar', 'places', 'tree', 'path', 'stat'];

        if (RequestType::is(RequestType::ADMIN)) {
            $uiOptions = array_diff($uiOptions, ['path']);
        }

        if (!is_user_logged_in()) {
            $uiOptions = array_diff($uiOptions, ['toolbar']);
        }

        return $uiOptions;
    }

    public function finderOptions()
    {
        $options = new ClientOptions();

        $options->setOption('url', admin_url('admin-ajax.php'));
        $options->setOption('themes', $this->themes());
        $options->setOption('theme', $this->getTheme());
        $options->setOption('lang', $this->getLangCode());
        $options->setOption('width', $this->getWidth());
        $options->setOption('height', $this->getHeight());
        $options->setOption('commands', $this->permissions()->getEnabledCommand());
        $disabledCommands = array_diff($this->permissions()->allCommands(), $this->permissions()->getEnabledCommand());
        if (\in_array('download', $disabledCommands)) {
            $disabledCommands[] = 'dblclick';
        }

        $options->setOption('disabled', array_values($disabledCommands));
        $options->setOption('commandsOptions', $this->finderCommandsOptions());
        $options->setOption('rememberLastDir', $this->getRememberLastDir());
        $options->setOption('reloadClearHistory', $this->getClearHistoryOnReload());
        $options->setOption('defaultView', $this->getViewType());
        $options->setOption('ui', $this->getUiOptions());
        // $options->setOption('resizable', true);
        $options->setOption(
            'contextmenu',
            $this->finderContextMenu()
        );
        $options->setOption('cdns', $this->finderCdns());

        return $options->getOptions();
    }

    public function finderCdns()
    {
        $base = Config::get('ASSET_URI') . '/libs/';
        return [
            'ace'        => $base . 'ace',
            'codemirror' => includes_url('js/codemirror'),
            'ckeditor'   => '',
            'ckeditor5'  => '',
            'tinymce'    => includes_url('js/tinymce'),
            'simplemde'  => $base . 'simplemde',
            'fabric'     => $base . 'fabric',
            'fabric16'   => '',
            'tui'        => $base . 'tui',
            'hls'        => $base . 'hls/hls.min.js',
            'dash'       => '',
            'flv'        => $base . 'flv/flv.min.js',
            'videojs'    => '',
            'prettify'   => $base . 'prettify/run_prettify.js',
            'psd'        => $base . 'psd/psd.min.js',
            'rar'        => $base . 'rar/rar.js',
            'zlibUnzip'  => $base . 'zlib/unzip.min.js',
            'zlibGunzip' => $base . 'zlib/gunzip.min.js',
            'bzip2'      => $base . 'bzip2/bzip2.js',
            'marked'     => $base . 'marked/marked.min.js',
            'sparkmd5'   => $base . 'spark-md5/spark-md5.min.js',
            'jssha'      => $base . 'jssha/sha.min.js',
            'amr'        => $base . 'amr/opencore-amr.min.js',
            'tiff'       => $base . 'tiff/tiff.min.js',
        ];
    }

    public function finderContextMenu()
    {
        $contextMenu = [
            // 'commands' => ['*'],
            // phpcs:ignore
            'files'    => [ 'getfile', '|', 'emailto', 'open', 'opennew', 'download', 'opendir', 'quicklook', 'email', '|', 'upload', 'mkdir', '|', 'copy', 'cut', 'paste', 'duplicate', '|', 'rm', 'empty', 'hide', '|', 'rename', 'edit', 'resize', '|', 'archive', 'extract', '|', 'selectall', 'selectinvert', '|', 'places', 'info', 'chmod', 'netunmount']
        ];
        if (!is_user_logged_in()) {
            $contextMenu = [
                'navbar' => [],
                'cwd'    => ['reload', 'back', 'sort'],
                'files'  => [],
            ];
            if (\count($this->permissions()->getEnabledCommand())) {
                $contextMenu['files'] = ['download'];
            }
        }

        return $contextMenu;
    }

    public function finderCommandsOptions()
    {
        $commandOptions                                 = [];
        $commandOptions['info']                         = [];
        $commandOptions['info']['hideItems']            = ['md5', 'sha256'];
        $commandOptions['download']['maxRequests']      = 10;
        $commandOptions['download']['minFilesZipdl']    = 2; // need to check
        $commandOptions['quicklook']['googleDocsMimes'] = ['application/pdf', 'image/tiff', 'application/vnd.ms-office', 'application/msword', 'application/vnd.ms-word', 'application/vnd.ms-excel', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];

        if (!$this->isLinkPathVisibleInInfo()) {
            $commandOptions['info']['hideItems'][] = 'link';
            $commandOptions['info']['hideItems'][] = 'path';
        }

        // Restrict TinyMCE to plugins/toolbar that WP core ships — prevents 404s for
        // plugins like preview, searchreplace, codesample, table etc. that are absent.
        $commandOptions['edit']['extraOptions']['tinymce'] = [
            'plugins' => 'directionality fullscreen image link media charmap lists textcolor paste',
            'toolbar' => 'formatselect | bold italic strikethrough forecolor backcolor | link image media | alignleft aligncenter alignright alignjustify | numlist bullist outdent indent | charmap | removeformat | fullscreen',
        ];

        return $commandOptions;
    }

    private function fallback()
    {
        // TODO: Need to remove
        $fallbackSettings = [
            'fm_root_folder_name'            => 'root_folder_name',
            'fm-show-hidden-files'           => 'show_hidden_files',
            'fm-create-hidden-files-folders' => 'create_hidden_files_folders',
            'fm-create-trash-files-folders'  => 'create_trash_files_folders',
            'fm_default_view_type'           => 'default_view_type',
            'fm-remember-last-dir'           => 'remember_last_dir',
            'fm-clear-history-on-reload'     => 'clear_history_on_reload',
            'fm_display_ui_options'          => 'display_ui_options',
        ];

        foreach ($fallbackSettings as $key => $newKey) {
            if (\array_key_exists($key, $this->preferences)) {
                $this->preferences[$newKey] = $this->preferences[$key];
                unset($this->preferences[$key]);
            }
        }

        $this->preferences['size']['width'] = $this->getWidth();

        $this->preferences['show_url_path']               = $this->isLinkPathVisibleInInfo();
        $this->preferences['show_hidden_files']           = $this->getVisibilityOfHiddenFile();
        $this->preferences['create_trash_files_folders']  = $this->isTrashAllowed();
        $this->preferences['create_hidden_files_folders'] = $this->isHiddenFolderAllowed();
        $this->preferences['remember_last_dir']           = $this->getRememberLastDir();
        $this->preferences['clear_history_on_reload']     = $this->getClearHistoryOnReload();
    }
}
