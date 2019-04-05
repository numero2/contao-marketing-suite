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


namespace numero2\MarketingSuite\Backend;

use Contao\Controller;
use Contao\DataContainer;
use Contao\Input;
use Contao\StringUtil;


class Wizard extends Controller {


    /**
     * Constructor
     */
    public function __construct() {

        self::loadLanguageFile('cms_be_wizard');

        // while we are using the wizard we disable all buttons with "save and ..."
        Input::setGet('nb', 1);
    }


    /**
     * Generates the top helper for use in input_field_callbacks
     *
     * @param \DataContainer $dc
     *
     * @return string
     */
    public function generateTopForInputField( DataContainer $dc ) {

        $aDCA = $GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field];

        if( empty($aDCA['type']) || $aDCA['type'] == 'default' ) {
            $aDCA['type'] = $dc->table;
        }

        $lngMSC = $GLOBALS['TL_LANG']['cms_be_wizard']['MSC'];

        if( empty($aDCA['type']) || empty($aDCA['step']) ) {
            return '';
        }

        $lng = $GLOBALS['TL_LANG']['cms_be_wizard'][$aDCA['type']]['step_'.$aDCA['step'].'_top'];

        if( !$lng || empty($aDCA['step']) ) {
            return '';
        }

        if( !empty($aDCA['show_popup']) ) {
            // TODO $this->showPopup($aDCA['show_popup'], $aDCA);
        }

        $str = '<div class="helper top">';
        $str .= '<h2>'.(!empty($lng['headline'])?$lng['headline']:sprintf($lngMSC['step'], $aDCA['step'])).'</h2>';

        if( !empty($lng[0]) ) {
            $str .= '<p>'.$lng[0].'</p>';
        }
        if( !empty($lng['help']) ) {
            $str .= '<p class="tl_help tl_tip" title="">'.$lng['help'].'</p>';
        }
        $str .= '</div>';

        return $str;
    }


    /**
     * Generates the top helper for use in listings
     *
     * @param array $aConfig
     *
     * @return string
     */
    public function generateTopForListing( $aConfig ) {

        $aDCA = $aConfig;

        $lngMSC = $GLOBALS['TL_LANG']['cms_be_wizard']['MSC'];

        if( empty($aDCA['type']) || empty($aDCA['step']) ) {
            return '';
        }

        $lng = $GLOBALS['TL_LANG']['cms_be_wizard'][$aDCA['type']]['step_'.$aDCA['step'].'_top'];

        if( !$lng ) {
            return '';
        }

        $str = '<div class="tl_header cms_helper_top_legend">';
        $str .= '<h2>'.(!empty($lng['headline'])?$lng['headline']:sprintf($lngMSC['step'], $aDCA['step'])).'</h2>';

        if( !empty($lng[0]) ) {
            $str .= '<p>'.$lng[0].'</p>';
        }
        if( !empty($lng['help']) ) {
            $str .= '<p class="tl_help tl_tip" title="">'.$lng['help'].'</p>';
        }
        $str .= '</div>';

        return $str;
    }


    /**
     * Generates the bottom helper for use in input_field_callbacks
     *
     * @param \DataContainer $dc
     *
     * @return string
     */
    public function generateBottomForInputField( DataContainer $dc ) {

        $aDCA = $GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field];

        if( empty($aDCA['type']) || $aDCA['type'] == 'default' ) {

            $aDCA['type'] = $dc->table;
        }

        $lngMSC = $GLOBALS['TL_LANG']['cms_be_wizard']['MSC'];

        if( empty($aDCA['type']) || empty($aDCA['step']) ) {
            return '';
        }

        $lng = $GLOBALS['TL_LANG']['cms_be_wizard'][$aDCA['type']]['step_'.$aDCA['step'].'_bottom'];

        if( !$lng || empty($aDCA['step']) ) {
            return '';
        }

        $str = '<div class="helper top">';

        if( !empty($lng[0]) ) {
            $str .= '<p>'.$lng[0].'</p>';
        }
        if( !empty($lng['help']) ) {
            $str .= '<p class="tl_help tl_tip" title="">'.$lng['help'].'</p>';
        }
        $str .= '</div>';

        return $str;
    }


    /**
     * Overwrites all buttons with one continue button
     *
     * @param array $aButtons
     *
     * @return array
     */
    public function overrideButtonsWithContinue( $aButtons ) {

        if( $aButtons ) {

            foreach( $aButtons as $key => $value ) {

                if( $key === 'save' ) {
                    $aButtons[$key] = str_replace('>'.$GLOBALS['TL_LANG']['MSC']['save'], '>'.$GLOBALS['TL_LANG']['MSC']['continue'], $value);
                } else {
                    unset($aButtons[$key]);
                }
            }
        }

        return $aButtons;
    }


    /**
     * Overwrites all buttons with one continue button
     *
     * @param array $aButtons
     *
     * @return array
     */
    public function addFinishButton( $aButtons ) {

        if( $aButtons ) {

            foreach( $aButtons as $key => $value ) {

                if( $key === 'save' ) {
                } else {
                    unset($aButtons[$key]);
                }
            }

            $aButtons['saveNclose'] = '<button type="submit" name="saveNclose" id="saveNclose" class="tl_submit" accesskey="c">'.$GLOBALS['TL_LANG']['MSC']['finish'].'</button>';
        }

        return $aButtons;
    }


    /**
     * Adds a modal window to the current backend page that will be open
     *
     * @param string $strTemplate
     * @param array $arrQueryData
     */
    public function showPopup( $strTemplate, $arrData ) {

        $title = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['wizard_popup_title']);

        $aQueryData = [
            'type' => $arrData['type']
        ,   'step' => $arrData['step']
        ];

        if( \System::getContainer()->get('request_stack')->getCurrentRequest()->getMethod() == "GET" ) {

            $GLOBALS['TL_MOOTOOLS'][] = "<script>Backend.openModalIframe({'title':'$title','url':'" . $this->generatePopupUrl($strTemplate, $aQueryData). "'});</script>";
        }
    }


    /**
     * generates the url for the popup with the given data
     *
     * @param string $strTemplate
     * @param array $arrQueryData
     *
     * @return string
     */
    public function generatePopupUrl( $strTemplate, $arrQueryData=[] ) {

        return TL_PATH.'/contao/cms_wizard_popup?do='.urlencode($strTemplate).'&'.http_build_query($arrQueryData);
    }
}
