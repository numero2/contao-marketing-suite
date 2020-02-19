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
 * Table tl_calendar_events
 */
if( !empty($GLOBALS['TL_DCA']['tl_calendar_events']) ) {

    $GLOBALS['TL_DCA']['tl_calendar_events']['palettes']['default'] = str_replace(
        ',description'
    ,   ',description,snippet_preview;'
    ,   $GLOBALS['TL_DCA']['tl_calendar_events']['palettes']['default']
    );

    if( \numero2\MarketingSuite\Backend\License::hasFeature('page_snippet_preview') ) {

        $GLOBALS['TL_DCA']['tl_calendar_events']['fields'] = array_merge(
            array_slice(
                $GLOBALS['TL_DCA']['tl_calendar_events']['fields']
            ,   0
            ,   array_search('description', array_keys($GLOBALS['TL_DCA']['tl_calendar_events']['fields'])) + 1
            )
        ,   [
                'snippet_preview' => [
                    'label' => &$GLOBALS['TL_LANG']['MSC']['snippet_preview']
                ,   'input_field_callback'  => ['\numero2\MarketingSuite\Widget\SnippetPreview', 'generate']
                ]
            ]
        ,   array_slice(
                $GLOBALS['TL_DCA']['tl_calendar_events']['fields']
            ,   array_search('description', array_keys($GLOBALS['TL_DCA']['tl_calendar_events']['fields']))
            )
        );

        unset($GLOBALS['TL_DCA']['tl_calendar_events']['fields']['serp_preview']);
        unset($GLOBALS['TL_DCA']['tl_calendar_events']['fields']['serpPreview']);
    }
}
