<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2019 Leo Feyer
 *
 * @package   Contao Marketing Suite
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright 2020 numero2 - Agentur für digitales Marketing
 */


require_once TL_ROOT.'/vendor/numero2/contao-marketing-suite/src/Resources/contao/config/constants.php';


/**
 * MODELS
 */
$GLOBALS['TL_MODELS'][\numero2\MarketingSuite\TagModel::getTable()] = '\numero2\MarketingSuite\TagModel';
$GLOBALS['TL_MODELS'][\numero2\MarketingSuite\LinkShortenerModel::getTable()] = '\numero2\MarketingSuite\LinkShortenerModel';
$GLOBALS['TL_MODELS'][\numero2\MarketingSuite\LinkShortenerStatisticsModel::getTable()] = '\numero2\MarketingSuite\LinkShortenerStatisticsModel';
$GLOBALS['TL_MODELS'][\numero2\MarketingSuite\ContentGroupModel::getTable()] = '\numero2\MarketingSuite\ContentGroupModel';
$GLOBALS['TL_MODELS'][\numero2\MarketingSuite\ConversionItemModel::getTable()] = '\numero2\MarketingSuite\ConversionItemModel';
$GLOBALS['TL_MODELS'][\numero2\MarketingSuite\MarketingItemModel::getTable()] = '\numero2\MarketingSuite\MarketingItemModel';


/**
 * BACK END MODULES
 */
if( !empty($GLOBALS['BE_MOD']['content']['news']) ) {
    $GLOBALS['BE_MOD']['content']['news']['cms_schedule'] = ['\numero2\MarketingSuite\BackendModule\NewsSchedule', 'generate'];
}


