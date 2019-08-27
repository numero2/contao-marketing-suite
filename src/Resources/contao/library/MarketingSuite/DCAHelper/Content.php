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
use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\Image;
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
    public function getContentElementTags($dc) {

        $oTags = TagModel::findByType('content_module_element');

        if( $oTags ) {
            return $oTags->fetchEach('name');
        }

        return [];
    }
}
