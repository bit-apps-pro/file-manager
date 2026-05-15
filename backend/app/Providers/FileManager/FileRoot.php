<?php

namespace BitApps\FM\Providers\FileManager;

use function BitApps\FM\Functions\fileSystemAdapter;

use Exception;

\defined('ABSPATH') || exit();

/**
 * Provides Volume/Root options for elFInder
 *
 * @see https://github.com/Studio-42/elFinder/wiki/Connector-configuration-options-2.1#root-options
 */
class FileRoot
{
    private $_id;

    /**
     * Volume driver. LocalFileSystem | MySQL | FTP
     *
     * @var string LocalFileSystem | MySQL | FTP
     */
    private $_driver;

    /**
     * Is support autoload.
     *
     * @var bool
     */
    private $_autoload;

    /**
     * Folder hash value on elFinder to be the parent of this volume (elFinder >= 2.1.15)
     *
     * @var string
     */
    private $_phash;

    /**
     * Folder hash value on elFinder to trash bin of this volume (elFinder >= 2.1.24)
     * Folder hash value on elFinder to trash bin of this volume, it require 'copyJoin' to true
     *
     * @var string
     */
    private $_trashHash;

    /**
     * To make hash same to Linux one on windows too
     *
     * @var bool
     */
    private $_winHashFix;

    /**
     * Root directory path
     *
     * @var string
     */
    private $_path;

    /**
     * Open this path on initial request instead of root path.
     * Notice: In order to validate this option by a multi-route,
     * you have to set a value only to the volume which applies this option.
     *
     * @var string
     */
    private $_startPath;

    /**
     * URL that points to path directory (also called 'root URL')
     * If not set client will not see full path to files (replacement for old fileURL option),
     * also all files downloads will be handled by connector.
     *
     * @var string
     */
    private $_URL;

    /**
     * This volume's local encoding.
     * (server's file system encoding) It's necessary to be valid value to iconv.
     *
     * @see https://gist.github.com/nao-pon/78d64ad9a89d0267833564e5d61f0001
     *
     * @var string
     */
    private $_encoding;

    /**
     * This volume's local locale.
     * It's important for encoding setting.
     * It's necessary to be valid value in your server.
     *
     * Warning: Setting the locale correctly is very important.
     * Especially if you do not match the character encoding to that of the server file system,
     * it will create a security vulnerability.
     *
     * @var string
     */
    private $_locale;

    /**
     * Root path alias for volume root.
     * If not set will use directory name of path.
     *
     * @var string
     */
    private $_alias;

    /**
     * Enable i18n folder name that convert name to elFinderInstance.messages['folder_'+name]
     *
     * @var bool
     */
    private $_i18nFolderName;

    /**
     * Method to detect files mime types.
     *
     * @var string auto | internal | finfo | mime_content_type
     */
    private $_mimeDetect;

    /**
     * Path to alternative mime types file. Only used when mimeDetect set to internal.
     * If not set will use default mime.types
     *
     * @var string
     */
    private $_mimefile;

    /**
     * Additional MIME type normalize map
     *
     *      Example :
     *  `array(
     *   'tpl:application/vnd.groove-tool-template' => 'text/plain',
     *  '[ext]:[Detected MIME Type]' => '[Normalized MIME Type]'
     * )`
     *
     * @var array
     */
    private $_additionalMimeMap;

    /**
     * MIME regex of send HTTP header "Content-Disposition: inline"  on file open command.
     * '.*' is allow inline of all of MIME types
     *
     * '$^' is not allow inline of all of MIME types
     *
     * '^(?:image|text/plain$)' is recommended for safety on public elFinder
     *
     * Default value: '^(?:(?:video|audio)|image/(?!.+\+xml)
     * |application/(?:ogg|x-mpegURL|dash\+xml)|(?:text/plain|application/pdf)$)'
     *
     * @var string
     */
    private $_dispInlineRegex
        = '^(?:(?:video|audio)|image/(?!.+\+xml)|application/(?:ogg|x-mpegURL|dash\+xml)|(?:text/plain|application/pdf)$)';

    /**
     * Image manipulations library
     *
     * Default value: 'auto'
     *
     * @var string auto | imagick | gd | convert
     */
    private $_imgLib;

    /**
     * Directory for thumbnails.
     * If this is a simple filename, it will be prefixed with the root directory path.
     * If you choose to use a location outside of the root directory,
     * you should use a full pathname as a relative path using ellipses
     * will get mangled and may not work (create thumbnails because tmbPath is NOT writable) on some server OS's.
     *
     * Default value: '.tmb'
     *
     * @var string
     */
    private $_tmbPath;

