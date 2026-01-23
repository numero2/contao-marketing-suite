<?php

/**
 * Contao Marketing Suite Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2026, numero2 - Agentur für digitales Marketing GbR
 */


use Contao\Config;
use Contao\DC_Table;
use Contao\DataContainer;


/**
 * Table tl_cms_tag
 */
$GLOBALS['TL_DCA']['tl_cms_tag'] = [

    'config' => [
        'label'                     => Config::get('websiteTitle')
    ,   'dataContainer'             => DC_Table::class
    ,   'isAvailable'               => numero2\MarketingSuite\Backend\License::hasFeature('tags')
    ,   'sql' => [
            'keys' => [
                'id' => 'primary'
            ]
        ]
    ]
,   'list' => [
        'sorting' => [
            'mode'                  => DataContainer::MODE_TREE
        ,   'rootPaste'             => false
        ,   'icon'                  => 'pagemounts.svg'
        ,   'panelLayout'           => 'search,limit;filter'
        ]
    ,   'label' => [
            'fields'                => ['name']
        ,   'format'                => '%s'
        ]
    ,   'global_operations' => [
            'toggleNodes'
        ,   'frontend' => [
                'label'             => &$GLOBALS['TL_LANG']['tl_cms_tag']['frontend']
            ,   'href'              => 'table=tl_cms_tag_settings'
            ,   'icon'              => 'modules.svg'
            ,   'class'             => ''
            ,   'attributes'        => 'data-action="contao--scroll-offset#store"'
            ]
        ,   'all'
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
            ,   'attributes'        => 'data-action="contao--scroll-offset#store"'
            ]
        ,   'delete' => [
                'label'             => &$GLOBALS['TL_LANG']['tl_cms_tag']['delete']
            ,   'href'              => 'act=delete'
            ,   'icon'              => 'delete.gif'
            ,   'attributes'        => 'data-action="contao--scroll-offset#store" onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? '') . '\'))return false"'
            ]
        ,   'toggle' =>[
                'label'             => &$GLOBALS['TL_LANG']['tl_cms_tag']['toggle']
            ,   'href'              => 'act=toggle&amp;field=active'
            ,   'icon'              => 'visible.svg'
            ]
        ]
    ]
