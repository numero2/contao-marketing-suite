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
use numero2\MarketingSuite\Backend\License;


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
}
