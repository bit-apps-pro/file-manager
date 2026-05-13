<?php

namespace BitApps\FM\Providers\FileManager;

\defined('ABSPATH') || exit();
class ClientOptions
{
    /**
     * Connector URL
     *
     * @var string
     */
    private $_url = '';

    /**
     * Finder themes
     *
     * @var array
     */
    private $_themes;

    /**
     * Finder selected theme
     *
     * @var array
     */
    private $_theme;

    /**
     * The interface lang to use
     *
     * @var string
     */
    private $_lang = 'en';

    /**
     * Data to append to all requests and to upload files
     *
     * @var array
     */
    private $_customData = [];

    /**
     * Additional css class for file manager node
     *
     * @var string
     */
    private $_cssClass = '';

    /**
     * Auto load required CSS
     *
     * @var bool
     */
    private $_cssAutoLoad = false;

    /**
     * Remember last opened dir to open it after reload or in next session
     *
     * @var bool
     */
    private $_rememberLastDir = true;

    /**
     * Clear history's(elFinder) on reload(not browser) function
     *
     * @var bool
     */
    private $_reloadClearHistory = false;

    /**
     * Use browser native history with supported browsers
     *
     * @var bool
     */
    private $_useBrowserHistory = true;

    /**
     * Display only certain files based on their mime type
     *
     * @var string
     */
    private $_onlyMimes = [];

    /**
     * Used to validate file names
     *
     * @var string|bool
     */
    private $_validName;

    /**
     * Hash of default directory path to open
     *
     * @var string
     */
    private $_startPathHash = '';

    /**
     * Default view mode
     *
     * @var string icons | list
     */
    private $_defaultView = 'icons';

    /**
     * Default sort
     * 'name' - sort by name
     *
     * @var string name | kind |size | date
     */
    private $_sortType = 'nameDirsFirst';

    /**
     * Default sort order
     *
     * @var string asc | desc
     */
    private $_sortOrder = 'asc';

    /**
     * Display folders first?
     *
     * @var bool
     */
    private $_sortStickFolders = true;

    /**
     * The width of the elFinder main interface.
     * Can be the string 'auto' or any number measurement (in pixels).
     *
     * @var string|int
     */
    private $_width = 'auto';

    /**
     * The height of the elFinder main interface (in pixels)
     * number or string (ex. '100%') Default value: 400
     *
     * @var string|int
     */
    private $_height = 400;

    /**
     * Format dates using client
     * If set to false - backend date format will be used.
     *
     * @var bool
     */
    private $_clientFormatDate = true;

    /**
     * Show datetime in UTC timezone.
     * Requires clientFormatDate set to true.
     *
     * @var bool
     */
    private $_UTCDate = false;

    /**
     * File modification datetime format
     * Set format here to overwrite it. Format is set in PHP date manner
     *
     * @var string
     */
    private $_dateFormat = '';

    /**
     * File modification datetime format for last two days (today, yesterday)
     * Same syntax as for dateFormat. Use $1 for "Today" and "Yesterday" placeholder.
     *
     * Example: '$1 H:m:i' will return Today 21:59:34
     *
     * @var string
     */
    private $_fancyDateFormat = '';

    /**
     * Style of file mode at cwd-list, info dialog
     * 'string' (ex. rwxr-xr-x) or 'octal' (ex. 755) or 'both' (ex. rwxr-xr-x (755))
     *
     * @var string
     */
    private $_fileModeStyle = 'both';

    /**
     * Active commands list
     * '*' means all of the commands that have been load.
     *  available commands:
     *  [
     * 'archive', 'back', 'chmod', 'colwidth', 'copy', 'cut', 'download', 'duplicate', 'edit', 'extract',
     * 'forward', 'fullscreen', 'getfile', 'help', 'home', 'info', 'mkdir', 'mkfile', 'netmount', 'netunmount',
     * 'open', 'opendir', 'paste', 'places', 'quicklook', 'reload', 'rename', 'resize', 'restore', 'rm',
     * 'search', 'sort', 'up', 'upload', 'view', 'zipdl'
     * ]
     *
     * @see https://github.com/Studio-42/elFinder/wiki/Client-configuration-options-2.1#commands
     *
     * @var array
     */
    private $_commands = ['*'];

    /**
     * Commands to disable
     */
    private $_disabled = [];

    /**
     * Commands options used to interact with external callbacks, editors, plugins
     *
     * @see https://github.com/Studio-42/elFinder/wiki/Client-configuration-options-2.1#commandsoptions
     *
     * @var array
     */
    private $_commandsOptions;

    /**
     * Callback function for "getfile" command (js function)
     *
     * @var string
     */
    private $_getFileCallback;

    /**
     * Event listeners to bind on elFinder init (js function)
     *
     * @var string
     */
    private $_handlers;

