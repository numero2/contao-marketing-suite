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


$GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'][] = ['\numero2\MarketingSuite\DCAHelper\MarketingItem', 'addLabel'];
$GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'][] = ['\numero2\MarketingSuite\Widget\ElementStyle', 'addStylingFields'];
$GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'][] = ['\numero2\MarketingSuite\DCAHelper\Content', 'addTagVisibilityFields'];
$GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'][] = ['\numero2\MarketingSuite\DCAHelper\General', 'excludeFieldsFromMultipleEdit'];
$GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'][] = ['\numero2\MarketingSuite\DCAHelper\TextCMS', 'rewriteCustomTemplateCallback'];

array_insert($GLOBALS['TL_DCA']['tl_content']['list']['operations'], 3, [
    'reset_counter' => [
        'label'             => &$GLOBALS['TL_LANG']['tl_content']['reset_counter']
    ,   'icon'              => 'bundles/marketingsuite/img/backend/icons/icon_reset_counter.svg'
    ,   'attributes'        => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['reset_warning'] . '\')) return false; Backend.getScrollOffset();"'
    ,   'button_callback'   => ['\numero2\MarketingSuite\DCAHelper\ConversionItem', 'resetCounter']
    ]
]);

// Dynamically add parent table
if( Input::get('do') == 'cms_marketing' ) {

    $GLOBALS['TL_DCA']['tl_content']['config']['ptable'] = 'tl_cms_content_group';
    $GLOBALS['TL_DCA']['tl_content']['list']['sorting']['headerFields'] = ['name', 'type'];

    // HACK for >= 4.11 to make DC_Table choose the correct ptable in do=cms_marketing&table=tl_content
    if( version_compare(VERSION, '4.11', '>=') ) {
        if( Input::get('table') == 'tl_content' ) {
            unset($GLOBALS['BE_MOD']['marketing_suite']['cms_marketing']['tables'][0]);
        }
    }

    // change infos of header field and child record
    $GLOBALS['TL_DCA']['tl_content']['list']['sorting']['header_callback'] = ['\numero2\MarketingSuite\DCAHelper\MarketingItem', 'addHeader'];
    array_unshift($GLOBALS['TL_DCA']['tl_content']['list']['sorting']['child_record_callback'],  '\numero2\MarketingSuite\DCAHelper\MarketingItem', 'addType');
    // give the change to alter palettes
    $GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'][] = ['\numero2\MarketingSuite\DCAHelper\MarketingItem', 'addPalette'];

}
if( Input::get('do') == 'cms_conversion' ) {

    $GLOBALS['TL_DCA']['tl_content']['config']['closed'] = \numero2\MarketingSuite\DCAHelper\ConversionItem::isClosed();
    $GLOBALS['TL_DCA']['tl_content']['config']['ptable'] = 'tl_cms_conversion_item';

    $GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'][] = ['\numero2\MarketingSuite\DCAHelper\ConversionItem', 'onlyShowConversionItems'];
    $GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'][] = ['\numero2\MarketingSuite\DCAHelper\ConversionItem', 'modifyDCHeadline'];
    $GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'][] = ['\numero2\MarketingSuite\DCAHelper\ConversionItem', 'addPageScopeFields'];

    $GLOBALS['TL_DCA']['tl_content']['config']['notSortable'] = true;
    $GLOBALS['TL_DCA']['tl_content']['config']['notCopyable'] = true;

    unset($GLOBALS['TL_DCA']['tl_content']['list']['operations']['cut']);
    unset($GLOBALS['TL_DCA']['tl_content']['list']['operations']['copy']);

    // change view to table
    $GLOBALS['TL_DCA']['tl_content']['list']['sorting'] = [
        'mode'                  => 1
    ,   'fields'                => ['cms_mi_label']
    ,   'flag'                  => 6
    ,   'panelLayout'           => 'cms_license_message;cms_help;filter;search,limit'
    ,   'panel_callback'        => [
            'cms_license_message' => ['\numero2\MarketingSuite\Backend\LicenseMessage', 'generate']
        ,   'cms_help' => ['\numero2\MarketingSuite\Backend\Help', 'generate']
        ]
    ];
    $GLOBALS['TL_DCA']['tl_content']['list']['label'] = [
            'fields'            => ['cms_mi_label', 'type', 'cms_ci_clicks', 'cms_ci_views', 'cms_used']
        ,   'showColumns'       => true
        ,   'label_callback'    => ['\numero2\MarketingSuite\DCAHelper\ConversionItem', 'getLabel']
    ];

    // modify types
    $GLOBALS['TL_DCA']['tl_content']['fields']['type']['options_callback'] = ['\numero2\MarketingSuite\DCAHelper\ConversionItem', 'getConversionElementTypes'];
    $GLOBALS['TL_DCA']['tl_content']['fields']['type']['default'] = array_keys($GLOBALS['TL_CTE']['conversion_elements'])[0];
}


