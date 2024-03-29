<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2021 Leo Feyer
 *
 * @package   Contao Marketing Suite
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright 2021 numero2 - Agentur für digitales Marketing
 */


namespace numero2\MarketingSuite\Hooks;

use numero2\MarketingSuite\Widget\ElementStyle;


class DCA extends Hooks {


    /**
     * Adds styling related fields for all palettes containing our
     * 'cms_element_style' field
     *
     * @param string $strTable
     */
    public static function addStylingFields( $strTable ) {

        if( !empty($GLOBALS['TL_DCA'][$strTable]) && array_key_exists('palettes', $GLOBALS['TL_DCA'][$strTable]) ) {

            foreach( $GLOBALS['TL_DCA'][$strTable]['palettes'] as $palette ) {

                // check palette for styling field
                if( !is_array($palette) && strpos($palette, 'cms_element_style') !== false ) {

                    $oES = null;
                    $oES = new ElementStyle();

                    $oES->addStylingFields((object)['table' => $strTable]);

                    break;
                }
            }
        }
    }
}