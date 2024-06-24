<?php

/**
 * Contao Marketing Suite Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace Contao;

use InvalidArgumentException;
use numero2\MarketingSuite\Backend\License as voudu;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;


/**
 * Loads and writes the local configuration file
 *
 * Custom settings above or below the `### INSTALL SCRIPT ###` markers will be
 * preserved.
 */
class CMSConfig {

    /**
     * Object instance (Singleton)
     * @var Config
     */
    protected static $objInstance;

    /**
     * Files object
     * @var Files
     */
    protected $Files;

    /**
     * Top content
     * @var string
     */
    protected $strTop = '';

    /**
     * Bottom content
     * @var string
     */
    protected $strBottom = '';

    /**
     * Modification indicator
     * @var boolean
     */
    protected $blnIsModified = false;

    /**
     * Local file existence
     * @var boolean
     */
    protected static $blnHasLcf;

    /**
     * Data
     * @var array
     */
    protected $arrData = [];

    /**
     * Cache
     * @var array
     */
    protected $arrCache = [];

    /**
     * Root dir
     * @var string
     */
    protected $strRootDir;

    private static $arrDeprecatedMap = [];

    private static $arrDeprecated = [];

    private static $arrToBeRemoved = [];

    /**
     * Prevent direct instantiation (Singleton)
     */
    protected function __construct() {

        $this->strRootDir = System::getContainer()->getParameter('kernel.project_dir');
    }

    /**
     * Automatically save the local configuration
     */
    public function __destruct() {

        if( $this->blnIsModified ) {
            $this->save();
        }
    }

    /**
     * Prevent cloning of the object (Singleton)
     */
    final public function __clone() {}

    /**
     * Return the current object instance (Singleton)
     *
     * @return static The object instance
     */
    public static function getInstance() {
        if( static::$objInstance === null ) {
            static::$objInstance = new static();
            static::$objInstance->initialize();
        }

        return static::$objInstance;
    }

    /**
     * Load all configuration files
     */
    protected function initialize() {

        if( static::$blnHasLcf === null ) {
            static::preload();
        }

        $strCacheDir = System::getContainer()->getParameter('kernel.cache_dir');
        voudu::con();

        if( file_exists($strCacheDir . '/contao/config/cmsconfig.php') ) {
            include $strCacheDir . '/contao/config/cmsconfig.php';
        } else {
            try {
                $files = System::getContainer()->get('contao.resource_locator')->locate('config/cmsconfig.php', null, false);
            } catch( InvalidArgumentException $e ) {
                $files = [];
            }

            foreach( $files as $file ) {
                include $file;
            }
        }

        // Include the local configuration file again
        if( static::$blnHasLcf ) {
            include $this->strRootDir . '/system/config/cmsconfig.php';
        }

        static::loadParameters();
    }

    /**
     * Mark the object as modified
     */
    protected function markModified() {

        // Return if marked as modified already
        if( $this->blnIsModified === true ) {
            return;
        }

        $this->blnIsModified = true;

        // Reset the top and bottom content (see #344)
        $this->strTop = '';
        $this->strBottom = '';

        // Import the Files object (required in the destructor)
        $this->Files = Files::getInstance();

        // Parse the local configuration file
        if( static::$blnHasLcf ) {
            $strMode = 'top';
            $resFile = fopen($this->strRootDir . '/system/config/cmsconfig.php', 'r');

            while( !feof($resFile) ) {
                $strLine = fgets($resFile);
                $strTrim = trim($strLine);

                if( $strTrim == '?>' ) {
                    continue;
                }

                if( $strTrim == '### INSTALL SCRIPT START ###' ) {
                    $strMode = 'data';
                    continue;
                }

                if( $strTrim == '### INSTALL SCRIPT STOP ###' ) {
                    $strMode = 'bottom';
                    continue;
                }

                if( $strMode == 'top' ) {
                    $this->strTop .= $strLine;
                } else if( $strMode == 'bottom' ) {
                    $this->strBottom .= $strLine;
                } else if( $strTrim ) {
                    $arrChunks = array_map('trim', explode('=', $strLine, 2));
                    $this->arrData[$arrChunks[0]] = $arrChunks[1];
                }
            }

            fclose($resFile);
        }
    }

