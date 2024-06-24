<?php

/**
 * Contao Marketing Suite Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\MarketingSuiteBundle\EventListener\Hooks;

use Contao\CMSConfig;
use Contao\CoreBundle\ServiceAnnotation\Hook;
use numero2\MarketingSuite\Backend\License;


class MessageListener {


    /**
     * Check for enabled test mode
     *
     * @return string
     *
     * @Hook("getSystemMessages")
     */
    public function testModeCheck(): string {

        if( CMSConfig::get('testmode') && !License::hasNoLicense() ) {
            return '<p class="tl_error cms_testmode">' . sprintf(
                $GLOBALS['TL_LANG']['MSC']['testmode_enabled']
            ,   'https://contao-marketingsuite.com/sy5xkh'
            ) . '</p>';
        }

        return '';
    }
}
