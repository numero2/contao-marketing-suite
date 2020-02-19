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


namespace numero2\MarketingSuite;

use Contao\ContentElement;
use Contao\Controller;
use Contao\Environment;
use Contao\Input;
use Contao\StringUtil;
use numero2\MarketingSuite\Backend\License as rakfib;
use numero2\MarketingSuite\Tracking\ClickAndViews;


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

        if( TL_MODE == 'FE' ) {

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

        $tracking = new ClickAndViews();

        if( TL_MODE == "FE" ) {
            $tracking->increaseViewOnContentElement($this->objModel);
        }

        if( substr($this->url, 0, 7) == 'mailto:' ) {
            $this->url = StringUtil::encodeEmail($this->url);
        } else {
            $this->url = ampersand($this->url);
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

        if( TL_MODE == "FE" ) {

            if( Input::get('follow') && Input::get('follow') == $this->id ) {

                $tracking->increaseClickOnContentElement($this->objModel);
                $this->redirect(Controller::replaceInsertTags($this->url));
            }

            $this->Template->href = Controller::addToUrl('&follow='.$this->id, false);
        }
    }
}
