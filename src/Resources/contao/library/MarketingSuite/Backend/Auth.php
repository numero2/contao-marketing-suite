<?php

/**
 * Contao Marketing Suite Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\MarketingSuite\Backend;

use Contao\Config;
use Contao\Environment;
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

        // contao >= 4.8
        if( System::getContainer()->has('contao.security.token_checker') ) {

            $objTokenChecker = System::getContainer()->get('contao.security.token_checker');

            if( $objTokenChecker->hasBackendUser() ) {
                return true;
            }

        // contao 4.4
        } else {

            $strCookie = 'BE_USER_AUTH';
            $cookie = Input::cookie($strCookie);

            if( $cookie === null ) {
                return false;
            }

            $hash = System::getSessionHash($strCookie);

            // validate the session hash
            if( $cookie == $hash ) {

                // try to find the session
                $objSession = NULL;
                $objSession = SessionModel::findByHashAndName($hash, $strCookie);

                // validate the session ID and timeout
                if( $objSession !== null && $objSession->sessionID == System::getContainer()->get('session')->getId() && (System::getContainer()->getParameter('contao.security.disable_ip_check') || $objSession->ip == Environment::get('ip')) && ($objSession->tstamp + Config::get('sessionTimeout')) > time() ) {
                    return true;
                }
            }

            return false;
        }
    }
}
