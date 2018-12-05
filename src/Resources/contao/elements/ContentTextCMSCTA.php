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
namespace numero2\MarketingSuite;


class ContentTextCMSCTA extends \ContentText {


    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'ce_text_cms_cta';


    /**
     * Generate the content element
     */
    public function generate() {

        global $objPage;

        if( TL_MODE == 'FE' ) {

            if( !\numero2\MarketingSuite\Backend\License::hasFeature('ce_'.$this->type, $objPage->trail[0]) ) {
                return '';
            }
        }

        return parent::generate();
    }


    /**
     * Generate the content element
     */
    protected function compile() {

        global $objPage;

        if( TL_MODE == "FE" ) {
            $tracking = new Tracking\ClickAndViews();
            $tracking->increaseViewOnContentElement($this->objModel);
        }

        parent::compile();

        // add CTA data
        $this->Template->ctaTitle = $this->cta_title;
        $this->Template->ctaLink = $this->cta_link;

        if( TL_MODE == "FE" ) {

            if( \Input::get('follow') && \Input::get('follow') == $this->id ) {

                $tracking->increaseClickOnContentElement($this->objModel);
                $this->redirect(\Controller::replaceInsertTags($this->cta_link));
            }

            $this->Template->ctaLink = \Environment::get('request').'?follow='.$this->id;
        }
    }
}
