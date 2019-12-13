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
 * Table tl_cms_tag
 */
$GLOBALS['TL_DCA']['tl_cms_tag'] = [

    'config' => [
        'label'                     => Config::get('websiteTitle')
    ,   'dataContainer'             => 'Table'
    ,   'backlink'                  => 'do=cms_settings'
    ,   'isAvailable'               => \numero2\MarketingSuite\Backend\License::hasFeature('tags')
    ,   'onload_callback'           => [
            ['\numero2\MarketingSuite\DCAHelper\Tag', 'setRootType']
        ,   ['\numero2\MarketingSuite\DCAHelper\Tag', 'addDefault']
        ,   ['\numero2\MarketingSuite\DCAHelper\Tag', 'setTagFieldLabel']
        ,   ['\numero2\MarketingSuite\DCAHelper\Tag', 'unsetEnableOnCookieAcceptForSession']
    ]
    ,   'sql' => [
            'keys' => [
                'id' => 'primary'
            ]
        ]
    ]
,   'list' => [
        'sorting' => [
            'mode'                  => 5
        ,   'icon'                  => 'pagemounts.svg'
        ,   'paste_button_callback' => ['\numero2\MarketingSuite\DCAHelper\Tag', 'pasteTag']
        ,   'panelLayout'           => 'search,limit;filter'
        ]
    ,   'label' => [
            'fields'                => ['name']
        ,   'format'                => '%s'
        ,   'label_callback'        => ['\numero2\MarketingSuite\DCAHelper\Tag', 'getLabel']
        ]
    ,   'global_operations' => [
            'frontend' => [
                'label'             => &$GLOBALS['TL_LANG']['tl_cms_tag']['frontend']
            ,   'href'              => 'table=tl_cms_tag_settings'
            ,   'icon'              => 'modules.svg'
            ,   'attributes'        => 'onclick="Backend.getScrollOffset()"'
            ]
        ,   'all' => [
                'label'             => &$GLOBALS['TL_LANG']['MSC']['all']
            ,   'href'              => 'act=select'
            ,   'class'             => 'header_edit_all'
            ,   'attributes'        => 'onclick="Backend.getScrollOffset()" accesskey="e"'
            ]
        ]
    ,   'operations' => [
            'edit' => [
                'label'             => &$GLOBALS['TL_LANG']['tl_cms_tag']['edit']
            ,   'href'              => 'act=edit'
            ,   'icon'              => 'edit.gif'
            ]
        ,   'cut' => [
                'label'             => &$GLOBALS['TL_LANG']['tl_cms_tag']['cut']
            ,   'href'              => 'act=paste&amp;mode=cut'
            ,   'icon'              => 'cut.svg'
            ,   'attributes'        => 'onclick="Backend.getScrollOffset()"'
            ,   'button_callback'   => ['\numero2\MarketingSuite\DCAHelper\Tag', 'cutTag']
            ]
        ,   'delete' => [
                'label'             => &$GLOBALS['TL_LANG']['tl_cms_tag']['delete']
            ,   'href'              => 'act=delete'
            ,   'icon'              => 'delete.gif'
            ,   'attributes'        => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"'
            ]
        ,   'toggle' =>[
                'label'             => &$GLOBALS['TL_LANG']['tl_cms_tag']['toggle']
            ,   'icon'              => 'visible.svg'
            ,   'attributes'        => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleVisibility(this,%s)"'
            ,   'button_callback'   => ['\numero2\MarketingSuite\DCAHelper\Tag', 'toggleIcon']
            ]
        ]
    ]
,   'palettes' => [
        '__selector__'              => ['type']
    ,   'default'                   => '{common_legend},type,name'
    ,   'group'                     => '{common_legend},type,name;{description_legend},description;{expert_legend:hide},customTpl'
    ,   'session'                   => '{common_legend},type,name;{publish_legend},active'
    ,   'html'                      => '{common_legend},type,name;{tag_legend},html;{expert_legend:hide},customTpl;{publish_legend},pages_scope,pages,active,enable_on_cookie_accept'
    ,   'google_analytics'          => '{common_legend},type,name;{tag_legend},tag,alias;{config_legend},anonymize_ip;{expert_legend:hide},customTpl;{publish_legend},pages_scope,pages,active,enable_on_cookie_accept'
    ,   'google_tag_manager'        => '{common_legend},type,name;{tag_legend},tag;{expert_legend:hide},customTpl;{publish_legend},pages_scope,pages,active,enable_on_cookie_accept'
    ,   'facebook_pixel'            => '{common_legend},type,name;{tag_legend},tag;{expert_legend:hide},customTpl;{publish_legend},pages_scope,pages,active,enable_on_cookie_accept'
    ,   'content_module_element'    => '{common_legend},type,name;{tag_legend},fallbackTpl,fallback_text;{publish_legend},active'
    ,   'matomo'                    => '{common_legend},type,name;{tag_legend},matomo_url,matomo_siteid;{expert_legend:hide},customTpl;{publish_legend},pages_scope,pages,active,enable_on_cookie_accept'
    ]
,   'fields' => [
        'id' => [
            'sql'         => "int(10) unsigned NOT NULL auto_increment"
        ]
    ,   'pid' => [
            'sql'         => "int(10) unsigned NOT NULL default '0'"
        ]
    ,   'sorting' => [
            'sql'         => "int(10) unsigned NOT NULL default '0'"
        ]
    ,   'tstamp' => [
            'sql'         => "int(10) unsigned NOT NULL default '0'"
        ]
    ,   'type' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_tag']['type']
        ,   'inputType'             => 'select'
        ,   'filter'                => true
        ,   'options_callback'      => [ '\numero2\MarketingSuite\DCAHelper\Tag', 'getTagTypes' ]
        ,   'reference'             => &$GLOBALS['TL_LANG']['tl_cms_tag']['types']
        ,   'eval'                  => ['mandatory'=>true, 'maxlength'=>32, 'chosen'=>true, 'submitOnChange'=>true, 'tl_class'=>'w50']
        ,   'sql'                   => "varchar(32) NOT NULL default ''"
        ]
    ,   'name' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_tag']['name']
        ,   'inputType'             => 'text'
        ,   'search'                => true
        ,   'eval'                  => ['mandatory'=>true, 'maxlength'=>64, 'tl_class'=>'w50']
        ,   'sql'                   => "varchar(64) NOT NULL default ''"
        ]
    ,   'description' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_tag']['description']
        ,   'inputType'             => 'textarea'
        ,   'eval'                  => ['rte'=>'tinyMarketing', 'helpwizard'=>true, 'doNotSaveEmpty'=>true]
        ,   'sql'                   => "text NULL"
    ]
    ,   'html' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_tag']['html']
        ,   'inputType'             => 'textarea'
        ,   'eval'                  => ['mandatory'=>true, 'preserveTags'=>true, 'class'=>'monospace', 'rte'=>'ace|html', 'tl_class'=>'clr']
        ,   'sql'                   => "text NULL"
        ]
    ,   'tag' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_tag']['tag']
        ,   'inputType'             => 'text'
        ,   'search'                => true
        ,   'eval'                  => ['mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50']
        ,   'sql'                   => "varchar(255) NOT NULL default ''"
        ]
    ,   'matomo_url' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_tag']['matomo_url']
        ,   'inputType'             => 'text'
        ,   'eval'                  => ['mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50', 'helpwizard'=>true]
        ,   'explanation'           => 'matomoFields'
        ,   'sql'                   => "varchar(255) NOT NULL default ''"
        ]
    ,   'matomo_siteid' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_tag']['matomo_siteid']
        ,   'inputType'             => 'text'
        ,   'eval'                  => ['mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50', 'helpwizard'=>true]
        ,   'explanation'           => 'matomoFields'
        ,   'sql'                   => "varchar(255) NOT NULL default ''"
        ]
    ,   'anonymize_ip' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_tag']['anonymize_ip']
        ,   'inputType'             => 'checkbox'
        ,   'eval'                  => ['tl_class'=>'w50']
        ,   'default'               => "1"
        ,   'sql'                   => "char(1) NOT NULL default '1'"
        ]
    ,   'alias' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_tag']['alias']
        ,   'inputType'             => 'text'
        ,   'eval'                  => ['rgxp'=>'alnum', 'maxlength'=>32, 'tl_class'=>'w50']
        ,   'save_callback'         => [ ['\numero2\MarketingSuite\DCAHelper\Tag', 'generateAlias'] ]
        ,   'sql'                   => "varchar(32) NOT NULL default ''"
        ]
    ,   'fallbackTpl' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_tag']['fallbackTpl']
        ,   'inputType'             => 'select'
        ,   'options_callback'      => ['\numero2\MarketingSuite\DCAHelper\Tag', 'getFallbackTemplates']
        ,   'eval'                  => ['mandatory'=>false, 'includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50']
        ,   'sql'                   => "varchar(64) NOT NULL default ''"
        ]
    ,   'fallback_text' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_tag']['fallback_text']
        ,   'inputType'             => 'textarea'
        ,   'eval'                  => ['mandatory'=>false, 'rte'=>'tinyMarketing', 'tl_class'=>'clr']
        ,   'sql'                   => "text NULL"
        ]
    ,   'customTpl' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_tag']['customTpl']
        ,   'inputType'             => 'select'
        ,   'options_callback'      => ['\numero2\MarketingSuite\DCAHelper\Tag', 'getModuleTemplates']
        ,   'eval'                  => ['includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50']
        ,   'sql'                   => "varchar(64) NOT NULL default ''"
        ]
    ,   'pages_scope' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_tag']['pages_scope']
        ,   'inputType'             => 'radio'
        ,   'default'               => 'current_and_all_children'
        ,   'options_callback'      => ['\numero2\MarketingSuite\DCAHelper\Tag', 'getPageScopes']
        ,   'eval'                  => ['tl_class'=>'clr w50 no-height']
        ,   'sql'                   => "varchar(64) NOT NULL default ''"
        ]
    ,   'pages' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_tag']['pages']
        ,   'inputType'             => 'pageTree'
        ,   'foreignKey'            => 'tl_page.title'
        ,   'save_callback'         => [ ['\numero2\MarketingSuite\DCAHelper\Tag', 'sanityCheckPageScopeWithPages'] ]
        ,   'eval'                  => ['mandatory'=>true, 'multiple'=>true, 'fieldType'=>'checkbox', 'orderField'=>'orderPages', 'tl_class'=>'clr']
        ,   'relation'              => ['type'=>'hasMany', 'load'=>'lazy']
        ,   'sql'                   => "text NULL"
        ]
    ,    'orderPages' => [
            'eval'                  => ['doNotShow'=>true]
        ,   'sql'                   => "text NULL"
        ]
    ,   'active' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_tag']['active']
        ,   'inputType'             => 'checkbox'
        ,   'eval'                  => ['tl_class'=>'w50']
        ,   'sql'                   => "char(1) NOT NULL default ''"
        ]
    ,   'enable_on_cookie_accept' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_tag']['enable_on_cookie_accept']
        ,   'inputType'             => 'checkbox'
        ,   'eval'                  => ['tl_class'=>'w50']
        ,   'default'               => "1"
        ,   'sql'                   => "char(1) NOT NULL default '1'"
        ]
    ]
];
