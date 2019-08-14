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
use Contao\Image;
use Contao\StringUtil;
use numero2\MarketingSuite\Backend\License as xyjebv;


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
}
