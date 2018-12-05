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
        'default'                   => '{common_legend},send_anonymized_data;{test_legend},testmode'
    ]
,   'fields' => [
        /*
        'cms_install_key' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_settings']['cms_install_key']
        ,   'inputType'             => 'text'
        ,   'eval'                  => [ 'tl_class'=>'w50' ]
        ]
        */
        'send_anonymized_data' => [
            'label'            => &$GLOBALS['TL_LANG']['tl_cms_settings']['send_anonymized_data']
        //  'default'          => '1' // see config/cmsconfig.php
        ,   'inputType'        => 'checkbox'
        ,   'eval'             => [ 'tl_class'=>'w50' ]
        ]
    ,   'testmode' => [
            'label'            => &$GLOBALS['TL_LANG']['tl_cms_settings']['testmode']
        ,   'inputType'        => 'checkbox'
        ,   'eval'             => [ 'tl_class'=>'w50' ]
        ]
    ]
];
