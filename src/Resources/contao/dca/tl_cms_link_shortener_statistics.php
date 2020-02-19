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
 * Table tl_cms_link_shortener_statistics
 */
$GLOBALS['TL_DCA']['tl_cms_link_shortener_statistics'] = [

    'config' => [
        'dataContainer'             => 'Table'
    ,   'ptable'                    => 'tl_cms_link_shortener'
    ,   'isAvailable'               => \numero2\MarketingSuite\Backend\License::hasFeature('link_shortener')
    ,   'closed'                    => true
    ,   'onload_callback'           => [['numero2\MarketingSuite\DCAHelper\LinkShortenerStatistics', 'applyCMSFilters']]
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
        ,   'panel_callback' => [
                'date_filter'        => ['numero2\MarketingSuite\DCAHelper\LinkShortenerStatistics', 'generateCMSFilters']
            ]
        ]
    ,   'label' => [
            'fields'                => ['os', 'browser']
        ,   'showColumns'           => true
        ]
    ]
,   'palettes' => [
        'default'                   => '{common_legend},referer,unique_id,user_agent,os,browser,is_mobile,is_bot'
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
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_link_shortener_statistics']['referer']
        ,   'search'                => true
        ,   'sql'                   => "varchar(255) NOT NULL default ''"
        ]
    ,   'unique_id' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_link_shortener_statistics']['unique_id']
        ,   'sql'                   => "varchar(255) NOT NULL default ''"
        ]
    ,   'user_agent' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_link_shortener_statistics']['user_agent']
        ,   'search'                => true
        ,   'sql'                   => "varchar(255) NOT NULL default ''"
        ]
    ,   'os' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_link_shortener_statistics']['os']
        ,   'inputType'             => 'select'
        ,   'filter'                => true
        ,   'reference'             => &$GLOBALS['TL_LANG']['tl_cms_link_shortener_statistics']['os_names']
        ,   'sql'                   => "varchar(64) NOT NULL default ''"
        ]
    ,   'browser' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_link_shortener_statistics']['browser']
        ,   'inputType'             => 'select'
        ,   'filter'                => true
        ,   'reference'             => &$GLOBALS['TL_LANG']['tl_cms_link_shortener_statistics']['browser_names']
        ,   'sql'                   => "varchar(64) NOT NULL default ''"
        ]
    ,   'is_mobile' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_link_shortener_statistics']['is_mobile']
        ,   'inputType'             => 'checkbox'
        ,   'filter'                => true
        ,   'sql'                   => "varchar(1) NOT NULL default ''"
        ]
    ,   'is_bot' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_link_shortener_statistics']['is_bot']
        ,   'inputType'             => 'checkbox'
        ,   'filter'                => true
        ,   'sql'                   => "varchar(1) NOT NULL default ''"
        ]
    ]
];
