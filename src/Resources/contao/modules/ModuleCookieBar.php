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


namespace numero2\MarketingSuite;

use Contao\BackendTemplate;
use Contao\Environment;
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use numero2\MarketingSuite\Backend\License as agoc;
use numero2\MarketingSuite\Helper\Domain;
use numero2\MarketingSuite\Helper\StyleSheet;
use Patchwork\Utf8;


class ModuleCookieBar extends ModuleEUConsent {


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

        return parent::generate();
    }


    /**
     * Handles the data submitted by the consent form
     */
    protected function handleFormData() {

        $action = Environment::get('request');
        $action = preg_replace('|_cmsscb=[0-9]+[&]?|', '', $action);
        $action = preg_replace('|_cmselid=[\w]+[&]?|', '', $action);
        $action = Input::get('_cmselid') ? $action.'#'.Input::get('_cmselid') : $action;

        $this->formAction = $action;

        if( Input::post('FORM_SUBMIT') && Input::post('FORM_SUBMIT') == $this->type ) {

            $iCookieExpires = strtotime('+7 days');

            // get configured cookie lifetime
            if( agoc::hasFeature('tags_cookie_lifetime') ) {

                $aCookieConfig = [];
                $aCookieConfig = $this->cms_tag_cookie_lifetime;
                $aCookieConfig = !is_array($aCookieConfig)?StringUtil::deserialize($aCookieConfig):$aCookieConfig;

                if( (int)$aCookieConfig['value'] ) {
                    $iCookieExpires = strtotime('+'.(int)$aCookieConfig['value'].' '.$aCookieConfig['unit']);
                }
            }

            // DEPRECATED as of 1.0.21
            if( Input::post('submit') ) {
                Input::setPost('choice', Input::post('submit'));
                @trigger_error('The use of the field name "submit" in your '.$this->strTemplate.' template has been deprecated and will no longer work in Marketing Suite 2.0. Please create a new copy using the current version.', E_USER_DEPRECATED);
            }

            // store decision in cookie
            if( in_array(Input::post('choice'), ['accept','reject']) ) {

                $sDomain = NULL;

                // set cookies for all subdomains
                if( $this->cms_tag_accept_subdomains ) {

                    global $objPage;

                    $objRootPage = NULL;
                    $objRootPage = PageModel::findById($objPage->rootId);

                    $sDomain = $oRootPage->dns?:Environment::get('host');
                    $sDomain= Domain::getRegisterableDomain($sDomain);
                }

                $this->setCookie('cms_cookie', Input::post('choice'), $iCookieExpires, '', $sDomain);
            }

            $this->redirect($this->formAction);
        }
    }


    /**
     * Determines if the module should be visible
     *
     * @return boolean
     */
    protected function shouldBeShown() {

        $show = parent::shouldBeShown();

        // check if cookies not already set
        if( $show ) {

            if( in_array(Input::cookie('cms_cookie'), ['accept','reject']) ) {
                $show = false;
            }
        }

        // check if forced to show up
        if( !$show ) {
            $show = Input::get('_cmsscb') ? true : $show;
        }

        return $show;
    }


    /**
     * Generate module
     */
    protected function compile() {

        $this->loadLanguageFile('cms_default');

        if( !empty($this->customTpl) ) {
            $this->Template = new FrontendTemplate($this->customTpl);
        }

        $this->Template->action = $this->formAction;
        $this->Template->formId = $this->type;

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

        // generate default styling if enabled
        if( $this->cms_tag_set_style ) {
            $this->generateStyling();
        }

        parent::compile();
    }


    /**
     * Generates stylesheet for this module
     */
    protected function generateStyling() {

        $GLOBALS['TL_HEAD'][] = '<link rel="stylesheet" href="bundles/marketingsuite/css/cookie-bar.css">';

        $strStyle = "";

        $strClass = "mod_".$this->type;

        $oStyleSheet = null;
        $oStyleSheet = new StyleSheet();

        $mainStyle = [
            'font' => '1'
        ,   'fontcolor' => (string)$this->cms_tag_font_color
        ,   'background' => '1'
        ,   'bgcolor' => (string)$this->cms_tag_background_color
        ];

        $main = $oStyleSheet->generateDefinition($mainStyle);

        if( strlen($main) > 20 ) {
            $strStyle .= "." . $strClass . ' ' . trim($main) . "\n";
        }

        $acceptStyle = [
            'font' => '1'
        ,   'fontcolor' => (string)$this->cms_tag_accept_font
        ,   'background' => '1'
        ,   'bgcolor' => (string)$this->cms_tag_accept_background
        ,   'bgimage' => strlen((string)$this->cms_tag_accept_background)?'none':''
        ,   'border' => '1'
        ,   'borderwidth' => ['top'=>'0', 'right'=>'0', 'bottom'=>'0', 'left'=>'0', 'unit'=>'']
        ];

        $accept = $oStyleSheet->generateDefinition($acceptStyle);

        if( strlen($accept) > 20 ) {
            $strStyle .= "." . $strClass . ' button[type="submit"][value="accept"] ' . trim($accept) . "\n";
        }

        $rejectStyle = [
            'font' => '1'
        ,   'fontcolor' => (string)$this->cms_tag_reject_font
        ,   'background' => '1'
        ,   'bgcolor' => (string)$this->cms_tag_reject_background
        ,   'bgimage' => strlen((string)$this->cms_tag_reject_background)?'none':''
        ,   'border' => '1'
        ,   'borderwidth' => ['top'=>'0', 'right'=>'0', 'bottom'=>'0', 'left'=>'0', 'unit'=>'']
        ];

        $reject = $oStyleSheet->generateDefinition($rejectStyle);

        if( strlen($reject) > 20 ) {
            $strStyle .= "." . $strClass . ' button[type="submit"][value="reject"] ' . trim($reject) . "\n";
        }

        if( strlen($strStyle) ) {
            $GLOBALS['TL_HEAD'][] = '<style>'.$strStyle.'</style>';
        }
    }
}
