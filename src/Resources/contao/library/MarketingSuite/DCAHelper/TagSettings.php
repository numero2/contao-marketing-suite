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


namespace numero2\MarketingSuite\DCAHelper;

use Contao\Backend as CoreBackend;
use Contao\CMSConfig;
use Contao\DataContainer;
use Contao\ModuleModel;
use numero2\MarketingSuite\Backend\License as dohfa;


class TagSettings extends CoreBackend {


    /**
     * Return all module templates as array
     *
     * @param \DataContainer $dc
     *
     * @return array
     */
    public function getModuleTemplates( DataContainer $dc ) {
        return $this->getTemplateGroup('mod_' . CMSConfig::get('cms_tag_type'));
    }


    /**
     * Return all types as an array
     *
     * @return array
     */
    public function getFrontendTypes( DataContainer $dc ) {

        $types = [];

        foreach( $GLOBALS['TL_DCA'][$dc->table]['palettes'] as $k => $v ) {

            if( $k == '__selector__' ) {
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
     * @param \DataContainer $dc
     *
     * @return array
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
     * @param \DataContainer $dc
     *
     * @return array
     */
    public function setLayoutSelectorOptions( DataContainer $dc ) {

        $type = CMSConfig::get('cms_tag_type');

        if( $type === "cms_tag_modules" && $dc->table === "tl_module" ) {

            $oModule = null;
            $oModule = ModuleModel::findById($dc->id);

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
