<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2022 Leo Feyer
 *
 * @package   Contao Marketing Suite
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright 2022 numero2 - Agentur für digitales Marketing
 */


namespace numero2\MarketingSuite;

use Contao\ContentElement;
use Contao\Controller;
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\StringUtil;
use numero2\MarketingSuite\Backend\License as jlkshgf;
use numero2\MarketingSuite\Helper\ContentElementStyleable as Helper;
use numero2\MarketingSuite\Helper\InterfaceStyleable;
use numero2\MarketingSuite\Helper\TraitContentElementStyleable;
use numero2\MarketingSuite\Tracking\ClickAndViews;
use numero2\MarketingSuite\Tracking\Session;


class ContentOverlay extends ContentElement implements InterfaceStyleable {


    use TraitContentElementStyleable;


    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'ce_cms_overlay';

    /**
     * Marker if style preview is enabled
     * @var boolean
     */
    public $isStylePreview = false;


    /**
    * Generate the content element
    */
    public function generate() {

        global $objPage;

        if( strlen($this->cms_layout_option) ) {
            $this->strTemplate .= '_'.$this->cms_layout_option;
        }

        if( TL_MODE == 'FE' ) {

            if( !jlkshgf::hasFeature('ce_'.$this->type, $objPage->trail[0]) ) {
                return '';
            }

            $session = new Session();
            if( $session->getOverlayClosed($this->id, $this->tstamp) ) {
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

        $tracking = new ClickAndViews();
        $session = new Session();

        // append choosen layout as class
        if( $this->cms_layout_option ) {
            $this->cssID = [ $this->cssID[0], $this->cssID[1] . ($this->cssID[1] ? ' ' : '') . $this->cms_layout_option . (TL_MODE=='FE'?' hidden':'')];
        }

        $aLifetime = $this->cms_lifetime;
        $aLifetime = !is_array($aLifetime)?StringUtil::deserialize($aLifetime):$aLifetime;
        $iExpires = 0;
        if( !empty($aLifetime['value']) ) {
            $iExpires = strtotime('+'.(int)$aLifetime['value'].' '.$aLifetime['unit']);
        } else {
            $iExpires = strtotime('+10 years');
        }

        $this->Template->expires = $iExpires;
        $this->Template->layout = $this->cms_layout_selector;
        $this->Template->isPreview = $this->isStylePreview;

        if( TL_MODE == "FE" ) {

            $this->Template->close = Controller::addToUrl('&close='.$this->id, false);
            $this->Template->close = version_compare(VERSION, '4.10', '<') ? ampersand($this->Template->close,false) : StringUtil::ampersand($this->Template->close,false);

            if( Input::get('close') && Input::get('close') == $this->id ) {

                $session->storeOverlayClosed($this->id, $this->tstamp, $iExpires);
                $this->redirect($objPage->getFrontendUrl());
            }

            // "view" will be triggered via ajax since we store in localstorage if the overlay
            // was already shown or not
            $this->Template->view = StringUtil::decodeEntities(Controller::addToUrl('&view='.$this->id, false));
            $this->Template->view = version_compare(VERSION, '4.10', '<') ? ampersand($this->Template->view,false) : StringUtil::ampersand($this->Template->view,false);

            if( Input::get('view') && Input::get('view') == $this->id ) {

                // make sure to force the view, otherwise our xhr request would not be counted
                $tracking->increaseViewOnContentElement($this->objModel, true);

                $this->redirect($objPage->getFrontendUrl());
            }
        }

        if( !$this->isStylePreview ) {

            $this->Template->cmsID = Helper::getUniqueID($this);

            $this->injectStylesheet();

            // render javascript
            $oScript = new FrontendTemplate('scripts/script_ce_cms_overlay_'.$this->cms_layout_option);
            $oScript->setData($this->Template->getData());

            $this->Template->script = $oScript->parse();
        }
    }
}
