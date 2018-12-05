<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2018 Leo Feyer
 *
 * @package   Contao Marketing Suite
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright 2018 numero2 - Agentur für digitales Marketing
 */


/**
 * Namespace
 */
namespace numero2\MarketingSuite;


class Runonce extends \Controller {


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

        $this->generateExampleGroupsInTags();
        $this->generateEmptyCMSConfigFile();
    }



    /**
     * Generate example groups in tag
     */
    protected function generateExampleGroupsInTags() {

        $oDB = \Database::getInstance();

        if( !$oDB->tableExists('tl_cms_tag') || TagModel::countAll() ) {
            return;
        }

        \System::loadLanguageFile('cms_default');

        $oTag = new TagModel();
        $oTag->tstamp = time();
        $oTag->sorting = 32;
        $oTag->anonymize_ip = '1';
        $oTag->enable_on_cookie_accept = '1';
        $oTag->pid = '0';
        $oTag->type = 'group';

        $defaultData = $GLOBALS['TL_LANG']['cms_tag_default'];

        if( is_array($defaultData) && count($defaultData) ) {
            foreach( $defaultData as $dataKey => $data ) {

                $current =  clone $oTag;
                foreach( $data as $key => $value ) {
                    $current->{$key}  = $value;
                }

                $current->save();

                if( $dataKey == 0 ) {
                    $sessionCookie =  clone $oTag;

                    $sessionCookie->name = 'Session-Cookie';
                    $sessionCookie->enable_on_cookie_accept = '';
                    $sessionCookie->pid = $current->id;
                    $sessionCookie->type = 'session';

                    $sessionCookie->save();
                }

                $oTag->sorting *= 2;
            }
        }
    }

    protected function generateEmptyCMSConfigFile() {

        if( !file_exists(TL_ROOT . '/system/config/cmsconfig.php') ) {

            file_put_contents(TL_ROOT . '/system/config/cmsconfig.php', "<?php\n\n### INSTALL SCRIPT START ###\n### INSTALL SCRIPT STOP ###\n");
        }
    }
}
