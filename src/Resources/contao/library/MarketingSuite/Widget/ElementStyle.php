<?php

/**
 * Contao Marketing Suite Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\MarketingSuite\Widget;

use Contao\Backend as CoreBackend;
use Contao\ContentElement;
use Contao\ContentModel;
use Contao\Controller;
use Contao\DataContainer;
use Contao\Input;
use Contao\Model;
use Contao\StringUtil;
use Contao\System;
use Exception;
use numero2\MarketingSuite\Backend;
use numero2\MarketingSuite\Helper\InterfaceStyleable;


class ElementStyle extends CoreBackend {


    /**
     * Renders a container for a live preview of the element
     *
     * @param Contao\DataContainer $dc
     * @param array $aData Array containing fields to be overwritten for the preview
     *
     * @return string
     */
    public function generatePreview( $dc, $aData=[] ) {

        $sElementClass = null;
        $oModel = null;

        // find the correct element class
        switch( $dc->table ) {

            case 'tl_content':

                foreach( $GLOBALS['TL_CTE'] as $group => $elements ) {

                    if( array_key_exists($dc->activeRecord->type, $elements) ) {
                        $sElementClass = $elements[$dc->activeRecord->type];
                        $oModel = ContentModel::findById( $dc->activeRecord->id );
                        break;
                    }
                }

            break;
        }

        if( !$sElementClass || !$oModel ) {
            return;
        }

        $oModel->preventSaving(true);

        // overwrite some fields in the model to update preview
        if( !empty($aData) ) {

            $this->addStylingFields($dc);

            $aStyle = [];

            foreach( $GLOBALS['TL_DCA'][$dc->table]['fields'] as $name => $arrData ) {

                // only check available fields with an inputType
                if( !array_key_exists($name, $aData) || !array_key_exists('inputType', $arrData) ) {
                    continue;
                }

                // prepare the value
                $varValue = StringUtil::deserialize($aData[$name]);

                // some values need to be serialized
                if( $arrData['inputType'] == 'inputUnit' || ($arrData['eval']['multiple']??null) ) {
                    $varValue = serialize($varValue);
                }

                $oModel->$name = $varValue;
            }
        }

        // initialize the element
        $oElement = null;
        $oElement = new $sElementClass( $oModel );

        // check if element is styleable
        if( !($oElement instanceof InterfaceStyleable) ) {

            throw new Exception(
                sprintf("Class %s does not implement the interface InterfaceStyleable and therefore is not styleable ", $sElementClass)
            );
        }

        // enable style preview mode
        $oElement->isStylePreview = true;

        // render element
        $sMarkup = "";
        $sMarkup = $oElement->generate();
        $sMarkup = System::getContainer()->get('contao.insert_tag.parser')->replace($sMarkup);

        // generate template
        $aData = [];

        $aData['id'] = $oElement->id;
        $aData['element'] = $sMarkup;
        $aData['headline'] = $GLOBALS['TL_LANG']['tl_content']['cms_element_preview']['headline'];
        $aData['explanation'] = $GLOBALS['TL_LANG']['tl_content']['cms_element_preview']['explanation'];
        $aData['stylesheet'] = $oElement::getStylesheetPath();

        return Backend::parseWithTemplate('backend/widgets/element_style_preview', $aData);
    }


    /**
     * Add styling related fields to the given DataContainer
     *
     * @param Contao\DataContainer $dc
     */
    public function addStylingFields( $dc ) {

        Controller::loadDataContainer($dc->table);

        // add palettes to current DCA
        $GLOBALS['TL_DCA'][$dc->table]['palettes']['__selector__'][] = 'cms_element_style';
        $GLOBALS['TL_DCA'][$dc->table]['subpalettes']['cms_element_style'] =  'cms_element_preview,cms_layout_selector';

        Controller::loadLanguageFile('tl_content');

        // add fields and fields to current DCA
        // (needs to be done here in order for the new fields to be added during migration)
        $GLOBALS['TL_DCA'][$dc->table]['fields']['cms_element_style'] = [
            'label'     => &$GLOBALS['TL_LANG']['tl_content']['cms_element_style']
        ,   'inputType' => 'checkbox'
        ,   'eval'      => ['submitOnChange'=>true]
        ,   'sql'       => "char(1) NOT NULL default '1'"
        ];

        $GLOBALS['TL_DCA'][$dc->table]['fields']['cms_element_preview'] = [
            'input_field_callback' => ['\numero2\MarketingSuite\Widget\ElementStyle', 'generatePreview']
        ];

        Controller::loadLanguageFile('tl_cms_tag_settings');

        $GLOBALS['TL_DCA'][$dc->table]['fields']['cms_layout_selector'] = [
            'label'            => &$GLOBALS['TL_LANG']['tl_cms_tag_settings']['cms_layout_selector']
        ,   'inputType'        => 'cmsLayoutSelector'
        ,   'options'          => []
        ,   'reference'        => &$GLOBALS['TL_LANG']['tl_cms_tag_settings']['cms_layout_selector_options']
        ,   'explanation'      => 'layoutSelector'
        ,   'eval'             => ['sprite'=>'', 'helpwizard'=>true, 'tl_class'=>'clr']
        ,   'sql'              => "varchar(64) NOT NULL default ''"
        ];

        // load current record
        if( !property_exists($dc,'activeRecord') || !$dc->activeRecord ) {

            if( Input::get('act') == 'edit' && Input::get('table') && Input::get('id') ) {

                try {

                    $strModel = Model::getClassFromTable(Input::get('table'));

                    if( class_exists($strModel) ) {

                        $oRow = null;
                        $oRow = $strModel::findOneById(Input::get('id'));

                        if( $oRow ) {
                            $dc->activeRecord = (object)$oRow->row();
                        }
                    }

                } catch( \Exception $e ) {

                }
            }
        }

        // TODO type: text_cms uses ContentText but does not exist anymore
        $strClass = ContentElement::findClass($dc->activeRecord->type??null);

        $isStylableClass = $strClass && class_exists($strClass) && in_array('numero2\MarketingSuite\Helper\InterfaceStyleable', class_implements($strClass));

        if( !$isStylableClass ) {
            return;
        }

        $GLOBALS['TL_DCA'][$dc->table]['fields']['cms_layout_selector']['options'] = $strClass::getLayoutOptions();
        $GLOBALS['TL_DCA'][$dc->table]['fields']['cms_layout_selector']['eval']['sprite'] = $strClass::getLayoutSprite($dc->activeRecord->type);
    }
}
