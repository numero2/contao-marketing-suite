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


namespace numero2\MarketingSuiteBundle\EventListener\KernelResponse;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use numero2\MarketingSuite\Encryption;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;


class HealthCheckHeaderListener {


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
     * Adds an encryptet header containing the id of the root page
     * and adds the "Access-Control-Allow-Origin" header
     * for the current request
     *
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse( FilterResponseEvent $event ) {

        if( !$this->framework->isInitialized() ) {
            return;
        }

        $request = $event->getRequest();

        $response = $event->getResponse();

        // only check on HEAD request with certain header
        if( $request->getMethod() != 'HEAD' && !$request->headers->has('X-Requested-With') ) {
            return;
        }

        $page = NULL;
        $page = \Frontend::getRootPageFromUrl();

        if( $page ) {

            $pageID = NULL;
            $pageID = Encryption::encrypt($page->id);

            $response->headers->set('X-CMS-HealthCheck', $pageID);
        }
    }
}
