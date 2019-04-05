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
 * Table tl_calendar_events
 */
if( !empty($GLOBALS['TL_DCA']['tl_calendar_events']) ) {

    $GLOBALS['TL_DCA']['tl_calendar_events']['palettes']['default'] = str_replace(
        ',description;'
    ,   ',description,snippet_preview;'
    ,   $GLOBALS['TL_DCA']['tl_calendar_events']['palettes']['default']
    );

    // disable snippet-preview in multi edit mode
    if( \Input::get('act') != 'editAll' ) {

        if( \numero2\MarketingSuite\Backend\License::hasFeature('page_snippet_preview') ) {

            $GLOBALS['TL_DCA']['tl_calendar_events']['fields']['snippet_preview'] = [
                'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['snippet_preview']
            ,   'input_field_callback'  => ['\numero2\MarketingSuite\Widget\SnippetPreview', 'generate']
            ];

            $GLOBALS['TL_DCA']['tl_calendar_events']['fields']['pageTitle']['eval']['tl_class'] .= ' snippet';
            $GLOBALS['TL_DCA']['tl_calendar_events']['fields']['pageTitle']['eval']['data-snippet-length'] = \numero2\MarketingSuite\Widget\SnippetPreview::TITLE_LENGTH;
            $GLOBALS['TL_DCA']['tl_calendar_events']['fields']['pageTitle']['load_callback'][] = ['\numero2\MarketingSuite\Widget\SnippetPreview', 'addSnippetCount'];
            $GLOBALS['TL_DCA']['tl_calendar_events']['fields']['pageTitle']['wizard'][] = ['\numero2\MarketingSuite\Widget\SnippetPreview', 'generateInputField'];

            $GLOBALS['TL_DCA']['tl_calendar_events']['fields']['description']['eval']['tl_class'] .= ' snippet';
            $GLOBALS['TL_DCA']['tl_calendar_events']['fields']['description']['eval']['data-snippet-length'] = \numero2\MarketingSuite\Widget\SnippetPreview::DESCRIPTION_LENGTH;
            $GLOBALS['TL_DCA']['tl_calendar_events']['fields']['description']['load_callback'][] = ['\numero2\MarketingSuite\Widget\SnippetPreview', 'addSnippetCount'];
            $GLOBALS['TL_DCA']['tl_calendar_events']['fields']['description']['wizard'][] = ['\numero2\MarketingSuite\Widget\SnippetPreview', 'generateInputField'];
        }
    }
}
