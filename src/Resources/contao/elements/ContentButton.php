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

use Contao\DataContainer;
use numero2\MarketingSuite\Helper\ContentElementStyleable as Helper;
use numero2\MarketingSuite\Helper\styleable;
use numero2\MarketingSuite\Helper\StyleSheet;


class ContentButton extends ContentHyperlink implements styleable {


    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'ce_cms_button';

    /**
     * Marker if style preview is enabled
     * @var boolean
     */
    protected $isStylePreview;


    /**
     * Generate the content element
     */
    protected function compile() {

        // set default values for styling preview
        if( $this->isStylePreview ) {

            if( !$this->url && !$this->linkTitle ) {

                $this->url = '#';
                $this->linkTitle = 'Button';
            }

            $this->titleText = $this->titleText?:'Tooltip';
        }

        parent::compile();

        $this->Template->unique = Helper::getUniqueID($this);

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

        // get default styling
        $strStyle = NULL;
        $strStyle = Helper::getDefaultStylesheet($this);

        if( $this->cms_style ) {

            $aStyle = [];
            $aStyle = deserialize($this->cms_style);

            $oStyleSheet = NULL;
            $oStyleSheet = new StyleSheet();

            $uniqueID = Helper::getUniqueID($this);

            // split in normal styling and hover stylings
            $aStyleHover = [];

            if( $aStyle && count($aStyle) ) {

                foreach( $aStyle as $key => $value ) {

                    // text-align won't work, we need justify-content
                    if( $key == 'textalign' ) {

                        if( $value == 'left' ) {
                            $aStyle['own'] .= 'justify-content: flex-start;';
                        } else if( $value == 'right' ) {
                            $aStyle['own'] .= 'justify-content: flex-end;';
                        }

                        continue;
                    }

                    if( in_array($key, ['bgcolor', 'bordercolor', 'fontcolor', 'hover_bgcolor', 'hover_bordercolor', 'hover_fontcolor']) ) {
                        $aStyle[$key] = (string)$value;
                    }

                    if( strpos($key, 'hover_') === 0 ) {
                        $aStyleHover[substr($key, 6)] = $value;
                        unset($aStyle[$key]);
                    }
                }
            }

            if( count($aStyle) ) {

                $aStyle += [
                    'size' => 1,
                    'positioning' => 1,
                    'alignment' => 1,
                    'padding' => 1,
                    'background' => 1,
                    'border' => 1,
                    'font' => 1,
                ];

                $styleDef = $oStyleSheet->generateDefinition($aStyle);

                $strStyle .= '[data-cms-unique="'.$uniqueID.'"]'. $styleDef;
            }

            if( count($aStyleHover) ) {

                $aStyleHover += [
                    'background' => 1,
                    'border' => 1,
                    'font' => 1,
                ];

                $styleHoverDef = $oStyleSheet->generateDefinition($aStyleHover);

                $strStyle .= '[data-cms-unique="'.$uniqueID.'"]:hover'. $styleHoverDef;
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

        ,   'hover_bgcolor' => "hover"
        ,   'hover_bordercolor' => "hover"
        ,   'hover_fontcolor' => "hover"
        ];
    }
}
