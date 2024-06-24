<?php

/**
 * Contao Marketing Suite Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\MarketingSuite\BackendModule;

use Contao\BackendModule as CoreBackendModule;
use Contao\BackendTemplate;
use Contao\Config;
use Contao\ContentModel;
use Contao\Database;
use Contao\Image;
use Contao\PageModel;
use Contao\System;
use numero2\MarketingSuite\Backend;
use numero2\MarketingSuite\Backend\Help;
use numero2\MarketingSuite\Backend\License as ekga;
use numero2\MarketingSuite\Backend\LicenseMessage;
use numero2\MarketingSuite\ContentGroupModel;
use numero2\MarketingSuite\ConversionItemModel;
use numero2\MarketingSuite\LinkShortenerModel;
use numero2\MarketingSuite\MarketingItemModel;
use numero2\MarketingSuite\StatisticModel;
use numero2\MarketingSuiteBundle\EventListener\DataContainer\ConversionItemListener;
use numero2\MarketingSuiteBundle\EventListener\DataContainer\MarketingItemListener;


class Dashboard extends CoreBackendModule {


    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'backend/modules/dashboard';


    /**
     * Generate the module
     */
    protected function compile() {

        $this->loadLanguageFile('cms_be_dashboard');
        ekga::jakrut();

        // get fieldset states
        $objSessionBag = NULL;
        $objSessionBag = System::getContainer()->get('request_stack')->getSession()->getBag('contao_backend');

        $fs = null;
        $fs = $objSessionBag->get('fieldset_states');

        if( !empty($fs['cms_dashboard']) ) {
            $aFsStates = $fs['cms_dashboard'];
        } else {
            $aFsStates = [];
        }

        // add items to the dashboard
        $aItems = [];
        $aLegends = [];

        $this->requestToken = System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();

        $this->addMarketingItems( $aItems, $aLegends );
        $this->addConversionElements( $aItems, $aLegends );
        $this->addLinkShortener( $aItems, $aLegends );

        // add chart.js library
        $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/marketingsuite/vendor/chartjs/Chart.bundle' .(System::getContainer()->get('kernel')->isDebug()?'':'.min'). '.js';

        // initialize backend help
        $oBEHelp = new Help();
        $this->Template->be_help = $oBEHelp->generate();

        // intialize message for missing license
        $oLicMessage = new LicenseMessage();
        $this->Template->licence_message = $oLicMessage->generate();

        // set template vars
        $this->Template->items = $aItems;
        $this->Template->legends = $aLegends;
        $this->Template->fsStates = $aFsStates;
    }


    /**
     * Adds marketing items to the dashboard
     *
     * @param array &$aItems List of all items in dashboard
     * @param array &$aLegends List of all legends in dashboard
     */
    private function addMarketingItems( &$aItems, &$aLegends ) {

        // add marketing items
        $this->loadLanguageFile('tl_cms_marketing_item');

        $oMarketingItems = null;
        $oMarketingItems = MarketingItemModel::findBy(['init_step=?'], [''], ['order'=>'type ASC, tstamp DESC']);

        if( $oMarketingItems ) {

            $routePrefix = System::getContainer()->getParameter('contao.backend.route_prefix');

            while( $oMarketingItems->next() ) {

                $objTemplate = null;
                $objTemplate = new BackendTemplate('backend/modules/dashboard_items/item_'.$oMarketingItems->type);

                $arrRow = [];
                $arrRow = $oMarketingItems->row();

                $ref = System::getContainer()->get('request_stack')->getCurrentRequest()->get('_contao_referer_id');
                $routePrefix = System::getContainer()->getParameter('contao.backend.route_prefix');

                if( $oMarketingItems->type == 'a_b_test' ) {

                    $oContentGroup = null;
                    $oContentGroup = ContentGroupModel::findByPid( $oMarketingItems->id );

                    if( $oContentGroup ) {

                        $arrRow['groups'] = [];

                        while( $oContentGroup->next() ) {
                            $aGroup = $oContentGroup->row();

                            $aGroup['clicks'] = 0;
                            $statsClicks = StatisticModel::countBy(['pid=? AND ptable=? AND type=? AND tstamp>?'], [$oContentGroup->id, ContentGroupModel::getTable(), 'click', $aGroup['reset']]);
                            if( !empty($statsClicks) ) {
                                $aGroup['clicks'] = $statsClicks;
                            }

                            $aGroup['views'] = 0;
                            $statsViews = StatisticModel::countBy(['pid=? AND ptable=? AND type=? AND tstamp>?'], [$oContentGroup->id, ContentGroupModel::getTable(), 'view', $aGroup['reset']]);
                            if( !empty($statsViews) ) {
                                $aGroup['views'] = $statsViews;
                            }

                            $arrRow['groups'][] = $aGroup;
                        }
                    }

                    $arrRow['typeLabel'] = $GLOBALS['TL_LANG']['CTE'][$arrRow['content_type']][0];

                    if( ekga::hasFeature('me_'.$oMarketingItems->type) ) {
                        $arrRow['href'] = $routePrefix.'?do=cms_marketing&amp;table=tl_cms_content_group&amp;id=' .$arrRow['id']. '&amp;rt=' .$this->requestToken. '&amp;ref=' .$ref;
                    }

                } else if( $oMarketingItems->type == 'a_b_test_page' ) {

                    $oPages = null;
                    $oPages = PageModel::findBy( ['id=? OR id=?'], [$oMarketingItems->page_a, $oMarketingItems->page_b] );

                    if( $oPages ) {

                        $arrRow['pages'] = [];

                        while( $oPages->next() ) {

                            // gather clicks on conversion items on this page
                            $oPages->cms_mi_views = 0;
                            $statsViews = StatisticModel::countBy(['pid=? AND ptable=? AND type=? AND tstamp>?'], [$oPages->id, PageModel::getTable(), 'view', $oPages->cms_mi_reset]);
                            if( !empty($statsViews) ) {
                                $oPages->cms_mi_views = $statsViews;
                            }

                            $objCI = ConversionItemModel::findAllOn($oPages->current());

                            $oPages->cms_mi_clicks = 0;
                            if( $objCI ) {
                                $arrCI = $objCI->fetchEach('id');

                                $statsClicks = StatisticModel::countBy(
                                    ['pid in ('.implode(',', $arrCI).') AND ptable=? AND type=? AND tstamp>?'],
                                    [ContentModel::getTable(), 'click', $oPages->cms_mi_reset]
                                );
                                $oPages->cms_mi_clicks = $statsClicks;
                            }

                            $arrRow['pages'][] = $oPages->row();
                        }
                    }

                    if( ekga::hasFeature('me_'.$oMarketingItems->type) ) {
                        $arrRow['href'] = $routePrefix.'?do=cms_marketing&amp;act=edit&amp;id=' .$arrRow['id']. '&amp;rt=' .$this->requestToken. '&amp;ref=' .$ref;
                    }

                } else {

                    continue;
                }

                $arrRow['used'] = MarketingItemListener::generateUsedOverlay($arrRow, Image::getHtml('monitor', $GLOBALS['TL_LANG']['tl_cms_marketing_item']['used']['0']));

                $objTemplate->setData( $arrRow );

                $aItems[ $oMarketingItems->type ][] = $objTemplate->parse();

                if( empty($aLegends[$oMarketingItems->type]) ) {
                    $aLegends[$oMarketingItems->type] = $GLOBALS['TL_LANG']['tl_cms_marketing_item']['types'][$oMarketingItems->type];
                }
            }
        }
    }


    /**
     * Adds conversion elements to the dashboard
     *
     * @param array &$aItems List of all items in dashboard
     * @param array &$aLegends List of all legends in dashboard
     */
    private function addConversionElements( &$aItems, &$aLegends ) {

        $this->loadLanguageFile('tl_content');

        $aElements = [];
        $aElements = array_keys($GLOBALS['TL_CTE']['conversion_elements']);

        $oConversionElements = null;
        $oConversionElements = ContentModel::findBy([ContentModel::getTable().'.type in ("'.implode('","', $aElements).'")'], [], ['order'=>'tstamp DESC']);

        if( $oConversionElements ) {

            $routePrefix = System::getContainer()->getParameter('contao.backend.route_prefix');

            $aLegends['conversion'] = $GLOBALS['TL_LANG']['CTE']['conversion_elements_dash'];
            $routePrefix = System::getContainer()->getParameter('contao.backend.route_prefix');

            while( $oConversionElements->next() ) {

                // skip conversion elements associated to content marketing since
                // they're already tracked there
                if( $oConversionElements->cms_mi_isMainTracker ) {
                    continue;
                }

                $objTemplate = null;
                $objTemplate = new BackendTemplate('backend/modules/dashboard_items/item_conversion');

                $arrRow = [];
                $arrRow = $oConversionElements->row();

                $arrRow['cms_ci_clicks'] = 0;
                $statsClicks = StatisticModel::countBy(['pid=? AND ptable=? AND type=? AND tstamp>?'], [$arrRow['id'], ContentModel::getTable(), 'click', $arrRow['cms_ci_reset']]);
                if( !empty($statsClicks) ) {
                    $arrRow['cms_ci_clicks'] = $statsClicks;
                }

                $arrRow['cms_ci_views'] = 0;
                $statsViews = StatisticModel::countBy(['pid=? AND ptable=? AND type=? AND tstamp>?'], [$arrRow['id'], ContentModel::getTable(), 'view', $arrRow['cms_ci_reset']]);
                if( !empty($statsViews) ) {
                    $arrRow['cms_ci_views'] = $statsViews;
                }

                $do = $this->getModuleNameForPTable( $arrRow['ptable'] );
                $ref = System::getContainer()->get('request_stack')->getCurrentRequest()->get('_contao_referer_id');
                if( ekga::hasFeature('ce_'.$oConversionElements->type) ) {
                    $arrRow['href'] = $routePrefix.'?do='.$do.'&amp;table=tl_content&amp;id=' .$arrRow['id']. '&amp;act=edit&amp;rt=' .$this->requestToken. '&amp;ref=' .$ref;
                }
                if( $do == 'cms_conversion' ) {
                    $arrRow['used'] = ConversionItemListener::generateUsedOverlay($arrRow, Image::getHtml('monitor', $GLOBALS['TL_LANG']['tl_content']['cms_used']['0']));
                } else {

                    $aElements = [];
                    $oContent = ContentModel::findBy([ContentModel::getTable().'.id=?'], [$arrRow['id']], ['return'=>'Collection']);

                    if( $oContent && $oContent->count() ) {
                        $aElements[$GLOBALS['TL_LANG']['MOD']['tl_content']] = $oContent;
                    }

                    if( count($aElements) ) {

                        $aOverlay = [
                            'label' => Image::getHtml('monitor', $GLOBALS['TL_LANG']['tl_content']['cms_used']['0'])
                        ,   'content' => $aElements
                        ,   'position' => 'top_right'
                        ];
                        $arrRow['used'] = Backend::parseWithTemplate('backend/elements/overlay_tree', $aOverlay);
                    }
                }

                $objTemplate->setData( $arrRow );

                $aItems['conversion'][] = $objTemplate->parse();
            }
        }
    }


    /**
     * Adds link shortener to the dashboard
     *
     * @param array &$aItems List of all items in dashboard
     * @param array &$aLegends List of all legends in dashboard
     */
    private function addLinkShortener( &$aItems, &$aLegends ) {

        $this->loadLanguageFile('tl_cms_link_shortener');
        $this->loadLanguageFile('tl_cms_link_shortener_statistics');

        $oLinkShortener = null;
        $oLinkShortener = LinkShortenerModel::findBy(['active=?'], [1], ['order'=>'tstamp DESC']);

        if( $oLinkShortener ) {

            $routePrefix = System::getContainer()->getParameter('contao.backend.route_prefix');

            $aLegends['link_shortener'] = $GLOBALS['TL_LANG']['CMS']['link_shortener'][0];
            $routePrefix = System::getContainer()->getParameter('contao.backend.route_prefix');

            while( $oLinkShortener->next() ) {

                $objTemplate = null;
                $objTemplate = new BackendTemplate('backend/modules/dashboard_items/item_link_shortener');

                $arrRow = [];
                $arrRow = $oLinkShortener->row();

                if( preg_match("/{{link_url::([0-9]*)}}/", $arrRow['target'], $id) ) {

                    $objPage = PageModel::findOneById($id[1]);
                    if( $objPage ) {
                        $arrRow['target'] = $objPage->title . ' (' . $objPage->alias . Config::get('urlSuffix') . ')';
                    }
                }

                $db = Database::getInstance();
                // count requests
                $objResult = $db->prepare("
                    SELECT count(1) AS count
                    FROM tl_cms_link_shortener_statistics
                    WHERE pid=?
                ")->execute($arrRow['id']);

                $arrRow['stats']['requests'] = $objResult->count;

                if( $objResult->count > 0 ) {
                    // count unique requests
                    $objResult = $db->prepare("
                        SELECT DISTINCT unique_id
                        FROM tl_cms_link_shortener_statistics
                        WHERE pid=?
                    ")->execute($arrRow['id']);

                    $arrRow['stats']['unique_requests'] = $objResult->numRows;

                    // count bot requests
                    $objResult = $db->prepare("
                        SELECT count(1) AS bot_count
                        FROM tl_cms_link_shortener_statistics
                        WHERE pid=? AND is_bot=?
                    ")->execute($arrRow['id'], 1);

                    $arrRow['stats']['bot_requests'] = $objResult->bot_count;
                }

                if( ekga::hasFeature('link_shortener') ) {
                    $ref = System::getContainer()->get('request_stack')->getCurrentRequest()->get('_contao_referer_id');

                    $arrRow['href'] = $routePrefix.'?do=cms_tools&amp;mod=link_shortener&amp;table=tl_cms_link_shortener&amp;id=' .$arrRow['id']. '&amp;act=edit&amp;rt=' .$this->requestToken. '&amp;ref=' .$ref;
                    $arrRow['hrefStats'] = $routePrefix.'?do=cms_tools&amp;mod=link_shortener&amp;table=tl_cms_link_shortener&amp;key=link_shortener_statistics&amp;id=' .$arrRow['id']. '&amp;rt=' .$this->requestToken. '&amp;ref=' .$ref;
                }

                $objTemplate->setData( $arrRow );

                $aItems['link_shortener'][] = $objTemplate->parse();
            }
        }
    }


    /**
     * Returns the module name for the given table
     *
     * @param string $strTable
     *
     * @return string
     */
    private function getModuleNameForPTable( $strTable=null ) {

        if( !$strTable ) {
            return false;
        }

        foreach( $GLOBALS['BE_MOD'] as $modules ) {

            foreach( $modules as $module => $data ) {

                if( !empty($data['tables']) && in_array($strTable, $data['tables']) ) {
                    return $module;
                }
            }
        }
    }
}
