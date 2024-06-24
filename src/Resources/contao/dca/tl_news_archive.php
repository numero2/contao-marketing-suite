<?php

/**
 * Contao Marketing Suite Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


use Contao\ArrayUtil;
use numero2\MarketingSuite\Backend\License as ghasdw;


/**
 * Table tl_news_archive
 */
if( !empty($GLOBALS['TL_DCA']['tl_news_archive']) ) {


    /**
     * Add global operations to tl_news_archive
     */
    if( ghasdw::hasFeature('news_schedule') && ghasdw::hasFeature('news_schedule_multiple') ) {

        ArrayUtil::arrayInsert($GLOBALS['TL_DCA']['tl_news_archive']['list']['global_operations'], 1, [
            'cms_schedule' => [
                    'href'      => 'key=cms_schedule'
                ,   'class'     => ''
                ,   'icon'      => 'bundles/marketingsuite/img/backend/icons/icon_news_schedule.svg'
            ]
        ]);
    }
}
