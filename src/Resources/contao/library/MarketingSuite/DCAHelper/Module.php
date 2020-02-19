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
use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\DataContainer;
use Contao\Input;
use Contao\Message;
use numero2\MarketingSuite\Backend\License as rtjhp;
use numero2\MarketingSuite\DCAHelper\TagSettings;


class Module extends CoreBackend {


    public static $aIgnoreFields = ['cms_tag_type'];
    public static $aCoreFields = ['customTpl', 'cssID'];


    /**
     * Add additional fields to palette for tag visibility
     *
     * @param Contao\DataContainer $dc
     */
    public function addMarketingSuiteFields( $dc ) {

        $this->addTagModules($dc);
        $this->addTagVisibilityFields($dc);
    }


    /**
     * Add additional fields to palette for tag visibility
     *
     * @param Contao\DataContainer $dc
     */
    public function addTagVisibilityFields( $dc ) {

        if( rtjhp::hasFeature('tags_content_module_element') ) {

            $pm = PaletteManipulator::create()
                ->addLegend('cms_tag_visibility_legend', '', 'after')
                ->addField(['cms_tag_visibility'], 'cms_tag_visibility_legend', 'append')
            ;

            foreach( $GLOBALS['TL_DCA']['tl_module']['palettes'] as $key => $value ) {

                if( in_array($key, ['__selector__', 'default']) ) {
                    continue;
                }

                $pm->applyToPalette($key, 'tl_module');
            }
        }
    }


    /**
     * Add additional fields to palette for tag mouldes
     *
     * @param Contao\DataContainer $dc
     */
    public function addTagModules( $dc ) {

        $aTypes = TagSettings::getFrontendTypes($dc);
        $this->loadLanguageFile('tl_cms_tag_settings');

        if( CMSConfig::get('cms_tag_type') == "cms_tag_modules" ) {

            $this->loadDataContainer('tl_cms_tag_settings');

            $aIgnoreFields = self::$aIgnoreFields;
            $aCoreFields =  self::$aCoreFields;
            $aSettingsFields = array_map(function($value) { return 'cms_tag_'.$value; }, $aCoreFields);

            foreach( $aTypes as $k => $v ) {
                if( in_array($k, ['default', 'cms_tag_modules']) ) {
                    continue;
                }

                $strPalette = str_replace($aSettingsFields, $aCoreFields, $GLOBALS['TL_DCA']['tl_cms_tag_settings']['palettes'][$k]);
                $GLOBALS['TL_DCA']['tl_module']['palettes'][$k] = str_replace('{title_legend},cms_tag_type', $GLOBALS['TL_DCA']['tl_module']['palettes'][$k], $strPalette);

            }

            foreach( $GLOBALS['TL_DCA']['tl_cms_tag_settings']['subpalettes'] as $k => $v ) {
                if( in_array($k, $aIgnoreFields) || in_array($k, $aSettingsFields) ) {
                    continue;
                }

                $GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = $k;
                $GLOBALS['TL_DCA']['tl_module']['subpalettes'][$k] = $v;
            }

            self::addSQLDefinitionForTagSettings('tl_module');

        } else {

            foreach( $aTypes as $k => $v ) {
                if( in_array($k, ['default', 'cms_tag_modules']) ) {
                    continue;
                }

                if( Input::get('act') == 'edit' ) {

                    $GLOBALS['TL_LANG']['FMD'][$k][0] .= '['.$GLOBALS['TL_LANG']['MSC']['cms_disabled'].']';

                } else {
                    $GLOBALS['TL_LANG']['FMD'][$k][0] .= '] ['.$GLOBALS['TL_LANG']['MSC']['cms_disabled'];
                }
            }
        }
    }


    /**
     * Add the SQL definition from tl_cms_tag_settings
     *
     * @param string $strTable
     */
    public function addSQLDefinitionForTagSettings( $strTable ) {

        if( $strTable != 'tl_module' ) {
            return;
        }

        $this->loadDataContainer('tl_cms_tag_settings');

        foreach( $GLOBALS['TL_DCA']['tl_cms_tag_settings']['fields'] as $k => $v ) {
            if( in_array($k, self::$aIgnoreFields) || in_array($k, self::$aCoreFields) ) {
                continue;
            }

            $GLOBALS['TL_DCA']['tl_module']['fields'][$k] = $v;
        }
    }


    /**
     * Add message if the current type is a EU consent module and is not configured as frontend module
     *
     * @param string $varValue
     * @param Contao\DataContianer $dc
     */
    public function addTypeMessage( $varValue, DataContainer $dc ) {

        if( CMSConfig::get('cms_tag_type') != 'cms_tag_modules' ) {

            $aTypes = [];
            $aTypes = TagSettings::getFrontendTypes($dc);

            if( !empty($aTypes) && in_array($varValue, $aTypes) ) {
                Message::addError($GLOBALS['TL_LANG']['tl_cms_tag_settings']['msg']['why_disabled']);
            }
        }

        return $varValue;
    }
}
