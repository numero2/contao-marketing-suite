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
 * Table tl_cms_content_group
 */
$GLOBALS['TL_DCA']['tl_cms_content_group'] = [

    'config' => [
        'dataContainer'             => 'Table'
    ,   'ctable'                    => ['tl_content']
    ,   'ptable'                    => 'tl_cms_marketing_item'
    ,   'onsubmit_callback'         => [['\numero2\MarketingSuite\MarketingItem\ABTest', 'submitContentGroup']]
    ,   'onload_callback'           => [['\numero2\MarketingSuite\MarketingItem\ABTest', 'loadContentGroup']]
    ,   'closed'                    => true
    ,   'notDeletable'              => true
    ,   'notSortable'               => true
    ,   'notCreatable'              => true
    ,   'switchToEdit'              => true
    ,   'sql' => [
            'keys' => [
                'id' => 'primary'
            ]
        ]
    ]
,   'list' => [
        'sorting' => [
            'mode'                  => 4
        ,   'fields'                => ['id']
        ,   'disableGrouping'       => true
        ,   'panelLayout'           => 'cms_help;filter;search'
        ,   'panel_callback'        => [
                'cms_help' => ['\numero2\MarketingSuite\DCAHelper\ContentGroup', 'addHelp']
            ]
        ,   'headerFields'          => ['name', 'type', 'ranking']
        ,   'header_callback'       => ['\numero2\MarketingSuite\DCAHelper\ContentGroup', 'addHeaderInfo']
        ,   'child_record_callback' => ['\numero2\MarketingSuite\DCAHelper\ContentGroup', 'addCteType']
        ]
    ,   'operations' => [
            'edit' => [
                'label'             => &$GLOBALS['TL_LANG']['tl_cms_content_group']['edit']
            ,   'href'              => 'table=tl_content'
            ,   'icon'              => 'edit.gif'
            ]
        ,   'editheader' => [
                'label'             => &$GLOBALS['TL_LANG']['tl_cms_content_group']['editheader']
            ,   'href'              => 'act=edit'
            ,   'icon'              => 'header.svg'
            ]
        ,   'toggle_always_use_this' => [
                'label'             => &$GLOBALS['TL_LANG']['tl_cms_content_group']['toggle_always_use_this']
            ,   'icon'              => 'bundles/marketingsuite/img/backend/icons/icon_always_use_this.svg'
            ,   'attributes'        => 'onclick="Backend.getScrollOffset();return CMSBackend.toggleFieldReload(this,%s)"'
            ,   'button_callback'   => ['\numero2\MarketingSuite\DCAHelper\ContentGroup', 'toggleAlwaysUseThis']
            ]
        ]
    ]
,   'palettes' => [
        '__selector__'              => ['type']
    ,   'default'                   => '{common_legend},type,name'
    ,   'a_b_test'                  => '{common_legend},type,name'
    ]
,   'fields' => [
        'id' => [
            'sql'         => "int(10) unsigned NOT NULL auto_increment"
        ]
    ,   'pid' => [
            'sql'         => "int(10) unsigned NOT NULL default '0'"
        ]
    ,   'tstamp' => [
            'sql'         => "int(10) unsigned NOT NULL default '0'"
        ]
    ,   'clicks' => [
            'sql'         => "int(10) unsigned NOT NULL default '0'"
        ]
    ,   'views' => [
            'sql'         => "int(10) unsigned NOT NULL default '0'"
        ]
    ,   'reset' => [
            'sql'         => "int(10) unsigned NOT NULL default '0'"
        ]
    ,   'type' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_content_group']['type']
        ,   'inputType'             => 'select'
        ,   'filter'                => true
        ,   'options_callback'      => [ '\numero2\MarketingSuite\DCAHelper\ContentGroup', 'getTypes']
        ,   'eval'                  => ['mandatory'=>true, 'maxlength'=>32, 'chosen'=>true, 'submitOnChange'=>true, 'readonly'=>'readonly',  'tl_class'=>'w50']
        ,   'sql'                   => "varchar(32) NOT NULL default ''"
        ]
    ,   'name' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_content_group']['name']
        ,   'inputType'             => 'text'
        ,   'search'                => true
        ,   'eval'                  => ['mandatory'=>true, 'maxlength'=>64, 'tl_class'=>'w50']
        ,   'sql'                   => "varchar(64) NOT NULL default ''"
        ]
    ,   'always_use_this' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_content_group']['always_use_this']
        ,   'inputType'             => 'checkbox'
        ,   'eval'                  => [ 'tl_class'=>'w50' ]
        ,   'sql'                   => "char(1) NOT NULL default ''"
        ]
    ,   'helper_top' => [
            'input_field_callback'  => [ '\numero2\MarketingSuite\Backend\Wizard', 'generateTopForInputField' ]
        ]
    ,   'helper_bottom' => [
            'input_field_callback'  => [ '\numero2\MarketingSuite\Backend\Wizard', 'generateBottomForInputField' ]
        ]
    ]
];