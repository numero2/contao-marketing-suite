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

use Contao\Backend;
use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\DataContainer;
use Contao\Image;
use Contao\Input;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Exception;
use numero2\MarketingSuite\Backend\License as xyjebv;
use numero2\MarketingSuite\TagModel;


class ContentListener {


    /**
     * Change the edit button if the type of element is not available
     *
     * @param array  $row
     * @param string $href
     * @param string $label
     * @param string $title
     * @param string $icon
     * @param string $attributes
     *
     * @return string
     *
     * @Callback(table="tl_content", target="list.operations.edit.button")
     */
    public function editButton( $row, $href, $label, $title, $icon, $attributes ) {

        if( strpos($row['type'], 'cms_') !== false ) {

            $typeAvailable = array_reduce($GLOBALS['TL_CTE'], function( $result, $item ) use ($row) {

                if( $result ) {
                    return $result;
                }

                if(array_key_exists($row['type'], $item) ) {
                    return true;
                }

                return $result;
            }, false);

            if( !$typeAvailable ) {
                return Image::getHtml('edit_.svg', $label).' ';
            }
        }

        return '<a href="'.Backend::addToUrl($href.'&amp;id='.$row['id']).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ';
    }


    /**
     * Add additional fields to palette for tag visibility
     *
     * @param Contao\DataContainer $dc
     *
     * @Callback(table="tl_content", target="config.onload")
     */
    public function addTagVisibilityFields( $dc ) {

        if( xyjebv::hasFeature('tags_content_module_element') ) {

            $pm = PaletteManipulator::create()
                ->addLegend('cms_tag_visibility_legend', 'invisible_legend', 'before')
                ->addField(['cms_tag_visibility'], 'cms_tag_visibility_legend', 'append')
            ;

            foreach( $GLOBALS['TL_DCA']['tl_content']['palettes'] as $key => $value ) {

                if( in_array($key, ['__selector__', 'default']) ) {
                    continue;
                }

                $pm->applyToPalette($key, 'tl_content');
            }
        }
    }


    /**
     * Gather all tags with a certain type
     *
     * @param Contao\DataContainer $dc
     *
     * @return array
     *
     * @Callback(table="tl_content", target="fields.cms_tag.options")
     * @Callback(table="tl_module", target="fields.cms_tag.options")
     */
    public function getContentElementTags( DataContainer $dc ) {

        $oTags = TagModel::findByType('content_module_element');

        if( $oTags ) {
            return $oTags->fetchEach('name');
        }

        return [];
    }


    /**
     * Return all tag types as array
     *
     * @return array
     *
     * @Callback(table="tl_content", target="fields.cms_pages_scope.options")
     */
    public function getPageScopes() {

        System::loadLanguageFile('tl_cms_tag');

        $types = [
            'none' => $GLOBALS['TL_LANG']['tl_cms_tag']['page_scopes']['none']
        ,   'current_and_all_children' => $GLOBALS['TL_LANG']['tl_cms_tag']['page_scopes']['current_and_all_children']
        ,   'current_and_direct_children' => $GLOBALS['TL_LANG']['tl_cms_tag']['page_scopes']['current_and_direct_children']
        ,   'current_page' => $GLOBALS['TL_LANG']['tl_cms_tag']['page_scopes']['current_page']
        ];

        return $types;
    }


    /**
     * performs a sanity chack for the field cms_pages_scope and pages
     *
     * @param string $varValue
     * @param Contao\DataContainer $dc
     *
     * @return string
     *
     * @Callback(table="tl_content", target="fields.cms_pages.save")
     */
    public function sanityCheckPageScopeWithPages( $varValue, DataContainer $dc ) {

        if( Input::post('cms_pages_scope') == "current_page" ) {

            $oPages = PageModel::findMultipleByIds(StringUtil::deserialize($varValue));

            if( $oPages ) {
                foreach( $oPages as $oPage ) {
                    if( $oPage->type == 'root' ) {
                        throw new Exception($GLOBALS['TL_LANG']['ERR']['no_root_pages_for_pagescope_current']);
                    }
                    if( in_array($oPage->type, ['forward', 'redirect']) ) {
                        throw new Exception($GLOBALS['TL_LANG']['ERR']['no_forward_redirect_pages_for_pagescope_current']);
                    }
                }
            }
        }

        return $varValue;
    }


    /**
     * Return the layout_options for the given conversion type
     *
     * @param Contao\DataContainer $dc
     *
     * @return array
     *
     * @Callback(table="tl_content", target="fields.cms_layout_option.options")
     */
    public function getLayoutOptions( DataContainer $dc ) {

        switch( $dc->activeRecord->type ) {
            case 'cms_overlay':
                return [
                    'modal_overlay' => $GLOBALS['TL_LANG']['tl_content']['layout_options']['modal_overlay']
                //,   'toast' => $GLOBALS['TL_LANG']['tl_content']['layout_options']['toast']
                ];
                break;
        }

        return [];
    }
}
