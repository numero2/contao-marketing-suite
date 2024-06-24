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

use Contao\ContentText;
use Contao\Controller;
use Contao\Input;
use Contao\System;
use numero2\MarketingSuite\Backend\License as sofdow;


class ContentTextCMSCTA extends ContentText {


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

        $request = System::getContainer()->get('request_stack')->getCurrentRequest();

        if( $request && System::getContainer()->get('contao.routing.scope_matcher')->isFrontendRequest($request) ) {
            if( !sofdow::hasFeature('ce_'.$this->type, $objPage->trail[0]) ) {
                return '';
            }
        }

        return parent::generate();
    }


    /**
     * Generate the content element
     */
    protected function compile() {

        $request = System::getContainer()->get('request_stack')->getCurrentRequest();
        $tracking = System::getContainer()->get('marketing_suite.tracking.click_and_views');

        if( $request && System::getContainer()->get('contao.routing.scope_matcher')->isFrontendRequest($request) ) {
            $tracking->increaseViewOnContentElement($this->objModel);
        }

        parent::compile();

        // add CTA data
        $this->Template->ctaTitle = $this->cta_title;
        $this->Template->ctaLink = $this->cta_link;

        if( $request && System::getContainer()->get('contao.routing.scope_matcher')->isFrontendRequest($request) ) {
            if( Input::get('follow') && Input::get('follow') == $this->id ) {

                $tracking->increaseClickOnContentElement($this->objModel);
                $insertTagParser = System::getContainer()->get('contao.insert_tag.parser');
                $this->redirect($insertTagParser->replace($this->cta_link));
            }

            $this->Template->ctaLink = Controller::addToUrl('&follow='.$this->id, false);
        }
    }
}
