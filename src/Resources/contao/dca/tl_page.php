<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2021 Leo Feyer
 *
 * @package   Contao Marketing Suite
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright 2021 numero2 - Agentur für digitales Marketing
 */


/**
 * Modify config of tl_page
 */
$GLOBALS['TL_DCA']['tl_page']['config']['onundo_callback'][] = ['\numero2\MarketingSuite\DCAHelper\Page', 'refreshLicenseOnUndo'];
$GLOBALS['TL_DCA']['tl_page']['config']['onrestore_version_callback'][] = ['\numero2\MarketingSuite\DCAHelper\Page', 'refreshLicenseOnRestoreVersion'];


/**
 * Modify palettes of tl_page
 */
$GLOBALS['TL_DCA']['tl_page']['palettes']['regular'] = str_replace(
    ',description'
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
if( !empty($GLOBALS['TL_DCA']['tl_page']['palettes']['rootfallback']) ) {
    $GLOBALS['TL_DCA']['tl_page']['palettes']['rootfallback'] = str_replace(
        ';{protected_legend'
    ,   ';{cms_legend:hide},cms_root_license,cms_refresh_license;{protected_legend'
    ,   $GLOBALS['TL_DCA']['tl_page']['palettes']['rootfallback']
    );
}


/**
 * Add fields to tl_page
 */
$GLOBALS['TL_DCA']['tl_page']['fields'] = array_merge(
    $GLOBALS['TL_DCA']['tl_page']['fields']
,   [
        'cms_root_license' => [
            'inputType'             => 'text'
        ,   'exclude'               => true
        ,   'save_callback'         => [['\numero2\MarketingSuite\DCAHelper\License', 'save']]
        ,   'load_callback'         => [['\numero2\MarketingSuite\DCAHelper\License', 'check']]
        ,   'eval'                  => ['maxlength'=>255, 'doNotCopy'=>true, 'tl_class'=>'w50 clr']
        ,   'sql'                   => "varchar(255) NOT NULL default ''"
        ]
    ,   'cms_refresh_license' => [
            'input_field_callback'  => ['\numero2\MarketingSuite\DCAHelper\License', 'refresh']
        ]
    ,   'cms_exclude_health_check' => [
            'inputType'             => 'checkbox'
        ,   'default'               => '0'
        ,   'eval'                  => ['tl_class'=>'w50']
        ,   'sql'                   => "char(1) NOT NULL default '0'"
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
    ,   'cms_mi_views' => [ // Will be deprecated in favor of tl_statistics
            'sql'               => "int(10) unsigned NOT NULL default '0'"
        ,   'eval'              => ['doNotCopy'=>true, 'readonly'=>'readonly', 'tl_class'=>'w50']
        ]
    ,   'cms_mi_reset' => [
            'sql'               => "int(10) unsigned NOT NULL default '0'"
        ,   'eval'              => ['doNotCopy'=>true, 'readonly'=>'readonly', 'tl_class'=>'w50']
        ]
    ]
);

$GLOBALS['TL_DCA']['tl_page']['fields']['includeCache']['load_callback'][] = ['\numero2\MarketingSuite\DCAHelper\Page', 'addCacheInfo'];

if( \numero2\MarketingSuite\Backend\License::hasFeature('page_snippet_preview') ) {

    $GLOBALS['TL_DCA']['tl_page']['fields'] = array_merge(
        array_slice(
            $GLOBALS['TL_DCA']['tl_page']['fields']
        ,   0
        ,   array_search('description', array_keys($GLOBALS['TL_DCA']['tl_page']['fields'])) + 1
        )
    ,   [
            'snippet_preview' => [
                'label' => &$GLOBALS['TL_LANG']['MSC']['snippet_preview']
            ,   'input_field_callback'  => ['\numero2\MarketingSuite\Widget\SnippetPreview', 'generate']
            ]
        ]
    ,   array_slice(
            $GLOBALS['TL_DCA']['tl_page']['fields']
        ,   array_search('description', array_keys($GLOBALS['TL_DCA']['tl_page']['fields']))
        )
    );

    unset($GLOBALS['TL_DCA']['tl_page']['fields']['serp_preview']);
    unset($GLOBALS['TL_DCA']['tl_page']['fields']['serpPreview']);
}
