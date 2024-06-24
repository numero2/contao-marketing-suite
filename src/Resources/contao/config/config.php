<?php

/**
 * Contao Marketing Suite Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


use Contao\ArrayUtil;
use Contao\ContentText;
use Contao\System;
use numero2\MarketingSuite\Backend\License;
use numero2\MarketingSuite\BackendModule\Avalex;
use numero2\MarketingSuite\BackendModule\Dashboard;
use numero2\MarketingSuite\BackendModule\HealthCheck;
use numero2\MarketingSuite\BackendModule\LinkShortenerStatistics;
use numero2\MarketingSuite\BackendModule\Module;
use numero2\MarketingSuite\BackendModule\NewsSchedule;
use numero2\MarketingSuite\ContentButton;
use numero2\MarketingSuite\ContentConversionItem;
use numero2\MarketingSuite\ContentGroupModel;
use numero2\MarketingSuite\ContentHyperlink;
use numero2\MarketingSuite\ContentMarketingItem;
use numero2\MarketingSuite\ContentOverlay;
use numero2\MarketingSuite\ContentTextCMSCTA;
use numero2\MarketingSuite\ConversionItemModel;
use numero2\MarketingSuite\LinkShortenerModel;
use numero2\MarketingSuite\LinkShortenerStatisticsModel;
use numero2\MarketingSuite\MarketingItemModel;
use numero2\MarketingSuite\ModuleAcceptTags;
use numero2\MarketingSuite\ModuleConversionItem;
use numero2\MarketingSuite\ModuleCookieBar;
use numero2\MarketingSuite\ModuleMarketingItem;
use numero2\MarketingSuite\StatisticModel;
use numero2\MarketingSuite\TagModel;
use numero2\MarketingSuite\Widget\LayoutSelector;

$rootDir = System::getContainer()->getParameter('kernel.project_dir');
require_once $rootDir.'/vendor/numero2/contao-marketing-suite/src/Resources/contao/config/constants.php';


/**
 * MODELS
 */
$GLOBALS['TL_MODELS'][TagModel::getTable()] = TagModel::class;
$GLOBALS['TL_MODELS'][LinkShortenerModel::getTable()] = LinkShortenerModel::class;
$GLOBALS['TL_MODELS'][LinkShortenerStatisticsModel::getTable()] = LinkShortenerStatisticsModel::class;
$GLOBALS['TL_MODELS'][ContentGroupModel::getTable()] = ContentGroupModel::class;
$GLOBALS['TL_MODELS'][ConversionItemModel::getTable()] = ConversionItemModel::class;
$GLOBALS['TL_MODELS'][MarketingItemModel::getTable()] = MarketingItemModel::class;
$GLOBALS['TL_MODELS'][StatisticModel::getTable()] = StatisticModel::class;


/**
 * BACK END MODULES
 */
if( !empty($GLOBALS['BE_MOD']['content']['news']) ) {
    $GLOBALS['BE_MOD']['content']['news']['cms_schedule'] = [NewsSchedule::class, 'generate'];
}


ArrayUtil::arrayInsert($GLOBALS['BE_MOD'], 1, [

    'marketing_suite' => [
        'cms_dashboard' => [
            'callback'  => Dashboard::class
        ]
    ,   'cms_marketing' => [
            'tables'    => ['tl_cms_marketing_item', 'tl_cms_content_group', 'tl_content']
        ]
    ,   'cms_conversion' => [
            'tables'    => ['tl_cms_conversion_item', 'tl_content']
        ]
    ,   'cms_health_check' => [
            'callback'  => HealthCheck::class
        ]
    ,   'cms_tools' => [
            'callback'  => Module::class
        ]
    ,   'cms_settings' => [
            'callback'  => Module::class
        ]
    ]
]);


/**
 * MARKETING SUITE BACK END MODULES
 */
$GLOBALS['CMS_MOD'] = [

    'tools' => [
        'link_shortener' => [
            'tables'    => ['tl_cms_link_shortener', 'tl_cms_link_shortener_statistics']
        ,   'icon'      => '/bundles/marketingsuite/img/backend/icons/icon_link_shortener.svg'
        ,   'link_shortener_statistics' => [LinkShortenerStatistics::class, 'generate']
        ]
    ,   'search_statistic' => [
            'tables'    => [StatisticModel::getTable()]
        ,   'icon'      => '/bundles/marketingsuite/img/backend/icons/icon_search_statistic.svg'
        ]
    ]
,   'settings' => [
        'settings' => [
            'tables'    => ['tl_cms_settings']
        ,   'icon'      => '/bundles/marketingsuite/img/backend/icons/icon_settings.svg'
        ]
    ,   'tags' => [
            'tables'    => ['tl_cms_tag', 'tl_cms_tag_settings']
        ,   'icon'      => '/bundles/marketingsuite/img/backend/icons/icon_tags.svg'
        ]
    ,   'avalex' => [
            'callback'  => Avalex::class
        ,   'icon'      => '/bundles/marketingsuite/img/backend/icons/icon_avalex.svg'
        ]
    ]
];


/**
 * BACK END FORM FIELDS
 */
$GLOBALS['BE_FFL']['cmsLayoutSelector'] = LayoutSelector::class;


/**
 * FRONT END MODULES
 */
$GLOBALS['FE_MOD']['marketing_suite'] = [
    'cms_marketing_item'    => ModuleMarketingItem::class
,   'cms_conversion_item'   => ModuleConversionItem::class
,   'cms_cookie_bar'        => ModuleCookieBar::class
,   'cms_accept_tags'       => ModuleAcceptTags::class
];


/**
 * CONTENT ELEMENTS
 */
$GLOBALS['TL_CTE']['texts']['text_cms'] = ContentText::class;
$GLOBALS['TL_CTE']['marketing_suite'] = [
    'cms_marketing_item'    => ContentMarketingItem::class
,   'cms_conversion_item'   => ContentConversionItem::class
];
$GLOBALS['TL_CTE']['conversion_elements'] = [
    'text_cms_cta'  => ContentTextCMSCTA::class
,   'cms_hyperlink' => ContentHyperlink::class
,   'cms_button'    => ContentButton::class
,   'cms_form'      => $GLOBALS['TL_CTE']['includes']['form']
,   'cms_overlay'   => ContentOverlay::class
];
