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


use Contao\Config;
use Contao\System;
use numero2\MarketingSuite\Backend\License;
use numero2\MarketingSuite\Backend\Messages;
use numero2\MarketingSuite\BackendModule\Avalex;
use numero2\MarketingSuite\BackendModule\Dashboard;
use numero2\MarketingSuite\BackendModule\Facebook;
use numero2\MarketingSuite\BackendModule\Feedback;
use numero2\MarketingSuite\BackendModule\HealthCheck;
use numero2\MarketingSuite\BackendModule\Module;
use numero2\MarketingSuite\BackendModule\NewsSchedule;
use numero2\MarketingSuite\BackendModule\SearchStatistic;
use numero2\MarketingSuite\ContentButton;
use numero2\MarketingSuite\ContentConversionItem;
use numero2\MarketingSuite\ContentGroupModel;
use numero2\MarketingSuite\ContentHyperlink;
use numero2\MarketingSuite\ContentMarketingItem;
use numero2\MarketingSuite\ContentOverlay;
use numero2\MarketingSuite\ContentTextCMSCTA;
use numero2\MarketingSuite\ConversionItemModel;
use numero2\MarketingSuite\DCAHelper\LinkShortenerStatistics;
use numero2\MarketingSuite\DCAHelper\Module as ModuleHelper;
use numero2\MarketingSuite\Hooks\ConversionItem;
use numero2\MarketingSuite\Hooks\DCA;
use numero2\MarketingSuite\Hooks\Hooks;
use numero2\MarketingSuite\Hooks\LinkShortener;
use numero2\MarketingSuite\Hooks\Tags;
use numero2\MarketingSuite\LinkShortenerModel;
use numero2\MarketingSuite\LinkShortenerStatisticsModel;
use numero2\MarketingSuite\MarketingItem\ABTestPage;
use numero2\MarketingSuite\MarketingItemModel;
use numero2\MarketingSuite\ModuleAcceptTags;
use numero2\MarketingSuite\ModuleConversionItem;
use numero2\MarketingSuite\ModuleCookieBar;
use numero2\MarketingSuite\ModuleMarketingItem;
use numero2\MarketingSuite\StatisticModel;
use numero2\MarketingSuite\TagModel;
use numero2\MarketingSuite\Tracking\ClickAndViews;
use numero2\MarketingSuite\Tracking\Session;
use numero2\MarketingSuite\Widget\LayoutSelector;

require_once TL_ROOT.'/vendor/numero2/contao-marketing-suite/src/Resources/contao/config/constants.php';


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


array_insert($GLOBALS['BE_MOD'], 1, [

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
    /*,   'cms_feedback' => [
            'callback'  => '\numero2\MarketingSuite\BackendModule\Feedback'
        ]*/
    ]
]);


/**
 * MARKETING SUITE BACK END MODULES
 */
$GLOBALS['CMS_MOD'] = [

    'tools' => [
        'link_shortener' => [
            'tables'    => ['tl_cms_link_shortener', 'tl_cms_link_shortener_statistics']
        ,   'icon'      => 'bundles/marketingsuite/img/backend/icons/icon_link_shortener.svg'
        ,   'link_shortener_statistics' => [LinkShortenerStatistics::class, 'generate']
        ]
    ,   'search_statistic' => [
            'tables'    => [StatisticModel::getTable()]
        ,   'icon'      => 'bundles/marketingsuite/img/backend/icons/icon_search_statistic.svg'
        ]
    ]
,   'settings' => [
        'settings' => [
            'tables'    => ['tl_cms_settings']
        ,   'icon'      => 'bundles/marketingsuite/img/backend/icons/icon_settings.svg'
        ]
    ,   'tags' => [
            'tables'    => ['tl_cms_tag', 'tl_cms_tag_settings']
        ,   'icon'      => 'bundles/marketingsuite/img/backend/icons/icon_tags.svg'
        ]
    ,   'avalex' => [
            'callback'  => Avalex::class
        ,   'icon'      => 'bundles/marketingsuite/img/backend/icons/icon_avalex.svg'
        ]
    ,   'facebook' => [
            'tables'    => ['tl_cms_facebook']
        ,   'icon'      => 'bundles/marketingsuite/img/backend/icons/icon_facebook.svg'
        ,   'pre_form_callback'  => [Facebook::class, 'generatePreForm']
        ]
    ]
];

