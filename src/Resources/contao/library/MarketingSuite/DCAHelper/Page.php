<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2019 Leo Feyer
 *
 * @package   Contao Marketing Suite
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright 2020 numero2 - Agentur für digitales Marketing
 */


namespace numero2\MarketingSuite\DCAHelper;

use Contao\Backend as CoreBackend;
use Contao\DataContainer;
use Contao\Message;


class Page extends CoreBackend {


    /**
     * Adds information if cache is enabled
     *
     * @param mixed $value
     * @param \DataContainer $dc
     *
     * @return string
     */
    public function addCacheInfo( $value, DataContainer $dc ) {

        // display message after 2020-05-01 00:00
        if( $value && time() >= 1588284000 ) {
            Message::addInfo($GLOBALS['TL_LANG']['tl_page']['MSG']['cms_cache_info']);
        }

        return $value;
    }
}
