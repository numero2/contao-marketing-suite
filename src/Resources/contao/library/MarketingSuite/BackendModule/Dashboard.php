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
 * Namespace
 */
namespace numero2\MarketingSuite\BackendModule;

use numero2\MarketingSuite\MarketingItemModel;
use numero2\MarketingSuite\ContentGroupModel;


class Dashboard extends \BackendModule {


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
        \numero2\MarketingSuite\Backend\License::jakrut();

        // get fieldset states
        $objSessionBag = NULL;
        $objSessionBag = \System::getContainer()->get('session')->getBag('contao_backend');

        $fs = NULL;
        $fs = $objSessionBag->get('fieldset_states');

        if( !empty($fs['cms_dashboard']) ) {
            $aFsStates = $fs['cms_dashboard'];
        } else {
            $aFsStates = [];
        }

        // add items to the dashboard
        $aItems = [];
        $aLegends = [];

        $this->addMarketingItems( $aItems, $aLegends );
        $this->addConversionElements( $aItems, $aLegends );

        // add chart.js library
        $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/marketingsuite/vendor/chartjs/Chart.bundle' .(\System::getContainer()->get('kernel')->isDebug()?'':'.min'). '.js';

        // initialize backend help
        $objBEHelp = new \numero2\MarketingSuite\Backend\Help();
        $this->Template->be_help = $objBEHelp->generate();

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

        $oMarketingItems = NULL;
        $oMarketingItems = MarketingItemModel::findAll(['order'=>'type ASC, tstamp DESC']);

        if( $oMarketingItems ) {

            while( $oMarketingItems->next() ) {

                $objTemplate = NULL;
                $objTemplate = new \BackendTemplate('backend/modules/dashboard_items/item_'.$oMarketingItems->type);

                $arrRow = [];
                $arrRow = $oMarketingItems->row();

                if( $oMarketingItems->type == 'a_b_test' ) {

                    $oContentGroup = NULL;
                    $oContentGroup = ContentGroupModel::findByPid( $oMarketingItems->id );

                    if( $oContentGroup ) {

                        $arrRow['groups'] = [];

                        while( $oContentGroup->next() ) {
                            $arrRow['groups'][] = $oContentGroup->row();
                        }
                    }

                } else {

                    continue;
                }

                $arrRow['typeLabel'] = $GLOBALS['TL_LANG']['CTE'][$arrRow['content_type']][0];

                $ref = \System::getContainer()->get('request_stack')->getCurrentRequest()->get('_contao_referer_id');
                $arrRow['href'] = 'contao?do=cms_marketing&amp;table=tl_cms_content_group&amp;id=' .$arrRow['id']. '&amp;rt=' .REQUEST_TOKEN. '&amp;ref=' .$ref;

                $objTemplate->setData( $arrRow );

                $aItems[ $oMarketingItems->type ][] = $objTemplate->parse();

                if( empty($aLegends[$oMarketingItems->type]) ) {
                    $aLegends[$oMarketingItems->type] = $GLOBALS['TL_LANG']['tl_cms_marketing_item']['types'][$oMarketingItems->type];
                }
            }
        }
    }


    /**
     * Adds coonversion elements to the dashboard
     *
     * @param array &$aItems List of all items in dashboard
     * @param array &$aLegends List of all legends in dashboard
     */
    private function addConversionElements( &$aItems, &$aLegends ) {

        $this->loadLanguageFile('tl_content');

        $aElements = [];
        $aElements = array_keys($GLOBALS['TL_CTE']['conversion_elements']);

        $oConversionElements = NULL;
        $oConversionElements = \ContentModel::findBy(['type in ("'.implode('","', $aElements).'")'], [], ['order'=>'tstamp DESC']);

        if( $oConversionElements ) {

            $aLegends['conversion'] = $GLOBALS['TL_LANG']['CTE']['conversion_elements_dash'];

            while( $oConversionElements->next() ) {

                // skip conversion elements associated to content marketing since
                // they're already tracked there
                if( $oConversionElements->cms_mi_isMainTracker ) {
                    continue;
                }

                $objTemplate = NULL;
                $objTemplate = new \BackendTemplate('backend/modules/dashboard_items/item_conversion');

                $arrRow = [];
                $arrRow = $oConversionElements->row();

                $do = $this->getModuleNameForPTable( $arrRow['ptable'] );
                $ref = \System::getContainer()->get('request_stack')->getCurrentRequest()->get('_contao_referer_id');
                $arrRow['href'] = 'contao?do='.$do.'&amp;table=tl_content&amp;id=' .$arrRow['id']. '&amp;act=edit&amp;rt=' .REQUEST_TOKEN. '&amp;ref=' .$ref;

                $objTemplate->setData( $arrRow );

                $aItems['conversion'][] = $objTemplate->parse();
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
    private function getModuleNameForPTable( $strTable=NULL ) {

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
