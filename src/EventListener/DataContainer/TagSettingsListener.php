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
use Contao\Controller;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\DataContainer;
use Contao\ModuleModel;
use numero2\MarketingSuite\Backend\License as dohfa;


class TagSettingsListener {


    /**
     * Return all module templates as array
     *
     * @param Contao\DataContainer $dc
     *
     * @return array
     *
     * @Callback(table="tl_cms_tag_settings", target="fields.cms_tag_customTpl.options")
     */
    public function getModuleTemplates( DataContainer $dc ) {

        return Controller::getTemplateGroup('mod_' . CMSConfig::get('cms_tag_type'));
    }


    /**
     * Return all types as an array
     *
     * @return array
     *
     * @Callback(table="tl_cms_tag_settings", target="fields.cms_tag_type.options")
     */
    public function getFrontendTypes( DataContainer $dc ) {

        $types = [];

        foreach( $GLOBALS['TL_DCA'][$dc->table]['palettes'] as $k => $v ) {

            if( $k == '__selector__' || (substr($k,0,4) != 'cms_' && $k !== 'default') ) {
                continue;
            }

            if( !dohfa::hasFeature('tag'.substr($k, 3)) && !in_array($k, ['default', 'cms_tag_modules']) ) {
                continue;
            }

            $types[$k] = $k;
        }

        return $types;
    }


    /**
     * Modifies the palettes
     *
     * @param Contao\DataContainer $dc
     *
     * @return array
     *
     * @Callback(table="tl_cms_tag_settings", target="config.onload")
     */
    public function modifyPalettes( DataContainer $dc ) {

        if( is_array($GLOBALS['TL_DCA'][$dc->table]['palettes']) ) {

            foreach( $GLOBALS['TL_DCA'][$dc->table]['palettes'] as $type => $palette ) {

                if( !is_array($palette) ) {

                    // remove fields that are not included in the current license
                    $aRemoveFields = [];

                    if( !dohfa::hasFeature('tags_cookie_lifetime') ) {
                        $aRemoveFields[] = 'cms_tag_cookie_lifetime';
                    }

                    if( !dohfa::hasFeature('tags_accept_subdomains') ) {
                        $aRemoveFields[] = 'cms_tag_accept_subdomains';
                    }

                    if( !empty($aRemoveFields) ) {
                        $GLOBALS['TL_DCA'][$dc->table]['palettes'][$type] = str_replace(
                            $aRemoveFields
                        ,   ''
                        ,    $palette
                        );
                    }
                }
            }
        }
    }


    /**
     * Sets the available layout options based on the type of consent
     *
     * @param Contao\DataContainer $dc
     *
     * @return array
     *
     * @Callback(table="tl_cms_tag_settings", target="config.onload")
     */
    public function setLayoutSelectorOptions( DataContainer $dc ) {

        $type = CMSConfig::get('cms_tag_type');

        if( $type === "cms_tag_modules" && $dc->table === "tl_module" ) {

            $oModule = null;
            if( $dc->id ?? null ) {
                $oModule = ModuleModel::findById($dc->id);
            }

            if( $oModule ) {
                $type = $oModule->type;
            }
        }

        if( $type ) {

            $class = null;
            $class = !empty($GLOBALS['FE_MOD']['marketing_suite'][$type]) ? $GLOBALS['FE_MOD']['marketing_suite'][$type] : null;

            if( $class ) {
                if( method_exists($class, 'getLayoutOptions') ) {
                    $GLOBALS['TL_DCA'][$dc->table]['fields']['cms_layout_selector']['options'] = $class::getLayoutOptions();
                }

                if( method_exists($class, 'getLayoutSprite') ) {
                    $GLOBALS['TL_DCA'][$dc->table]['fields']['cms_layout_selector']['eval']['sprite'] = $class::getLayoutSprite($type);
                }
            }
        }
    }
}
