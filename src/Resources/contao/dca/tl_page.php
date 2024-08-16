<?php

/**
 * Contao Marketing Suite Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


use Contao\CoreBundle\DataContainer\PaletteManipulator;


/**
 * Modify palettes of tl_page
 */
PaletteManipulator::create()
    ->addField(['snippet_preview'], 'description', PaletteManipulator::POSITION_AFTER)
    ->addField(['cms_exclude_health_check'], 'expert_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('regular', 'tl_page');

PaletteManipulator::create()
    ->addLegend('cms_legend', 'protected_legend', PaletteManipulator::POSITION_BEFORE)
    ->addField(['cms_root_license', 'cms_refresh_license'], 'cms_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('root', 'tl_page')
    ->applyToPalette('rootfallback', 'tl_page');

$GLOBALS['TL_DCA']['tl_page']['palettes']['ab_test'] = $GLOBALS['TL_DCA']['tl_page']['palettes']['regular'];



/**
 * Add fields to tl_page
 */
$GLOBALS['TL_DCA']['tl_page']['fields'] = array_merge(
    $GLOBALS['TL_DCA']['tl_page']['fields']
,   [
        'cms_root_license' => [
            'inputType'             => 'text'
        ,   'exclude'               => true
        ,   'eval'                  => ['maxlength'=>255, 'doNotCopy'=>true, 'tl_class'=>'w50 clr']
        ,   'sql'                   => "varchar(255) NOT NULL default ''"
        ]
    ,   'cms_refresh_license' => [
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
    ,   'cms_mi_reset' => [
            'sql'               => "int(10) unsigned NOT NULL default '0'"
        ,   'eval'              => ['doNotCopy'=>true, 'readonly'=>'readonly', 'tl_class'=>'w50']
        ]
    ]
);


if( numero2\MarketingSuite\Backend\License::hasFeature('page_snippet_preview') ) {

    $GLOBALS['TL_DCA']['tl_page']['fields'] = array_merge(
        array_slice(
            $GLOBALS['TL_DCA']['tl_page']['fields']
        ,   0
        ,   array_search('description', array_keys($GLOBALS['TL_DCA']['tl_page']['fields'])) + 1
        )
    ,   [
            'snippet_preview' => [
                'input_field_callback'  => ['\numero2\MarketingSuite\Widget\SnippetPreview', 'generate']
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
