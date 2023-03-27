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


namespace numero2\MarketingSuite\MarketingItem;

use Contao\Cache;
use Contao\Config;
use Contao\Date;
use Contao\Frontend;
use Contao\PageModel;
use Contao\System;
use numero2\MarketingSuite\Backend\License as dsgvfsop;
use numero2\MarketingSuite\ConversionItemModel;
use numero2\MarketingSuite\MarketingItemModel;
use numero2\MarketingSuite\Tracking\ClickAndViews;
use numero2\MarketingSuite\Tracking\Session;


class ABTestPage extends MarketingItem {


    /**
     * Alter child record of tl_content
     *
     * @param array $arrRow
     * @param string $buffer
     * @param object $objMarketingItem
     * @param object $objContentGroup
     *
     * @return string
     */
    public function alterContentChildRecord( $arrRow, $buffer, $objMarketingItem, $objContentGroup ) {
        return $buffer;
    }


    /**
     * Alter header of tl_content
     *
     * @param array $args
     * @param \DataContainer $dc
     * @param object $objMarketingItem
     * @param object $objContentGroup
     *
     * @return array
     */
    public function alterContentHeader( $args, $dc, $objMarketingItem, $objContentGroup ) {
        return $args;
    }


    /**
     * Alter dca configuration of tl_content
     *
     * @param \DataContainer $dc
     * @param object $objMarketingItem
     * @param object $objContent
     * @param object $objContentGroup
     */
    public function alterContentDCA( $dc, $objMarketingItem, $objContent, $objContentGroup ) {
    }


    /**
     * Handles what happens when marketing item is loaded
     *
     * @param \DataContainer $dc
     * @param object $objMI
     */
    public function loadMarketingItem( $dc, $objMI ) {

        if( !empty($objMI->init_step) ) {

            if( count($GLOBALS['TL_DCA'][$dc->table]['edit']['buttons_callback']) ) {
                array_pop($GLOBALS['TL_DCA'][$dc->table]['edit']['buttons_callback']);
                $GLOBALS['TL_DCA'][$dc->table]['edit']['buttons_callback'][] = ['\numero2\MarketingSuite\Backend\Wizard', 'addFinishButton'];
            }
        }
    }


    /**
     * Handles what happens after a user submits the form
     *
     * @param \DataContainer $dc
     * @param object $objMI
     */
    public function submitMarketingItem( $dc, $objMarketingItem ) {

        if( isset($_POST['saveNclose']) ) {

            $objMarketingItem->init_step = '';
            $objMarketingItem->save();
        }
    }


    /**
     * Selects one contentId that should be displayed to the user
     *
     * @param object $objContents
     * @param object $objMI
     * @param object $objContentParent
     * @param object $objContent
     *
     * @return integer
     */
    public function selectContentId( $objContents, $objMI, $objContentParent, $objContent ) {

        if( $objMI->auto_winner_after && $objMI->stop_auto_winner < time() ) {

            // find winner
            $aClicks = [];
            foreach( $objContent as $key => $oPage) {
                $aClicks[$key] = 0;

                // gather clicks on conversion items on this page
                $objCI = ConversionItemModel::findAllOn($oPage);

                if( $objCI ) {
                    $arrCI = $objCI->fetchEach('cms_ci_clicks');
                    $aClicks[$key] += array_sum($arrCI);
                }
            }
            arsort($aClicks);

            // if no real winner keep it running
            if( array_values($aClicks)[0] != array_values($aClicks)[1] ) {

                // select winner and make always use this
                $winnerId = array_keys($aClicks)[0];
                $winnerId = $winnerId == '0'?'page_a':'page_b';

                $objMI->always_page_a = '';
                $objMI->always_page_b = '';

                $objMI->{'always_'.$winnerId} = '1';

                $objMI->auto_winner_after = '';
                $objMI->save();
            }
        }

        // check if alway_use_this was selected
        if( $objMI->always_page_a ) {
            return $objContent[0]->id;
        }
        if( $objMI->always_page_b ) {
            return $objContent[1]->id;
        }

        if( $objMI && count($objContent) == 2 ) {

            $tracking = new Session();
            $views = new ClickAndViews();

            // if already selected in session tracking
            $id = $tracking->getABTestPageSelected($objMI->id);
            if( !in_array($id, [$objContent[0]->id, $objContent[1]->id]) ) {

                // choose page with less views
                if( $objContent[0]->cms_mi_views <= $objContent[1]->cms_mi_views ) {
                    $id = $objContent[0]->id;
                } else {
                    $id = $objContent[1]->id;
                }

                // save selected
                $tracking->storeABTestPageSelected($objMI->id, $id);

                // increase view counter
                $views->increaseViewOnMarketingElement($objContent[$id==$objContent[0]->id?0:1]);
            }

            return $id;
        }

        return null;
    }


