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

use Contao\CoreBundle\Routing\ScopeMatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;


class BackendAssetsListener implements EventSubscriberInterface {


    /**
     * @var Contao\CoreBundle\Routing\ScopeMatcher
     */
    protected $scopeMatcher;


    public function __construct( ScopeMatcher $scopeMatcher ) {
        $this->scopeMatcher = $scopeMatcher;
    }


    public static function getSubscribedEvents(): array {
        return [KernelEvents::REQUEST => 'onKernelRequest'];
    }


    public function onKernelRequest( RequestEvent $e ): void {

        $request = $e->getRequest();

        if( $this->scopeMatcher->isBackendRequest($request) ) {
            $GLOBALS['TL_CSS'][] = 'bundles/marketingsuite/css/backend/backend.css';
            $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/marketingsuite/js/backend.js';
        }
    }
}
