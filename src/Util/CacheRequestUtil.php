<?php

/**
 * Contao Marketing Suite Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\MarketingSuiteBundle\Util;

use Symfony\Component\HttpFoundation\RequestStack;


class CacheRequestUtil {


    /**
     * @var RequestStack
     */
    private $requestStack;


    public function __construct( RequestStack $requestStack ) {

        $this->requestStack = $requestStack;
    }


    public function set( string $key, $value ): void {

        $request = $this->requestStack->getCurrentRequest();

        if( $request ) {
            $request->attributes->set('cms_'.$key, $value);
        }
    }

    public function has( string $key ): bool {

        $request = $this->requestStack->getCurrentRequest();

        if( $request ) {
            return $request->attributes->has('cms_'.$key);
        }

        return false;
    }

    public function get( string $key ) {

        $request = $this->requestStack->getCurrentRequest();

        if( $request ) {
            return $request->attributes->get('cms_'.$key);
        }

        return null;
    }
}
