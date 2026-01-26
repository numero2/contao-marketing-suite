<?php

/**
 * Contao Marketing Suite Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2026, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\MarketingSuiteBundle\EventListener\Hooks;

use Contao\CMSConfig;
use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\LayoutModel;
use Contao\System;
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


    /**
     * Check for potential use of Twig layouts
     *
     * @return string
     *
     * @Hook("getSystemMessages")
     */
    public function twigLayoutCheck(): string {

        $schemaManager = System::getContainer()->get('database_connection')->createSchemaManager();
        $tableColumns = $schemaManager->listTableColumns( LayoutModel::getTable() );

        if( array_key_exists('type',$tableColumns) ) {

            $layout = LayoutModel::findByType('modern');

            if( $layout?->count() > 0 ) {
                return '<p class="tl_error cms_twig_layout">' . $GLOBALS['TL_LANG']['MSC']['twig_layout_detected'] . '</p>';
            }
        }

        return '';
    }
}
