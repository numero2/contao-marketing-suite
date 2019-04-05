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
 * @copyright 2019 numero2 - Agentur für digitales Marketing
 */


namespace numero2\MarketingSuite\Helper;

use Contao\ContentElement;
use Contao\System;


class ContentElementStyleable {


    /**
     * Returns the default stylesheet for the given object
     *
     * @param \ContentElement $object
     *
     * @return string
     */
    public static function getDefaultStylesheet( ContentElement $object ) {

        $reflect = NULL;
        $reflect = new \ReflectionClass($object);

        $path = realpath(__DIR__.'/../../../../public/css/element-defaults');
        $filename = $path . '/' . $reflect->getShortName() . '.css';

        if( file_exists($filename) ) {

            $sStyle = file_get_contents($filename);
            $sStyle = str_replace('{UNIQUE}', self::getUniqueID($object), $sStyle);

            return $sStyle;

        } else {

            throw new \Exception(
                sprintf("No matching default stylesheet found for element %s ",$sElementClass)
            );
        }
    }


    /**
     * Generate a unique id which will be used as a selector in frontend
     *
     * @param \ContentElement $object
     *
     * @return string
     */
    public static function getUniqueID( $object ) {

        return substr(sha1($object->id),0,12);
    }
}