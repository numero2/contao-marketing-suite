<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2022 Leo Feyer
 *
 * @package   Contao Marketing Suite
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright 2022 numero2 - Agentur für digitales Marketing
 */


namespace numero2\MarketingSuiteBundle\Util;

use Exception;


class DoctrineUtil {


    /**
     * probe if the function exist for the given objects and returns the first result.
     *
     * @param string $name
     * @param array $arguments
     *
     * @return mixed
     */
    public static function __callStatic( string $name, array $arguments ) {

        foreach( $arguments as $object ) {
            if( method_exists($object, $name) ) {
                return $object->{$name}();
            }
        }

        throw new Exception("Error processing " . $name . " for this doctrine version", 1);
    }
}
