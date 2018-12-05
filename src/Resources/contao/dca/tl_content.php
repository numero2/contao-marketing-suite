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

use Contao\CoreBundle\DataContainer\PaletteManipulator;

$GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'][] = ['tl_content_cms', 'addMarketingItemLabel'];
$GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'][] = ['tl_content_cms', 'loadStyleFields'];

// Dynamically add parent table
if( Input::get('do') == 'cms_marketing' ) {

    $GLOBALS['TL_DCA']['tl_content']['config']['ptable'] = 'tl_cms_content_group';
    $GLOBALS['TL_DCA']['tl_content']['list']['sorting']['headerFields'] = ['name', 'type'];

    // change infos of header field and child record
    $GLOBALS['TL_DCA']['tl_content']['list']['sorting']['header_callback'] = ['tl_content_cms', 'addMarketingItemHeader'];
    array_unshift($GLOBALS['TL_DCA']['tl_content']['list']['sorting']['child_record_callback'],  'tl_content_cms', 'addMarketingItemType');
    // give the change to alter palettes
    $GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'][] = ['tl_content_cms', 'addMarketingItemPalette'];

}
if( Input::get('do') == 'cms_conversion' ) {

    $GLOBALS['TL_DCA']['tl_content']['config']['ptable'] = 'tl_cms_conversion_item';
    $GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'][] = ['tl_content_cms', 'onlyShowConversionItems'];
    $GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'][] = ['tl_content_cms', 'modifyDCHeadline'];

    // change view to table
    $GLOBALS['TL_DCA']['tl_content']['list']['sorting'] = [
        'mode'                  => 1
    ,   'fields'                => ['cms_mi_label']
    ,   'flag'                  => 6
    ,   'panelLayout'           => 'cms_help;filter;search,limit'
    ,   'panel_callback'        => [
            'cms_help' => ['numero2\MarketingSuite\Backend\Help', 'generate']
        ]
    ];
    $GLOBALS['TL_DCA']['tl_content']['list']['label'] = [
            'fields'            => ['cms_mi_label', 'type', 'cms_ci_clicks', 'cms_used']
        ,   'showColumns'       => true
        ,   'label_callback'    => ['tl_content_cms', 'getLabel']
    ];

    // modify types
    $GLOBALS['TL_DCA']['tl_content']['fields']['type']['options_callback'] = ['\numero2\MarketingSuite\ConversionItem', 'getConversionElementTypes'];
    $GLOBALS['TL_DCA']['tl_content']['fields']['type']['default'] = array_keys($GLOBALS['TL_CTE']['conversion_elements'])[0];
}


/**
 * Add palettes to tl_content
 */
$GLOBALS['TL_DCA']['tl_content']['palettes'] = array_merge_recursive(
    $GLOBALS['TL_DCA']['tl_content']['palettes']
,   [
        '__selector__' => ['cms_inline_style']
    ,   'text_cms' => '{type_legend},type,headline;{text_legend},text_cms,text_analysis,text;{image_legend},addImage;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop'
    ,   'text_cms_cta' => '{type_legend},type,headline;{text_legend},text_cms_cta,text;{cta_legend},cta_title,cta_link;{image_legend},addImage;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop'
    ,   'cms_marketing_item' => '{type_legend},type;{marketing_suite_legend},cms_mi_id;{invisible_legend:hide},invisible,start,stop'
    ,   'cms_conversion_item' => '{type_legend},type;{marketing_suite_legend},cms_ci_id;{invisible_legend:hide},invisible,start,stop'
    ,   'cms_button' => '{type_legend},type,headline;{link_legend},url,target,linkTitle,titleText;{style_legend},cms_inline_style;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop'
    ,   'cms_hyperlink' => '{type_legend},type,headline;{link_legend},url,target,linkTitle,titleText;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop'
    ]
);