    /**
     * Umask for thumbnails dir creation.
     *
     * Default value: 0777
     *
     * @var octal
     */
    private $_tmbPathMode;

    /**
     * URL for thumbnails directory set in tmbPath.
     * Set it only if you want to store thumbnails outside root directory.
     * If you want chose original image as thumbnail it is able to set 'self'. (elFinder >= 2.1.12)
     *
     * @var string
     */
    private $_tmbURL;

    /**
     * Thumbnails size in pixels
     *
     * Default value: 48
     *
     * @var int
     */
    private $_tmbSize;

    /**
     * Crop thumbnails to fit tmbSize.
     * true - resize and crop.
     * false - scale image to fit thumbnail size.
     *
     * @var bool
     */
    private $_tmbCrop;

    /**
     * Thumbnails background color (hex #rrggbb or transparent)
     *
     * Default value: 'transparent'
     *
     * @var string
     */
    private $_tmbBgColor;

    /**
     * Image rotate fallback background color (hex #rrggbb).
     * Uses this color if it can not specify to transparent.
     *
     * Default value: '#ffffff'
     *
     * @var bool
     */
    private $_bgColorFb;

    /**
     * Fallback self image to thumbnail (nothing imgLib)
     *
     * Default value: true
     *
     * @var bool
     */
    private $_tmbFbSelf;

    /**
     * Replace files on paste or give new names to pasted files.
     * true - old file will be replaced with new one
     * false - new file get name - original_name-number.ext
     *
     * Default value: true
     *
     * @var bool
     */
    private $_copyOverwrite;

    /**
     * Merge new and old content on paste
     * true - join new and old directories content
     * false - replace old directories with new ones
     *
     * Default value: true
     *
     * @var bool
     */
    private $_copyJoin = true;

    /**
     * Allow to copy from this volume to other ones.
     *
     * Default value: true
     *
     * @var true
     */
    private $_copyFrom = true;

    /**
     * Allow to copy from other volumes to this one
     *
     * Default value: true
     *
     * @var bool
     */
    private $_copyTo = true;

    /**
     * (temporary) Directory for extracts etc.
     *
     * @var string
     */
    private $_tmpPath;

    /**
     * Replace files with the same name on upload or give them new names
     * true - replace old files
     * false - give new names like original_name-number.ext
     *
     * Default value: true
     *
     * @var bool
     */
    private $_uploadOverwrite;

    /**
     * Mime types allowed to upload
     * Default value: array()
     * Example:
     * 'uploadAllow' => array('image') # allow any images
     * 'uploadAllow' => array('image/png', 'application/x-shockwave-flash') # allow png and flash
     *
     * @var array
     */
    private $_uploadAllow = [];

    /**
     * Mime types not allowed to upload.
     * Same values accepted as in uploadAllow
     *
     * Default value: array()
     *  Example:
     *  'uploadDeny' => array('all') # deny of all types
     *
     * @var array
     */
    private $_uploadDeny = [];

    /**
     * Order to process uploadAllow and uploadDeny options.
     * Logic is the same as Apache web server options Allow, Deny, Order
     *
     * Default value: array('deny', 'allow')
     *
     * @var array
     */
    private $_uploadOrder = ['deny', 'allow'];

    /**
     * Maximum upload file size.
     * Can be set as number or string with unit 10M, 500K, 1G.
     * Note: elFinder 2.1+ support chunked file uploading.
     * 0 means unlimited upload.
     *
     * @var int|string
     */
    private $_uploadMaxSize;

    /**
     * Maximum number of chunked upload connection, -1 to disable chunked upload.
     *
     * Default value: 3
     *
     * @var int
     */
    private $_uploadMaxConn;

    /**
     * Default file/directory permissions.
     * Setting hidden, locked here - take no effect
     *
     * Default value: 'defaults' => array('read'  => true, 'write' => true)
     *
     * @var array
     */
    private $_defaults = ['read' => true, 'write' => true];

    /**
     * File permission attributes.
     *
     * @see https://github.com/Studio-42/elFinder/wiki/Simple-file-permissions-control
     *
     * @var array
     */
    private $_attributes;

    /**
     * Validate new file name regex or function
     *
     * Default value:Default value: '^[^\.].*'
     *
     * @var string|callable
     */
    private $_acceptedName;

    /**
     * Function or class instance method to control files permissions.
     *
     * @var callable|null
     */
    private $_accessControl;

    /**
     * Data that will be passed to access control method.
     *
     * @var mixed
     */
    private $_accessControlData;

    /**
     * List of commands disabled on this root.
     *
     * @see https://github.com/Studio-42/elFinder/wiki/Client-Server-API-2.1#command-list
     *
     * @var array
     */
    private $_disabled = [];

