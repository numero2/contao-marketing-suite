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


namespace numero2\MarketingSuite\Helper;

use Contao\StyleSheets;
use Contao\Controller;


class StyleSheet {


    private $emptyDefinitions;
    private $oStyleSheet;


    public function __construct() {

        Controller::loadDataContainer('tl_style');

        $aEmptyDefinitions = [];

        // create empty set of style definitions, otherwise core will throw warnings in PHP>8
        foreach( $GLOBALS['TL_DCA']['tl_style']['fields'] as $field => $dca ) {

            if( empty($dca['inputType']) ) {
                continue;
            }

            if( $dca['inputType'] === 'inputUnit' ) {
                $aEmptyDefinitions[$field] = ['value'=>'', 'unit'=>''];
            } else if( $dca['inputType'] === 'trbl' ) {
                $aEmptyDefinitions[$field] = ['bottom'=>'', 'left'=>'', 'right'=>'', 'top'=>'', 'unit'=>''];
            } else if( $dca['inputType'] === 'text' && !empty($dca['eval']['multiple']) && !empty($dca['eval']['size']) && $dca['eval']['size'] > 1) {
                $aEmptyDefinitions[$field] = array_pad([], intval($dca['eval']['size']), '');
            } else  {
                $aEmptyDefinitions[$field] = '';
            }
        }

        $this->emptyDefinitions = $aEmptyDefinitions;

        $this->oStyleSheet = new StyleSheets();
    }


    /**
     * compile the given style information to valid css
     *
     * @param array $aStyle
     *
     * @return string
     */
    public function generateDefinition( array $aStyle ): string {

        foreach( $aStyle as $field => $value) {

            if( empty($this->emptyDefinitions[$field]) ) {
                continue;
            }

            if( is_array($this->emptyDefinitions[$field]) ) {
                $aStyle[$field] = (array)$value;
            }
        }

        return $this->oStyleSheet->compileDefinition(array_merge($this->emptyDefinitions, $aStyle), false, [], [], true);
    }
}
