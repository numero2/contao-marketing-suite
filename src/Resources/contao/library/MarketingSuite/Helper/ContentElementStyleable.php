<?php

/**
 * Contao Marketing Suite Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
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
