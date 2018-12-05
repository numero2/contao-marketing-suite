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
 * Table tl_cms_facebook
 */
$GLOBALS['TL_DCA']['tl_cms_facebook'] = [

    'config' => [
        'dataContainer'             => 'CMSFile'
    ,   'isAvailable'               => \numero2\MarketingSuite\Backend\License::hasFeature('news_publish_facebook')
    ,   'closed'                    => true
    ,   'onload_callback'           => [
            ['\numero2\MarketingSuite\BackendModule\Facebook', 'modifyPalette']
        ,   ['\numero2\MarketingSuite\BackendModule\Facebook', 'revokeAuthentication']
        ]
    ]
,   'palettes' => [
        'default'                   => '{app_legend},cms_fb_app_id,cms_fb_app_secret;{pages_legend},cms_fb_pages_available,authorization'
    ]
,   'fields' => [
        'cms_fb_app_id' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_facebook']['cms_fb_app_id']
        ,   'inputType'             => 'text'
        ,   'eval'                  => [ 'mandatory'=>true, 'tl_class'=>'w50' ]
        ]
    ,   'cms_fb_app_secret' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_facebook']['cms_fb_app_secret']
        ,   'inputType'             => 'text'
        ,   'load_callback'         => [[ '\numero2\MarketingSuite\Encryption', 'decrypt' ]]
        ,   'save_callback'         => [[ '\numero2\MarketingSuite\Encryption', 'encrypt' ]]
        ,   'eval'                  => [ 'mandatory'=>true, 'tl_class'=>'w50', 'hideInput'=>true ]
        ]
    ,   'cms_fb_pages_available' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_facebook']['cms_fb_pages_available']
        ,   'inputType'             => 'checkboxWizard'
        ,   'options_callback'      => [ '\numero2\MarketingSuite\BackendModule\Facebook', 'getPagesOptions' ]
        ,   'load_callback'         => [[ '\numero2\MarketingSuite\BackendModule\Facebook', 'loadAvailablePages' ]]
        ,   'save_callback'         => [[ '\numero2\MarketingSuite\BackendModule\Facebook', 'saveAvailablePages' ]]
        ,   'eval'                  => [ 'mandatory'=>true, 'multiple'=>true ]
        ]
    ,   'authorization' => [
            'input_field_callback'  => [ '\numero2\MarketingSuite\BackendModule\Facebook', 'generateAuthorizationField' ]
        ]
    ]
];
