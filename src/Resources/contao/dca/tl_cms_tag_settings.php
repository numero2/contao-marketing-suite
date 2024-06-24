<?php

/**
 * Contao Marketing Suite Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


use Contao\DC_CMSFile;
use numero2\MarketingSuite\Backend\License as klrjgdg;


/**
 * Table tl_cms_tag_settings
 */
$GLOBALS['TL_DCA']['tl_cms_tag_settings'] = [

    'config' => [
        'dataContainer'             => DC_CMSFile::class
    ,   'isAvailable'               => klrjgdg::hasFeature('tag_settings')
    ,   'closed'                    => true
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
    ,   'cms_tag_set_style' => 'cms_layout_selector'
    ]
,   'fields' => [
        'cms_tag_type' => [
            'default'          => 'default'
        ,   'inputType'        => 'select'
        ,   'reference'        => &$GLOBALS['TL_LANG']['FMD']
        ,   'eval'             => ['chosen'=>true, 'submitOnChange'=>true, 'tl_class'=>'w50']
        ,   'mapping'          => 'type'
        ]
    ,   'cms_tag_override_label' => [
            'inputType'        => 'checkbox'
        ,   'eval'             => ['tl_class'=>'clr', 'submitOnChange'=>true]
        ,   'sql'               => "char(1) NOT NULL default ''"
        ]
    ,   'cms_tag_accept_label' => [
            'inputType'        => 'text'
        ,   'eval'             => ['mandatory'=>true, 'tl_class'=>'w50']
        ,   'sql'              => "varchar(255) NOT NULL default ''"
        ]
    ,   'cms_tag_accept_all_label' => [
            'inputType'        => 'text'
        ,   'eval'             => ['mandatory'=>false, 'tl_class'=>'w50']
        ,   'sql'              => "varchar(255) NOT NULL default ''"
        ]
    ,   'cms_tag_reject_label' => [
            'inputType'        => 'text'
        ,   'eval'             => ['tl_class'=>'clr w50']
        ,   'sql'              => "varchar(255) NOT NULL default ''"
        ]
    ,   'cms_tag_text' => [
            'inputType'        => 'textarea'
        ,   'eval'             => ['mandatory'=>true, 'rte'=>'tinyMarketing', 'tl_class'=>'clr', 'helpwizard'=>true, 'allowHtml'=>true]
        ,   'explanation'      => 'tagDescription'
        ,   'sql'              => "mediumtext NULL"
        ]
    ,   'cms_tag_customTpl' => [
            'inputType'        => 'select'
        ,   'eval'             => ['includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50']
        ,   'mapping'          => 'customTpl'
        ]
    ,   'cms_tag_cssID' => [
            'inputType'        => 'text'
        ,   'eval'             => ['multiple'=>true, 'size'=>2, 'tl_class'=>'w50']
        ,   'mapping'          => 'cssID'
        ]
    ,   'cms_tag_set_style' => [
            'inputType'        => 'checkbox'
        ,   'eval'             => ['tl_class'=>'clr', 'submitOnChange'=>true]
        ,   'sql'              => "char(1) NOT NULL default '1'"
        ]
    ,   'cms_layout_selector' => [
            'inputType'        => 'cmsLayoutSelector'
        ,   'options'          => []
        ,   'default'          => 'light'
        ,   'reference'        => &$GLOBALS['TL_LANG']['tl_cms_tag_settings']['cms_layout_selector_options']
        ,   'explanation'      => 'layoutSelector'
        ,   'eval'             => ['sprite'=>'', 'helpwizard'=>true, 'tl_class'=>'clr']
        ,   'sql'              => "varchar(64) NOT NULL default 'light'"
        ]
    ,   'cms_exclude_pages' => [
            'inputType'        => 'pageTree'
        ,   'foreignKey'       => 'tl_page.title'
        ,   'eval'             => ['fieldType'=>'checkbox', 'multiple'=>true, 'tl_class'=>'w50']
        ,   'sql'              => "blob NULL"
        ]
    ,   'cms_tag_cookie_lifetime' => [
            'inputType'        => 'inputUnit'
        ,   'options'          => ['days', 'weeks', 'months', 'years']
        ,   'reference'        => &$GLOBALS['TL_LANG']['tl_cms_tag_settings']['cms_tag_cookie_lifetime_options']
        ,   'eval'             => ['tl_class'=>'w50', 'disabled'=>!klrjgdg::hasFeature('tags_cookie_lifetime')]
        ,   'sql'              => "varchar(64) NOT NULL default ''"
        ]
    ,   'cms_tag_accept_subdomains' => [
            'inputType'        => 'checkbox'
        ,   'eval'             => ['tl_class'=>'clr w50', 'disabled'=>!klrjgdg::hasFeature('tags_accept_subdomains')]
        ,   'sql'               => "char(1) NOT NULL default ''"
        ]
    ]
];