$GLOBALS['TL_DCA']['tl_content']['subpalettes'] = array_merge(
    $GLOBALS['TL_DCA']['tl_content']['subpalettes']
,   [
        'cms_inline_style' => 'cms_button_preview,cms_button_categories,display,align,width,height,margin,padding,bgcolor,borderwidth,borderstyle,bordercolor,borderradius,textalign,fontsize,fontcolor,lineheight,letterspacing,hover_bgcolor,hover_bordercolor,hover_fontcolor,cms_button_hash_info'
    ]
);


/**
 * Add fields to tl_content
 */
$GLOBALS['TL_DCA']['tl_content']['fields'] = array_merge(
    $GLOBALS['TL_DCA']['tl_content']['fields']
,   [
        'cms_helper_top' => [
            'input_field_callback'     => [ '\numero2\MarketingSuite\Backend\Wizard', 'generateTopForInputField' ]
        ]
    ,   'cms_helper_bottom' => [
            'input_field_callback'     => [ '\numero2\MarketingSuite\Backend\Wizard', 'generateBottomForInputField' ]
        ]
    ,   'text_cms' => [
            'label'             => &$GLOBALS['TL_LANG']['tl_content']['text']
        ,   'exclude'           => true
        ,   'search'            => true
        ,   'inputType'         => 'textarea'
        ,   'eval'              => ['mandatory'=>true, 'rte'=>'tinyMarketing', 'helpwizard'=>true, 'doNotSaveEmpty'=>true, 'tl_class'=>'text-cms']
        ,   'explanation'       => 'insertTags'
        ,   'load_callback'     => [ ['tl_content_cms', 'loadTextCMS'] ]
        ,   'save_callback'     => [ ['tl_content_cms', 'saveTextCMS'] ]
        ]
    ,   'text_analysis' => [
            'input_field_callback'  => [ 'tl_content_cms', 'textAnalysis' ]
        ]
    ,   'text_cms_cta' => [
            'label'             => &$GLOBALS['TL_LANG']['tl_content']['text']
        ,   'exclude'           => true
        ,   'search'            => true
        ,   'inputType'         => 'textarea'
        ,   'eval'              => ['mandatory'=>true, 'rte'=>'tinyMarketing', 'helpwizard'=>true, 'doNotSaveEmpty'=>true]
        ,   'explanation'       => 'insertTags'
        ,   'load_callback'     => [ ['tl_content_cms', 'loadTextCMS'] ]
        ,   'save_callback'     => [ ['tl_content_cms', 'saveTextCMS'] ]
        ]
    ,   'cta_title' => [
            'label'             => &$GLOBALS['TL_LANG']['tl_content']['cta_title']
        ,   'exclude'           => true
        ,   'search'            => true
        ,   'inputType'         => 'text'
        ,   'eval'              => ['mandatory'=>true, 'maxlength'=>255, 'allowHtml'=>true, 'tl_class'=>'w50']
        ,   'sql'               => "varchar(255) NOT NULL default ''"
        ]
    ,   'cta_link' => [
            'label'             => &$GLOBALS['TL_LANG']['tl_content']['cta_link']
        ,   'exclude'           => true
        ,   'search'            => true
        ,   'inputType'         => 'text'
        ,   'eval'              => ['mandatory'=>true, 'rgxp'=>'url', 'decodeEntities'=>true, 'maxlength'=>255, 'dcaPicker'=>true, 'tl_class'=>'w50 wizard']
        ,   'sql'               => "varchar(255) NOT NULL default ''"
        ]
    ,   'cms_mi_id' => [
            'label'             => &$GLOBALS['TL_LANG']['tl_content']['cms_mi_id']
        ,   'inputType'         => 'select'
        ,   'foreignKey'        => 'tl_cms_marketing_item.name'
        ,   'eval'              => ['mandatory'=>true, 'chosen'=>'true', 'inlcudeBlankOption'=>true, 'tl_class'=>'clr w50 wizard']
        ,   'options_callback'  => ['\numero2\MarketingSuite\MarketingItem','getAvailableOptions']
        ,   'wizard'            => [['\numero2\MarketingSuite\MarketingItem','marketingItemWizard']]
        ,   'relation'          => ['type'=>'hasOne', 'load'=>'lazy']
        ,   'sql'               => "int(10) unsigned NOT NULL default '0'"
        ]
    ,   'cms_mi_pages_criteria' => [
            'label'             => &$GLOBALS['TL_LANG']['tl_content']['cms_mi_pages_criteria']
        ,   'inputType'         => 'radio'
        ,   'default'           => 'all'
        ,   'options_callback'  => ['\numero2\MarketingSuite\MarketingItem\VisitedPages', 'getPagesCriteria']
        ,   'eval'              => ['submitOnChange'=>true, 'tl_class'=>'clr w50 no-height']
        ,   'sql'               => "varchar(64) NOT NULL default ''"
        ]
    ,   'cms_mi_pages' => [
            'label'             => &$GLOBALS['TL_LANG']['tl_content']['cms_mi_pages']
        ,   'inputType'         => 'pageTree'
        ,   'foreignKey'        => 'tl_page.title'
        ,   'eval'              => ['mandatory'=>true, 'multiple'=>true, 'fieldType'=>'checkbox', 'orderField'=>'cms_mi_orderPages', 'tl_class'=>'clr']
        ,   'relation'          => ['type'=>'hasMany', 'load'=>'lazy']
        ,   'sql'               => "text NULL"
        ]
    ,   'cms_mi_orderPages' => [
            'eval'              => ['doNotShow'=>true]
        ,   'sql'               => "text NULL"
        ]
    ,   'cms_mi_label' => [
            'label'             => &$GLOBALS['TL_LANG']['tl_content']['cms_mi_label']
        ,   'exclude'           => true
        ,   'search'            => true
        ,   'inputType'         => 'text'
        ,   'eval'              => ['mandatory'=>true, 'maxlength'=>255, 'doNotCopy'=>true, 'tl_class'=>'w50']
        ,   'sql'               => "varchar(255) NOT NULL default ''"
        ]
    ,   'cms_mi_views' => [
            'sql'               => "int(10) unsigned NOT NULL default '0'"
        ,   'eval'              => ['doNotCopy'=>true, 'readonly'=>'readonly', 'tl_class'=>'w50']
        ]
    ,   'cms_mi_isMainTracker' => [
            'eval'              => ['doNotCopy'=>true]
        ,   'sql'               => "char(1) NOT NULL default ''"
        ]
    ,   'cms_ci_id' => [
            'label'             => &$GLOBALS['TL_LANG']['tl_content']['cms_ci_id']
        ,   'inputType'         => 'select'
        ,   'options_callback'  => ['\numero2\MarketingSuite\ConversionItem', 'getConversionElements']
        ,   'eval'              => ['mandatory'=>true, 'chosen'=>'true', 'inlcudeBlankOption'=>true, 'tl_class'=>'clr w50 wizard']
        ,   'wizard'            => [['\numero2\MarketingSuite\ConversionItem','conversionItemWizard']]
        ,   'sql'               => "int(10) unsigned NOT NULL default '0'"
        ]
    ,   'cms_ci_clicks' => [
            'label'             => &$GLOBALS['TL_LANG']['tl_content']['cms_ci_clicks']
        ,   'eval'              => ['doNotCopy'=>true, 'readonly'=>'readonly', 'tl_class'=>'w50']
        ,   'sql'               => "int(10) unsigned NOT NULL default '0'"
        ]
    ,   'cms_ci_clicks' => [
            'label'             => &$GLOBALS['TL_LANG']['tl_content']['cms_ci_clicks']
        ,   'eval'              => ['doNotCopy'=>true, 'readonly'=>'readonly', 'tl_class'=>'w50']
        ,   'sql'               => "int(10) unsigned NOT NULL default '0'"
        ]
    ,   'cms_ci_views' => [
            'label'             => &$GLOBALS['TL_LANG']['tl_content']['cms_ci_views']
        ,   'eval'              => ['doNotCopy'=>true, 'readonly'=>'readonly', 'tl_class'=>'w50']
        ,   'sql'               => "int(10) unsigned NOT NULL default '0'"
        ]
    ,   'cms_used' => [
            'label'             => &$GLOBALS['TL_LANG']['tl_content']['cms_used']
        ]
    ,   'cms_inline_style' => [
            'label'             => &$GLOBALS['TL_LANG']['tl_content']['cms_inline_style']
        ,   'inputType'         => 'checkbox'
        ,   'eval'              => ['submitOnChange'=>true]
        ,   'sql'               => "char(1) NOT NULL default '1'"
        ]
    ,   'cms_style' => [
            'sql'               => "blob NULL"
        ]
    ,   'cms_button_preview' => [
            'input_field_callback'  => [ 'tl_content_cms', 'previewButton' ]
        ]
    ,   'cms_button_categories' => [
            'input_field_callback'  => [ 'tl_content_cms', 'categoriesButton' ]
        ]
    ,   'cms_button_hash_info' => [
            'input_field_callback'  => [ 'tl_content_cms', 'buttonHashInfo' ]
        ]
    ]
);