    /**
     * Handles what happens after a user submits the child edit form
     *
     * @param \DataContainer $dc
     */
    public function submitContent( $dc ) {
    }


    /**
     * Change settings onload
     *
     * @param \DataContainer $dc
     */
    public function loadContentGroup( $dc ) {
    }


    /**
     * Selects which page will be displayed for the A/B Test Pages
     *
     * @param arr $arrFragments
     */
    public function selectAorBPage( $arrFragments ) {
        // NOTE: this won't be called when domain.tld/ is requested

        $objPage = null;
        $objPages = PageModel::findBy(['alias=?'], $arrFragments[0]);

        $rootPageId = Frontend::getRootPageFromUrl();

        if( $rootPageId ) {
            $rootPageId = $rootPageId->id;
        }

        if( !dsgvfsop::hasFeature('me_a_b_test_page', $rootPageId) ) {
            return $arrFragments;
        }

        if( $objPages && $objPages->count() ) {
            foreach( $objPages as $value) {

                $value = $value->loadDetails();

                // check for same root
                if( $rootPageId == $value->trail[0] ) {

                    $objPage = $value;
                    break;
                }
            }
        }

        if( !$objPage ) {
            return $arrFragments;
        }

        // on health check do not change the current page
        $request = System::getContainer()->get('request_stack')->getCurrentRequest();
        if( $request && $request->headers ) {
            $headers = $request->headers;
            if( $headers && $headers->has('X-Requested-With') == 'CMS-HealthCheck' ) {
                return $arrFragments;
            }
        }

        $objMI = MarketingItemModel::findOneBy(['type=? AND page_a=? AND active=? AND init_step=?'], ['a_b_test_page', $objPage->id, '1', '']);

        if( $objMI ) {

            // save requested page
            Cache::set('cms_page_id', $objPage->id);

            $objPageB = PageModel::findOneById($objMI->page_b);

            $selected = $this->selectContentId(null, $objMI, null, [$objPage, $objPageB]);

            if( $selected == $objPageB->id ) {
                $arrFragments[0] = $objPageB->alias;
            }
        }

        return $arrFragments;
    }


    /**
     * Return a string describing the current status of this a_b_test_page.
     *
     * @param array $arrMI
     *
     * @return string
     */
    public function getStatus( $arrMI ) {

        $strReturn = "";

        if( $arrMI['auto_winner_after'] ) {

            $strAutoWinner = $GLOBALS['TL_LANG']['tl_cms_marketing_item']['list_label']['auto_winner_after_date'];
            $strAutoWinner = sprintf($strAutoWinner, Date::parse(Config::get('datimFormat'), $arrMI['stop_auto_winner']));

            $strReturn = $strAutoWinner;

        } else if( $arrMI['always_page_a'] || $arrMI['always_page_b'] ) {

            $strAlways = $GLOBALS['TL_LANG']['tl_cms_marketing_item']['list_label']['always_use_this_name'];
            $strAlways = sprintf($strAlways, $GLOBALS['TL_LANG']['tl_cms_marketing_item'][$arrMI['always_page_a']?'page_a':'page_b'][0]);

            $strReturn = $strAlways;
        }

        return $strReturn;
    }
}
