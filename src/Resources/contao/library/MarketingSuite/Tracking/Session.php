<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2023 Leo Feyer
 *
 * @package   Contao Marketing Suite
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright 2023 numero2 - Agentur für digitales Marketing
 */


namespace numero2\MarketingSuite\Tracking;

use Contao\Cache;
use Contao\Database;
use Contao\Session as CoreSession;
use numero2\MarketingSuite\Backend\License as koqfklea;


class Session {


    /**
     * max entries of visited pages in the session
     */
    const MAX_ENTRIES = 100;


    /**
     * Session data
     * @var \Session
     */
    private $_session = null;


    /**
     * Constructor
     */
    public function __construct() {

    }
    

    public function __get($name) {
        
        // we don't want to start a session unless we actually need it
        if( $name == 'session' ) {

            $this->_session = $this->_session??CoreSession::getInstance();
            return $this->_session;
        }
    }


    /**
     * Stores the current FE page in the session
     */
    public function storeVisitedPage() {

        global $objPage;

        if( TL_MODE != 'FE' || !koqfklea::hasFeature('me_visited_pages', $objPage->trail[0]) ) {
            return;
        }

        // check if `visited_pages` is used at all
        $oInUse = null;
        $oInUse = Database::getInstance()->query("
            SELECT
                COUNT(1) AS count
            FROM tl_cms_marketing_item as mi
            WHERE
                    mi.type='visited_pages'
                AND mi.init_step = ''
                AND mi.active = 1
        ");

        if( $oInUse && !$oInUse->count ) {
            return;
        }

        $aSession = $this->session->get('cms_visitedPages');

        if( !is_array($aSession) ) {
            $aSession = [];
        }

        $pageId = 0;
        $pageId = $objPage->id;

        // get requested page maybe changed by a_b_test_page
        if( Cache::has('cms_page_id') ) {
            $pageId = Cache::get('cms_page_id');
        }

        // stored pages if it's a new page id
        if( empty($aSession[0]) || $aSession[0] != $pageId ) {

            array_unshift($aSession, $pageId);

            // cut stored pages to MAX_ENTRIES length, leave last in as it is used in first page criteria
            if( count($aSession) > self::MAX_ENTRIES ) {
                array_splice($aSession, self::MAX_ENTRIES-1, -1);
            }

            $this->session->set('cms_visitedPages', $aSession);
        }
    }


    /**
     * Returns all visited pages in the session
     * [0] -> new, ... [1] -> old
     *
     * @return array
     */
    public function getVisitedPages() {

        $aSession = $this->session->get('cms_visitedPages');

        if( !is_array($aSession) ) {
            $aSession = [];
        }

        return $aSession;
    }


    /**
     * Stores which element was selected by which marketing_item
     *
     * @param integer $contentID
     * @param integer $selectedID
     */
    public function storeABTestSelected( $contentID, $selectedID ) {

        if( TL_MODE != 'FE' ) {
            return;
        }

        $aSession = $this->session->get('cms_a_b_test_selected');
        if( !is_array($aSession) ) {
            $aSession = [];
        }

        $aSession[$contentID] = $selectedID;

        $this->session->set('cms_a_b_test_selected', $aSession);
    }


    /**
     * Returns which element was selected by which marketing_item
     *
     * @param integer $contentID
     *
     * @return integer
     */
    public function getABTestSelected( $contentID ) {

        $aSession = $this->session->get('cms_a_b_test_selected');

        if( is_array($aSession) && !empty($aSession[$contentID]) ) {
            return $aSession[$contentID];
        }

        return null;
    }


    /**
     * Stores which page was selected by which marketing_item
     *
     * @param integer $pageID
     * @param integer $selectedID
     */
    public function storeABTestPageSelected( $pageID, $selectedID ) {

        if( TL_MODE != 'FE' ) {
            return;
        }

        $aSession = $this->session->get('cms_a_b_test_page_selected');
        if( !is_array($aSession) ) {
            $aSession = [];
        }

        $aSession[$pageID] = $selectedID;

        $this->session->set('cms_a_b_test_page_selected', $aSession);
    }


    /**
     * Returns which page was selected by which marketing_item
     *
     * @param integer $pageID
     *
     * @return integer
     */
    public function getABTestPageSelected( $pageID ) {

        $aSession = $this->session->get('cms_a_b_test_page_selected');

        if( is_array($aSession) && !empty($aSession[$pageID]) ) {
            return $aSession[$pageID];
        }

        return null;
    }


    /**
     * Stores that the given overlay was closed
     *
     * @param integer $contentID
     * @param integer $changed
     * @param integer $expires
     */
    public function storeOverlayClosed( $contentID, $changed, $expires ) {

        if( TL_MODE != 'FE' ) {
            return;
        }

        $aSession = $this->session->get('cms_overlay_closed');
        if( !is_array($aSession) ) {
            $aSession = [];
        }

        $aSession[$contentID] = ['changed'=>$changed, 'expire'=>$expires];

        $this->session->set('cms_overlay_closed', $aSession);
    }


    /**
     * Returns which page was selected by which marketing_item
     *
     * @param integer $contentID
     * @param integer $changed
     *
     * @return integer
     */
    public function getOverlayClosed( $contentID, $changed ) {

        $aSession = $this->session->get('cms_overlay_closed');

        if( is_array($aSession) && !empty($aSession[$contentID]) ) {
            return $changed <= $aSession[$contentID]['changed'] && time() <= $aSession[$contentID]['expire'];
        }

        return false;
    }
}
