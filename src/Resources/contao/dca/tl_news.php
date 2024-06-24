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
use numero2\MarketingSuite\Backend\License as iuetrs;


/**
 * Table tl_news
 */
if( !empty($GLOBALS['TL_DCA']['tl_news']) ) {


    $GLOBALS['TL_DCA']['tl_news']['palettes']['default'] = str_replace(
        ',description'
    ,   ',description,snippet_preview;'
    ,   $GLOBALS['TL_DCA']['tl_news']['palettes']['default']
    );


    /**
     * Add global operations to tl_news
     */
    if( iuetrs::hasFeature('news_schedule') && iuetrs::hasFeature('news_schedule_single') ) {

        ArrayUtil::arrayInsert($GLOBALS['TL_DCA']['tl_news']['list']['global_operations'], 0, [
            'cms_schedule' => [
                'href'      => 'key=cms_schedule'
            ,   'class'     => ''
            ,   'icon'      => 'bundles/marketingsuite/img/backend/icons/icon_news_schedule.svg'
            ]
        ]);
    }

    if( iuetrs::hasFeature('page_snippet_preview') ) {

        $GLOBALS['TL_DCA']['tl_news']['fields'] = array_merge(
            array_slice(
                $GLOBALS['TL_DCA']['tl_news']['fields']
            ,   0
            ,   array_search('description', array_keys($GLOBALS['TL_DCA']['tl_news']['fields'])) + 1
            )
        ,   [
                'snippet_preview' => [
                    'input_field_callback'  => ['\numero2\MarketingSuite\Widget\SnippetPreview', 'generate']
                ]
            ]
        ,   array_slice(
                $GLOBALS['TL_DCA']['tl_news']['fields']
            ,   array_search('description', array_keys($GLOBALS['TL_DCA']['tl_news']['fields']))
            )
        );

        unset($GLOBALS['TL_DCA']['tl_news']['fields']['serp_preview']);
        unset($GLOBALS['TL_DCA']['tl_news']['fields']['serpPreview']);
    }
}