/**
 * Add palettes to tl_content
 */
$GLOBALS['TL_DCA']['tl_content']['palettes'] = array_merge_recursive(
    $GLOBALS['TL_DCA']['tl_content']['palettes']
,   [
        'text_cms' => '{type_legend},type,headline;{text_legend},text_cms,text_analysis,text;{image_legend},addImage;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop'
    ,   'text_cms_cta' => '{type_legend},type,headline;{text_legend},text_cms_cta,text;{cta_legend},cta_title,cta_link;{image_legend},addImage;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop'
    ,   'cms_marketing_item' => '{type_legend},type;{marketing_suite_legend},cms_mi_id;{invisible_legend:hide},invisible,start,stop'
    ,   'cms_conversion_item' => '{type_legend},type;{marketing_suite_legend},cms_ci_id;{invisible_legend:hide},invisible,start,stop'
    ,   'cms_button' => '{type_legend},type,headline;{link_legend},url,target,linkTitle,titleText;{style_legend},cms_element_style;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop'
    ,   'cms_hyperlink' => '{type_legend},type,headline;{link_legend},url,target,linkTitle,titleText;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop'
    ,   'cms_form' => &$GLOBALS['TL_DCA']['tl_content']['palettes']['form']
    ,   'cms_overlay' => '{type_legend},type,cms_layout_option,headline;{text_legend},text;{style_legend},cms_element_style;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop,cms_lifetime'
    ]
);

$GLOBALS['TL_DCA']['tl_content']['list']['operations']['edit']['button_callback'] = ['\numero2\MarketingSuite\DCAHelper\Content', 'editButton'];


/**
 * Add subpalettes to tl_content
 */
$GLOBALS['TL_DCA']['tl_content']['palettes']['__selector__'][] = 'cms_tag_visibility';
$GLOBALS['TL_DCA']['tl_content']['subpalettes']['cms_tag_visibility']= 'cms_tag';


/**
 * Add fields to tl_content
 */
