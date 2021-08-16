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
use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\DataContainer;
use Contao\Image;
use Contao\Input;
use Contao\PageModel;
use Contao\StringUtil;
use numero2\MarketingSuite\Backend\License as xyjebv;
use numero2\MarketingSuite\TagModel;


class Content extends CoreBackend {


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
     */
    public function editButton( $row, $href, $label, $title, $icon, $attributes ) {

        if( strpos($row['type'], 'cms_') !== false ) {

            $typeAvailable = array_reduce($GLOBALS['TL_CTE'], function($result, $item) use ($row) {

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

        return '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ';
    }


    /**
     * Add additional fields to palette for tag visibility
     *
     * @param DataContainer $dc
     */
    public function addTagVisibilityFields($dc) {

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
     * @param DataContainer $dc
     *
     * @return array
     */
    public function getContentElementTags( DataContainer $dc) {

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
     */
    public function getPageScopes() {

        $this->loadLanguageFile('tl_cms_tag');

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
     * @param Datacontainer $dc
     *
     * @return string
     */
    public function sanityCheckPageScopeWithPages( $varValue, Datacontainer $dc ) {

        if( Input::post('cms_pages_scope') == "current_page" ) {

            $oPages = PageModel::findMultipleByIds(StringUtil::deserialize($varValue));

            if( $oPages ) {
                foreach( $oPages as $oPage ) {
                    if( $oPage->type == 'root' ) {
                        throw new \Exception($GLOBALS['TL_LANG']['ERR']['no_root_pages_for_pagescope_current']);
                    }
                    if( in_array($oPage->type, ['forward', 'redirect']) ) {
                        throw new \Exception($GLOBALS['TL_LANG']['ERR']['no_forward_redirect_pages_for_pagescope_current']);
                    }
                }
            }
        }

        return $varValue;
    }


    /**
     * Return the layout_options for the given conversion type
     *
     * @param Contao\Datacontainer $dc
     *
     * @return array
     */
    public function getLayoutOptions( Datacontainer $dc ) {

        switch( $dc->activeRecord->type ) {
            case 'cms_overlay':
                return [
                    'modal_overlay' => $GLOBALS['TL_LANG']['tl_content']['layout_options']['modal_overlay']
                ,   'toast' => $GLOBALS['TL_LANG']['tl_content']['layout_options']['toast']
                ];
                break;
        }

        return [];
    }

}
