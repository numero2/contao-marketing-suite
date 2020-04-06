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
use Contao\StyleSheets;
use Contao\Input;
use Contao\Controller;
use numero2\MarketingSuite\Backend\License as jlkshgf;
use numero2\MarketingSuite\Helper\ContentElementStyleable as Helper;
use numero2\MarketingSuite\Helper\styleable;
use numero2\MarketingSuite\Tracking\ClickAndViews;
use numero2\MarketingSuite\Tracking\Session;


class ContentOverlay extends ContentElement implements styleable {


    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'ce_cms_overlay';

    /**
     * Marker if style preview is enabled
     * @var boolean
     */
    protected $isStylePreview;


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

        if( TL_MODE == "FE" ) {
            $tracking->increaseViewOnContentElement($this->objModel);
        }

        // set default values for styling preview
        if( $this->isStylePreview ) {

            if( !$this->url && !$this->linkTitle ) {

                $this->url = '#';
                $this->linkTitle = 'Button';
            }

            $this->titleText = $this->titleText?:'Tooltip';
        }

        $this->Template->unique = Helper::getUniqueID($this);


        // append choosen layout as class
        if( $this->cms_layout_option ) {
            $this->cssID = [ $this->cssID[0], $this->cssID[1] . ($this->cssID[1] ? ' ' : '') . $this->cms_layout_option ];
        }

        if( TL_MODE == "FE" ) {

            if( Input::get('close') && Input::get('close') == $this->id ) {
                $aLifetime = $this->cms_lifetime;
                $aLifetime = !is_array($aLifetime)?deserialize($aLifetime):$aLifetime;
                $iExpires = 0;
                if( !empty($aLifetime['value']) ) {
                    $iExpires = strtotime('+'.(int)$aLifetime['value'].' '.$aLifetime['unit']);
                } else {
                    $iExpires = strtotime('+10 years');
                }

                $session->storeOverlayClosed($this->id, $this->tstamp, $iExpires);

                $this->redirect($objPage->getFrontendUrl());
            }

            $this->Template->close = Controller::addToUrl('&close='.$this->id, false);
        }

        $strStyle = $this->generateStylesheet();

        if( strlen($strStyle) ) {
            $GLOBALS['TL_HEAD'][] = '<style>'.$strStyle.'</style>';
        }
    }


    /**
     * @inheritdoc
     */
    public function generateStylesheet() {

        if( !$this->cms_element_style ) {
            return;
        }

        $GLOBALS['TL_HEAD'][] = '<link rel="stylesheet" href="bundles/marketingsuite/css/ce_overlay.css">';

        if( $this->cms_style ) {

            $aStyle = [];
            $aStyle = deserialize($this->cms_style);

            if( count($aStyle) ) {

                $oStyleSheet = NULL;
                $oStyleSheet = new StyleSheets();

                $uniqueID = Helper::getUniqueID($this);

                $strStyle = "";

                // common styles
                $strStyle .= $oStyleSheet->compileDefinition([
                    'selector' => '.ce_cms_overlay > div[data-cms-unique="'.$uniqueID.'"]'
                ,   'border' => 1
                ,   'background' => 1
                ,   'font' => 1
                ,   'alignment' => 1
                ]+$aStyle, false, [], [], true);

                // "close" button
                if( !empty($aStyle['bordercolor']) ) {

                    $strStyle .= $oStyleSheet->compileDefinition([
                        'selector' => '.ce_cms_overlay > div[data-cms-unique="'.$uniqueID.'"] .close > span'
                    ,   'background' => 1
                    ,   'bgcolor' => $aStyle['bordercolor']
                    ], false, [], [], true);
                }
            }

            if( !empty($aStyle['cms_element_style_custom']) ) {
                $strStyle .= $aStyle['cms_element_style_custom'];
            }
        }

        return $strStyle;
    }


    /**
     * @inheritdoc
     */
    public function setStylePreview( $active=true ) {

        $this->isStylePreview = $active;
    }


    /**
    * @inheritdoc
    */
    public static function getStyleFieldsConfig( $dc ) {

        switch( $dc->activeRecord->cms_layout_option ) {

            case 'modal_overlay':
                return [
                    'bgcolor' => 'start background-border'
                ,   'borderwidth' => 'background-border'
                ,   'borderstyle' => 'background-border'
                ,   'bordercolor' => 'start background-border'
                ,   'borderradius' => 'background-border'

                ,   'textalign' => 'text-font'
                ,   'fontsize' => 'text-font'
                ,   'fontcolor' => 'text-font start'
                ,   'lineheight' => 'text-font'
                ];
                break;
        }

        return [];
        /*
        return [
            'width' => "start sizes"
        ,   'height' => "start sizes"
        ,   'margin' => "sizes"
        ,   'padding' => "sizes"

        ,   'bgcolor' => "background-border start"
        ,   'borderwidth' => "background-border"
        ,   'borderstyle' => "background-border"
        ,   'bordercolor' => "background-border"
        ,   'borderradius' => "background-border"

        ,   'textalign' => "text-font"
        ,   'fontsize' => "text-font"
        ,   'fontcolor' => "text-font start"
        ,   'lineheight' => "text-font"
        ,   'letterspacing' => "text-font"
        ];
         */
    }
}
