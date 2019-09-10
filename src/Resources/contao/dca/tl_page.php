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
 * Add palettes to tl_page
 */
$GLOBALS['TL_DCA']['tl_page']['palettes']['regular'] = str_replace(
    ',description;'
,   ',description,snippet_preview;'
,   $GLOBALS['TL_DCA']['tl_page']['palettes']['regular']
);
$GLOBALS['TL_DCA']['tl_page']['palettes']['regular'] = str_replace(
    ',guests'
,   ',guests,cms_exclude_health_check'
,   $GLOBALS['TL_DCA']['tl_page']['palettes']['regular']
);
$GLOBALS['TL_DCA']['tl_page']['palettes']['root'] = str_replace(
    ';{protected_legend'
,   ';{cms_legend:hide},cms_root_license,cms_refresh_license;{protected_legend'
,   $GLOBALS['TL_DCA']['tl_page']['palettes']['root']
);


/**
 * Add fields to tl_page
 */
$GLOBALS['TL_DCA']['tl_page']['fields'] = array_merge(
    $GLOBALS['TL_DCA']['tl_page']['fields']
,   [
        'cms_root_license' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_page']['cms_root_license']
        ,   'inputType'             => 'text'
        ,   'exclude'               => true
        ,   'save_callback'         => [ ['\numero2\MarketingSuite\DCAHelper\License', 'save'] ]
        ,   'load_callback'         => [ ['\numero2\MarketingSuite\DCAHelper\License', 'check'] ]
        ,   'eval'                  => ['maxlength'=>255, 'doNotCopy'=>true, 'tl_class'=>'w50 clr']
        ,   'sql'                   => "varchar(255) NOT NULL default ''"
        ]
    ,   'cms_refresh_license' => [
            'label' => &$GLOBALS['TL_LANG']['tl_page']['cms_refresh_license']
        ,   'input_field_callback'  => ['\numero2\MarketingSuite\DCAHelper\License', 'refresh']
        ]
    ,   'cms_root_key' => [
            'eval'                  => ['doNotShow'=>true, 'doNotCopy'=>true]
        ,   'sql'                   => "blob NULL"
        ]
    ,   'cms_root_data' => [
            'eval'                  => ['doNotShow'=>true, 'doNotCopy'=>true]
        ,   'sql'                   => "blob NULL"
        ]
    ,   'cms_root_sign' => [
            'eval'                  => ['doNotShow'=>true, 'doNotCopy'=>true]
        ,   'sql'                   => "blob NULL"
        ]
    ,   'cms_exclude_health_check' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_page']['cms_exclude_health_check']
        ,   'inputType'             => 'checkbox'
        ,   'default'               => '0'
        ,   'eval'                  => ['tl_class'=>'w50']
        ,   'sql'                   => "char(1) NOT NULL default '0'"
        ]
    ,   'cms_mi_views' => [
            'sql'               => "int(10) unsigned NOT NULL default '0'"
        ,   'eval'              => ['doNotCopy'=>true, 'readonly'=>'readonly', 'tl_class'=>'w50']
        ]
    ,   'cms_mi_reset' => [
            'sql'               => "int(10) unsigned NOT NULL default '0'"
        ,   'eval'              => ['doNotCopy'=>true, 'readonly'=>'readonly', 'tl_class'=>'w50']
        ]
    ]
);


// disable snippet-preview in multi edit mode
if( \Input::get('act') != 'editAll' ) {

    if( \numero2\MarketingSuite\Backend\License::hasFeature('page_snippet_preview') ) {

        $GLOBALS['TL_DCA']['tl_page']['fields']['snippet_preview'] = [
            'label' => &$GLOBALS['TL_LANG']['tl_page']['snippet_preview']
        ,   'input_field_callback'  => ['\numero2\MarketingSuite\Widget\SnippetPreview', 'generate']
        ];

        $GLOBALS['TL_DCA']['tl_page']['fields']['pageTitle']['eval']['tl_class'] .= ' snippet';
        $GLOBALS['TL_DCA']['tl_page']['fields']['pageTitle']['eval']['data-snippet-length'] = \numero2\MarketingSuite\Widget\SnippetPreview::TITLE_MAX_LENGTH;
        $GLOBALS['TL_DCA']['tl_page']['fields']['pageTitle']['load_callback'][] = ['\numero2\MarketingSuite\Widget\SnippetPreview', 'addSnippetCount'];
        $GLOBALS['TL_DCA']['tl_page']['fields']['pageTitle']['wizard'][] = ['\numero2\MarketingSuite\Widget\SnippetPreview', 'generateInputField'];

        $GLOBALS['TL_DCA']['tl_page']['fields']['description']['eval']['tl_class'] .= ' snippet';
        $GLOBALS['TL_DCA']['tl_page']['fields']['description']['eval']['data-snippet-length'] = \numero2\MarketingSuite\Widget\SnippetPreview::DESCRIPTION_MAX_LENGTH;
        $GLOBALS['TL_DCA']['tl_page']['fields']['description']['load_callback'][] = ['\numero2\MarketingSuite\Widget\SnippetPreview', 'addSnippetCount'];
        $GLOBALS['TL_DCA']['tl_page']['fields']['description']['wizard'][] = ['\numero2\MarketingSuite\Widget\SnippetPreview', 'generateInputField'];
    }
}