    /**
     * Include file owner, group & mode in stat results
     *
     * Default value: false
     *
     * @var bool
     */
    private $_statOwner = false;

    /**
     * Allow exec chmod of read-only files
     *
     * Default value: false
     *
     * @var bool
     */
    private $_allowChmodReadOnly = false;

    /**
     * How many sub dir levels return per request
     *
     * Default value: 1
     *
     * @var int
     */
    private $_treeDeep;

    /**
     * Check children directories for other directories in it.
     * true - every folder will be check for children folders,
     * -1 - every folder will be check asynchronously,
     * false - all folders will be marked as having sub folders
     *
     * Default value: true
     *
     * @var bool|int
     */
    private $_checkSubfolders;

    /**
     * Directory separator
     *
     * Default value: DIRECTORY_SEPARATOR
     *
     * @var string
     */
    private $_separator;

    /**
     * File modification date format
     *
     * Default value: 'j M Y H:i'
     *
     * @var string
     */
    private $_dateFormat = 'j M Y H:i';

    /**
     * File modification time format
     *
     * Default value: 'H:i'
     *
     * @var string
     */
    private $_timeFormat = 'H:i';

    /**
     * Library to crypt/decrypt files names (not implemented yet)
     *
     * @var [type]
     */
    private $_cryptLib;

    /**
     * Allowed archive's mime types to create
     *
     * @var array
     */
    private $_archiveMimes;

    /**
     * Manual config for archiver's.
     *
     * @var array
     */
    private $_archivers;

    /**
     * Temporary directory for extracting archives (LocalFileSystem volume only)
     *
     * @var string
     */
    private $_quarantine;

    /**
     * Configure plugin options of each volume
     *
     * @var array
     */
    private $_plugin;

    /**
     * Constructs Volume root
     *
     * @param string      $path
     * @param string      $url
     * @param null|string $alias
     * @param string      $driver LocalFileSystem | MySQL | FTP
     */
    public function __construct($path, $url, $alias = null, $driver = 'LocalFileSystem')
    {
        $this->_path   = $path;
        $this->_URL    = $url;
        $this->_driver = $driver;
        $this->_alias  = $alias;
    }

    /**
     * Is this folder readable
     */
    public function isReadable()
    {
        return fileSystemAdapter()->is_readable($this->_path);
    }

    /**
     * Set alias for the root
     *
     * @param string $alias
     *
     * @return FileRoot
     */
    public function setAlias($alias)
    {
        $this->_alias = $alias;

        return $this;
    }

    /**
     * Sets driver for the root
     *
     * @param string $driver LocalFileSystem | MySQL | FTP
     *
     * @return FileRoot
     */
    public function setDriver($driver)
    {
        $this->_driver = $driver;

        return $this;
    }

    /**
     * Sets path for the root
     *
     * @param string $path
     *
     * @return FileRoot
     */
    public function setPath($path)
    {
        $this->_path = $path;

        return $this;
    }

    /**
     * Sets url
     *
     * @param string $url
     *
     * @return FileRoot
     */
    public function setURL($url)
    {
        $this->_URL = $url;

        return $this;
    }

    /**
     * Sets mime types denied to upload
     *
     * @param array $mimes
     *
     * @return FileRoot
     */
    public function setUploadDeny($mimes)
    {
        $this->_uploadDeny = $mimes;
    }

    /**
     * Sets mime types allowed to upload
     *
     * @param array $mimes
     *
     * @return FileRoot
     */
    public function setUploadAllow($mimes)
    {
        $this->_uploadAllow = $mimes;

        return $this;
    }

    /**
     * Sets which mimes get precedence $_uploadDeny or $_uploadAllow.
     *
     * @param array $order
     *
     * @return FileRoot
     */
    public function setUploadOrder($order)
    {
        $this->_uploadOrder = $order;

        return $this;
    }

    /**
     * Sets a callback function for file folder access control.
     * What is the permission for this file or folder like read or write.
     * Or hidden or locked
     *
     * @return void
     */
    public function setAccessControl(callable $callback)
    {
        $this->_accessControl = $callback;

        return $this;
    }

    /**
     * Sets callback function for file, folder name validation
     *
     * @param callable $callback
     *
     * @return FileRoot
     */
    public function setAcceptedName(callable $callback)
    {
        $this->_acceptedName = $callback;

        return $this;
    }

    /**
     * Sets commands are need to disable for this volume
     *
     * @param array $commands
     *
     * @return FileRoot
     */
    public function setDisabled($commands)
    {
        $this->_disabled = $commands;

        return $this;
    }

