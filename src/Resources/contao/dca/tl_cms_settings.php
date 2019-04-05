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
 * Table tl_cms_settings
 */
$GLOBALS['TL_DCA']['tl_cms_settings'] = [

    'config' => [
        'dataContainer'             => 'CMSFile'
    ,   'isAvailable'               => true
    ,   'closed'                    => true
    ]
,   'palettes' => [
        'default'                   => '{common_legend},send_anonymized_data;{health_check_legend},health_check_ignore_older_than;{test_legend},testmode'
    ]
,   'fields' => [
        'send_anonymized_data' => [
            'label'            => &$GLOBALS['TL_LANG']['tl_cms_settings']['send_anonymized_data']
        ,   'inputType'        => 'checkbox'
        ,   'eval'             => [ 'tl_class'=>'w50' ]
        ]
    ,   'health_check_ignore_older_than' => [
            'label'            => &$GLOBALS['TL_LANG']['tl_cms_settings']['health_check_ignore_older_than']
        ,   'inputType'        => 'text'
        ,   'eval'             => ['rgxp'=>'date', 'datepicker'=>true, 'tl_class'=>'w50 wizard']
    ]
    ,   'testmode' => [
            'label'            => &$GLOBALS['TL_LANG']['tl_cms_settings']['testmode']
        ,   'inputType'        => 'checkbox'
        ,   'eval'             => [ 'tl_class'=>'w50' ]
        ]
    ]
];
