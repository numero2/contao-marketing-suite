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


/**
 * Table tl_cms_settings
 */
$GLOBALS['TL_DCA']['tl_cms_settings'] = [

    'config' => [
        'dataContainer'             => DC_CMSFile::class
    ,   'isAvailable'               => true
    ,   'closed'                    => true
    ]
,   'palettes' => [
        'default'                   => '{common_legend},send_anonymized_data,dnt_backend_users,hide_missing_features;{health_check_legend},health_check_ignore_older_than;{test_legend},testmode'
    ]
,   'fields' => [
        'send_anonymized_data' => [
            'inputType'        => 'checkbox'
        ,   'eval'             => ['tl_class'=>'w50']
        ]
    ,   'dnt_backend_users' => [
            'inputType'        => 'checkbox'
        ,   'eval'             => ['tl_class'=>'w50']
        ]
    ,   'hide_missing_features' => [
            'inputType'        => 'checkbox'
        ,   'eval'             => ['tl_class'=>'w50']
        ]
    ,   'health_check_ignore_older_than' => [
            'inputType'        => 'text'
        ,   'eval'             => ['rgxp'=>'date', 'datepicker'=>true, 'tl_class'=>'w50']
    ]
    ,   'testmode' => [
            'inputType'        => 'checkbox'
        ,   'eval'             => ['tl_class'=>'w50']
        ]
    ]
];
