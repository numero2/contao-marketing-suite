<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2019 Leo Feyer
 *
 * @package   Contao Marketing Suite
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright 2019 numero2 - Agentur für digitales Marketing
 */


namespace numero2\MarketingSuite\DCAHelper;

use Contao\Backend as CoreBackend;
use Contao\CMSConfig;
use Contao\Config;
use Contao\Database;
use Contao\DataContainer;
use Contao\Date;
use Contao\Input;
use Contao\Message;
use Contao\PageModel;
use numero2\MarketingSuite\Api\MarketingSuite as API;
use numero2\MarketingSuite\Backend;
use numero2\MarketingSuite\Backend\License as Lic;


class License extends CoreBackend {


    /**
     * Saves the license and empties dependent fields on change
     *
     * @param mixed $value
     * @param \DataContainer $dc
     *
     * @return string
     *
     * @throws \Exception
     */
    public function save( $value, DataContainer $dc ) {

        // new license key, drop old data
        if( $value != $dc->activeRecord->cms_root_license ) {

            $objResult = Database::getInstance()->prepare("UPDATE tl_page SET cms_root_data=NULL, cms_root_key=NULL, cms_root_sign=NULL where id=?")
                ->execute($dc->activeRecord->id);

            $dc->activeRecord->cms_root_data = NULL;
            $dc->activeRecord->cms_root_key = NULL;
            $dc->activeRecord->cms_root_sign = NULL;

            // check license
            if( !empty($value) && (empty($dc->activeRecord->cms_root_data) || empty($dc->activeRecord->cms_root_key) || empty($dc->activeRecord->cms_root_sign) )  ) {

                $objPage = NULL;
                $objPage = PageModel::findOneById($dc->activeRecord->id);

                if( $objPage ) {

                    $oAPI = NULL;
                    $oAPI = new API();

                    try {

                        if( $oAPI->checkLicense($value, $objPage) ) {
                            $oAPI->getFeatures($value, $objPage);
                        }

                    } catch( \Exception $e ) {

                        $this->handleLicenseCheckException($e,true);
                    }
                }
            }
        }

        return $value;
    }


    /**
     * Checks if the license is still valid
     *
     * @param mixed $value
     * @param \DataContainer $dc
     *
     * @return string
     */
    public function check( $value, DataContainer $dc ) {

        Lic::sepcop();

        if( $value && !Input::post('cms_root_license') ) {

            $objPage = NULL;
            $objPage = PageModel::findOneById($dc->activeRecord->id);

            if( $objPage ) {

                $oAPI = NULL;
                $oAPI = new API();

                try {

                    // check if license valid
                    if( $oAPI->checkLicense($value, $objPage) ) {

                        Message::addNew(sprintf(
                            $GLOBALS['TL_LANG']['cms_api_messages']['license_valid']
                        ,   Date::parse(Config::get('datimFormat'), time())
                        ));

                        // update list of available features
                        $oAPI->getFeatures($value, $objPage);

                        // check if we're on a testdomain
                        if( Lic::isTestDomain($dc->activeRecord->id) && !CMSConfig::get('testmode') ) {
                            Message::addInfo($GLOBALS['TL_LANG']['cms_api_messages']['is_testdomain']);
                        }
                    }

                } catch( \Exception $e ) {

                    $this->handleLicenseCheckException($e);
                }
            }
        }

        return $value;
    }


    /**
     * Handles the given exception
     *
     * @param \Exception $e Exception as returned by API
     * @param bool $throw Re-throw the given exception
     *
     * @throws \Exception
     */
    private function handleLicenseCheckException( \Exception $e, $throw=false ) {

        switch( $e->getCode() ) {

            case 1000:
                Message::addInfo($GLOBALS['TL_LANG']['cms_api_messages']['errors']['connection_error']);
            break;

            default:

                $msg = $GLOBALS['TL_LANG']['cms_api_messages']['errors'][$e->getCode()]?:$GLOBALS['TL_LANG']['cms_api_messages']['errors']['unknown_error'];

                if( $throw ) {

                    throw new \Exception(
                        $msg
                    ,   $e->getCode()
                    );

                } else {
                    Message::addError($msg);
                }

            break;
        }
    }


    /**
     * Generates a button that will refresh the license data if clicked
     *
     * @param \DataContainer $dc
     *
     * @return string HTML markup for the button
     */
    public function refresh( DataContainer $dc ) {

        $label = $GLOBALS['TL_DCA']['tl_page']['fields']['cms_refresh_license']['label'];
        $objPage = PageModel::findOneById($dc->activeRecord->id);

        if( Input::get('cms_license') && Input::get('cms_license') == 'refresh' ) {

            $oAPI = NULL;
            $oAPI = new API();

            try {

                if( $objPage && !empty($objPage->cms_root_license) ) {
                    if( $oAPI->checkLicense($objPage->cms_root_license, $objPage) ) {
                        $oAPI->getFeatures($objPage->cms_root_license, $objPage);
                    }
                }

            } catch( \Exception $e ) {
                $this->handleLicenseCheckException($e,false);
            }

            $this->redirect($this->addToUrl('', true, ['cms_license']));
        }

        if( !$objPage || empty($objPage->cms_root_license) ) {
            return '';
        }

        return
            '<div class="w50 widget">'
                .'<h3>'.$label[0].'</h3>'
                .'<a class ="button" href="'.$this->addToUrl('cms_license=refresh').'">'.$label['button'].'</a>'
                .'<p class="tl_help tl_tip" title="">'.$label[1].'</p>'
            .'</div>';
    }
}