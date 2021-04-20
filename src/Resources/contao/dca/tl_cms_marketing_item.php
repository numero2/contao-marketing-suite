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
 * Table tl_cms_marketing_item
 */
$GLOBALS['TL_DCA']['tl_cms_marketing_item'] = [

    'config' => [
        'dataContainer'             => 'Table'
    ,   'closed'                    => \numero2\MarketingSuite\DCAHelper\MarketingItem::isClosed()
    ,   'ctable'                    => ['tl_content','tl_cms_content_group']
    ,   'onsubmit_callback'         => [['\numero2\MarketingSuite\DCAHelper\MarketingItem', 'submitMarketingItem']]
    ,   'onload_callback'           => [['\numero2\MarketingSuite\DCAHelper\MarketingItem', 'loadMarketingItem']]
    ,   'switchToEdit'              => true
    ,   'sql' => [
            'keys' => [
                'id' => 'primary'
            ]
        ]
    ]
,   'list' => [
        'sorting' => [
            'mode'                  => 2
        ,   'fields'                => ['name']
        ,   'flag'                  => 1
        ,   'panelLayout'           => 'cms_license_message;cms_cms_help;filter;search'
        ,   'panel_callback'        => [
                'cms_license_message' => ['\numero2\MarketingSuite\Backend\LicenseMessage', 'generate']
            ,   'cms_help' => ['\numero2\MarketingSuite\Backend\Help', 'generate']
            ]
        ]
    ,   'label' => [
            'fields'                => ['name', 'type', 'status', 'used']
        ,   'showColumns'           => true
        ,   'label_callback'        => ['\numero2\MarketingSuite\DCAHelper\MarketingItem', 'getLabel']
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
                'label'             => &$GLOBALS['TL_LANG']['tl_cms_marketing_item']['edit']
            ,   'href'              => 'table=tl_cms_content_group'
            ,   'icon'              => 'edit.svg'
            ,   'button_callback'   => ['\numero2\MarketingSuite\DCAHelper\MarketingItem', 'editButton']
            ]
        ,   'editheader' => [
                'label'             => &$GLOBALS['TL_LANG']['tl_cms_marketing_item']['editheader']
            ,   'href'              => 'act=edit'
            ,   'icon'              => 'header.svg'
            ,   'button_callback'   => ['\numero2\MarketingSuite\DCAHelper\MarketingItem', 'editHeaderButton']
            ]
        ,   'reset_counter' => [
                'label'             => &$GLOBALS['TL_LANG']['tl_cms_marketing_item']['reset_counter']
            ,   'icon'              => 'bundles/marketingsuite/img/backend/icons/icon_reset_counter.svg'
            ,   'attributes'        => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['reset_warning'] . '\')) return false; Backend.getScrollOffset();"'
            ,   'button_callback'   => ['\numero2\MarketingSuite\DCAHelper\MarketingItem', 'resetCounter']
            ]
        ,   'delete' => [
                'label'             => &$GLOBALS['TL_LANG']['tl_cms_marketing_item']['delete']
            ,   'href'              => 'act=delete'
            ,   'icon'              => 'delete.svg'
            ,   'attributes'        => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"'
            ]
        ,   'toggle' =>[
                'label'             => &$GLOBALS['TL_LANG']['tl_cms_marketing_item']['toggle']
            ,   'icon'              => 'visible.svg'
            ,   'attributes'        => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleVisibility(this,%s)"'
            ,   'button_callback'   => ['\numero2\MarketingSuite\DCAHelper\MarketingItem', 'toggleIcon']
            ]
        ]
    ]
,   'palettes' => [
        '__selector__'              => ['type','auto_winner_after']
    ,   'default'                   => '{common_legend},type'
    ,   'a_b_test'                  => '{common_legend},type,name;{a_b_test_legend},content_type,auto_winner_after;{publish_legend},active'
    ,   'a_b_test_page'             => '{common_legend},type,name;{a_b_test_legend},page_a,page_b,auto_winner_after;{publish_legend},active'
    ,   'current_page'              => '{common_legend},type,name;{current_page_legend},content_type,pages;{publish_legend},active'
    ,   'visited_pages'             => '{common_legend},type,name;{publish_legend},active'
    ]
,   'subpalettes' => [
        'auto_winner_after' => 'stop_auto_winner'
    ]
