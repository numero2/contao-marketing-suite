<?php

/**
 * Contao Marketing Suite Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


use Contao\DC_Table;


/**
 * Table tl_cms_link_shortener
 */
$GLOBALS['TL_DCA']['tl_cms_link_shortener'] = [

    'config' => [
        'dataContainer'             => DC_Table::class
    ,   'ctable'                    => ['tl_cms_link_shortener_statistics']
    ,   'isAvailable'               => \numero2\MarketingSuite\Backend\License::hasFeature('link_shortener')
    ,   'sql' => [
            'keys' => [
                'id' => 'primary'
            ,   'prefix' => 'index'
            ,   'alias' => 'index'
            ]
        ]
    ]
,   'list' => [
        'sorting' => [
            'mode'                  => 1
        ,   'flag'                  => 11
        ,   'fields'                => ['group_name']
        ,   'panelLayout'           => 'search,limit;filter'
        ]
    ,   'label' => [
            'fields'                => ['target', 'prefix', 'alias']
        ]
    ,   'global_operations' => [
            'all' => [
                'label'             => &$GLOBALS['TL_LANG']['MSC']['all']
            ,   'href'              => 'act=select'
            ,   'class'             => 'header_edit_all'
            ,   'attributes'        => 'onclick="Backend.getScrollOffset()" accesskey="e"'
            ]
        ]
    ,   'operations' => [
            'edit' => [
                'label'             => &$GLOBALS['TL_LANG']['tl_cms_link_shortener']['edit']
            ,   'href'              => 'act=edit'
            ,   'icon'              => 'edit.gif'
            ]
        ,   'statistics' => [
                'label'             => &$GLOBALS['TL_LANG']['tl_cms_link_shortener']['statistics']
            ,   'href'              => 'key=link_shortener_statistics'
            ,   'icon'              => 'bundles/marketingsuite/img/backend/icons/icon_statistics.svg'
            ]
        ,   'reset_counter' => [
                'label'             => &$GLOBALS['TL_LANG']['tl_cms_link_shortener']['reset_counter']
            ,   'icon'              => 'bundles/marketingsuite/img/backend/icons/icon_reset_counter.svg'
            ,   'attributes'        => 'data-action="contao--scroll-offset#store" onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['reset_warning'] ?? '') . '\'))return false"'
            ]
        ,   'delete' => [
                'label'             => &$GLOBALS['TL_LANG']['tl_cms_link_shortener']['delete']
            ,   'href'              => 'act=delete'
            ,   'icon'              => 'delete.gif'
            ,   'attributes'        => 'data-action="contao--scroll-offset#store" onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? '') . '\'))return false"'
            ]
        ,   'toggle' =>[
                'label'             => &$GLOBALS['TL_LANG']['tl_cms_link_shortener']['toggle']
            ,   'href'              => 'act=toggle&amp;field=active'
            ,   'icon'              => 'visible.svg'
            ]
        ]
    ]
,   'palettes' => [
        '__selector__'              => ['type']
    ,   'default'                   => '{common_legend},target,group_name;{link_legend},domain,prefix,prefix_preview,alias,alias_preview;{description_legend},description;{publish_legend},active,stop,fallback'
    ]
,   'fields' => [
        'id' => [
            'sql'         => "int(10) unsigned NOT NULL auto_increment"
        ]
    ,   'tstamp' => [
            'sql'         => "int(10) unsigned NOT NULL default '0'"
        ]
    ,   'target' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_link_shortener']['target']
        ,   'inputType'             => 'text'
        ,   'search'                => true
        ,   'eval'                  => ['mandatory'=>true, 'rgxp'=>'cms_url', 'decodeEntities'=>true, 'maxlength'=>255, 'dcaPicker'=>true, 'addWizardClass'=>false, 'tl_class'=>'w50']
        ,   'sql'                   => "varchar(255) NOT NULL default ''"
        ]
    ,   'group_name' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_link_shortener']['group_name']
        ,   'inputType'             => 'text'
        ,   'filter'                => true
        ,   'wizard'                => [['numero2\MarketingSuite\Widget\SuggestWizard', 'generate']]
        ,   'eval'                  => ['maxlength'=>64, 'tl_class'=>'w50 suggest', 'autocomplete'=>'off']
        ,   'sql'                   => "varchar(64) NOT NULL default ''"
        ]
    ,   'domain' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_link_shortener']['domain']
        ,   'inputType'             => 'radio'
        ,   'filter'                => true
        ,   'eval'                  => ['mandatory'=>true, 'tl_class'=>'clr']
        ,   'sql'                   => "varchar(255) NOT NULL default ''"
        ]
    ,   'prefix' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_link_shortener']['prefix']
        ,   'inputType'             => 'text'
        ,   'search'                => true
        ,   'eval'                  => ['maxlength'=>255, 'tl_class'=>'w50']
        ,   'sql'                   => "varchar(255) NOT NULL default ''"
        ]
    ,   'prefix_preview' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_link_shortener']['prefix_preview']
        ,   'input_field_callback'  => ['\numero2\MarketingSuite\Widget\LinkPreview', 'generate']
        ]
    ,   'alias' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_link_shortener']['alias']
        ,   'inputType'             => 'text'
        ,   'search'                => true
        ,   'eval'                  => ['maxlength'=>255, 'tl_class'=>'w50']
        ,   'sql'                   => "varchar(255) NOT NULL default ''"
        ]
    ,   'alias_preview' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_link_shortener']['alias_preview']
        ,   'input_field_callback'  => ['\numero2\MarketingSuite\Widget\LinkPreview', 'generate']
        ]
    ,   'description' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_link_shortener']['description']
        ,   'inputType'             => 'textarea'
        ,   'eval'                  => ['tl_class'=>'clr']
        ,   'sql'                   => "text NULL"
        ]
    ,   'active' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_link_shortener']['active']
        ,   'inputType'             => 'checkbox'
        ,   'filter'                => true
        ,   'toggle'                => true
        ,   'eval'                  => ['tl_class'=>'w50']
        ,   'sql'                   => "char(1) NOT NULL default ''"
        ]
    ,   'stop' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_link_shortener']['stop']
        ,   'inputType'             => 'text'
        ,   'eval'                  => ['rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'clr w50 wizard']
        ,   'sql'                   => "varchar(10) NOT NULL default ''"
    ]
    ,   'fallback' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_link_shortener']['fallback']
        ,   'inputType'             => 'text'
        ,   'search'                => true
        ,   'eval'                  => ['rgxp'=>'cms_url', 'decodeEntities'=>true, 'maxlength'=>255, 'dcaPicker'=>true, 'addWizardClass'=>false, 'tl_class'=>'clr w50']
        ,   'sql'                   => "varchar(255) NOT NULL default ''"
        ]
    ]
];
