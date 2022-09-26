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
 * Table tl_cms_conversion_item
 */
$GLOBALS['TL_DCA']['tl_cms_conversion_item'] = [

    'config' => [
        'dataContainer'             => 'Table'
    ,   'ctable'                    => ['tl_content']
    ,   'onload_callback'           => [ ['\numero2\MarketingSuite\DCAHelper\ConversionItem', 'generateOneEntryAndRedirect'] ]
    ,   'switchToEdit'              => true
    ,   'sql' => [
            'keys' => [
                'id' => 'primary'
            ]
        ]
    ]
,   'list' => [
        'sorting' => [
            'mode'                  => 2
        ,   'fields'                => ['name']
        ,   'flag'                  => 1
        ,   'panelLayout'           => 'filter;search'
        ]
    ,   'label' => [
            'fields'                => ['name', 'type']
        ,   'showColumns'           => true
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
                'label'             => &$GLOBALS['TL_LANG']['tl_cms_conversion_item']['edit']
            ,   'href'              => 'table=tl_content'
            ,   'icon'              => 'edit.gif'
            ]
        ,   'delete' => [
                'label'             => &$GLOBALS['TL_LANG']['tl_cms_conversion_item']['delete']
            ,   'href'              => 'act=delete'
            ,   'icon'              => 'delete.gif'
            ,   'attributes'        => 'onclick="if (!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? '') . '\')) return false; Backend.getScrollOffset();"'
            ]
        ]
    ]
,   'palettes' => [
        '__selector__'      => ['type']
    ,   'default'           => '{common_legend},name'
    ]
,   'fields' => [
        'id' => [
            'sql'           => "int(10) unsigned NOT NULL auto_increment"
        ]
    ,   'tstamp' => [
            'sql'           => "int(10) unsigned NOT NULL default '0'"
        ]
    ,   'name' => [
            'label'         => &$GLOBALS['TL_LANG']['tl_cms_conversion_item']['name']
        ,   'inputType'     => 'text'
        ,   'search'        => true
        ,   'eval'          => ['mandatory'=>true, 'maxlength'=>64, 'tl_class'=>'w50']
        ,   'sql'           => "varchar(64) NOT NULL default ''"
        ]
    ]
];
