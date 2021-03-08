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
use Contao\Frontend;
use numero2\MarketingSuite\Encryption;


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
     * Adds an encrypted header containing the id of the root page
     * to the current request
     *
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse( $event ) {

        if( !$this->framework->isInitialized() ) {
            return;
        }

        $request = $event->getRequest();

        // only check on special header
        if( !$request->headers->has('X-Requested-With') || $request->headers->get('X-Requested-With') != 'CMS-HealthCheck' ) {
            return;
        }

        $page = NULL;

        try {
            $page = Frontend::getRootPageFromUrl();
        } catch( \Exception $e ) {
        }

        if( $page ) {

            $pageID = NULL;
            $pageID = Encryption::encrypt($page->id);

            $response = $event->getResponse();
            $response->headers->set('X-CMS-HealthCheck', $pageID);
        }
    }
}