    /**
     * UI plugins to load
     * ['toolbar', 'places', 'tree', 'path', 'stat']
     *
     * @var array
     */
    private $_ui = ['toolbar', 'places', 'tree', 'path', 'stat'];

    /**
     * Specifies the configuration for the elFinder UI
     *
     * @var string
     */
    private $_uiOptions;

    /**
     * The configuration for the right-click context menu
     *
     * Example:
     * {
     * // navbar folder menu
     * navbar : ['open', '|', 'copy', 'cut', 'paste', 'duplicate', '|', 'rm', '|', 'info'],
     *
     *  // current directory menu
     * cwd    : ['reload', 'back', '|', 'upload', 'mkdir', 'mkfile', 'paste', '|', 'info'],
     *
     * // current directory file menu
     * files  : [
     *      'getfile', '|','open', 'quicklook', '|', 'download', '|', 'copy', 'cut', 'paste', 'duplicate', '|',
     *      'rm', '|', 'edit', 'rename', 'resize', '|', 'archive', 'extract', '|', 'info'
     *      ]
     * }
     *
     * @see https://github.com/Studio-42/elFinder/wiki/Client-configuration-options-2.1#contextmenu
     *
     * @var array
     */
    private $_contextmenu;

    /**
     * Whether or not the elFinder interface will be resizable
     *
     * @var bool
     */
    private $_resizable = true;

    /**
     * Timeout for open notification dialogs in millisecond
     *
     * @var int
     */
    private $_notifyDelay = 800;

    /**
     * Position and width of notification dialogs
     * {position: {top : '12px', right : '12px'}, width : 280}
     *
     * @var array
     */
    private $_notifyDialog;

    /**
     * Allow to drag and drop to upload files
     *
     * @var string
     */
    private $_dragUploadAllow = 'auto';

    /**
     * Allow shortcuts
     *
     * @var bool
     */
    private $_allowShortcuts = true;

    /**
     * Amount of thumbnails to create per one request
     *
     * @var int
     */
    private $_loadTmbs = 5;

    /**
     * Lazy load
     *
     * @var int
     */
    private $_showFiles = 30;

    /**
     * Lazy load.  Distance in px to cwd bottom edge to start displaying files.
     *
     * @var int
     */
    private $_showThreshold = 50;

    /**
     * The AJAX request type
     *
     * @var string post | get
     */
    private $_requestType = 'post';

    /**
     * Separate URL to upload file to
     *
     * @var string
     */
    private $_urlUpload = '';

    /**
     * Timeout for upload using iframe
     *
     * @var int
     */
    private $_iframeTimeout = 0;

    /**
     * Sync content by refreshing cwd every N seconds
     *
     * @var int
     */
    private $_sync = 0;

    /**
     * Cookie option for browsers that does not support localStorage
     *
     * @var array
     */
    private $_cookie;

    /**
     * Passing custom headers during Ajax calls
     *
     * @var array
     */
    private $_customHeaders;

    /**
     * Any custom xhrFields to send across every ajax request, useful for CORS (Cross-origin resource sharing) support
     *
     * @var array
     */
    private $_xhrFields;

    /**
     * Debug config
     *
     * @var bool|array
     */
    private $_debug = ['error', 'warning', 'event-destroy'];

    /**
     * Increase chunk size.(bytes)
     *
     * @var int
     */
    private $_uploadMaxChunkSize = 10485760;

    /**
     * Directory path to rm.wav file
     *
     * @var string
     */
    private $_soundPath;

    /**
     * Constructs Finder frontend options
     *
     * @param mixed $debug
     */
    public function __construct($debug = null)
    {
        if (!\is_null($debug)) {
            $this->_debug = $debug;
        }
    }

    public function setOption($name, $value)
    {
        if (property_exists($this, "_{$name}")) {
            $this->{"_{$name}"} = $value;
        }

        return $this;
    }

    public function getOption($name)
    {
        if (property_exists($this, "_{$name}")) {
            return $this->{"_{$name}"};
        }
    }

    public function getOptions()
    {
        $options = [];

        $optionsToAdd = [
            'url',
            'themes',
            'theme',
            'cssAutoLoad',
            'contextmenu',
            'customData',
            'lang',
            'requestType',
            'width',
            'height',
            'commands',
            'disabled',
            'commandsOptions',
            'rememberLastDir',
            'reloadClearHistory',
            'defaultView',
            'ui',
            'sortOrder',
            'sortStickFolders',
            'dragUploadAllow',
            'fileModeStyle',
            'resizable',
        ];
        foreach ($optionsToAdd as $option) {
            if (isset($this->{"_{$option}"})) {
                $options[$option] = $this->getOption($option);
            }
        }

        return $options;
    }
}
