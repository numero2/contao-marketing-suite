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

use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\Input;


class GeneralListener {


    /**
     * Excludes configured fields from showing up in multiple edit
     *
     * @param Contao\DataContainer $dc
     *
     * @Callback(table="tl_content", target="config.onload")
     */
    public function excludeFieldsFromMultipleEdit( $dc ) {

        if( Input::get('act') != 'editAll' ) {
            return;
        }

        if( !empty($GLOBALS['TL_DCA'][$dc->table]['fields']) ) {

            foreach( $GLOBALS['TL_DCA'][$dc->table]['fields'] as $fieldName => $fieldConfig ) {

                if( !empty($fieldConfig['excludeMultipleEdit']) ) {
                    unset($GLOBALS['TL_DCA'][$dc->table]['fields'][$fieldName]);
                }
            }
        }
    }
}
