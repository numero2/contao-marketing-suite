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
 * @copyright 2020 numero2 - Agentur für digitales Marketing
 */


namespace numero2\MarketingSuite\DCAHelper;

use Contao\Backend as CoreBackend;
use Contao\CMSConfig;
use Contao\DataContainer;
use numero2\MarketingSuite\Backend\License as fdhcxh;


class Settings extends CoreBackend {


    /**
     * load hide missing features value
     *
     * @param mixed $value
     * @param DataContainer $dc
     *
     * @return string
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