,   'palettes' => [
        '__selector__'              => ['type']
    ,   'default'                   => '{common_legend},type,name'
    ,   'group'                     => '{common_legend},type,root,name;{description_legend},description'
    ,   'session'                   => '{common_legend},type,name;{publish_legend},active'
    ,   'html'                      => '{common_legend},type,name;{tag_legend},html;{expert_legend:hide},customTpl;{publish_legend},pages_scope,pages,active,enable_on_cookie_accept'
    ,   'google_analytics'          => '{common_legend},type,name;{tag_legend},tag,alias;{config_legend},anonymize_ip;{expert_legend:hide},customTpl;{publish_legend},pages_scope,pages,active,enable_on_cookie_accept'
    ,   'google_analytics4'         => '{common_legend},type,name;{tag_legend},tag,alias;{config_legend},anonymize_ip;{expert_legend:hide},customTpl;{publish_legend},pages_scope,pages,active,enable_on_cookie_accept'
    ,   'google_tag_manager'        => '{common_legend},type,name;{tag_legend},tag;{expert_legend:hide},customTpl;{publish_legend},pages_scope,pages,active,enable_on_cookie_accept'
    ,   'facebook_pixel'            => '{common_legend},type,name;{tag_legend},tag;{expert_legend:hide},customTpl;{publish_legend},pages_scope,pages,active,enable_on_cookie_accept'
    ,   'content_module_element'    => '{common_legend},type,name;{tag_legend},fallbackTpl,fallback_text;{publish_legend},pages_root,active'
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
            'inputType'             => 'select'
        ,   'filter'                => true
        ,   'reference'             => &$GLOBALS['TL_LANG']['tl_cms_tag']['types']
        ,   'eval'                  => ['mandatory'=>true, 'maxlength'=>32, 'chosen'=>true, 'submitOnChange'=>true, 'tl_class'=>'w50']
        ,   'sql'                   => "varchar(32) NOT NULL default ''"
        ]
    ,   'name' => [
            'inputType'             => 'text'
        ,   'search'                => true
        ,   'eval'                  => ['mandatory'=>true, 'maxlength'=>64, 'tl_class'=>'w50']
        ,   'sql'                   => "varchar(64) NOT NULL default ''"
        ]
    ,   'root' => [
            'inputType'             => 'select'
        ,   'eval'                  => ['submitOnChange'=>true, 'tl_class'=>'w50']
        ,   'sql'                   => "int(10) unsigned NOT NULL default '0'"
        ]
    ,   'root_pid' => [
            'sql'                   => "int(10) unsigned NOT NULL default '0'"
        ]
    ,   'description' => [
            'inputType'             => 'textarea'
        ,   'eval'                  => ['rte'=>'tinyMarketing', 'doNotSaveEmpty'=>true, 'allowHtml'=>true]
        ,   'sql'                   => "text NULL"
        ]
    ,   'html' => [
            'inputType'             => 'textarea'
        ,   'eval'                  => ['mandatory'=>true, 'preserveTags'=>true, 'class'=>'monospace', 'rte'=>'ace|html', 'tl_class'=>'clr']
        ,   'sql'                   => "text NULL"
        ]
    ,   'tag' => [
            'inputType'             => 'text'
        ,   'search'                => true
        ,   'eval'                  => ['mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50']
        ,   'sql'                   => "varchar(255) NOT NULL default ''"
        ]
    ,   'matomo_url' => [
            'inputType'             => 'text'
        ,   'eval'                  => ['mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50', 'helpwizard'=>true]
        ,   'explanation'           => 'matomoFields'
        ,   'sql'                   => "varchar(255) NOT NULL default ''"
        ]
    ,   'matomo_siteid' => [
            'inputType'             => 'text'
        ,   'eval'                  => ['mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50', 'helpwizard'=>true]
        ,   'explanation'           => 'matomoFields'
        ,   'sql'                   => "varchar(255) NOT NULL default ''"
        ]
    ,   'anonymize_ip' => [
            'inputType'             => 'checkbox'
        ,   'eval'                  => ['tl_class'=>'w50']
        ,   'default'               => "1"
        ,   'sql'                   => "char(1) NOT NULL default '1'"
        ]
    ,   'alias' => [
            'inputType'             => 'text'
        ,   'eval'                  => ['rgxp'=>'alnum', 'maxlength'=>32, 'tl_class'=>'w50']
        ,   'sql'                   => "varchar(32) NOT NULL default ''"
        ]
    ,   'fallbackTpl' => [
            'inputType'             => 'select'
        ,   'eval'                  => ['mandatory'=>false, 'includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50']
        ,   'sql'                   => "varchar(64) NOT NULL default ''"
        ]
    ,   'fallback_text' => [
            'inputType'             => 'textarea'
        ,   'explanation'           => 'optinFallback'
        ,   'eval'                  => ['mandatory'=>false, 'rte'=>'tinyMarketing', 'helpwizard'=>true, 'tl_class'=>'clr', 'allowHtml'=>true]
        ,   'sql'                   => "text NULL"
        ]
    ,   'customTpl' => [
            'inputType'             => 'select'
        ,   'eval'                  => ['includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50']
        ,   'sql'                   => "varchar(64) NOT NULL default ''"
        ]
    ,   'pages_scope' => [
            'inputType'             => 'radio'
        ,   'default'               => 'current_and_all_children'
        ,   'eval'                  => ['tl_class'=>'clr w50 no-height']
        ,   'sql'                   => "varchar(64) NOT NULL default 'current_and_all_children'"
        ]
    ,   'pages' => [
            'inputType'             => 'pageTree'
        ,   'foreignKey'            => 'tl_page.title'
        ,   'eval'                  => ['mandatory'=>true, 'multiple'=>true, 'fieldType'=>'checkbox', 'tl_class'=>'clr']
        ,   'relation'              => ['table'=>'tl_page', 'type'=>'hasMany', 'load'=>'lazy']
        ,   'sql'                   => "text NULL"
        ]
    ,   'pages_root' => [
            'inputType'             => 'checkboxWizard'
        ,   'eval'                  => ['multiple'=>true, 'tl_class'=>'w50 clr']
        ,   'sql'                   => "text NULL"
        ]
    ,   'active' => [
            'inputType'             => 'checkbox'
        ,   'toggle'                => true
        ,   'eval'                  => ['tl_class'=>'clr w50']
        ,   'sql'                   => "char(1) NOT NULL default ''"
        ]
    ,   'enable_on_cookie_accept' => [
            'inputType'             => 'checkbox'
        ,   'eval'                  => ['tl_class'=>'w50']
        ,   'default'               => "1"
        ,   'sql'                   => "char(1) NOT NULL default '1'"
        ]
    ]
];