    /**
     * Sets regex for allowed mime type for Content-Type Header
     *
     * @param string $regex
     *
     * @return FileRoot
     */
    public function setDispInlineRegex($regex)
    {
        $this->_dispInlineRegex = $regex;

        return $this;
    }

    /**
     * Sets hash for trash bin for this volume
     * Folder hash value on elFinder to trash bin of this volume, it require 'copyJoin' to true
     *
     * @param string $hash
     *
     * @return FileRoot
     */
    public function setTrashHash($hash)
    {
        $this->_trashHash = $hash;

        return $this;
    }

    /**
     * Sets  winHash fix
     *
     * @param bool $status
     *
     * @return FileRoot
     */
    public function setWinHashFix($status)
    {
        $this->_winHashFix = $status;

        return $this;
    }

    /**
     * Sets default file permission for this volume
     *
     * @param array $permission
     *
     * @return FileRoot
     */
    public function setDefaults($permission)
    {
        $this->_defaults = $permission;

        return $this;
    }

    /**
     * Sets $_allowChmodReadOnly. Which determine exec to readonly file is allowed or not
     *
     * @param bool $allow
     *
     * @return FileRoot
     */
    public function setAllowChmodReadOnly($allow)
    {
        $this->_allowChmodReadOnly = $allow;

        return $this;
    }

    /**
     * Sets $_statOwner. Which determine Inclusion of file owner, group & mode in stat results
     *
     * @param bool $allow
     *
     * @return FileRoot
     */
    public function setStatOwner($allow)
    {
        $this->_statOwner = $allow;

        return $this;
    }

    /**
     * Sets attributes fo file permission using pattern
     *
     * @param array $attributes
     *
     * @return FileRoot
     */
    public function setAttributes(array $attributes)
    {
        $this->_attributes = $attributes;

        return $this;
    }

    /**
     * Sets copyTo which determines copy from other volume is allowed or not
     *
     * @param bool $copyTo
     *
     * @return FileRoot
     */
    public function setCopyTo($copyTo)
    {
        $this->_copyTo = $copyTo;

        return $this;
    }

    /**
     * Sets maximum size of file upload
     *
     * @param string|int $uploadMaxSize
     *
     * @return FileRoot
     */
    public function setUploadMaxSize($uploadMaxSize)
    {
        $this->_uploadMaxSize = $uploadMaxSize;

        return $this;
    }

    /**
     * Sets allowed archive mime type
     *
     * @param array $archiveMimes
     *
     * @return void
     */
    public function setArchiveMimes($archiveMimes)
    {
        $this->_archiveMimes = $archiveMimes;

        return $this;
    }

    public function setMaxTargets($archiveMimes)
    {
        $this->_archiveMimes = $archiveMimes;

        return $this;
    }

    public function setOption($name, $value)
    {
        if (property_exists($this, "_{$name}")) {
            return $this->{"_{$name}"} = $value;
        }

        throw new Exception('Property [' . esc_html($name) . '] not Exists in ' . __CLASS__);
    }

    public function getOption($name)
    {
        if (property_exists($this, $name)) {
            return $this->{$name};
        }
    }

    public function getOptions()
    {
        $options['tmbPath']            = $this->getOption('_tmbPath');
        $options['id']                 = $this->getOption('_id');
        $options['alias']              = $this->getOption('_alias');
        $options['driver']             = $this->getOption('_driver');
        $options['path']               = $this->getOption('_path');
        $options['URL']                = $this->getOption('_URL');
        $options['uploadDeny']         = $this->getOption('_uploadDeny');
        $options['uploadAllow']        = $this->getOption('_uploadAllow');
        $options['uploadOrder']        = $this->getOption('_uploadOrder');
        $options['accessControl']      = $this->getOption('_accessControl');
        $options['acceptedName']       = $this->getOption('_acceptedName');
        $options['disabled']           = $this->getOption('_disabled');
        $options['dispInlineRegex']    = $this->getOption('_dispInlineRegex');
        $options['trashHash']          = $this->getOption('_trashHash');
        $options['winHashFix']         = $this->getOption('_winHashFix');
        $options['defaults']           = $this->getOption('_defaults');
        $options['allowChmodReadOnly'] = $this->getOption('_allowChmodReadOnly');
        $options['statOwner']          = $this->getOption('_statOwner');
        $options['attributes']         = $this->getOption('_attributes');
        $options['copyTo']             = $this->getOption('_copyTo');
        $options['uploadMaxSize']      = $this->getOption('_uploadMaxSize');
        $options['archiveMimes']       = $this->getOption('_archiveMimes');
        $options['maxTargets']         = $this->getOption('_maxTargets');

        return $options;
    }
}
