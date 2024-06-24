<?php

/**
 * Contao Marketing Suite Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\MarketingSuite\Widget;

use Contao\Database;
use Contao\DataContainer;
use numero2\MarketingSuite\Backend;


class SuggestWizard {


    /**
     * Displays a dynamic preview of the already used values for the field
     *
     * @param Contao\DataContainer $dc
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
     * @param Contao\DataContainer $dc
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
