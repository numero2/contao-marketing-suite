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


/**
 * Table tl_cms_tag_settings
 */
$GLOBALS['TL_DCA']['tl_cms_tag_settings'] = [

    'config' => [
        'dataContainer'             => 'CMSFile'
    ,   'isAvailable'               => \numero2\MarketingSuite\Backend\License::hasFeature('tag_settings')
    ,   'closed'                    => true
    ,   'onload_callback'           => [
            ['\numero2\MarketingSuite\DCAHelper\TagSettings', 'modifyPalettes']
        ]
    ]
,   'palettes' => [
        '__selector__' => ['cms_tag_type', 'cms_tag_override_label', 'cms_tag_set_style']
    ,   'default' => "{title_legend},cms_tag_type"
    ,   'cms_cookie_bar' => "{title_legend},cms_tag_type;{config_legend:hide},cms_tag_override_label,cms_tag_reject_label,cms_exclude_pages,cms_tag_cookie_lifetime,cms_tag_accept_subdomains;{style_legend:hide},cms_tag_set_style;{template_legend:hide},cms_tag_customTpl;{expert_legend:hide},cms_tag_cssID"
    ,   'cms_accept_tags' => "{title_legend},cms_tag_type;{config_legend:hide},cms_tag_override_label,cms_exclude_pages,cms_tag_cookie_lifetime,cms_tag_accept_subdomains;{style_legend:hide},cms_tag_set_style;{template_legend:hide},cms_tag_customTpl;{expert_legend:hide},cms_tag_cssID"
    ,   'cms_tag_modules' => "{title_legend},cms_tag_type"
    ]
,   'subpalettes' => [
        'cms_tag_override_label' => 'cms_tag_accept_label,cms_tag_accept_all_label,cms_tag_text'
    ,   'cms_tag_set_style' => 'cms_tag_font_color,cms_tag_background_color,cms_tag_accept_font,cms_tag_accept_background,cms_tag_reject_font,cms_tag_reject_background'
    ]
,   'fields' => [
        'cms_tag_type' => [
            'label'            => &$GLOBALS['TL_LANG']['tl_cms_tag_settings']['cms_tag_type']
        ,   'default'          => 'default'
        ,   'inputType'        => 'select'
        ,   'options_callback' => [ '\numero2\MarketingSuite\DCAHelper\TagSettings', 'getFrontendTypes']
        ,   'reference'        => &$GLOBALS['TL_LANG']['FMD']
        ,   'eval'             => [ 'chosen'=>true, 'submitOnChange'=>true, 'tl_class'=>'w50' ]
        ,   'mapping'          => 'type'
        ]
    ,   'cms_tag_override_label' => [
            'label'            => &$GLOBALS['TL_LANG']['tl_cms_tag_settings']['cms_tag_override_label']
        ,   'inputType'        => 'checkbox'
        ,   'eval'             => [ 'tl_class'=>'clr', 'submitOnChange'=>true ]
        ,   'sql'               => "char(1) NOT NULL default ''"
        ]
    ,   'cms_tag_accept_label' => [
            'label'            => &$GLOBALS['TL_LANG']['tl_cms_tag_settings']['cms_tag_accept_label']
        ,   'inputType'        => 'text'
        ,   'eval'             => [ 'mandatory'=>true, 'tl_class'=>'w50' ]
        ,   'sql'              => "varchar(255) NOT NULL default ''"
        ]
    ,   'cms_tag_accept_all_label' => [
            'label'            => &$GLOBALS['TL_LANG']['tl_cms_tag_settings']['cms_tag_accept_all_label']
        ,   'inputType'        => 'text'
        ,   'eval'             => [ 'mandatory'=>false, 'tl_class'=>'w50' ]
        ,   'sql'              => "varchar(255) NOT NULL default ''"
        ]
    ,   'cms_tag_reject_label' => [
            'label'            => &$GLOBALS['TL_LANG']['tl_cms_tag_settings']['cms_tag_reject_label']
        ,   'inputType'        => 'text'
        ,   'eval'             => [ 'tl_class'=>'clr w50' ]
        ,   'sql'              => "varchar(255) NOT NULL default ''"
        ]
    ,   'cms_tag_text' => [
            'label'            => &$GLOBALS['TL_LANG']['tl_cms_tag_settings']['cms_tag_text']
        ,   'inputType'        => 'textarea'
        ,   'eval'             => [ 'mandatory'=>true, 'rte'=>'tinyMarketing', 'tl_class'=>'clr', 'helpwizard'=>true]
        ,   'explanation'      => 'tagDescription'
        ,   'sql'              => "mediumtext NULL"
        ]
    ,   'cms_tag_customTpl' => [
            'label'            => &$GLOBALS['TL_LANG']['tl_cms_tag_settings']['cms_tag_customTpl']
        ,   'inputType'        => 'select'
        ,   'options_callback' => [ '\numero2\MarketingSuite\DCAHelper\TagSettings', 'getModuleTemplates' ]
        ,   'eval'             => [ 'includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50' ]
        ,   'mapping'          => 'customTpl'
        ]
    ,   'cms_tag_cssID' => [
            'label'            => &$GLOBALS['TL_LANG']['tl_cms_tag_settings']['cms_tag_cssID']
        ,   'inputType'        => 'text'
        ,   'eval'             => [ 'multiple'=>true, 'size'=>2, 'tl_class'=>'w50' ]
        ,   'mapping'          => 'cssID'
        ]
    ,   'cms_tag_set_style' => [
            'label'            => &$GLOBALS['TL_LANG']['tl_cms_tag_settings']['cms_tag_set_style']
        ,   'inputType'        => 'checkbox'
        ,   'eval'             => [ 'tl_class'=>'clr', 'submitOnChange'=>true ]
        ,   'sql'              => "char(1) NOT NULL default '1'"
        ]
    ,   'cms_tag_font_color' => [
            'label'            => &$GLOBALS['TL_LANG']['tl_cms_tag_settings']['cms_tag_font_color']
        ,   'inputType'        => 'text'
        ,   'eval'             => [ 'maxlength'=>6, 'colorpicker'=>true, 'isHexColor'=>true, 'decodeEntities'=>true, 'tl_class'=>'w50' ]
        ,   'sql'              => "varchar(64) NOT NULL default ''"
        ]
    ,   'cms_tag_background_color' => [
            'label'            => &$GLOBALS['TL_LANG']['tl_cms_tag_settings']['cms_tag_background_color']
        ,   'inputType'        => 'text'
        ,   'eval'             => [ 'maxlength'=>6, 'colorpicker'=>true, 'isHexColor'=>true, 'decodeEntities'=>true, 'tl_class'=>'w50' ]
        ,   'sql'              => "varchar(64) NOT NULL default ''"
        ]
    ,   'cms_tag_accept_font' => [
            'label'            => &$GLOBALS['TL_LANG']['tl_cms_tag_settings']['cms_tag_accept_font']
        ,   'inputType'        => 'text'
        ,   'eval'             => [ 'maxlength'=>6, 'colorpicker'=>true, 'isHexColor'=>true, 'decodeEntities'=>true, 'tl_class'=>'w50' ]
        ,   'sql'              => "varchar(64) NOT NULL default ''"
        ]
    ,   'cms_tag_accept_background' => [
            'label'            => &$GLOBALS['TL_LANG']['tl_cms_tag_settings']['cms_tag_accept_background']
        ,   'inputType'        => 'text'
        ,   'eval'             => [ 'maxlength'=>6, 'colorpicker'=>true, 'isHexColor'=>true, 'decodeEntities'=>true, 'tl_class'=>'w50' ]
        ,   'sql'              => "varchar(64) NOT NULL default ''"
        ]
    ,   'cms_tag_reject_font' => [
            'label'            => &$GLOBALS['TL_LANG']['tl_cms_tag_settings']['cms_tag_reject_font']
        ,   'inputType'        => 'text'
        ,   'eval'             => [ 'maxlength'=>6, 'colorpicker'=>true, 'isHexColor'=>true, 'decodeEntities'=>true, 'tl_class'=>'w50' ]
        ,   'sql'              => "varchar(64) NOT NULL default ''"
        ]
    ,   'cms_tag_reject_background' => [
            'label'            => &$GLOBALS['TL_LANG']['tl_cms_tag_settings']['cms_tag_reject_background']
        ,   'inputType'        => 'text'
        ,   'eval'             => [ 'maxlength'=>6, 'colorpicker'=>true, 'isHexColor'=>true, 'decodeEntities'=>true, 'tl_class'=>'w50' ]
        ,   'sql'              => "varchar(64) NOT NULL default ''"
        ]
    ,   'cms_exclude_pages' => [
            'label'            => &$GLOBALS['TL_LANG']['tl_cms_tag_settings']['cms_exclude_pages']
        ,   'inputType'        => 'pageTree'
        ,   'foreignKey'       => 'tl_page.title'
        ,   'eval'             => [ 'fieldType'=>'checkbox', 'multiple'=>true, 'tl_class'=>'w50' ]
        ,   'sql'              => "blob NULL"
        ]
    ,   'cms_tag_cookie_lifetime' => [
            'label'            => &$GLOBALS['TL_LANG']['tl_cms_tag_settings']['cms_tag_cookie_lifetime']
        ,   'inputType'        => 'inputUnit'
        ,   'options'          => ['days','weeks','months','years']
        ,   'reference'        => &$GLOBALS['TL_LANG']['tl_cms_tag_settings']['cms_tag_cookie_lifetime_options']
        ,   'eval'             => [ 'tl_class'=>'w50', 'disabled'=>!\numero2\MarketingSuite\Backend\License::hasFeature('tags_cookie_lifetime') ]
        ,   'sql'              => "varchar(64) NOT NULL default ''"
        ]
    ,   'cms_tag_accept_subdomains' => [
            'label'            => &$GLOBALS['TL_LANG']['tl_cms_tag_settings']['cms_tag_accept_subdomains']
        ,   'inputType'        => 'checkbox'
        ,   'eval'             => [ 'tl_class'=>'clr w50', 'disabled'=>!\numero2\MarketingSuite\Backend\License::hasFeature('tags_accept_subdomains') ]
        ,   'sql'               => "char(1) NOT NULL default ''"
        ]
    ]
];