$GLOBALS['TL_DCA']['tl_content']['fields'] = array_merge(
    $GLOBALS['TL_DCA']['tl_content']['fields']
,   [
        'cms_helper_top' => [
            'input_field_callback'     => [ '\numero2\MarketingSuite\Backend\Wizard', 'generateTopForInputField' ]
        ,   'excludeMultipleEdit'      => true
        ]
    ,   'cms_helper_bottom' => [
            'input_field_callback'     => [ '\numero2\MarketingSuite\Backend\Wizard', 'generateBottomForInputField' ]
        ,   'excludeMultipleEdit'      => true
        ]
    ,   'text_cms' => [
            'label'             => &$GLOBALS['TL_LANG']['tl_content']['text']
        ,   'exclude'           => true
        ,   'search'            => true
        ,   'inputType'         => 'textarea'
        ,   'eval'              => ['mandatory'=>true, 'rte'=>'tinyMarketing', 'helpwizard'=>true, 'doNotSaveEmpty'=>true, 'tl_class'=>'text-cms']
        ,   'explanation'       => 'insertTags'
        ,   'load_callback'     => [ ['\numero2\MarketingSuite\DCAHelper\TextCMS', 'loadContentFromOriginalField'] ]
        ,   'save_callback'     => [ ['\numero2\MarketingSuite\DCAHelper\TextCMS', 'preventSavingToDB'] ]
        ]
    ,   'text_analysis' => [
            'input_field_callback'  => [ '\numero2\MarketingSuite\DCAHelper\TextCMS', 'generateInputField' ]
        ,   'excludeMultipleEdit'      => true
        ]
    ,   'text_cms_cta' => [
            'label'             => &$GLOBALS['TL_LANG']['tl_content']['text']
        ,   'exclude'           => true
        ,   'search'            => true
        ,   'inputType'         => 'textarea'
        ,   'eval'              => ['mandatory'=>true, 'rte'=>'tinyMarketing', 'helpwizard'=>true, 'doNotSaveEmpty'=>true]
        ,   'explanation'       => 'insertTags'
        ,   'load_callback'     => [ ['\numero2\MarketingSuite\DCAHelper\TextCMS', 'loadContentFromOriginalField'] ]
        ,   'save_callback'     => [ ['\numero2\MarketingSuite\DCAHelper\TextCMS', 'preventSavingToDB'] ]
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
        ,   'options_callback'  => ['\numero2\MarketingSuite\DCAHelper\MarketingItem','getAvailableOptions']
        ,   'wizard'            => [['\numero2\MarketingSuite\DCAHelper\MarketingItem','marketingItemWizard']]
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
        ,   'eval'              => ['mandatory'=>true, 'multiple'=>true, 'fieldType'=>'checkbox', 'tl_class'=>'clr']
        ,   'relation'          => ['type'=>'hasMany', 'load'=>'lazy']
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
        ,   'options_callback'  => ['\numero2\MarketingSuite\DCAHelper\ConversionItem', 'getConversionElements']
        ,   'eval'              => ['mandatory'=>true, 'chosen'=>'true', 'inlcudeBlankOption'=>true, 'tl_class'=>'clr w50 wizard']
        ,   'wizard'            => [['\numero2\MarketingSuite\DCAHelper\ConversionItem','conversionItemWizard']]
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
    ,   'cms_ci_reset' => [
            'sql'               => "int(10) unsigned NOT NULL default '0'"
        ]
    ,   'cms_used' => [
            'label'             => &$GLOBALS['TL_LANG']['tl_content']['cms_used']
        ]
    ,   'cms_tag_visibility' => [
            'label'             => &$GLOBALS['TL_LANG']['tl_content']['cms_tag_visibility']
        ,   'inputType'         => 'checkbox'
        ,   'eval'              => ['submitOnChange'=>true]
        ,   'sql'               => "char(1) NOT NULL default ''"
        ]
    ,   'cms_tag' => [
            'label'             => &$GLOBALS['TL_LANG']['tl_content']['cms_tag']
        ,   'inputType'         => 'select'
        ,   'options_callback'  => ['\numero2\MarketingSuite\DCAHelper\Content', 'getContentElementTags']
        ,   'eval'              => ['mandatory'=>true, 'chosen'=>true, 'includeBlankOption'=>true, 'tl_class'=>'clr w50']
        ,   'sql'               => "int(10) unsigned NOT NULL default '0'"
        ]
    ,   'cms_layout_option' => [
            'label'             => &$GLOBALS['TL_LANG']['tl_content']['cms_layout_option']
        ,   'inputType'         => 'select'
        ,   'options_callback'  => ['\numero2\MarketingSuite\DCAHelper\Content', 'getLayoutOptions']
        ,   'eval'              => ['mandatory'=>true, 'submitOnChange'=>true, 'includeBlankOption'=>true, 'tl_class'=>'clr w50']
        ,   'sql'               => "varchar(64) NOT NULL default ''"
        ]
    ,   'cms_lifetime' => [
            'label'            => &$GLOBALS['TL_LANG']['tl_content']['cms_lifetime']
        ,   'inputType'        => 'inputUnit'
        ,   'options'          => ['days', 'weeks', 'months', 'years']
        ,   'reference'        => &$GLOBALS['TL_LANG']['tl_content']['cms_lifetime_options']
        ,   'eval'             => ['tl_class'=>'w50']
        ,   'sql'              => "varchar(64) NOT NULL default ''"
        ]
    ,   'cms_pages_scope' => [
            'label'             => &$GLOBALS['TL_LANG']['tl_content']['cms_pages_scope']
        ,   'inputType'         => 'radio'
        ,   'default'           => 'none'
        ,   'options_callback'  => ['\numero2\MarketingSuite\DCAHelper\Content', 'getPageScopes']
        ,   'eval'              => ['tl_class'=>'clr w50 no-height']
        ,   'sql'               => "varchar(64) NOT NULL default 'none'"
        ]
    ,   'cms_pages' => [
            'label'             => &$GLOBALS['TL_LANG']['tl_content']['cms_pages']
        ,   'inputType'         => 'pageTree'
        ,   'foreignKey'        => 'tl_page.title'
        ,   'save_callback'     => [ ['\numero2\MarketingSuite\DCAHelper\Content', 'sanityCheckPageScopeWithPages'] ]
        ,   'eval'              => ['multiple'=>true, 'fieldType'=>'checkbox', 'tl_class'=>'clr']
        ,   'relation'          => ['type'=>'hasMany', 'load'=>'lazy']
        ,   'sql'               => "text NULL"
        ]
    ]
);

$GLOBALS['TL_DCA']['tl_content']['fields']['text']['save_callback'][] = ['\numero2\MarketingSuite\DCAHelper\TextCMS', 'saveContentToOriginalField'];