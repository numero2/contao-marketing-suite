<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2020 Leo Feyer
 *
 * @package   Contao Marketing Suite
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright 2020 numero2 - Agentur für digitales Marketing
 */


namespace numero2\MarketingSuite\Backend;

use Contao\BackendModule;
use Contao\Input;
use Contao\StringUtil;
use numero2\MarketingSuite\Backend\License as awclhdo;


class LicenseMessage extends BackendModule {


    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'backend/elements/license_message';

    private $aLabels;
    private $aTokens;


    /**
    * Generate the module
    * @return string
    */
    public function generate() {

        $strModule = Input::get('do');

        if( !empty(Input::get('mod')) ) {
            $strModule = Input::get('do').'_'.Input::get('mod');
        } else {
            if( awclhdo::hasFeature(substr($strModule, 4)) || awclhdo::hasFeature(substr($strModule.'_element', 4)) ) {
                return '';
            }
        }

        self::loadLanguageFile('cms_be_license_message');

        $aLabelIndixes = [
            Input::get('do').'_'.Input::get('mod').'_'.Input::get('table')
        ,   Input::get('do').'_'.Input::get('mod')
        ,   Input::get('do')
        ];

        $sLabelIndex = array_reduce(
            $aLabelIndixes
        ,   function( $carry, $item ) {
                $labels = $GLOBALS['TL_LANG']['cms_be_license_message'][$item];
                return (!$carry && $labels)?$item:$carry;
            }
        );

        $this->aLabels = $GLOBALS['TL_LANG']['cms_be_license_message'][$sLabelIndex];


        if( empty($this->aLabels) || count($this->aLabels) == 0 ) {
            return '';
        }

        // HOOK: parse simple tokens
        if( isset($GLOBALS['TL_HOOKS']['cmsBeHelperParseSimpleTokens'][$strModule]) && \is_array($GLOBALS['TL_HOOKS']['cmsBeHelperParseSimpleTokens'][$strModule]) ) {

            $arrTokens = [];

            foreach( $GLOBALS['TL_HOOKS']['cmsBeHelperParseSimpleTokens'][$strModule] as $callback ) {

                $this->import($callback[0]);

                $arrTokens = array_merge(
                    $arrTokens
                ,   $this->{$callback[0]}->{$callback[1]}()
                );
            }

            $this->aTokens = $arrTokens;
        }

        return parent::generate();
    }


    /**
     * Compile the module
     */
    protected function compile() {

        // add labels
        foreach( $this->aLabels as $key => $value ) {

            if( !empty($this->aTokens) ) {
                $value = StringUtil::parseSimpleTokens($value, $this->aTokens);
            }

            $this->Template->$key = $value;
        }
    }
}
