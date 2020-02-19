<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2020 Leo Feyer
 *
 * @package   Contao Marketing Suite
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright 2020 numero2 - Agentur für digitales Marketing
 */


namespace numero2\MarketingSuite;

use Contao\Controller;


class Runonce extends Controller {


    /**
     * Initialize the object
     */
    public function __construct() {
        parent::__construct();
    }


    /**
     * Run the controller
     */
    public function run() {
        $this->generateEmptyCMSConfigFile();
    }


    /**
     * Generate an empty cmsconfig file if none exists
     */
    protected function generateEmptyCMSConfigFile() {

        if( !file_exists(TL_ROOT . '/system/config/cmsconfig.php') ) {
            file_put_contents(TL_ROOT . '/system/config/cmsconfig.php', "<?php\n\n### INSTALL SCRIPT START ###\n### INSTALL SCRIPT STOP ###\n");
        }
    }
}