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

use Contao\CMSConfig;


class Messages {


    /**
     * Check for enabled test mode
     *
     * @return string
     */
    public function testModeCheck() {

        if( CMSConfig::get('testmode') ) {
            return '<p class="tl_error">' . $GLOBALS['TL_LANG']['MSC']['testmode_enabled'] . '</p>';
        }

        return '';
    }
}
