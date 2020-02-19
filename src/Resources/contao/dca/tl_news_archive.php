<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2020 Leo Feyer
 *
 * @package   Contao Marketing Suite
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright 2020 numero2 - Agentur für digitales Marketing
 */


/**
 * Table tl_news_archive
 */
if( !empty($GLOBALS['TL_DCA']['tl_news_archive']) ) {


    /**
     * Add callbacks to tl_news_archive
     */
     $GLOBALS['TL_DCA']['tl_news_archive']['config']['onload_callback'][] = [ '\numero2\MarketingSuite\BackendModule\Facebook', 'checkNewsArchiveHasPages' ];


    /**
     * Add global operations to tl_news_archive
     */
    if( \numero2\MarketingSuite\Backend\License::hasFeature('news_schedule') && \numero2\MarketingSuite\Backend\License::hasFeature('news_schedule_multiple') ) {
        array_insert($GLOBALS['TL_DCA']['tl_news_archive']['list']['global_operations'], 1, [
            'cms_schedule' => [
                'label'     => &$GLOBALS['TL_LANG']['tl_news_archive']['cms_schedule']
                ,   'href'      => 'key=cms_schedule'
                ,   'icon'      => 'bundles/marketingsuite/img/backend/icons/icon_news_schedule.svg'
            ]
        ]);
    }


    /**
     * Add palettes to tl_news_archive
     */
    if( \numero2\MarketingSuite\Backend\License::hasFeature('news_publish_facebook') ) {
        $GLOBALS['TL_DCA']['tl_news_archive']['palettes']['default'] = str_replace(
            ';{protected_legend'
        ,   ';{cms_social_media_legend},cms_facebook_pages;{protected_legend'
        ,   $GLOBALS['TL_DCA']['tl_news_archive']['palettes']['default']
        );
    }


    /**
     * Add fields to tl_news_archive
     */
    $GLOBALS['TL_DCA']['tl_news_archive']['fields'] = array_merge(
       $GLOBALS['TL_DCA']['tl_news_archive']['fields']
    ,   [
           'cms_facebook_pages' => [
                'label'             => &$GLOBALS['TL_LANG']['tl_news_archive']['cms_facebook_pages']
            ,   'exclude'           => true
            ,   'inputType'         => 'checkboxWizard'
            ,   'options_callback'  => [ '\numero2\MarketingSuite\BackendModule\Facebook', 'getAvailablePagesOptions' ]
            ,   'eval'              => [ 'multiple'=>true ]
            ,   'sql'               => "blob NULL"
            ]
        ]
    );
}
