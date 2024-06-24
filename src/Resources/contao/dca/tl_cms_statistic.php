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
use numero2\MarketingSuite\Backend\License;


/**
 * Table tl_cms_statistic
 */
$GLOBALS['TL_DCA']['tl_cms_statistic'] = [

    'config' => [
        'dataContainer'             => DC_Table::class
    ,   'dynamicPtable'             => true
    ,   'isAvailable'               => License::hasFeature('search_statistic')
    ,   'closed'                    => true
    ,   'notCopyable'               => true
    ,   'notCreatable'              => true
    ,   'notDeletable'              => true
    ,   'notEditable'               => true
    ,   'notSortable'               => true
    ,   'sql' => [
            'keys' => [
                'id' => 'primary'
            ,   'pid,ptable' => 'index'
            ]
        ]
    ]
,   'list' => [
        'sorting' => [
            'mode'                  => 2
        ,   'fields'                => ['tstamp']
        ,   'flag'                  => 1
        ,   'panelLayout'           => 'filter;sort,search,limit'
        ]
    ,   'label' => [
            'fields'                => ['pid', 'ptable', 'type']
        ,   'showColumns'           => true
        ]
    ]
,   'fields' => [
        'id' => [
            'sql'         => "int(10) unsigned NOT NULL auto_increment"
        ]
    ,   'pid' => [
            'search'      => true
        ,   'sql'         => "int(10) unsigned NOT NULL default '0'"
        ]
    ,   'ptable' => [
            'search'      => true
        ,   'sql'         => "varchar(64) NOT NULL default ''"
        ]
    ,   'tstamp' => [
            'flag'        => 6
        ,   'sorting'     => true
        ,   'sql'         => "int(10) unsigned NOT NULL default '0'"
        ]
    ,   'type' => [
            'filter'      => true
        ,   'options'     => ['click', 'view', 'search']
        ,   'reference'   => &$GLOBALS['TL_LANG']['tl_cms_tag']['types']
        ,   'sql'         => "varchar(64) NOT NULL default ''"
        ]
    ,   'page' => [
            'filter'      => true
        ,   'foreignKey'  => 'tl_page.CONCAT(title, " [ID " ,id, "]")'
        ,   'sql'         => "int(10) unsigned NOT NULL default '0'"
        ]
    ,   'url' => [
            'sorting'     => true
        ,   'search'      => true
        ,   'sql'         => "varchar(255) NOT NULL default ''"
        ]
    ,   'keywords' => [
           'search'      => true
        ,  'sorting'     => true
        ,   'sql'         => "varchar(255) NOT NULL default ''"
        ]
    ]
];