    /**
     * Save the local configuration file
     */
    public function save() {

        if( !$this->strTop ) {
            $this->strTop = '<?php';
        }

        $strFile  = trim($this->strTop) . "\n\n";
        $strFile .= "### INSTALL SCRIPT START ###\n";

        foreach( $this->arrData as $k=>$v ) {
            $strFile .= "$k = $v\n";
        }

        $strFile .= "### INSTALL SCRIPT STOP ###\n";
        $this->strBottom = trim($this->strBottom);

        if( $this->strBottom ) {
            $strFile .= "\n" . $this->strBottom . "\n";
        }

        $strTemp = Path::join($this->strRootDir, 'system/tmp', md5(uniqid(mt_rand(), true)));

        // Write to a temp file first
        $objFile = fopen($strTemp, 'w');
        fwrite($objFile, $strFile);
        fclose($objFile);

        // Make sure the file has been written (see #4483)
        if( !filesize($strTemp) ) {
            System::getContainer()->get('monolog.logger.contao.error')->error('The local cms configuration file could not be written. Have your reached your quota limit?');
            return;
        }

        $fs = new Filesystem();

        // Adjust the file permissions (see #8178)
        $fs->chmod($strTemp, 0666 & ~umask());

        $strDestination = Path::join($this->strRootDir, 'system/config/cmsconfig.php');

        // Get the realpath in case it is a symlink (see #2209)
        if( $realpath = realpath($strDestination) ) {
            $strDestination = $realpath;
        }

        // Then move the file to its final destination
        $fs->rename($strTemp, $strDestination, true);

        // Reset the Zend OPcache
        if( \function_exists('opcache_invalidate') ) {
            opcache_invalidate($strDestination, true);
        }

        // Recompile the APC file (thanks to Trenker)
        if( \function_exists('apc_compile_file') && !\ini_get('apc.stat') ) {
            apc_compile_file($strDestination);
        }

        $this->blnIsModified = false;
    }

    /**
     * Add a configuration variable to the local configuration file
     *
     * @param string $strKey   The full variable name
     * @param mixed  $varValue The configuration value
     */
    public function add( $strKey, $varValue ) {
        $this->markModified();
        $this->arrData[$strKey] = $this->escape($varValue) . ';';
    }

    /**
     * Alias for Config::add()
     *
     * @param string $strKey   The full variable name
     * @param mixed  $varValue The configuration value
     */
    public function update( $strKey, $varValue ) {
        $this->add($strKey, $varValue);
    }

    /**
     * Remove a configuration variable
     *
     * @param string $strKey The full variable name
     */
    public function delete( $strKey ) {
        $this->markModified();
        unset($this->arrData[$strKey]);
    }

    /**
     * Check whether a configuration value exists
     *
     * @param string $strKey The short key
     *
     * @return boolean True if the configuration value exists
     */
    public static function has( $strKey ) {
        return \array_key_exists($strKey, $GLOBALS['TL_CMSCONFIG']);
    }

    /**
     * Return a configuration value
     *
     * @param string $strKey The short key
     *
     * @return mixed The configuration value
     */
    public static function get( $strKey ) {

        if( $newKey = self::getNewKey($strKey) ) {
            trigger_deprecation('contao/core-bundle', '5.0', 'Using "%s(\'%s\')" has been deprecated. Use the "%s" parameter instead.', __METHOD__, $strKey, $newKey);
        }

        if( isset(self::$arrToBeRemoved[$strKey]) ) {
            trigger_deprecation('contao/core-bundle', '5.0', 'Using "%s(\'%s\')" has been deprecated.', __METHOD__, $strKey, self::$arrToBeRemoved[$strKey]);
        }

        return $GLOBALS['TL_CMSCONFIG'][$strKey] ?? null;
    }

