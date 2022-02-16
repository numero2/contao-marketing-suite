<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2021 Leo Feyer
 *
 * @package   Contao Marketing Suite
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright 2021 numero2 - Agentur für digitales Marketing
 */


namespace numero2\MarketingSuite\DCAHelper;

use Contao\Backend as CoreBackend;
use Contao\DataContainer;
use Contao\Message;
use Contao\PageModel;
use Exception;
use numero2\MarketingSuite\Api\MarketingSuite as API;


class Page extends CoreBackend {


    /**
     * Adds information if cache is enabled
     *
     * @param mixed $value
     * @param DataContainer $dc
     *
     * @return string
     */
    public function addCacheInfo( $value, DataContainer $dc ) {

        if( $value ) {
            Message::addInfo($GLOBALS['TL_LANG']['tl_page']['MSG']['cms_cache_info']);
        }

        return $value;
    }


    /**
     * refresh the license on undo action
     *
     * @param string $table
     * @param array $aRow
     * @param DataContainer $dc
     */
    public function refreshLicenseOnUndo( string $table, array $aRow, DataContainer $dc ): void {
        $this->refreshLicenseOnRestoreVersion( $table, 0, 0, $aRow );
    }


    /**
     * refresh the license on restore a version
     *
     * @param string $table
     * @param int $pid
     * @param int $iVersion
     * @param array $aRow
     */
    public function refreshLicenseOnRestoreVersion( string $table, int $pid, int $iVersion, array $aRow ): void {

        if( $table !== 'tl_page' ) {
            return;
        }

        if( !empty($aRow['cms_root_license']) ) {

            $oPage = PageModel::findOneById($aRow['id']);

            if( $oPage ) {

                $oPage->refresh();

                $oPage->cms_root_key = null;
                $oPage->cms_root_data = null;
                $oPage->cms_root_sign = null;
                $oPage->save();

                $oAPI = new API();

                try {

                    if( $oAPI->checkLicense($oPage->cms_root_license, $oPage) ) {
                        $oAPI->getFeatures($oPage->cms_root_license, $oPage);
                    }

                } catch( Exception $e ) {
                }
            }
        }
    }

}
