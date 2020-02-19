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


namespace numero2\MarketingSuiteBundle\EventListener\KernelResponse;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;


class TestHeaderListener {


    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;



    /**
     * Constructor.
     *
     * @param ContaoFrameworkInterface $framework
     */
    public function __construct( ContaoFrameworkInterface $framework ) {

        $this->framework = $framework;
    }


    /**
     * Adds the test mode headers to the response.
     *
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse( FilterResponseEvent $event ) {

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