$GLOBALS['TL_DCA']['tl_content']['fields']['text']['save_callback'][] = ['tl_content_cms', 'saveTextOriginal'];
$GLOBALS['TL_DCA']['tl_content']['fields']['customTpl']['options_callback']  = ['tl_content_cms', 'getElementTemplates'];


use numero2\MarketingSuite\Backend;


class tl_content_cms extends Backend {


    /**
     * Generates the labels for the table view
     *
     * @param array         $row
     * @param string        $label
     * @param DataContainer $dc
     * @param array         $args
     *
     * @return array
     */
    public function getLabel($row, $label, DataContainer $dc, $args) {

        $count = 0;
        $aElements = [];

        $oContent = \ContentModel::findBy(['type=? AND cms_ci_id=?'], ['cms_conversion_item', $row['id']]);

        if( count($oContent) ) {

            $count += count($oContent);
            $aElements[$GLOBALS['TL_LANG']['MOD']['tl_content']] = $oContent;
        }

        $oModule = \ModuleModel::findBy(['tl_module.type=? AND tl_module.cms_ci_id=?'], ['cms_conversion_item', $row['id']]);

        if( count($oModule) ) {

            $count += count($oModule);
            $aElements[$GLOBALS['TL_LANG']['MOD']['tl_module']] = $oModule;
        }

        $args[3] = '';

        if( count($aElements) ) {

            $aOverlay = [
                'label' => 'Elemente (' . $count . ')'
            ,   'content' => $aElements
            ];
            $args[3] = \numero2\MarketingSuite\Backend::parseWithTemplate('backend/elements/overlay_tree', $aOverlay );
        }

        return $args;
    }


