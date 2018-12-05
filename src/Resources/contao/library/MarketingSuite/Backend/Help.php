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


/**
 * Namespace
 */
namespace numero2\MarketingSuite\Backend;


class Help extends \BackendModule {


    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'backend/elements/help';

    private $aLabels;
    private $aTokens;
    private $strSuffix;
    private $strModule;


    /**
     * Set specific attributes
     *
     * @param string $strKey
     * @param mixed  $varValue
     */
    public function __set( $strKey, $varValue ) {

        switch( $strKey ) {
            case 'suffix':
                $this->strSuffix = $varValue;
                break;
            default:
                parent::__set($strKey, $varValue);
                break;
        }
    }


    /**
    * Generate the module
    *
    * @return string
    */
    public function generate() {

        $this->import('BackendUser', 'User');

        if( $this->User->cms_pro_mode_enabled == 1 ) {
            return '';
        }

        $this->strModule = \Input::get('do');

        if( !empty(\Input::get('mod')) ) {
            $this->strModule = \Input::get('do').'_'.\Input::get('mod');
        }

        self::loadLanguageFile('cms_be_help');

        $aLabelIndixes = [
            \Input::get('do').'_'.\Input::get('mod').'_'.\Input::get('table').'_'.$this->strSuffix
        ,   \Input::get('do').'_'.\Input::get('mod').'_'.\Input::get('table')
        ,   \Input::get('do').'_'.\Input::get('mod').'_'.$this->strSuffix
        ,   \Input::get('do').'_'.\Input::get('mod')
        ,   \Input::get('do').'_'.$this->strSuffix
        ,   \Input::get('do')
        ];

        $sLabelIndex = array_reduce(
            $aLabelIndixes
        ,   function( $carry, $item ) {
                $labels = $GLOBALS['TL_LANG']['cms_be_help'][$item];
                return (!$carry && $labels)?$item:$carry;
            }
        );

        $this->aLabels = $GLOBALS['TL_LANG']['cms_be_help'][$sLabelIndex];

        if( count($this->aLabels) == 0 ) {
            return '';
        }

        // HOOK: parse simple tokens
        if( isset($GLOBALS['TL_HOOKS']['cmsBeHelperParseSimpleTokens'][$this->strModule]) && \is_array($GLOBALS['TL_HOOKS']['cmsBeHelperParseSimpleTokens'][$this->strModule]) ) {

            $arrTokens = [];

            foreach( $GLOBALS['TL_HOOKS']['cmsBeHelperParseSimpleTokens'][$this->strModule] as $callback ) {

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
                $value = \StringUtil::parseSimpleTokens($value,$this->aTokens);
            }

            $this->Template->$key = $value;
        }

        // get fieldset state
        $objSessionBag = NULL;
        $objSessionBag = \System::getContainer()->get('session')->getBag('contao_backend');

        $fs = NULL;
        $fs = $objSessionBag->get('fieldset_states');

        if( empty($fs[$this->strTable]) || !$fs[$this->strTable]['cms_be_help_legend'] ) {
            $this->Template->collapsed = true;
        }

        $this->Template->table = $this->strTable;
    }
}
