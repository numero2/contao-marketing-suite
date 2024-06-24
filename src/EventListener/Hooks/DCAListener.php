<?php

/**
 * Contao Marketing Suite Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\MarketingSuiteBundle\EventListener\Hooks;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\System;
use numero2\MarketingSuite\Widget\ElementStyle;


class DCAListener {


    /**
     * Adds styling related fields for all palettes containing our 'cms_element_style' field
     *
     * @param string $strTable
     *
     * @Hook("loadDataContainer")
     */
    public static function addStylingFields( $strTable ) {

        if( !empty($GLOBALS['TL_DCA'][$strTable]) && array_key_exists('palettes', $GLOBALS['TL_DCA'][$strTable]) ) {

            foreach( $GLOBALS['TL_DCA'][$strTable]['palettes'] as $palette ) {

                // check palette for styling field
                if( !is_array($palette) && strpos($palette, 'cms_element_style') !== false ) {

                    $oES = null;
                    $oES = System::importStatic(ElementStyle::class);

                    $oES->addStylingFields((object)['table' => $strTable]);

                    break;
                }
            }
        }
    }
}
