<?php

/**
 * Contao Marketing Suite Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
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

        $scopeMatcher = System::getContainer()->get('contao.routing.scope_matcher');
        $requestStack = System::getContainer()->get('request_stack');

        if( $scopeMatcher->isBackendRequest($requestStack->getCurrentRequest()) ) {

            $objTemplate = new BackendTemplate('be_wildcard');

            $objTemplate->wildcard = '### '.$GLOBALS['TL_LANG']['FMD']['cms_cookie_bar'][0].' ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = System::getContainer()->get('router')->generate('contao_backend') . '?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

            return $objTemplate->parse();
        }

        return parent::generate();
    }


    /**
     * Handles the data submitted by the consent form
     */
    protected function handleFormData() {

        global $objPage;

        $action = Environment::get('uri');
        $action = preg_replace('|_cmsscb=[0-9]+[&]?|', '', $action);
        $action = preg_replace('|_cmselid=[\w]+[&]?|', '', $action);
        $action = Input::get('_cmselid') ? $action.'#'.Input::get('_cmselid') : $action;

        $this->formAction = $action;

        if( Input::post('FORM_SUBMIT') && Input::post('FORM_SUBMIT') == $this->type ) {

            $iCookieExpires = strtotime('+7 days');

            // get configured cookie lifetime
            if( agoc::hasFeature('tags_cookie_lifetime', $objPage->trail[0]) ) {

                $aCookieConfig = [];
                $aCookieConfig = $this->cms_tag_cookie_lifetime;
                $aCookieConfig = !is_array($aCookieConfig)?StringUtil::deserialize($aCookieConfig):$aCookieConfig;

                if( (int)$aCookieConfig['value'] ) {
                    $iCookieExpires = strtotime('+'.(int)$aCookieConfig['value'].' '.$aCookieConfig['unit']);
                }
            }

            // store decision in cookie
            if( in_array(Input::post('choice'), ['accept', 'reject']) ) {

                $sDomain = null;

                // set cookies for all subdomains
                if( $this->cms_tag_accept_subdomains ) {

                    global $objPage;

                    $objRootPage = null;
                    $objRootPage = PageModel::findById($objPage->rootId);

                    $sDomain = $objRootPage->dns?:Environment::get('host');
                    $sDomain = Domain::getRegisterableDomain($sDomain);
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
    protected function shouldBeShown(): bool {

        $show = parent::shouldBeShown();

        // check if cookies not already set
        if( $show ) {

            if( in_array(Input::cookie('cms_cookie'), ['accept', 'reject']) ) {
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

        $this->Template->rejectLabel = $this->cms_tag_reject_label ?? '';

        $insertTagParser = System::getContainer()->get('contao.insert_tag.parser');

        $this->Template->acceptLabel = $insertTagParser->replace($this->Template->acceptLabel);
        $this->Template->content = $insertTagParser->replace($this->Template->content);
        $this->Template->rejectLabel = $insertTagParser->replace($this->Template->rejectLabel);

        parent::compile();
    }
}
