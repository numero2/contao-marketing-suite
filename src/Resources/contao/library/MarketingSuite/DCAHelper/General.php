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
use Contao\Input;


class General extends CoreBackend {


    /**
     * Excludes configured fields from showing up in multiple edit
     *
     * @param DataContainer $dc
     */
    public function excludeFieldsFromMultipleEdit($dc) {

        if( \Input::get('act') != 'editAll' ) {
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
