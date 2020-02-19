<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2019 Leo Feyer
 *
 * @package   Contao Marketing Suite Administration
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright 2020 numero2 - Agentur für digitales Marketing
 */


namespace numero2\MarketingSuite\Backend;

use Contao\Input;
use Contao\SessionModel;
use Contao\System;


class Auth {


    /**
     * checks if the current user is also Logged in in backend
     *
     * @return boolean
     */
    public static function isBackendUserLoggedIn() {

        // since 4.8
        if( System::getContainer()->has('contao.security.token_checker') ) {

            $objTokenChecker = System::getContainer()->get('contao.security.token_checker');

            if( $objTokenChecker->hasBackendUser() ) {
                return true;
            }
        }

        // needed for contao 4.4
        $strCookie = 'BE_USER_AUTH';
        $cookie = Input::cookie($strCookie);

        if( $cookie === null ) {
            return false;
        }

        $hash = System::getSessionHash($strCookie);

        // Validate the cookie hash
        if( $cookie == $hash ) {
            // Try to find the session
            $objSession = SessionModel::findByHashAndName($hash, $strCookie);

            // Validate the session ID and timeout
            if( $objSession !== null && $objSession->sessionID == System::getContainer()->get('session')->getId() && (System::getContainer()->getParameter('contao.security.disable_ip_check') || $objSession->ip == \Environment::get('ip')) && ($objSession->tstamp + \Config::get('sessionTimeout')) > time() ) {

                return true;
            }
        }

        return false;
    }
}
