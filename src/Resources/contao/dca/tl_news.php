<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2018 Leo Feyer
 *
 * @package   Contao Marketing Suite
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright 2018 numero2 - Agentur für digitales Marketing
 */


/**
 * Table tl_news
 */
if( !empty($GLOBALS['TL_DCA']['tl_news']) ) {


    \System::loadLanguageFile('tl_cms_facebook');

    $GLOBALS['TL_DCA']['tl_news']['palettes']['default'] = str_replace(
        ',description'
    ,   ',description,snippet_preview;'
    ,   $GLOBALS['TL_DCA']['tl_news']['palettes']['default']
    );


    /**
     * Add callbacks to tl_news
     */
     $GLOBALS['TL_DCA']['tl_news']['config']['onload_callback'][] = [ '\numero2\MarketingSuite\BackendModule\Facebook', 'showOpenGraphHint' ];
     $GLOBALS['TL_DCA']['tl_news']['config']['onload_callback'][] = [ '\numero2\MarketingSuite\BackendModule\Facebook', 'checkNewsArchiveHasPages' ];


    /**
     * Add global operations to tl_news
     */
    if( \numero2\MarketingSuite\Backend\License::hasFeature('news_schedule') && \numero2\MarketingSuite\Backend\License::hasFeature('news_schedule_single') ) {

        array_insert($GLOBALS['TL_DCA']['tl_news']['list']['global_operations'], 0, [
            'cms_schedule' => [
                'label'     => &$GLOBALS['TL_LANG']['tl_news']['cms_schedule']
            ,   'href'      => 'key=cms_schedule'
            ,   'icon'      => 'bundles/marketingsuite/img/backend/icons/icon_news_schedule.svg'
            ]
        ]);
    }

    /**
     * Add palettes to tl_news
     */
    if( \numero2\MarketingSuite\Backend\License::hasFeature('news_publish_facebook') ) {

        $GLOBALS['TL_DCA']['tl_news']['palettes']['default'] = str_replace(
            ';{publish_legend}'
        ,   ';{cms_social_media_legend},cms_publish_facebook;{publish_legend}'
        ,   $GLOBALS['TL_DCA']['tl_news']['palettes']['default']
        );
    }

    $GLOBALS['TL_DCA']['tl_news']['palettes']['__selector__'][] = 'cms_publish_facebook';
    $GLOBALS['TL_DCA']['tl_news']['subpalettes']['cms_publish_facebook'] = 'cms_facebook_pages';


    /**
     * Add fields to tl_news
     */
    $GLOBALS['TL_DCA']['tl_news']['fields'] = array_merge(
       $GLOBALS['TL_DCA']['tl_news']['fields']
    ,   [
           'cms_publish_facebook' => [
                'label'              => &$GLOBALS['TL_LANG']['tl_news']['cms_publish_facebook']
            ,   'exclude'            => true
            ,   'inputType'          => 'checkbox'
            ,   'eval'               => [ 'submitOnChange'=>true ]
            ,   'sql'                => "char(1) NOT NULL default ''"
            ]
        ,   'cms_facebook_pages' => [
                 'label'             => &$GLOBALS['TL_LANG']['tl_news']['cms_facebook_pages']
             ,   'exclude'           => true
             ,   'inputType'         => 'checkboxWizard'
             ,   'options_callback'  => [ '\numero2\MarketingSuite\BackendModule\Facebook', 'getAvailablePagesOptionsForNews' ]
             ,   'eval'              => [ 'mandatory'=>true, 'multiple'=>true ]
             ,   'sql'               => "blob NULL"
             ]
         ,   'cms_facebook_queue_publish' => [
                'sql'                => "char(1) NOT NULL default ''"
            ]
         ,   'cms_facebook_posts' => [
                'sql'                => "blob NULL"
            ]
        ]
    );

    if( \numero2\MarketingSuite\Backend\License::hasFeature('news_publish_facebook') ) {

        $GLOBALS['TL_DCA']['tl_news']['fields']['published']['save_callback'][] = [ '\numero2\MarketingSuite\BackendModule\Facebook', 'queueForPublishing' ];
        $GLOBALS['TL_DCA']['tl_news']['fields']['published']['load_callback'][] = [ '\numero2\MarketingSuite\BackendModule\Facebook', 'publishUpdatePost' ];
    }

    if( \numero2\MarketingSuite\Backend\License::hasFeature('page_snippet_preview') ) {

        $GLOBALS['TL_DCA']['tl_news']['fields'] = array_merge(
            array_slice(
                $GLOBALS['TL_DCA']['tl_news']['fields']
            ,   0
            ,   array_search('description', array_keys($GLOBALS['TL_DCA']['tl_news']['fields'])) + 1
            )
        ,   [
                'snippet_preview' => [
                    'label' => &$GLOBALS['TL_LANG']['MSC']['snippet_preview']
                ,   'input_field_callback'  => ['\numero2\MarketingSuite\Widget\SnippetPreview', 'generate']
                ]
            ]
        ,   array_slice(
                $GLOBALS['TL_DCA']['tl_news']['fields']
            ,   array_search('description', array_keys($GLOBALS['TL_DCA']['tl_news']['fields']))
            )
        );

        unset($GLOBALS['TL_DCA']['tl_news']['fields']['serp_preview']);
    }
}
