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
use Contao\Frontend;
use Exception;
use numero2\MarketingSuite\Encryption;


class HealthCheckHeaderListener {


    /**
     * @var Contao\CoreBundle\Framework\ContaoFramework;
     */
    private $framework;


    public function __construct( ContaoFramework $framework ) {

        $this->framework = $framework;
    }


    /**
     * Adds an encrypted header containing the id of the root page
     * to the current request
     *
     * @param Symfony\Component\HttpKernel\Event\ResponseEvent $event
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
        } catch( Exception $e ) {
        }

        if( $page ) {

            $pageID = NULL;
            $pageID = Encryption::encrypt($page->id);

            $response = $event->getResponse();
            $response->headers->set('X-CMS-HealthCheck', $pageID);
        }
    }
}
