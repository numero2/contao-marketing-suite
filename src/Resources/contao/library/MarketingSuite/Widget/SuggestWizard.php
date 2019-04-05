<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2018 Leo Feyer
 *
 * @package   Contao Marketing Suite
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright 2018 numero2 - Agentur für digitales Marketing
 */


namespace numero2\MarketingSuite\Widget;


use Contao\DataContainer;
use Contao\Database;
use numero2\MarketingSuite\Backend;
use numero2\MarketingSuite\Backend\License as sldkf;

class SuggestWizard {


    /**
     * Displays a dynamic preview of the already used values for the field
     *
     * @param \DataContainer $dc
     *
     * @return string
     */
    public function generate( DataContainer $dc ) {

        $aSuggestion = self::getSuggestions($dc);

        $aData = [
            'suggestions' => $aSuggestion
        ,   'field' => $dc->field
        ];

        return Backend::parseWithTemplate('backend/widgets/suggest_wizard', $aData);
    }


    /**
     * searches al used values for the field
     *
     * @param \DataContainer $dc
     *
     * @return string
     */
    public static function getSuggestions( DataContainer $dc ) {

        $objResult = Database::getInstance()->prepare("SELECT DISTINCT $dc->field FROM $dc->table")
                        ->execute();

        $aResult = [];

        while( $objResult->next() ) {
            if( strlen($objResult->{$dc->field}) ) {
                $aResult[] = $objResult->{$dc->field};
            }
        }

        return $aResult;
    }
}
