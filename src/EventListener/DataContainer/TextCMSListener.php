<?php

/**
 * Contao Marketing Suite Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\MarketingSuiteBundle\EventListener\DataContainer;

use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\DataContainer;
use Contao\Input;
use Contao\System;
use numero2\MarketingSuite\Backend;
use numero2\MarketingSuite\Backend\License as luh;
use numero2\MarketingSuite\Content\TextAnalysis;


class TextCMSListener {


    /**
    * Loads text content from field 'text'
    *
    * @param string $value
    * @param Contao\DataContainer $dc
    *
    * @return string
    *
    * @Callback(table="tl_content", target="fields.text_cms.load")
    * @Callback(table="tl_content", target="fields.text_cms_cta.load")
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
    * @param Contao\DataContainer $dc
    *
    * @return null
    *
    * @Callback(table="tl_content", target="fields.text_cms.save")
    * @Callback(table="tl_content", target="fields.text_cms_cta.save")
    */
    public function preventSavingToDB( $value, DataContainer $dc ) {
        return NULL;
    }


    /**
    * Saves the content of our "fake" textfield to field 'text'
    *
    * @param string $value
    * @param Contao\DataContainer $dc
    *
    * @return string
    *
    * @Callback(table="tl_content", target="fields.text.save")
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
     * @param Contao\DataContainer $dc
     *
     * @return string
     *
     * @Callback(table="tl_content", target="fields.text_analysis.input_field")
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

        foreach( $data['misc'] as $key => $value ) {
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
     * @param Contao\DataContainer $dc
     *
     * @Callback(table="tl_content", target="config.onload")
     */
    public function rewriteCustomTemplateCallback( $dc ) {

        $ogCallback = NULL;
        $ogCallback = $GLOBALS['TL_DCA']['tl_content']['fields']['customTpl']['options_callback'];

        if( !$ogCallback || !luh::hasFeature('text_cms') ) {
            return;
        }

        $GLOBALS['TL_DCA']['tl_content']['fields']['customTpl']['options_callback'] = function( $dc ) use ($ogCallback) {

            // 'text_cms' should behave the same like 'text'
            if( $dc->activeRecord->type == 'text_cms' ) {
                $dc->activeRecord->type = 'text';
            }

            // call the original callback function
            if( \is_array($ogCallback) ) {

                $cls = System::importStatic($ogCallback[0]);
                return $cls->{$ogCallback[1]}($dc);

            } elseif( \is_callable($ogCallback) ) {

                return $ogCallback($dc);
            }
        };
    }
}