if( !class_exists('Facebook\Facebook') ) {
    unset($GLOBALS['CMS_MOD']['settings']['facebook']);
}


/**
 * BACK END STYLESHEET
 */
if( TL_MODE === 'BE' ) {
    $GLOBALS['TL_CSS'][] = 'bundles/marketingsuite/css/backend/backend.css';
}


/**
 * BACK END JAVASCRIPT
 */
if( TL_MODE === 'BE' ) {
    $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/marketingsuite/js/backend.js';
}


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
array_insert(
    $GLOBALS['TL_CTE']['texts']
,   array_search('text', array_keys($GLOBALS['TL_CTE']['texts']))
,   [ 'text_cms' => 'ContentText' ]
);
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


/**
 * REGISTER HOOKS
 */
if( Config::getInstance()->isComplete() ) {

    $GLOBALS['TL_HOOKS']['addCustomRegexp'][] = [Hooks::class, 'validateRgxp'];
    $GLOBALS['TL_HOOKS']['cmsBeHelperParseSimpleTokens']['cms_settings_facebook'][] = [Facebook::class, 'parseSimpleTokens'];
    $GLOBALS['TL_HOOKS']['compileFormFields'][] = [ClickAndViews::class, 'increaseViewOnForm'];
    $GLOBALS['TL_HOOKS']['executePostActions'][] = [Hooks::class, 'postActionHookForDC_CMSFile'];
    $GLOBALS['TL_HOOKS']['executePreActions'][] = [Hooks::class, 'executePreActions'];
    $GLOBALS['TL_HOOKS']['generatePage'][] = [Tags::class, 'generateEUConsent'];
    $GLOBALS['TL_HOOKS']['generatePage'][] = [Tags::class, 'generateScripts'];
    $GLOBALS['TL_HOOKS']['generatePage'][] = [ConversionItem::class, 'generateGlobalConversionItems'];
    $GLOBALS['TL_HOOKS']['generatePage'][] = [Session::class, 'storeVisitedPage'];
    $GLOBALS['TL_HOOKS']['getSystemMessages'][] = [Messages::class, 'testModeCheck'];
    $GLOBALS['TL_HOOKS']['getSystemMessages'][] = [License::class, 'getSystemMessages'];
    $GLOBALS['TL_HOOKS']['getSystemMessages'][] = [Messages::class, 'legacyRoutingCheck'];
    $GLOBALS['TL_HOOKS']['getUserNavigation'][] = [Feedback::class, 'setNavigationLink'];
    $GLOBALS['TL_HOOKS']['initializeSystem'][] = [Hooks::class, 'initializeSystem'];
    $GLOBALS['TL_HOOKS']['loadDataContainer'][] = [DCA::class, 'addStylingFields'];
    $GLOBALS['TL_HOOKS']['processFormData'][] = [ClickAndViews::class, 'increaseClickOnForm'];
    $GLOBALS['TL_HOOKS']['replaceInsertTags'][] = [Tags::class, 'replaceTagInsertTags'];
    $GLOBALS['TL_HOOKS']['getContentElement'][] = [Tags::class, 'replaceTagContentModuleElement'];
    $GLOBALS['TL_HOOKS']['getFrontendModule'][] = [Tags::class, 'replaceTagContentModuleElement'];
    $GLOBALS['TL_HOOKS']['replaceInsertTags'][] = [LinkShortener::class, 'replaceLinkShortenerInsertTags'];
    $GLOBALS['TL_HOOKS']['insertTagFlags'][] = [LinkShortener::class, 'replaceLinkShortenerInsertTagFlags'];
    $GLOBALS['TL_HOOKS']['loadDataContainer'][] = [ModuleHelper::class, 'addSQLDefinitionForTagSettings'];

    if( version_compare(VERSION, '4.10', '<') || System::getContainer()->getParameter('contao.legacy_routing') ) {
        $GLOBALS['TL_HOOKS']['getPageIdFromUrl'][] = [ABTestPage::class, 'selectAorBPage'];
    }

    if( TL_MODE === 'BE' ) {
        $GLOBALS['TL_HOOKS']['initializeSystem'][] = [Module::class, 'initializeBackendModuleTables'];
    }

    /**
    * CUSTOM CRONJOBS
    */
    $GLOBALS['TL_HOOKS']['generatePage'][] = [License::class, 'dailyCron'];
    $GLOBALS['TL_HOOKS']['generatePage'][] = [License::class, 'weeklyCron'];
}
