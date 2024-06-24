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

use Contao\CMSConfig;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\DataContainer;
use numero2\MarketingSuite\Backend\License as fdhcxh;


class SettingsListener {


    /**
     * load hide missing features value
     *
     * @param mixed $value
     * @param Contao\DataContainer $dc
     *
     * @return string
     *
     * @Callback(table="tl_cms_settings", target="fields.hide_missing_features.load")
     */
    public function loadHideMissingFeatures( $value, DataContainer $dc ) {

        if( !fdhcxh::hasFeature($dc->field) ) {

            $GLOBALS['TL_DCA']['tl_cms_settings']['fields']['hide_missing_features']['eval']['disabled'] = true;

            if( $value ) {
                CMSConfig::set($dc->field, false);
                CMSConfig::persist($dc->field, false);
            }

            return false;
        }

        return $value;
    }
}