    /**
     * Temporarily set a configuration value
     *
     * @param string $strKey   The short key
     * @param mixed  $varValue The configuration value
     */
    public static function set( $strKey, $varValue ) {

        if( $newKey = self::getNewKey($strKey) ) {
            trigger_deprecation('contao/core-bundle', '5.0', 'Using "%s(\'%s\', …)" has been deprecated. Use the "%s" parameter instead.', __METHOD__, $strKey, $newKey);
        }

        if( isset(self::$arrToBeRemoved[$strKey]) ) {
            trigger_deprecation('contao/core-bundle', '5.0', 'Using "%s(\'%s\')" has been deprecated.', __METHOD__, $strKey, self::$arrToBeRemoved[$strKey]);
        }

        $GLOBALS['TL_CMSCONFIG'][$strKey] = $varValue;
    }

    /**
     * Return the new key if the existing one is deprecated
     *
     * @internal
     *
     * @param string $strKey The short key
     *
     * @return string|null
     */
    public static function getNewKey( $strKey ) {
        return self::$arrDeprecated[$strKey] ?? self::$arrDeprecatedMap[$strKey] ?? null;
    }

    /**
     * Permanently set a configuration value
     *
     * @param string $strKey   The short key or full variable name
     * @param mixed  $varValue The configuration value
     */
    public static function persist( $strKey, $varValue ) {

        $objConfig = static::getInstance();

        if( strncmp($strKey, '$GLOBALS', 8) !== 0 ) {
            $strKey = "\$GLOBALS['TL_CMSCONFIG']['$strKey']";
        }

        $objConfig->add($strKey, $varValue);
    }

    /**
     * Permanently remove a configuration value
     *
     * @param string $strKey The short key or full variable name
     */
    public static function remove( $strKey ) {

        $objConfig = static::getInstance();

        if( strncmp($strKey, '$GLOBALS', 8) !== 0 ) {
            $strKey = "\$GLOBALS['TL_CMSCONFIG']['$strKey']";
        }

        $objConfig->delete($strKey);
    }

    /**
     * Preload the default and local configuration
     */
    public static function preload() {

        $projectDir = System::getContainer()->getParameter('kernel.project_dir');

        // Include the local configuration file
        if( ($blnHasLcf = file_exists($projectDir . '/system/config/cmsconfig.php')) === true ) {
            include $projectDir . '/system/config/cmsconfig.php';
        }

        static::loadParameters();

        static::$blnHasLcf = $blnHasLcf;
    }

    /**
     * Override the database and SMTP parameters
     */
    protected static function loadParameters() {

        $container = System::getContainer();

        if( $container === null ) {
            return;
        }

        if( $container->hasParameter('contao.localconfig') && \is_array($params = $container->getParameter('contao.localconfig')) ) {
            foreach( $params as $key=>$value ) {
                $GLOBALS['TL_CMSCONFIG'][$key] = $value;
            }
        }

        foreach( self::$arrDeprecatedMap as $strKey=>$strParam ) {
            if( $container->hasParameter($strParam) ) {
                $GLOBALS['TL_CMSCONFIG'][$strKey] = $container->getParameter($strParam);
            }
        }
    }

    /**
     * Escape a value depending on its type
     *
     * @param mixed $varValue The value
     *
     * @return mixed The escaped value
     */
    protected function escape( $varValue ) {

        if( is_numeric($varValue) && $varValue < PHP_INT_MAX && !preg_match('/e|^[+-]?0[^.]/', $varValue) ) {
            return $varValue;
        }

        if( \is_bool($varValue) ) {
            return $varValue ? 'true' : 'false';
        }

        if( $varValue == 'true' ) {
            return 'true';
        }

        if( $varValue == 'false' ) {
            return 'false';
        }

        return "'" . str_replace('\\"', '"', preg_replace('/[\n\r\t ]+/', ' ', addslashes($varValue))) . "'";
    }
}