    /**
    * Loads text content from field 'text'
    *
    * @param  String $value
    * @param  DataContainer $dc
    *
    * @return String
    */
    public function loadTextCMS($value, DataContainer $dc) {

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
    * @param  String $value
    * @param  DataContainer $dc
    *
    * @return String
    */
    public function saveTextCMS($value, DataContainer $dc) {

        return NULL;
    }


    /**
    * Saves the content of our "fake" textfield to field 'text'
    *
    * @param  String $value
    * @param  DataContainer $dc
    *
    * @return String
    */
    public function saveTextOriginal($value, DataContainer $dc) {

        if( $dc->activeRecord->type == 'text_cms' ) {
            return \Input::postUnsafeRaw('text_cms');
        }

        if( $dc->activeRecord->type == 'text_cms_cta' ) {
            return \Input::postUnsafeRaw('text_cms_cta');
        }

        return $value;
    }


    /**
     * Return all content element templates as array
     *
     * @param DataContainer $dc
     *
     * @return array
     */
    public function getElementTemplates( DataContainer $dc ) {

        // 'text_cms' should behave the same like 'text'
        if( $dc->activeRecord->type == 'text_cms' ) {
            $dc->activeRecord->type = 'text';
        }

        $oContent = NULL;
        $oContent = new tl_content();

        return $oContent->getElementTemplates($dc);
    }


    /**
     * Renders a view for some statical analyses of the text
     *
     * @param DataContainer $dc
     *
     * @return string
     */
    public function textAnalysis( DataContainer $dc ) {

        $text = NULL;
        $text = $dc->activeRecord->text;

        $oAnalysis = NULL;
        $oAnalysis = new numero2\MarketingSuite\Content\Analysis($text);

        $data = [
            'syllables' => \numero2\MarketingSuite\Backend\License::hasFeature('text_analysis_syllables')?$oAnalysis->syllables:null
        ,   'stats'     => \numero2\MarketingSuite\Backend\License::hasFeature('text_analysis_stats')?$oAnalysis->stats:null
        ,   'sentences' => \numero2\MarketingSuite\Backend\License::hasFeature('text_analysis_sentences')?$oAnalysis->sentences:null
        ,   'misc'      => [
                'flesch' => \numero2\MarketingSuite\Backend\License::hasFeature('text_analysis_flesch')?$oAnalysis->flesch:null
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
     * Renders a view for some statical analyses of the text
     *
     * @param DataContainer $dc
     *
     * @return string
     */
    public function categoriesButton( DataContainer $dc ) {

        $data = [];

        $label = [
            'start' => $GLOBALS['TL_LANG']['tl_content']['button_categories']['start'],
            'sizes' => $GLOBALS['TL_LANG']['tl_content']['button_categories']['sizes'],
            'background-border' => $GLOBALS['TL_LANG']['tl_content']['button_categories']['background-border'],
            'text-font' => $GLOBALS['TL_LANG']['tl_content']['button_categories']['text-font'],
            'hover' => $GLOBALS['TL_LANG']['tl_content']['button_categories']['hover'],
        ];

        $this->import('BackendUser', 'User');

        if( $this->User->cms_pro_mode_enabled == 1 ) {
            $label['profi'] = $GLOBALS['TL_LANG']['tl_content']['button_categories']['profi'];
        }

        $data['labels'] = $label;

        return Backend::parseWithTemplate('backend/widgets/button_categories', $data);
    }


    /**
     * Renders a view for some statical analyses of the text
     *
     * @param DataContainer $dc
     *
     * @return string
     */
    public function buttonHashInfo( DataContainer $dc ) {

        return '<div class="long profi widget">
        <h3><label>'.$GLOBALS['TL_LANG']['tl_content']['cms_button_hash_info'][0].'</label></h3>
        <p>[data-unique="'.sha1($dc->activeRecord->id).'"] { ... }</p>
        <p class="tl_help tl_tip" title="">'.$GLOBALS['TL_LANG']['tl_content']['cms_button_hash_info'][1].'</p>
        </div>';
    }


    /**
     * Renders a view for some statical analyses of the text
     *
     * @param DataContainer $dc
     *
     * @return string
     */
    public function previewButton( DataContainer $dc ) {

        $url = NULL;
        $url = $dc->activeRecord->url;

        $data = [];
        if( !empty($url) ) {

            $objModel = ContentModel::findById($dc->activeRecord->id);
            $ceButton = new \numero2\MarketingSuite\ContentButton($objModel);

            $strButton = $ceButton->generate();

            $style = $GLOBALS['TL_HEAD'][0];
            unset($GLOBALS['TL_HEAD']);


            $data['id'] = $dc->activeRecord->id;
            $data['style'] = $style;
            $data['style'] = str_replace('<style>', '<style id="custom">', $data['style']);
            $data['style'] = addslashes($data['style']);
            $data['style'] = str_replace(["\r", "\n", "\r\n"], "", $data['style']);

            $data['button'] = $strButton;
            $data['button'] = addslashes($data['button']);
            $data['button'] = str_replace(["\r", "\n", "\r\n"], "", $data['button']);


            $data['framescript'] = "

                window.addEventListener('message', function(e) {

                    if( e.data ) {

                        var style = document.querySelector('head style#custom');
                        if( style ) {
                            style.innerHTML = e.data
                        }
                    }

                    var data = {height: document.querySelector('body > div').clientHeight};
                    window.parent.postMessage(data, '*');
                }, false);

                window.addEventListener('resize', function(e) {
                    var data = {height: document.querySelector('body > div').clientHeight};
                    window.parent.postMessage(data, '*');
                });

            ";
            $data['framescript'] = str_replace(["\r", "\n", "\r\n"], "", $data['framescript']);
        }

        return Backend::parseWithTemplate('backend/widgets/button_preview', $data);
    }


    /**
     * Load style fields from tl_style
     *
     * @param DataContainer $dc
     *
     * @return string
     */
    public function loadStyleFields( DataContainer $dc ) {

        // load more needed fields from tl_style
        if( !empty($GLOBALS['TL_DCA']['tl_content']['subpalettes']['cms_inline_style']) ) {

            $fields = explode(',', $GLOBALS['TL_DCA']['tl_content']['subpalettes']['cms_inline_style']);

            \Controller::loadDataContainer('tl_style');
            \Controller::loadLanguageFile('tl_style');

            foreach( $fields as $field ) {

                $styleField = $field;
                $hoverField = false;

                if( strpos($field, "hover_") === 0 ) {

                    $hoverField = true;
                    $styleField = substr($field, 6);
                }

                if( !empty($GLOBALS['TL_DCA']['tl_style']['fields'][$styleField]) ) {

                    $GLOBALS['TL_DCA']['tl_content']['fields'][$field] = $GLOBALS['TL_DCA']['tl_style']['fields'][$styleField];

                    unset($GLOBALS['TL_DCA']['tl_content']['fields'][$field]['sql']);
                    $GLOBALS['TL_DCA']['tl_content']['fields'][$field]['eval']['doNotSaveEmpty'] = true;
                    $GLOBALS['TL_DCA']['tl_content']['fields'][$field]['save_callback'] = [['tl_content_cms', 'saveStyle']];
                    $GLOBALS['TL_DCA']['tl_content']['fields'][$field]['load_callback'] = [['tl_content_cms', 'loadStyle']];

                    if( $hoverField ) {
                        unset($GLOBALS['TL_DCA']['tl_content']['fields'][$field]['label']);
                        $GLOBALS['TL_DCA']['tl_content']['fields'][$field]['label'][0] = 'Hover - '.$GLOBALS['TL_DCA']['tl_style']['fields'][$styleField]['label'][0];
                        $GLOBALS['TL_DCA']['tl_content']['fields'][$field]['label'][1] = $GLOBALS['TL_DCA']['tl_style']['fields'][$styleField]['label'][1];
                    }
                }

                $colorFields = ['bgcolor', 'bordercolor', 'fontcolor', 'hover_bgcolor', 'hover_bordercolor', 'hover_fontcolor'];

                if( in_array($field, $colorFields) ) {

                    unset($GLOBALS['TL_DCA']['tl_content']['fields'][$field]['eval']['multiple']);
                    unset($GLOBALS['TL_DCA']['tl_content']['fields'][$field]['eval']['size']);

                    unset($GLOBALS['TL_DCA']['tl_content']['fields'][$field]['label']);
                    $GLOBALS['TL_DCA']['tl_content']['fields'][$field]['label'] = $GLOBALS['TL_LANG']['tl_content']['cms_button_'.$field];
                }
            }

            // set classes for tab view
            $GLOBALS['TL_DCA']['tl_content']['fields']['display']['eval']['tl_class']           .= " sizes";
            $GLOBALS['TL_DCA']['tl_content']['fields']['align']['eval']['tl_class']             .= " sizes";
            $GLOBALS['TL_DCA']['tl_content']['fields']['width']['eval']['tl_class']             .= " sizes";
            $GLOBALS['TL_DCA']['tl_content']['fields']['height']['eval']['tl_class']            .= " sizes";
            $GLOBALS['TL_DCA']['tl_content']['fields']['margin']['eval']['tl_class']            .= " sizes";
            $GLOBALS['TL_DCA']['tl_content']['fields']['padding']['eval']['tl_class']           .= " sizes";
            $GLOBALS['TL_DCA']['tl_content']['fields']['bgcolor']['eval']['tl_class']           .= " background-border start";
            $GLOBALS['TL_DCA']['tl_content']['fields']['borderwidth']['eval']['tl_class']       .= " background-border";
            $GLOBALS['TL_DCA']['tl_content']['fields']['borderstyle']['eval']['tl_class']       .= " background-border";
            $GLOBALS['TL_DCA']['tl_content']['fields']['bordercolor']['eval']['tl_class']       .= " background-border";
            $GLOBALS['TL_DCA']['tl_content']['fields']['borderradius']['eval']['tl_class']      .= " background-border";
            $GLOBALS['TL_DCA']['tl_content']['fields']['textalign']['eval']['tl_class']         .= " text-font";
            $GLOBALS['TL_DCA']['tl_content']['fields']['fontsize']['eval']['tl_class']          .= " text-font start";
            $GLOBALS['TL_DCA']['tl_content']['fields']['fontcolor']['eval']['tl_class']         .= " text-font start";
            $GLOBALS['TL_DCA']['tl_content']['fields']['lineheight']['eval']['tl_class']        .= " text-font";
            $GLOBALS['TL_DCA']['tl_content']['fields']['letterspacing']['eval']['tl_class']     .= " text-font";
            $GLOBALS['TL_DCA']['tl_content']['fields']['hover_bgcolor']['eval']['tl_class']     .= " hover";
            $GLOBALS['TL_DCA']['tl_content']['fields']['hover_bordercolor']['eval']['tl_class'] .= " hover";
            $GLOBALS['TL_DCA']['tl_content']['fields']['hover_fontcolor']['eval']['tl_class']   .= " hover";

            // reduce the options
            $GLOBALS['TL_DCA']['tl_content']['fields']['display']['options'] = ["block", "inline-block"];

            $unitFields = ['width', 'height', 'margin', 'padding', 'fontsize', 'borderwidth', 'borderradius', 'lineheight', 'letterspacing'];
            $units = ["px", "%", "em", "rem", "vw", "vh"];

            foreach( $unitFields as $value) {
                $GLOBALS['TL_DCA']['tl_content']['fields'][$value]['options'] = $units;
            }

            $GLOBALS['TL_DCA']['tl_content']['config']['onsubmit_callback'][] = ['tl_content_cms', 'submitStyle'];
        }
    }


    /**
     * Save styling information to datacontainer
     *
     * @param mixed         $value
     * @param DataContainer $dc
     *
     * @return string
     */
    public function saveStyle( $value, DataContainer $dc ) {

        $aStyle = [];
        if( $dc->cms_style ) {
            $aStyle = $dc->cms_style;
        }

        $aStyle[$dc->field] = deserialize($value);

        $dc->cms_style = $aStyle;

        return '';
    }


    /**
     * Save styling information in datacontainer to database
     *
     * @param DataContainer $dc
     */
    public function submitStyle( DataContainer $dc ) {

        if( !empty($dc->cms_style) ) {
            \Database::getInstance()->prepare("UPDATE tl_content SET cms_style=? WHERE id=?")->execute(serialize($dc->cms_style), $dc->activeRecord->id);
        }
    }


    /**
     * Load styling information from one database field
     *
     * @param  String $value
     * @param  DataContainer $dc
     *
     * @return String
     */
    public function loadStyle($value, $dc) {

        $style = $dc->activeRecord->cms_style;

        if( $style ) {

            $aStyle = deserialize($style);
            if( is_array($aStyle) && array_key_exists($dc->field, $aStyle) ) {
                return $aStyle[$dc->field];
            }
        }

        return '';
    }


    /**
     * show marketing item label on all conversion items
     *
     * @param DataContainer $dc
     */
    public function addMarketingItemLabel($dc) {

        $pm = PaletteManipulator::create()
            ->addField(['cms_mi_label'], 'type', 'after')
        ;

        if( \numero2\MarketingSuite\Backend\License::hasFeature('conversion_element') && count($GLOBALS['TL_CTE']['conversion_elements']) ) {
            foreach( $GLOBALS['TL_CTE']['conversion_elements'] as $key => $value ) {

                if( !\numero2\MarketingSuite\Backend\License::hasFeature('ce_'.$key) ) {
                    unset($GLOBALS['TL_CTE']['conversion_elements'][$key]);
                    continue;
                }

                $pm->applyToPalette($key, 'tl_content');
            }
        }

        if( !\numero2\MarketingSuite\Backend\License::hasFeature('marketing_element') ) {
            unset($GLOBALS['TL_CTE']['marketing_suite']['cms_marketing_item']);
        }

        if( !\numero2\MarketingSuite\Backend\License::hasFeature('conversion_element') || !count($GLOBALS['TL_CTE']['conversion_elements']) ) {
            unset($GLOBALS['TL_CTE']['marketing_suite']['cms_conversion_item']);
            unset($GLOBALS['TL_CTE']['conversion_elements']);
        }

        if( !count($GLOBALS['TL_CTE']['marketing_suite']) ) {
            unset($GLOBALS['TL_CTE']['marketing_suite']);
        }

        if( !\numero2\MarketingSuite\Backend\License::hasFeature('text_cms') ) {
            unset($GLOBALS['TL_CTE']['texts']['text_cms']);
        }
    }


    /**
     * If we are on 'do=cms_marketing' also add information to child record about dynamic content to backend view
     *
     * @param array $arrRow
     */
    public function addMarketingItemType($arrRow) {

        $childRecords = $GLOBALS['TL_DCA']['tl_content']['list']['sorting']['child_record_callback'];

        $strBuffer = "";
        // execute old child record
        if( count($GLOBALS['TL_DCA']['tl_content']['list']['sorting']['child_record_callback']) > 2 ) {

            $strClass = $GLOBALS['TL_DCA']['tl_content']['list']['sorting']['child_record_callback'][2];
            $strMethod = $GLOBALS['TL_DCA']['tl_content']['list']['sorting']['child_record_callback'][3];

            $this->import($strClass);
            $strBuffer = $this->$strClass->$strMethod($arrRow);
        }

        // modify
        return \numero2\MarketingSuite\MarketingItem::alterContentChildRecord($arrRow, $strBuffer);
    }


    /**
     * If we are on 'do=cms_marketing' also add information to header about dynamic content to backend view
     *
     * @param array $arrRow
     * @param DataContainer $dc
     *
     * @return String
     */
    public function addMarketingItemHeader($arrRow, $dc) {
        return \numero2\MarketingSuite\MarketingItem::alterContentHeader($arrRow, $dc);
    }


    /**
     * If we are on 'do=cms_marketing' we change palettes
     *
     * @param DataContainer $dc
     */
    public function addMarketingItemPalette($dc) {
        \numero2\MarketingSuite\MarketingItem::alterContentDCA($dc);
    }


    /**
     * If we are on 'do=cms_conversion' we only show conversion items
     *
     * @param DataContainer $dc
     */
    public function onlyShowConversionItems($dc) {

        $GLOBALS['TL_DCA'][$dc->table]['list']['sorting']['filter'][] = array('ptable=? AND 1=1','tl_cms_conversion_item');
    }


    /**
     * Modifies the headline of the current dataContainer
     *
     * @return none
     */
    public function modifyDCHeadline() {

        $classNames = '';

        if( Input::get('do') == 'cms_conversion' ) {

            $classNames = 'conversion_item';

            if( Input::get('act') == 'edit' ) {
                $classNames .= ' edit';
            }
        }

        if( !empty($classNames) ) {

            if( version_compare(VERSION, '4.5', '<') ) {
                $GLOBALS['TL_MOOTOOLS'][] = "<script>document.querySelector('.main_headline').className += ' ".$classNames."';</script>";
            } else {
                $GLOBALS['TL_MOOTOOLS'][] = "<script>document.querySelector('#main_headline').className += ' ".$classNames."';</script>";
            }
        }
    }
}
