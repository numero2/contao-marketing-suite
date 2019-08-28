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


namespace numero2\MarketingSuite;

use Contao\BackendTemplate;
use Contao\Environment;
use Contao\Input;
use Contao\Module;
use Contao\StyleSheets;
use Contao\System;
use numero2\MarketingSuite\Backend\License as baguru;
use Patchwork\Utf8;


class ModuleAcceptTags extends Module {


    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'mod_cms_accept_tags';


    /**
     * Display a wildcard in the back end
     *
     * @return string
     */
    public function generate() {

        global $objPage;

        if( TL_MODE == 'BE' ) {

            $objTemplate = new BackendTemplate('be_wildcard');

            $objTemplate->wildcard = '### '.Utf8::strtoupper($GLOBALS['TL_LANG']['FMD']['cms_accept_tags'][0]).' ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

            return $objTemplate->parse();
        }

        if( TL_MODE == 'FE' ) {

            if( !baguru::hasFeature('tag_settings', $objPage->trail[0]) || !baguru::hasFeature('tag'.substr($this->type, 3), $objPage->trail[0]) ) {
                return '';
            }
        }

        return parent::generate();
    }


    /**
     * Generate module
     */
    protected function compile() {

        global $objPage;

        System::loadLanguageFile('cms_default');

        $action = Environment::get('request');
        $action = preg_replace('|_cmsscb=[0-9]+[&]?|', '', $action);
        $this->Template->action = $action;

        $this->Template->formId = 'cms_accept_tags';

        $oTags = TagModel::findBy(['type=?'], ['group'], ['order'=>'sorting ASC']);

        $accepted = [];
        if( !empty(Input::cookie('cms_cookies')) ) {
            $accepted = explode('-', Input::cookie('cms_cookies'));
        }

        $aTags = [];
        if( $oTags ) {

            foreach( $oTags as $key => $value ) {

                $childTags = TagModel::findBy(['pid=? AND active=?'], [$value->id, '1'], ['order'=>'sorting ASC']);

                if( $childTags && $childTags->count() ) {

                    $aChild = $childTags->fetchEach('enable_on_cookie_accept');

                    $showAllAlways = array_reduce(
                        $aChild
                        ,   function(&$carry, $value) {
                                return $carry && $value!=='1';
                            }
                        ,   true
                    );

                    $aTags[] = $value->row() + [
                        'accepted' => in_array($value->id, $accepted)?"1":""
                    ,   'required' => $showAllAlways?"1":""
                    ];
                }
            }

            $aTagIds = $oTags->fetchEach('id');
        }

        if( Input::post('FORM_SUBMIT') && Input::post('FORM_SUBMIT') == $this->Template->formId ) {

            $accepted = [];
            foreach( array_keys($_POST) as $value) {

                if( strpos($value, 'cookie_') === 0 ) {

                    $val = str_replace('cookie_', '', $value);

                    if( \Validator::isNumeric($val) && in_array($val, $aTagIds) ) {
                        $accepted[] = $val;
                    }
                }
            }

            $this->setCookie('cms_cookies', implode('-', $accepted), strtotime('+7 days'));
            $this->setCookie('cms_cookies_saved', "true", strtotime('+7 days'));
            $this->redirect($this->Template->action);
        }

        $this->Template->tags = $aTags;

        $this->Template->acceptLabel = $GLOBALS['TL_LANG']['cms_tag_settings_default']['accept_label'];
        $this->Template->content = $GLOBALS['TL_LANG']['cms_tag_settings_default']['text'];

        if( $this->cms_tag_override_label ) {

            $this->Template->acceptLabel = $this->cms_tag_accept_label;
            $this->Template->content = $this->cms_tag_text;
        }

        $this->Template->acceptLabel = $this->replaceInsertTags($this->Template->acceptLabel);
        $this->Template->content = $this->replaceInsertTags($this->Template->content);

        $GLOBALS['TL_HEAD'][] = '<link rel="stylesheet" href="bundles/marketingsuite/css/cookie-bar.css">';

        $strStyle = $this->generateStyling();

        if( strlen($strStyle) ) {
            $GLOBALS['TL_HEAD'][] = '<style>'.$strStyle.'</style>';
        }

        $this->Template->cmsID = uniqid('cms');
    }


    /**
     * Returns if the current module should be visible in frontend
     *
     * @return boolean
     */
    public function shouldBeShown() {

        $show = false;
        $show = Input::get('_cmsscb') ? true : $forceShow;

        if( !$show ) {

            if( Input::cookie('cms_cookies_saved') !== "true" ) {
                $show = true;
            }
        }

        return $show;
    }


    /**
     * Generates stylesheet for this element
     *
     * @return string
     */
    protected function generateStyling() {

        $strStyle = "";

        $strClass = "mod_".$this->type." form";

        $oStyleSheet = new StyleSheets();

        $mainStyle = [
            'font' => '1'
        ,   'fontcolor' => (string)$this->fontcolor
        ,   'background' => '1'
        ,   'bgcolor' => (string)$this->bgcolor
        ];
        $main = $oStyleSheet->compileDefinition($mainStyle, false, [], [], true);

        $acceptStyle = [
            'font'=>'1'
        ,   'fontcolor'=>(string)$this->acceptfont
        ,   'background'=>'1'
        ,   'bgcolor'=>(string)$this->acceptcolor
        ,   'bgimage' => strlen((string)$this->acceptcolor)?'none':''
        ,   'border'=>'1'
        ,   'borderwidth'=> ['top'=>'0', 'right'=>'0', 'bottom'=>'0', 'left'=>'0', 'unit'=>'']
        ];
        $accept = $oStyleSheet->compileDefinition($acceptStyle, false, [], [], true);

        if( strlen($main) > 20 ) {
            $strStyle .= "." . $strClass . ' ' . trim($main)."\n";
        }

        if( strlen($accept) > 20 ) {
            $strStyle .= "." . $strClass . ' button[name="submit"][value="accept"] ' . trim($accept)."\n";
        }

        return $strStyle;
    }
}
