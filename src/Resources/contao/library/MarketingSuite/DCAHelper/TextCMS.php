<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2019 Leo Feyer
 *
 * @package   Contao Marketing Suite
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright 2020 numero2 - Agentur für digitales Marketing
 */


namespace numero2\MarketingSuite\DCAHelper;

use Contao\Backend as CoreBackend;
use Contao\Controller;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Input;
use numero2\MarketingSuite\Backend;
use numero2\MarketingSuite\Backend\License as luh;
use numero2\MarketingSuite\Content\TextAnalysis;


class TextCMS extends CoreBackend {


    /**
    * Loads text content from field 'text'
    *
    * @param string $value
    * @param \DataContainer $dc
    *
    * @return string
    */
    public function loadContentFromOriginalField( $value, DataContainer $dc ) {

        // sets original text field hidden, we only need it for the versioning to work
        $GLOBALS['TL_DCA']['tl_content']['fields']['text']['eval']['tl_class'] = 'hidden';
        unset($GLOBALS['TL_DCA']['tl_content']['fields']['text']['eval']['mandatory']);
        unset($GLOBALS['TL_DCA']['tl_content']['fields']['text']['eval']['helpwizard']);
        unset($GLOBALS['TL_DCA']['tl_content']['fields']['text']['eval']['rte']);

        return $dc->activeRecord->text;
    }


    /**
    * Prevents writing of our "fake" textfield to DB
    *
    * @param string $value
    * @param \DataContainer $dc
    *
    * @return null
    */
    public function preventSavingToDB( $value, DataContainer $dc ) {
        return NULL;
    }


    /**
    * Saves the content of our "fake" textfield to field 'text'
    *
    * @param string $value
    * @param \DataContainer $dc
    *
    * @return string
    */
    public function saveContentToOriginalField( $value, DataContainer $dc ) {

        if( array_key_exists('text_cms', $_POST) ) {
            if( $dc->activeRecord->type == 'text_cms' ) {
                return Input::postUnsafeRaw('text_cms');
            }
        }

        if( array_key_exists('text_cms_cta', $_POST) ) {
            if( $dc->activeRecord->type == 'text_cms_cta' ) {
                return Input::postUnsafeRaw('text_cms_cta');
            }
        }

        return $value;
    }


    /**
     * Renders a view for some statical analyses of the text
     *
     * @param \DataContainer $dc
     *
     * @return string
     */
    public function generateInputField( DataContainer $dc ) {

        $text = NULL;
        $text = $dc->activeRecord->text;

        $oAnalysis = NULL;
        $oAnalysis = new TextAnalysis($text);

        $data = [
            'syllables' => luh::hasFeature('text_analysis_syllables')?$oAnalysis->syllables:null
        ,   'stats'     => luh::hasFeature('text_analysis_stats')?$oAnalysis->stats:null
        ,   'sentences' => luh::hasFeature('text_analysis_sentences')?$oAnalysis->sentences:null
        ,   'misc'      => [
                'flesch' => luh::hasFeature('text_analysis_flesch')?$oAnalysis->flesch:null
            ]
        ];

        foreach( $data['misc'] as $key => $value) {
            if( $value === null ) {
                unset($data['misc'][$key]);
            }
        }

        $hasData = false;
        foreach( $data as $key => $value) {
            if( $value ) {
                $hasData = true;
                break;
            }
        }

        if( !$hasData ) {
            return '';
        }

        $data['heading'] = $GLOBALS['TL_LANG']['tl_content']['text_analysis_heading'];
        $data['labels'] = $GLOBALS['TL_LANG']['tl_content']['text_analysis_labels'];

        return Backend::parseWithTemplate('backend/widgets/textAnalysis', $data);
    }


    /**
     * Make sure we call the original options_callback when modifying the
     * customTpl field
     *
     * @param DataContainer $dc
     */
    public function rewriteCustomTemplateCallback($dc) {

        $ogCallback = NULL;
        $ogCallback = $GLOBALS['TL_DCA']['tl_content']['fields']['customTpl']['options_callback'];

        if( !$ogCallback ) {
            return;
        }

        $GLOBALS['TL_DCA']['tl_content']['fields']['customTpl']['options_callback'] = function($dc) use ($ogCallback) {

            // 'text_cms' should behave the same like 'text'
            if( $dc->activeRecord->type == 'text_cms' ) {
                $dc->activeRecord->type = 'text';
            }

            // call the original callback function
            if( \is_array($ogCallback) ) {

                $this->import($ogCallback[0]);
                return $this->{$ogCallback[0]}->{$ogCallback[1]}($dc);

            } elseif( \is_callable($ogCallback) ) {

                return $ogCallback($dc);
            }
        };
    }
}