,   'fields' => [
        'id' => [
            'sql'         => "int(10) unsigned NOT NULL auto_increment"
        ]
    ,   'tstamp' => [
            'sql'         => "int(10) unsigned NOT NULL default '0'"
        ]
    ,   'init_step' => [
            'sql'         => "varchar(255) NOT NULL default ''"
        ]
    ,   'type' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_marketing_item']['type']
        ,   'inputType'             => 'select'
        ,   'filter'                => true
        ,   'options_callback'      => [ '\numero2\MarketingSuite\DCAHelper\MarketingItem', 'getMarketingItemTypes']
        ,   'eval'                  => ['mandatory'=>true, 'maxlength'=>32, 'chosen'=>true, 'submitOnChange'=>true, 'tl_class'=>'w50']
        ,   'sql'                   => "varchar(32) NOT NULL default ''"
        ]
    ,   'name' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_marketing_item']['name']
        ,   'inputType'             => 'text'
        ,   'search'                => true
        ,   'eval'                  => ['mandatory'=>true, 'maxlength'=>64, 'tl_class'=>'w50']
        ,   'sql'                   => "varchar(64) NOT NULL default ''"
        ]
    ,   'content_type' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_marketing_item']['content_type']
        ,   'inputType'             => 'select'
        ,   'search'                => true
        ,   'options_callback'      => ['\numero2\MarketingSuite\DCAHelper\MarketingItem', 'getContentElements']
        ,   'reference'             => &$GLOBALS['TL_LANG']['CTE']
        ,   'eval'                  => ['mandatory'=>true, 'chosen'=>true, 'tl_class'=>'w50']
        ,   'sql'                   => "varchar(64) NOT NULL default ''"
        ]
    ,   'pages' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_marketing_item']['pages']
        ,   'inputType'             => 'pageTree'
        ,   'foreignKey'            => 'tl_page.title'
        ,   'eval'                  => ['mandatory'=>true, 'multiple'=>true, 'fieldType'=>'checkbox', 'tl_class'=>'clr']
        ,   'relation'              => ['type'=>'hasMany', 'load'=>'lazy']
        ,   'sql'                   => "text NULL"
        ]
    ,   'auto_winner_after' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_marketing_item']['auto_winner_after']
        ,   'inputType'             => 'checkbox'
        ,   'eval'                  => ['submitOnChange'=>true, 'tl_class'=>'clr w50 cbx']
        ,   'sql'                   => "char(1) NOT NULL default ''"
        ]
    ,   'stop_auto_winner' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_marketing_item']['stop_auto_winner']
        ,   'inputType'             => 'text'
        ,   'eval'                  => ['mandatory'=>true, 'rgxp'=>'datim', 'datepicker'=>true, 'tl_class'=>'clr w50']
        ,   'sql'                   => "varchar(10) NOT NULL default ''"
        ]
    ,   'page_a' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_marketing_item']['page_a']
        ,   'inputType'             => 'pageTree'
        ,   'foreignKey'            => 'tl_page.title'
        ,   'eval'                  => ['mandatory'=>true, 'fieldType'=>'radio', 'tl_class'=>'clr page_a']
        ,   'relation'              => ['type'=>'hasOne', 'load'=>'lazy']
        ,   'load_callback'         => [['\numero2\MarketingSuite\DCAHelper\MarketingItem', 'addToggleAlwaysUseThis']]
        ,   'save_callback'         => [
                [ '\numero2\MarketingSuite\DCAHelper\MarketingItem', 'checkForNonIndexPage' ]
            ,   [ '\numero2\MarketingSuite\DCAHelper\MarketingItem', 'checkUniquePageForABTestPage' ]
            ,   [ '\numero2\MarketingSuite\DCAHelper\MarketingItem', 'checkForSameRootPage' ]
            ,   [ '\numero2\MarketingSuite\DCAHelper\MarketingItem', 'checkForConversionOnPage' ]
            ]
        ,   'sql'                   => "varchar(10) NOT NULL default ''"
        ]
    ,   'always_page_a' => [
            'sql'                   => "char(1) NOT NULL default ''"
        ]
    ,   'page_b' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_marketing_item']['page_b']
        ,   'inputType'             => 'pageTree'
        ,   'foreignKey'            => 'tl_page.title'
        ,   'eval'                  => ['mandatory'=>true, 'fieldType'=>'radio', 'tl_class'=>'clr page_b']
        ,   'relation'              => ['type'=>'hasOne', 'load'=>'lazy']
        ,   'load_callback'         => [['\numero2\MarketingSuite\DCAHelper\MarketingItem', 'addToggleAlwaysUseThis']]
        ,   'save_callback'         => [
                [ '\numero2\MarketingSuite\DCAHelper\MarketingItem', 'checkUniquePageForABTestPage' ]
            ,   [ '\numero2\MarketingSuite\DCAHelper\MarketingItem', 'checkForSameRootPage' ]
            ,   [ '\numero2\MarketingSuite\DCAHelper\MarketingItem', 'checkForConversionOnPage' ]
            ]
        ,   'sql'                   => "varchar(10) NOT NULL default ''"
        ]
    ,   'always_page_b' => [
            'sql'                   => "char(1) NOT NULL default ''"
        ]
    ,   'active' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_marketing_item']['active']
        ,   'inputType'             => 'checkbox'
        ,   'default'               => '1'
        ,   'eval'                  => ['tl_class'=>'w50']
        ,   'sql'                   => "char(1) NOT NULL default '1'"
        ]
    ,   'used' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_marketing_item']['used']
        ]
    ,   'status' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_marketing_item']['status']
        ]
    ,   'helper_top' => [
            'input_field_callback'  => [ '\numero2\MarketingSuite\Backend\Wizard', 'generateTopForInputField' ]
        ]
    ,   'helper_bottom' => [
            'input_field_callback'  => [ '\numero2\MarketingSuite\Backend\Wizard', 'generateBottomForInputField' ]
        ]
    ]
];
