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
 * Table tl_cms_link_shortener
 */
$GLOBALS['TL_DCA']['tl_cms_link_shortener'] = [

    'config' => [
        'dataContainer'             => 'Table'
    ,   'ctable'                    => ['tl_cms_link_shortener_statistics']
    ,   'isAvailable'               => \numero2\MarketingSuite\Backend\License::hasFeature('link_shortener')
    ,   'sql' => [
            'keys' => [
                'id' => 'primary'
            ,   'prefix' => 'index'
            ,   'alias' => 'index'
            ]
        ]
    ]
,   'list' => [
        'sorting' => [
            'mode'                  => 1
        ,   'flag'                  => 1
        ,   'fields'                => ['group_name']
        ,   'panelLayout'           => 'search,limit;filter'
        ]
    ,   'label' => [
            'fields'                => ['target','prefix','alias']
        ,   'label_callback'        => ['\numero2\MarketingSuite\DCAHelper\LinkShortener', 'labelCallback']
        ]
    ,   'global_operations' => [
            'all' => [
                'label'             => &$GLOBALS['TL_LANG']['MSC']['all']
            ,   'href'              => 'act=select'
            ,   'class'             => 'header_edit_all'
            ,   'attributes'        => 'onclick="Backend.getScrollOffset()" accesskey="e"'
            ]
        ]
    ,   'operations' => [
            'edit' => [
                'label'             => &$GLOBALS['TL_LANG']['tl_cms_link_shortener']['edit']
            ,   'href'              => 'act=edit'
            ,   'icon'              => 'edit.gif'
            ]
        ,   'statistics' => [
                'label'             => &$GLOBALS['TL_LANG']['tl_cms_link_shortener']['statistics']
            ,   'href'              => 'key=link_shortener_statistics'
            ,   'icon'              => 'bundles/marketingsuite/img/backend/icons/icon_statistics.svg'
            ]
        ,   'reset_counter' => [
                'label'             => &$GLOBALS['TL_LANG']['tl_cms_link_shortener']['reset_counter']
            ,   'icon'              => 'bundles/marketingsuite/img/backend/icons/icon_reset_counter.svg'
            ,   'attributes'        => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['reset_warning'] . '\')) return false; Backend.getScrollOffset();"'
            ,   'button_callback'   => ['\numero2\MarketingSuite\DCAHelper\LinkShortener', 'resetCounter']
            ]
        ,   'delete' => [
                'label'             => &$GLOBALS['TL_LANG']['tl_cms_link_shortener']['delete']
            ,   'href'              => 'act=delete'
            ,   'icon'              => 'delete.gif'
            ,   'attributes'        => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"'
            ]
        ,   'toggle' =>[
                'label'             => &$GLOBALS['TL_LANG']['tl_cms_link_shortener']['toggle']
            ,   'icon'              => 'visible.svg'
            ,   'attributes'        => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleVisibility(this,%s)"'
            ,   'button_callback'   => ['\numero2\MarketingSuite\DCAHelper\LinkShortener', 'toggleIcon']
            ]
        ]
    ]
,   'palettes' => [
        '__selector__'              => ['type']
    ,   'default'                   => '{common_legend},target,group_name;{link_legend},domain,prefix,prefix_preview,alias,alias_preview;{description_legend},description;{publish_legend},active,fallback'
    ]
,   'fields' => [
        'id' => [
            'sql'         => "int(10) unsigned NOT NULL auto_increment"
        ]
    ,   'tstamp' => [
            'sql'         => "int(10) unsigned NOT NULL default '0'"
        ]
    ,   'target' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_link_shortener']['target']
        ,   'inputType'             => 'text'
        ,   'search'                => true
        ,   'eval'                  => ['mandatory'=>true, 'rgxp'=>'cms_url', 'decodeEntities'=>true, 'maxlength'=>255, 'dcaPicker'=>true, 'addWizardClass'=>false, 'tl_class'=>'w50']
        ,   'sql'                   => "varchar(255) NOT NULL default ''"
        ]
    ,   'group_name' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_link_shortener']['group_name']
        ,   'inputType'             => 'text'
        ,   'filter'                => true
        ,   'wizard'                => [ ['numero2\MarketingSuite\Widget\SuggestWizard', 'generate'] ]
        ,   'eval'                  => ['maxlength'=>64, 'tl_class'=>'w50 suggest', 'autocomplete'=>'off']
        ,   'sql'                   => "varchar(64) NOT NULL default ''"
        ]
    ,   'domain' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_link_shortener']['domain']
        ,   'inputType'             => 'radio'
        ,   'filter'                => true
        ,   'options_callback'      => ['\numero2\MarketingSuite\DCAHelper\LinkShortener', 'getAvailableDomains']
        ,   'load_callback'         => [['\numero2\MarketingSuite\DCAHelper\LinkShortener', 'lockIfNotEmpty']]
        ,   'eval'                  => ['mandatory'=>true, 'tl_class'=>'clr']
        ,   'sql'                   => "varchar(255) NOT NULL default ''"
        ]
    ,   'prefix' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_link_shortener']['prefix']
        ,   'inputType'             => 'text'
        ,   'search'                => true
        ,   'load_callback'         => [['\numero2\MarketingSuite\DCAHelper\LinkShortener', 'lockIfNotEmpty']]
        ,   'save_callback'         => [
                ['\numero2\MarketingSuite\DCAHelper\LinkShortener', 'addRandomId']
            ,   ['\numero2\MarketingSuite\DCAHelper\LinkShortener', 'checkUnique']
            ,   ['\numero2\MarketingSuite\DCAHelper\LinkShortener', 'checkPageAlreadyExists']
            ]
        ,   'eval'                  => ['maxlength'=>255, 'tl_class'=>'w50']
        ,   'sql'                   => "varchar(255) NOT NULL default ''"
        ]
    ,   'prefix_preview' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_link_shortener']['prefix_preview']
        ,   'input_field_callback'  => ['\numero2\MarketingSuite\Widget\LinkPreview', 'generate']
        ]
    ,   'alias' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_link_shortener']['alias']
        ,   'inputType'             => 'text'
        ,   'search'                => true
        ,   'load_callback'         => [['\numero2\MarketingSuite\DCAHelper\LinkShortener', 'lockIfNotEmpty']]
        ,   'save_callback'         => [
                ['\numero2\MarketingSuite\DCAHelper\LinkShortener', 'checkUnique']
            ,   ['\numero2\MarketingSuite\DCAHelper\LinkShortener', 'checkPageAlreadyExists']
            ]
        ,   'eval'                  => ['maxlength'=>255, 'tl_class'=>'w50']
        ,   'sql'                   => "varchar(255) NOT NULL default ''"
        ]
    ,   'alias_preview' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_link_shortener']['alias_preview']
        ,   'input_field_callback'  => ['\numero2\MarketingSuite\Widget\LinkPreview', 'generate']
        ]
    ,   'description' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_link_shortener']['description']
        ,   'inputType'             => 'textarea'
        ,   'eval'                  => [ 'tl_class'=>'clr' ]
        ,   'sql'                   => "text NULL"
        ]
    ,   'active' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_link_shortener']['active']
        ,   'inputType'             => 'checkbox'
        ,   'filter'                => true
        ,   'eval'                  => ['tl_class'=>'w50']
        ,   'sql'                   => "char(1) NOT NULL default ''"
        ]
    ,   'fallback' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_link_shortener']['fallback']
        ,   'inputType'             => 'text'
        ,   'search'                => true
        ,   'eval'                  => ['rgxp'=>'cms_url', 'decodeEntities'=>true, 'maxlength'=>255, 'dcaPicker'=>true, 'addWizardClass'=>false, 'tl_class'=>'clr w50']
        ,   'sql'                   => "varchar(255) NOT NULL default ''"
        ]
    ]
];