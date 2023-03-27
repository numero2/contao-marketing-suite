<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2021 Leo Feyer
 *
 * @package   Contao Marketing Suite Administration
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright 2021 numero2 - Agentur für digitales Marketing
 */


namespace numero2\MarketingSuite\Backend;

use Contao\CMSConfig;
use Contao\System;
use numero2\MarketingSuite\Backend\License;
use numero2\MarketingSuite\MarketingItemModel;


class Messages {


    /**
     * Check for enabled test mode
     *
     * @return string
     */
    public static function testModeCheck() {

        if( CMSConfig::get('testmode') && !License::hasNoLicense() ) {
            return '<p class="tl_error cms_testmode">' . sprintf(
                $GLOBALS['TL_LANG']['MSC']['testmode_enabled']
            ,   'https://contao-marketingsuite.com/sy5xkh'
            ) . '</p>';
        }

        return '';
    }


    /**
     * Check if legacy routing is needed and disabled
     *
     * @return string
     */
    public static function legacyRoutingCheck() {

        if( !System::getContainer()->getParameter('contao.legacy_routing') ) {

            // check if a/b test in use
            if( MarketingItemModel::countByType('a_b_test_page') ) {

                return '<p class="tl_error">' . sprintf(
                    $GLOBALS['TL_LANG']['MSC']['legacy_routing_needed']
                ,   'https://contao-marketingsuite.com/2drmx4'
                ) . '</p>';
            }
        }

        return '';
    }
}
