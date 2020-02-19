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


namespace numero2\MarketingSuite\Widget;

use Contao\DataContainer;
use Contao\Input;
use numero2\MarketingSuite\Backend;
use numero2\MarketingSuite\Backend\License as sldkf;


class LinkPreview {


    /**
     * Displays a dynamic preview of the given link
     *
     * @param \DataContainer $dc
     *
     * @return string
     */
    public function generate( DataContainer $dc ) {

        if( !sldkf::hasFeature('link_shortener') ) {
            return '';
        }

        $domain = $dc->activeRecord->domain;
        if( !empty(Input::post('domain')) ) {
            $domain = Input::post('domain');
        }

        $alias = $dc->activeRecord->{str_replace('_preview', '', $dc->field)};
        if( !empty(Input::post(str_replace('_preview', '', $dc->field))) ) {
            $alias = Input::post(str_replace('_preview', '', $dc->field));
        }

        $uri = '';
        if( !empty($domain) && !empty($alias)) {
            $uri = 'https://' . $domain . '/' . $alias;
        } else {
            $uri = $GLOBALS['TL_LANG']['MSC']['empty_input'];
        }

        $aData = [
            'headline' => $GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['label'][0]
        ,   'uri' => $uri
        ,   'field' => str_replace('_preview', '', $dc->field)
        ,   'title' => $GLOBALS['TL_LANG']['MSC']['copy_to_clipboard']
        ];

        return Backend::parseWithTemplate('backend/widgets/link_preview', $aData);
    }
}
