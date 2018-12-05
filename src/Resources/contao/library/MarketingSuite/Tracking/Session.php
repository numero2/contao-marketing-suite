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
namespace numero2\MarketingSuite\Tracking;


class Session {


    /**
     * Session data
     * @var Array
     */
    private $session = null;


    public function __construct() {

        $this->session = \Session::getInstance();
    }


    /**
     * stores the current FE page in the session
     */
    public function storeVisitedPage() {

        global $objPage;

        if( TL_MODE != 'FE' || BE_USER_LOGGED_IN ) {
            return;
        }

        $aSession = $this->session->get('cms_visitedPages');
        if( !is_array($aSession) ) {
            $aSession = [];
        }

        if( $aSession[0] != $objPage->id ) {
            array_unshift($aSession, $objPage->id);
            $this->session->set('cms_visitedPages', $aSession);
        }
    }


    /**
     * returns all visited pages in the session
     * [0] -> new, ... [1] -> old
     */
    public function getVisitedPages() {

        $aSession = $this->session->get('cms_visitedPages');
        if( !is_array($aSession) ) {
            $aSession = [];
        }

        return $aSession;
    }


    /**
     * stores which element was selected by which marketing_item
     */
    public function storeABTestSelected($content, $selected) {

        if( TL_MODE != 'FE' || BE_USER_LOGGED_IN ) {
            return;
        }

        $aSession = $this->session->get('cms_a_b_test_selected');
        if( !is_array($aSession) ) {
            $aSession = [];
        }

        $aSession[$content] = $selected;

        $this->session->set('cms_a_b_test_selected', $aSession);
    }


    /**
     * returns which element was selected by which marketing_item
     */
    public function getABTestSelected($content) {

        $aSession = $this->session->get('cms_a_b_test_selected');

        if( is_array($aSession) && !empty($aSession[$content]) ) {
            return $aSession[$content];
        }

        return null;
    }
}
