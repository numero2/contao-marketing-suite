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
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\Module;
use Contao\StyleSheets;
use numero2\MarketingSuite\Backend\License as agoc;
use Patchwork\Utf8;


class ModuleCookieBar extends Module {


    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'mod_cms_cookie_bar';


    /**
     * Display a wildcard in the back end
     *
     * @return string
     */
    public function generate() {

        global $objPage;

        if( TL_MODE == 'BE' ) {

            $objTemplate = new BackendTemplate('be_wildcard');

            $objTemplate->wildcard = '### '.Utf8::strtoupper($GLOBALS['TL_LANG']['FMD']['cms_cookie_bar'][0]).' ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

            return $objTemplate->parse();
        }

        if( TL_MODE == 'FE' ) {

            if( !agoc::hasFeature('tag_settings', $objPage->trail[0]) || !agoc::hasFeature('tag'.substr($this->type, 3), $objPage->trail[0]) ) {
                return '';
            }
        }

        return parent::generate();
    }


    /**
     * Generate module
     */
    protected function compile() {

        $this->loadLanguageFile('cms_default');

        if( !empty($this->customTpl) ) {
            $this->Template = new FrontendTemplate($this->customTpl);
        }

        $action = Environment::get('request');
        $action = preg_replace('|_cmsscb=[0-9]+[&]?|', '', $action);
        $action = preg_replace('|_cmselid=[\w]+[&]?|', '', $action);
        $action = Input::get('_cmselid') ? $action.'#'.Input::get('_cmselid') : $action;

        $this->Template->action = $action;

        $this->Template->formId = 'cms_cookie_bar';

        if( Input::post('FORM_SUBMIT') && Input::post('FORM_SUBMIT') == $this->Template->formId ) {

            if( Input::post('submit') == 'accept' ) {
                $this->setCookie('cms_cookie', 'accept', strtotime('+7 days'));
            } else if( Input::post('submit') == 'reject' ) {
                $this->setCookie('cms_cookie', 'reject', strtotime('+7 days'));
            }

            $this->redirect($this->Template->action);
        }

        $this->Template->content = $this->cms_tag_text;

        $this->Template->acceptLabel = $GLOBALS['TL_LANG']['cms_tag_settings_default']['accept_label'];
        $this->Template->content = $GLOBALS['TL_LANG']['cms_tag_settings_default']['text'];

        if( $this->cms_tag_override_label ) {

            $this->Template->acceptLabel = $this->cms_tag_accept_label;
            $this->Template->content = $this->cms_tag_text;
        }

        $this->Template->rejectLabel = $this->cms_tag_reject_label;

        $this->Template->acceptLabel = $this->replaceInsertTags($this->Template->acceptLabel);
        $this->Template->content = $this->replaceInsertTags($this->Template->content);
        $this->Template->rejectLabel = $this->replaceInsertTags($this->Template->rejectLabel);

        $GLOBALS['TL_HEAD'][] = '<link rel="stylesheet" href="bundles/marketingsuite/css/cookie-bar.css">';

        $strStyle = $this->generateStyling();

        if( strlen($strStyle) ) {
            $GLOBALS['TL_HEAD'][] = '<style>'.$strStyle.'</style>';
        }

        $this->Template->tags = $aTags;
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

            if( !in_array(Input::cookie('cms_cookie'), ['accept','reject']) ) {
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

        $strClass = "mod_".$this->type;

        $oStyleSheet = new StyleSheets();

        $mainStyle = [
            'font' => '1'
        ,   'fontcolor' => (string)$this->fontcolor
        ,   'background' => '1'
        ,   'bgcolor' => (string)$this->bgcolor
        ];
        $main = $oStyleSheet->compileDefinition($mainStyle, false, [], [], true);

        $acceptStyle = [
            'font' => '1'
        ,   'fontcolor' => (string)$this->acceptfont
        ,   'background' => '1'
        ,   'bgcolor' => (string)$this->acceptcolor
        ,   'bgimage' => strlen((string)$this->acceptcolor)?'none':''
        ,   'border' => '1'
        ,   'borderwidth' => ['top'=>'0', 'right'=>'0', 'bottom'=>'0', 'left'=>'0', 'unit'=>'']
        ];
        $accept = $oStyleSheet->compileDefinition($acceptStyle, false, [], [], true);

        $rejectStyle = [
            'font' => '1'
        ,   'fontcolor' => (string)$this->rejectfont
        ,   'background' => '1'
        ,   'bgcolor' => (string)$this->rejectcolor
        ,   'bgimage' => strlen((string)$this->rejectcolor)?'none':''
        ,   'border' => '1'
        ,   'borderwidth' => ['top'=>'0', 'right'=>'0', 'bottom'=>'0', 'left'=>'0', 'unit'=>'']
        ];
        $reject = $oStyleSheet->compileDefinition($rejectStyle, false, [], [], true);

        if( strlen($main) > 20 ) {
            $strStyle .= "." . $strClass . ' ' . trim($main) . "\n";
        }

        if( strlen($accept) > 20 ) {
            $strStyle .= "." . $strClass . ' button[name="submit"][value="accept"] ' . trim($accept) . "\n";
        }

        if( strlen($reject) > 20 ) {
            $strStyle .= "." . $strClass . ' button[name="submit"][value="reject"] ' . trim($reject) . "\n";
        }

        return $strStyle;
    }
}
