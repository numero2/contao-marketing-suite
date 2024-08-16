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
 * Table tl_cms_link_shortener_statistics
 */
$GLOBALS['TL_DCA']['tl_cms_link_shortener_statistics'] = [

    'config' => [
        'dataContainer'             => DC_Table::class
    ,   'ptable'                    => 'tl_cms_link_shortener'
    ,   'isAvailable'               => numero2\MarketingSuite\Backend\License::hasFeature('link_shortener')
    ,   'closed'                    => true
    ,   'sql' => [
            'keys' => [
                'id' => 'primary'
            ,   'tstamp' => 'index'
            ]
        ]
    ]
,   'list' => [
        'sorting' => [
            'mode'                  => 1
        ,   'fields'                => ['user_agent']
        ,   'panelLayout'           => 'date_filter,search;filter'
        ]
    ,   'label' => [
            'fields'                => ['os', 'browser', 'device']
        ,   'showColumns'           => true
        ]
    ]
,   'palettes' => [
        'default'                   => '{common_legend},referer,unique_id,user_agent,os,browser,device,is_bot'
    ]
,   'fields' => [
        'id' => [
            'sql'         => "int(10) unsigned NOT NULL auto_increment"
        ]
    ,   'tstamp' => [
            'sql'         => "int(10) unsigned NOT NULL default '0'"
        ]
    ,   'pid' => [
            'sql'         => "int(10) unsigned NOT NULL default '0'"
        ]
    ,   'referer' => [
            'search'                => true
        ,   'sql'                   => "varchar(255) NOT NULL default ''"
        ]
    ,   'unique_id' => [
            'sql'                   => "varchar(255) NOT NULL default ''"
        ]
    ,   'user_agent' => [
            'search'                => true
        ,   'sql'                   => "varchar(255) NOT NULL default ''"
        ]
    ,   'os' => [
            'inputType'             => 'select'
        ,   'filter'                => true
        ,   'reference'             => &$GLOBALS['TL_LANG']['tl_cms_link_shortener_statistics']['os_names']
        ,   'sql'                   => "varchar(64) NOT NULL default ''"
        ]
    ,   'browser' => [
            'inputType'             => 'select'
        ,   'filter'                => true
        ,   'reference'             => &$GLOBALS['TL_LANG']['tl_cms_link_shortener_statistics']['browser_names']
        ,   'sql'                   => "varchar(64) NOT NULL default ''"
        ]
    ,   'device' => [
            'inputType'             => 'select'
        ,   'filter'                => true
        ,   'reference'             => &$GLOBALS['TL_LANG']['tl_cms_link_shortener_statistics']['device_names']
        ,   'sql'                   => "varchar(64) NOT NULL default ''"
        ]
    ,   'is_bot' => [
            'inputType'             => 'checkbox'
        ,   'filter'                => true
        ,   'sql'                   => "varchar(1) NOT NULL default ''"
        ]
    ]
];
