<?php

/**
 * Contao Marketing Suite Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\MarketingSuiteBundle\EventListener\DataContainer;

use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\DataContainer;
use Contao\Message;
use Contao\PageModel;
use Exception;
use numero2\MarketingSuite\Api\MarketingSuite as API;


class PageListener {


    /**
     * Adds information if cache is enabled
     *
     * @param mixed $value
     * @param Contao\DataContainer $dc
     *
     * @return string
     *
     * @Callback(table="tl_page", target="fields.includeCache.load")
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
     * @param Contao\DataContainer $dc
     *
     * @Callback(table="tl_page", target="config.onundo")
     */
    public function refreshLicenseOnUndo( string $table, array $aRow, DataContainer $dc ): void {
        $this->refreshLicenseOnRestore( $table, 0, 0, $aRow );
    }


    /**
     * refresh the license on restore a version
     *
     * @param string $table
     * @param int $pid
     * @param int $iVersion
     * @param array $aRow
     *
     * @Callback(table="tl_page", target="config.onrestore")
     */
    public function refreshLicenseOnRestore( string $table, int $pid, int $iVersion, array $aRow ): void {

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
