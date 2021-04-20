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


namespace numero2\MarketingSuite\Widget;

use Contao\Backend as CoreBackend;
use Contao\ContentModel;
use Contao\ContentElement;
use Contao\Controller;
use Contao\Database;
use Contao\DataContainer;
use Contao\Input;
use Contao\Model;
use numero2\MarketingSuite\Backend;
use numero2\MarketingSuite\Helper\ContentElementStyleable;
use numero2\MarketingSuite\Helper\styleable;


class ElementStyle extends CoreBackend {


    /**
     * Renders a container for a live preview of the element
     *
     * @param \DataContainer $dc
     * @param array $aData Array containing fields to be overwritten for the preview
     *
     * @return string
     */
    public function generatePreview( $dc, $aData=[] ) {

        $sElementClass = NULL;
        $oModel = NULL;

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

        $oModel->preventSaving(false);

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
                $varValue = deserialize($aData[$name]);

                // values related to styling are stored seperately
                if( $arrData['eval']['isStylingRelated'] ) {

                    $aStyle[$name] = $varValue;

                } else {

                    // some values need to be serialized
                    if( $arrData['inputType'] == 'inputUnit' || $arrData['eval']['multiple'] ) {
                        $varValue = serialize($varValue);
                    }

                    $oModel->$name = $varValue;
                }
            }

