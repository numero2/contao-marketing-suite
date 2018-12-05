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


class ContentButton extends ContentHyperlink {


    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'ce_cms_button';


    /**
     * Generate the content element
     */
    protected function compile() {

        parent::compile();

        $this->Template->unique = $this->getUnique();

        if( $this->cms_inline_style ) {

            $strStyle = $this->generateStyle();

            if( strlen($strStyle) ) {
                $GLOBALS['TL_HEAD'][] = '<style>'.$strStyle.'</style>';
            }
        }
    }


    /**
     * Generate the needed style for this button
     *
     * @return string
     */
    public function generateStyle() {

        $strStyle = '';

        if( $this->cms_inline_style ) {

            $aStyle = deserialize($this->cms_style);

            $stylesheet = new \StyleSheets();

            $this->unique = $this->getUnique();

            // split in normal styling and hover stylings
            $aStyleHover = [];

            if( $aStyle && count($aStyle) ) {
                foreach( $aStyle as $key => $value ) {

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

                $styleDef = $stylesheet->compileDefinition($aStyle, false, [], [], true);

                $strStyle .= '[data-unique="'.$this->unique.'"]'. $styleDef;
            }

            if( count($aStyleHover) ) {

                $aStyleHover += [
                    'background' => 1,
                    'border' => 1,
                    'font' => 1,
                ];

                $styleHoverDef = $stylesheet->compileDefinition($aStyleHover, false, [], [], true);

                $strStyle .= '[data-unique="'.$this->unique.'"]:hover'. $styleHoverDef;
            }
        }

        return $strStyle;
    }


    /**
     * Generates a unique hash for this element
     *
     * @return string
     */
    public function getUnique() {

        if( empty($this->unique) ) {

            $this->unique = sha1($this->id);
        }

        return $this->unique;
    }
}
