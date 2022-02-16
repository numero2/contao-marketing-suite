<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2021 Leo Feyer
 *
 * @package   Contao Marketing Suite
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright 2021 numero2 - Agentur für digitales Marketing
 */


namespace numero2\MarketingSuite\Helper;

use Contao\ContentElement;


class ContentElementStyleable {


    /**
     * Generate a unique id which will be used as a selector in frontend
     *
     * @param \ContentElement $object
     *
     * @return string
     */
    public static function getUniqueID( ContentElement $object ): string {
        return substr(sha1($object->id),0,12);
    }
}