array_insert($GLOBALS['BE_MOD'], 1, [

    'marketing_suite' => [
        'cms_dashboard' => [
            'callback'  => '\numero2\MarketingSuite\BackendModule\Dashboard'
        ]
    ,   'cms_marketing' => [
            'tables'    => ['tl_cms_marketing_item', 'tl_cms_content_group', 'tl_content']
        ]
    ,   'cms_conversion' => [
            'tables'    => ['tl_cms_conversion_item', 'tl_content']
        ]
    ,   'cms_health_check' => [
            'callback'  => '\numero2\MarketingSuite\BackendModule\HealthCheck'
        ]
    ,   'cms_tools' => [
            'callback'  => '\numero2\MarketingSuite\BackendModule\Module'
        ]
    ,   'cms_settings' => [
            'callback'  => '\numero2\MarketingSuite\BackendModule\Module'
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
        ,   'link_shortener_statistics' => [ '\numero2\MarketingSuite\DCAHelper\LinkShortenerStatistics', 'generate' ]
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
            'callback'  => '\numero2\MarketingSuite\BackendModule\Avalex'
        ,   'icon'      => 'bundles/marketingsuite/img/backend/icons/icon_avalex.svg'
        ]
    ,   'facebook' => [
            'tables'    => ['tl_cms_facebook']
        ,   'icon'      => 'bundles/marketingsuite/img/backend/icons/icon_facebook.svg'
        ,   'pre_form_callback'  => ['\numero2\MarketingSuite\BackendModule\Facebook', 'generatePreForm']
        ]
    ]
];


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
 * FRONT END MODULES
 */
$GLOBALS['FE_MOD']['marketing_suite'] = [
    'cms_marketing_item'    => '\numero2\MarketingSuite\ModuleMarketingItem'
,   'cms_conversion_item'   => '\numero2\MarketingSuite\ModuleConversionItem'
,   'cms_cookie_bar'        => '\numero2\MarketingSuite\ModuleCookieBar'
,   'cms_accept_tags'       => '\numero2\MarketingSuite\ModuleAcceptTags'
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
    'cms_marketing_item'    => '\numero2\MarketingSuite\ContentMarketingItem'
,   'cms_conversion_item'   => '\numero2\MarketingSuite\ContentConversionItem'
];
$GLOBALS['TL_CTE']['conversion_elements'] = [
    'text_cms_cta'  => '\numero2\MarketingSuite\ContentTextCMSCTA'
,   'cms_hyperlink' => '\numero2\MarketingSuite\ContentHyperlink'
,   'cms_button'    => '\numero2\MarketingSuite\ContentButton'
,   'form'          => $GLOBALS['TL_CTE']['includes']['form']
];


/**
 * REGISTER HOOKS
 */
$GLOBALS['TL_HOOKS']['addCustomRegexp'][] = ['\numero2\MarketingSuite\Hooks\Hooks', 'validateRgxp'];
$GLOBALS['TL_HOOKS']['cmsBeHelperParseSimpleTokens']['cms_settings_facebook'][] = ['\numero2\MarketingSuite\BackendModule\Facebook', 'parseSimpleTokens'];
$GLOBALS['TL_HOOKS']['compileFormFields'][] = ['\numero2\MarketingSuite\Tracking\ClickAndViews', 'increaseViewOnForm'];
$GLOBALS['TL_HOOKS']['executePostActions'][] = ['\numero2\MarketingSuite\Hooks\Hooks', 'postActionHookForDC_CMSFile'];
$GLOBALS['TL_HOOKS']['executePreActions'][] = ['\numero2\MarketingSuite\Hooks\Hooks', 'executePreActions'];
$GLOBALS['TL_HOOKS']['generatePage'][] = ['\numero2\MarketingSuite\Hooks\Tags', 'generateEUConsent'];
$GLOBALS['TL_HOOKS']['generatePage'][] = ['\numero2\MarketingSuite\Hooks\Tags', 'generateScripts'];
$GLOBALS['TL_HOOKS']['generatePage'][] = ['\numero2\MarketingSuite\Tracking\Session', 'storeVisitedPage'];
$GLOBALS['TL_HOOKS']['getPageIdFromUrl'][] = ['\numero2\MarketingSuite\MarketingItem\ABTestPage', 'selectAorBPage'];
$GLOBALS['TL_HOOKS']['getSystemMessages'][] = ['\numero2\MarketingSuite\Backend\License', 'getSystemMessages'];
$GLOBALS['TL_HOOKS']['getSystemMessages'][] = ['\numero2\MarketingSuite\Backend\Messages', 'testModeCheck'];
$GLOBALS['TL_HOOKS']['getUserNavigation'][] = ['\numero2\MarketingSuite\BackendModule\Feedback', 'setNavigationLink'];
$GLOBALS['TL_HOOKS']['initializeSystem'][] = ['\numero2\MarketingSuite\Hooks\Hooks', 'initializeSystem'];
$GLOBALS['TL_HOOKS']['loadDataContainer'][] = ['\numero2\MarketingSuite\Hooks\DCA', 'addStylingFields'];
$GLOBALS['TL_HOOKS']['processFormData'][] = ['\numero2\MarketingSuite\Tracking\ClickAndViews', 'increaseClickOnForm'];
$GLOBALS['TL_HOOKS']['replaceInsertTags'][] = ['\numero2\MarketingSuite\Hooks\Tags', 'replaceTagInsertTags'];
$GLOBALS['TL_HOOKS']['getContentElement'][] = ['\numero2\MarketingSuite\Hooks\Tags', 'replaceTagContentModuleElement'];
$GLOBALS['TL_HOOKS']['getFrontendModule'][] = ['\numero2\MarketingSuite\Hooks\Tags', 'replaceTagContentModuleElement'];
$GLOBALS['TL_HOOKS']['replaceInsertTags'][] = ['\numero2\MarketingSuite\Hooks\LinkShortener', 'replaceLinkShortenerInsertTags'];
$GLOBALS['TL_HOOKS']['insertTagFlags'][] = ['\numero2\MarketingSuite\Hooks\LinkShortener', 'replaceLinkShortenerInsertTagFlags'];
$GLOBALS['TL_HOOKS']['loadDataContainer'][] = ['\numero2\MarketingSuite\DCAHelper\Module', 'addSQLDefinitionForTagSettings'];


if( TL_MODE === 'BE' ) {
    $GLOBALS['TL_HOOKS']['initializeSystem'][] = ['\numero2\MarketingSuite\BackendModule\Module', 'initializeBackendModuleTables'];
}


/**
 * CRONJOBS
 */
$GLOBALS['TL_HOOKS']['generatePage'][] = ['\numero2\MarketingSuite\Backend\License', 'dailyCron'];
$GLOBALS['TL_HOOKS']['generatePage'][] = ['\numero2\MarketingSuite\Backend\License', 'weeklyCron'];
