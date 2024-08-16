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

use Contao\System;


class Auth {


    /**
     * checks if the current user is also Logged in in backend
     *
     * @return boolean
     */
    public static function isBackendUserLoggedIn() {

        if( System::getContainer()->has('contao.security.token_checker') ) {

            $objTokenChecker = System::getContainer()->get('contao.security.token_checker');

            if( $objTokenChecker->hasBackendUser() ) {
                return true;
            }
        }

        return false;
    }
}
