<?php

/**
 * Contao Marketing Suite Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\MarketingSuiteBundle\EventListener\KernelResponse;

use Contao\CoreBundle\Framework\ContaoFramework;


class TestHeaderListener {


    /**
     * @var Contao\CoreBundle\Framework\ContaoFramework;
     */
    private $framework;


    public function __construct( ContaoFramework $framework ) {

        $this->framework = $framework;
    }


    /**
     * Adds the test mode headers to the response.
     *
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse( $event ) {

        if( !$this->framework->isInitialized() ) {
            return;
        }

        if( !\Contao\CMSConfig::get('testmode') ) {
            return;
        }

        $response = $event->getResponse();

        $response->headers->set('X-CMS-Test-Mode', 'active');
    }
}
