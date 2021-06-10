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

use Contao\CMSConfig;
use Contao\Controller;
use Contao\Database;
use numero2\MarketingSuite\Backend\License;


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

        // newer versions will be handeled by classes in numero2\MarketingSuiteBundle\Migration
        if( version_compare(VERSION, '4.9', '<') ) {
            $this->generateEmptyCMSConfigFile();
            $this->migrateFormElements();
            // $this->migrateTestmode();
        }
    }


    /**
     * Generate an empty cmsconfig file if none exists
     */
    protected function generateEmptyCMSConfigFile() {

        if( !file_exists(TL_ROOT . '/system/config/cmsconfig.php') ) {
            file_put_contents(TL_ROOT . '/system/config/cmsconfig.php', "<?php\n\n### INSTALL SCRIPT START ###\n### INSTALL SCRIPT STOP ###\n");
        }
    }


    /**
     * Sets all content elements of type "form" to "cms_form" with marketing item label
     */
    protected function migrateFormElements() {

        $oDB = NULL;
        $oDB = Database::getInstance();

        if( $oDB->tableExists('tl_content') ) {

            if( $oDB->fieldExists('cms_mi_label', 'tl_content') ) {
                $oDB->execute("UPDATE tl_content SET type = 'cms_form' WHERE type = 'form' AND cms_mi_label != ''");
            }

            $oDB->execute("UPDATE tl_content SET type = 'cms_form' WHERE type = 'form' AND ptable = 'tl_cms_content_group'");
        }

        if( $oDB->tableExists('tl_cms_marketing_item') ) {

            $oDB->execute("UPDATE tl_cms_marketing_item SET type = 'cms_form' WHERE type = 'form'");
        }
    }


    /**
     * Sets testmode active if no root has a cms license
     */
    protected function migrateTestmode() {

        $oDB = NULL;
        $oDB = Database::getInstance();

        if( !CMSConfig::get('testmode') && (!$oDB->tableExists('tl_page') || !$oDB->fieldExists('cms_root_license', 'tl_page') || License::hasNoLicense()) ) {
            CMSConfig::persist('testmode', '1');
        }
    }
}
