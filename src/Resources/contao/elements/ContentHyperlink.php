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

use Contao\ContentElement;
use Contao\Controller;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use numero2\MarketingSuite\Backend\License as rakfib;


class ContentHyperlink extends ContentElement {


    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'ce_cms_hyperlink';


    /**
     * Generate the content element
     */
    public function generate() {

        global $objPage;

        $request = System::getContainer()->get('request_stack')->getCurrentRequest();
        if( $request && System::getContainer()->get('contao.routing.scope_matcher')->isFrontendRequest($request) ) {

            if( !rakfib::hasFeature('ce_'.$this->type, $objPage->trail[0]) ) {
                return '';
            }
        }

        return parent::generate();
    }


    /**
     * Generate the content element
     */
    protected function compile() {

        $tracking = System::getContainer()->get('marketing_suite.tracking.click_and_views');

        $request = System::getContainer()->get('request_stack')->getCurrentRequest();
        if( $request && System::getContainer()->get('contao.routing.scope_matcher')->isFrontendRequest($request) ) {
            $tracking->increaseViewOnContentElement($this->objModel);
        }

        if( substr($this->url, 0, 7) == 'mailto:' ) {
            $this->url = StringUtil::encodeEmail($this->url);
        } else {
            $this->url = StringUtil::ampersand($this->url);
        }

        if( $this->linkTitle == '' ) {
            $this->linkTitle = $this->url;
        }

        $this->Template->href = $this->url;
        $this->Template->link = $this->linkTitle;

        if( $this->titleText ) {
            $this->Template->linkTitle = StringUtil::specialchars($this->titleText);
        }

        $this->Template->target = '';
        if( $this->target ) {
            $this->Template->target = ' target="_blank"';
        }


        if( $request && System::getContainer()->get('contao.routing.scope_matcher')->isFrontendRequest($request) ) {

            if( Input::get('follow') && Input::get('follow') == $this->id ) {

                $tracking->increaseClickOnContentElement($this->objModel);

                $insertTagParser = System::getContainer()->get('contao.insert_tag.parser');
                $this->redirect($insertTagParser->replace($this->url));
            }

            $this->Template->href = Controller::addToUrl('&follow='.$this->id, false);
        }
    }
}