            // add styling
            if( !empty($aStyle) ) {
                $oModel->cms_style = serialize($aStyle);
            }
        }

        // initialize the element
        $oElement = NULL;
        $oElement = new $sElementClass( $oModel );

        // check if element is styleable
        if( !($oElement instanceof styleable) ) {

            throw new \Exception(
                sprintf("Class %s does not implement the interface styleable and therefore is not styleable ",$sElementClass)
            );
        }

        // enable style preview mode
        $oElement->setStylePreview();

        // render element
        $sMarkup = "";
        $sMarkup = $oElement->generate();
        $sMarkup = Controller::replaceInsertTags($sMarkup);

        $style = implode('\n',$GLOBALS['TL_HEAD']);
        unset($GLOBALS['TL_HEAD']);

        // generate template
        $aData = [];

        $aData['id'] = $oElement->id;
        $aData['element'] = $sMarkup;
        $aData['headline'] = $GLOBALS['TL_LANG']['tl_content']['cms_element_preview']['headline'];
        $aData['explanation'] = $GLOBALS['TL_LANG']['tl_content']['cms_element_preview']['explanation'];
        $aData['style'] = $style;

        return Backend::parseWithTemplate('backend/widgets/element_style_preview', $aData);
    }


    /**
     * Generates a navigation where we can choose styling options from
     * different categories
     *
     * @param \DataContainer $dc
     *
     * @return string
     */
    public function generateCategories( $dc ) {

        $this->addStylingFields($dc);

        $aGroups = [];

        // get list of groups the fields are split up into
        foreach( $GLOBALS['TL_DCA'][$dc->table]['fields'] as $name => $arrData ) {

            if( empty($arrData['eval']['data-cms-style-group']) ) {
                continue;
            }

            $groups = [];
            $groups = explode(' ', $arrData['eval']['data-cms-style-group']);

            $aGroups = array_merge($aGroups,$groups);
        }

        $aGroups = array_values( array_unique($aGroups) );

        // make sure "start" and "custom" always appear first and last
        usort($aGroups, function($a,$b) {
            if( $a == 'start' ) { return -1; }
            if( $a == 'custom' ) { return 1; }
        });

        $aData = [];
        $aData['groups'] = $aGroups;

        return Backend::parseWithTemplate('backend/widgets/element_style_categories', $aData);
    }


    /**
     * Add styling related fields to the given DataContainer
     *
     * @param \DataContainer $dc
     */
    public function addStylingFields( $dc ) {

        if( !property_exists($dc,'activeRecord') || !$dc->activeRecord ) {
            
            if( Input::get('act') == 'edit' && Input::get('table') && Input::get('id') ) {

                $strModel = Model::getClassFromTable(Input::get('table'));
                
                $oRow = NULL;
                $oRow = $strModel::findOneById(Input::get('id'));

                if( $oRow ) {
                    $dc->activeRecord = $oRow;
                }
            }
        }

        $aFields = [];
        $strClass = ContentElement::findClass($dc->activeRecord->type??null);
        $isStylableClass = $strClass && in_array('numero2\MarketingSuite\Helper\styleable', class_implements($strClass));

        if( $isStylableClass ) {
            $aFields = $strClass::getStyleFieldsConfig($dc);
        }

        // add palettes and fields to current dca
        $GLOBALS['TL_DCA'][$dc->table]['palettes']['__selector__'][] = 'cms_element_style';
        $GLOBALS['TL_DCA'][$dc->table]['subpalettes']['cms_element_style'] =  '';

        if( count($aFields) ) {
            $GLOBALS['TL_DCA'][$dc->table]['subpalettes']['cms_element_style'] = 'cms_element_preview,cms_element_style_categories,'.implode(',', array_keys($aFields));
        }

        $GLOBALS['TL_DCA'][$dc->table]['fields']['cms_element_style'] = [
            'label'     => &$GLOBALS['TL_LANG']['tl_content']['cms_element_style']
        ,   'inputType' => 'checkbox'
        ,   'eval'      => ['submitOnChange'=>true]
        ,   'sql'       => "char(1) NOT NULL default '1'"
        ];

        $GLOBALS['TL_DCA'][$dc->table]['fields']['cms_style'] = [
            'sql' => "blob NULL"
        ];

        $GLOBALS['TL_DCA'][$dc->table]['fields']['cms_element_preview'] = [
            'input_field_callback' => ['\numero2\MarketingSuite\Widget\ElementStyle', 'generatePreview']
        ];

        $GLOBALS['TL_DCA'][$dc->table]['fields']['cms_element_style_categories'] = [
            'input_field_callback' => ['\numero2\MarketingSuite\Widget\ElementStyle', 'generateCategories']
        ];

        // load more needed fields from tl_style
        if( $isStylableClass ) {

            Controller::loadDataContainer('tl_style');
            Controller::loadLanguageFile('tl_style');

            $this->import('BackendUser', 'User');

            foreach( $aFields as $field => $group ) {

                $styleField = $field;
                $hoverField = false;

                if( strpos($field, "hover_") === 0 ) {

                    $hoverField = true;
                    $styleField = substr($field, 6);
                }

                // get field definitions from tl_style and overwrite some settings
                if( !empty($GLOBALS['TL_DCA']['tl_style']['fields'][$styleField]) ) {

                    $GLOBALS['TL_DCA'][$dc->table]['fields'][$field] = $GLOBALS['TL_DCA']['tl_style']['fields'][$styleField];

                    unset($GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['sql']);

                    // overwrite label if available
                    if( !empty($GLOBALS['TL_LANG']['CMS_ELEMENT_STYLE']['fields'][$field]) ) {

                        unset($GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['label']);
                        $GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['label'] = $GLOBALS['TL_LANG']['CMS_ELEMENT_STYLE']['fields'][$field];
                    }

                    $GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['eval']['doNotSaveEmpty'] = true;
                    $GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['eval']['isStylingRelated'] = true;

                    // handling the field data
                    $GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['save_callback'] = [['\numero2\MarketingSuite\Widget\ElementStyle', 'appendStyle']];
                    $GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['load_callback'] = [['\numero2\MarketingSuite\Widget\ElementStyle', 'loadStyle']];
                }

                $colorFields = ['bgcolor', 'bordercolor', 'fontcolor', 'hover_bgcolor', 'hover_bordercolor', 'hover_fontcolor'];

                if( in_array($field, $colorFields) ) {

                    unset($GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['eval']['multiple']);
                    unset($GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['eval']['size']);
                }

                // set tab group
                $GLOBALS['TL_DCA'][$dc->table]['fields'][$field]['eval']['data-cms-style-group'] = $group;
            }

            // reduce the options
            $unitFields = ['width', 'height', 'margin', 'padding', 'fontsize', 'borderwidth', 'borderradius', 'lineheight', 'letterspacing'];
            $units = ["px", "%", "em", "rem", "vw", "vh"];

            foreach( $unitFields as $value) {
                $GLOBALS['TL_DCA'][$dc->table]['fields'][$value]['options'] = $units;
            }

            // add custom code field
            if( $this->User->cms_pro_mode_enabled == 1 ) {

                $GLOBALS['TL_DCA'][$dc->table]['fields']['cms_element_style_custom'] = [
                    'label'         => &$GLOBALS['TL_LANG']['CMS_ELEMENT_STYLE']['fields']['cms_element_style_custom']
                ,   'inputType'     => 'textarea'
                ,   'eval'          => [
                        'preserveTags' => true
                    ,   'decodeEntities' => true
                    ,   'class' => 'monospace'
                    ,   'rte' => 'ace|css'
                    ,   'helpwizard' => true
                    ,   'tl_class' => 'clr'
                    ,   'doNotSaveEmpty' => true
                    ,   'isStylingRelated' => true
                    ,   'data-cms-style-group' => 'custom'
                    ]
                ,   'explanation'   => 'insertTags'
                ,   'save_callback' => [['\numero2\MarketingSuite\Widget\ElementStyle', 'appendStyle']]
                ,   'load_callback' => [['\numero2\MarketingSuite\Widget\ElementStyle', 'loadStyle']]
                ];
            }

            // set default units
            $GLOBALS['TL_DCA'][$dc->table]['fields']['cms_style']['default'] = [
                'width'         => ['unit' => 'px']
            ,   'height'        => ['unit' => 'px']
            ,   'margin'        => ['unit' => 'px']
            ,   'padding'       => ['unit' => 'px']
            ,   'borderwidth'   => ['unit' => 'px']
            ,   'borderradius'  => ['unit' => 'px']
            ,   'fontsize'      => ['unit' => 'px']
            ,   'lineheight'    => ['unit' => 'px']
            ,   'letterspacing' => ['unit' => 'px']
            ];

            $GLOBALS['TL_DCA'][$dc->table]['config']['onsubmit_callback'][] = ['\numero2\MarketingSuite\Widget\ElementStyle', 'saveStyling'];
        }
    }


    /**
     * Load styling for the given field from our overall style
     *
     * @param string $value
     * @param \DataContainer $dc
     *
     * @return string
     */
    public function loadStyle( $value, DataContainer $dc ) {

        $sStyle = '';
        $sStyle = $dc->activeRecord->cms_style;

        if( $sStyle ) {

            $aStyle = [];
            $aStyle = deserialize($sStyle);

            if( is_array($aStyle) && array_key_exists($dc->field, $aStyle) && $aStyle[$dc->field] ) {

                return $aStyle[$dc->field];

            } else {

                // return custom styling default
                if( $dc->field == 'cms_element_style_custom' ) {

                    $uid = ContentElementStyleable::getUniqueID( $dc->activeRecord );
                    return '[data-cms-unique="'.$uid.'"] {'."\n\n".'}';
                }
            }
        }

        return '';
    }


    /**
     * Appends the styling value of the given field to our overall style
     *
     * @param mixed $value
     * @param \DataContainer $dc
     *
     * @return string
     */
    public function appendStyle( $value, DataContainer $dc ) {

        $aStyle = [];

        if( $dc->cms_style ) {
            $aStyle = $dc->cms_style;
        }

        $aStyle[$dc->field] = deserialize($value);

        $dc->cms_style = $aStyle;

        return '';
    }


    /**
     * Saves all the stylings into one field in the database
     *
     * @param \DataContainer $dc
     */
    public function saveStyling( DataContainer $dc ) {

        if( !empty($dc->cms_style) ) {
            Database::getInstance()->prepare("UPDATE " .$dc->table. " SET cms_style=? WHERE id=?")->execute(serialize($dc->cms_style), $dc->activeRecord->id);
        }
    }
}
