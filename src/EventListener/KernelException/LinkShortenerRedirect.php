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


namespace numero2\MarketingSuiteBundle\EventListener\KernelException;

use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\Environment;
use numero2\MarketingSuite\LinkShortenerModel;
use numero2\MarketingSuite\LinkShortenerStatisticsModel;
use numero2\MarketingSuite\Tracking\ClickAndViews;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;


class LinkShortenerRedirect {


    /**
     * @var ScopeMatcher
     */
    private $scopeMatcher;


    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;


    /**
     * Constructor.
     *
     * @param ScopeMatcher $scopeMatcher
     * @param ContaoFrameworkInterface $framework
     */
    public function __construct( ScopeMatcher $scopeMatcher, ContaoFrameworkInterface $framework ) {

        $this->scopeMatcher = $scopeMatcher;
        $this->framework = $framework;
    }


    /**
     * Try finding a link shortener enrty for this request.
     *
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException( $event ) {

        if( !$event->isMasterRequest() ) {
            return;
        }

        if( !$this->framework->isInitialized() ) {
            return;
        }

        $request = $event->getRequest();
        if( !$this->scopeMatcher->isFrontendRequest($request) ) {
            return;
        }

        if( $event instanceof GetResponseForExceptionEvent && $event->getException() instanceof PageNotFoundException) {

            $path = $request->getPathInfo();
            $path = urldecode(substr($path, 1));

            $oLink = NULL;
            $oLink = LinkShortenerModel::findOneByPath($path);

            if( $oLink ) {

                $oAgent = NULL;
                $oAgent = Environment::get('agent');

                // save stats for bots but not for backend users (if tracking disabled)
                if( !ClickAndViews::doNotTrack() || ClickAndViews::isBot() ) {

                    $oStats = NULL;
                    $oStats = new LinkShortenerStatisticsModel();

                    $oStats->tstamp = time();
                    $oStats->pid = $oLink->id;
                    $oStats->referer = $request->headers->get('referer');
                    $oStats->unique_id = md5($request->getClientIp().$oAgent->string);
                    $oStats->user_agent = $oAgent->string;
                    $oStats->os = $oAgent->os;
                    $oStats->browser = $oAgent->browser;
                    $oStats->is_mobile = ($oAgent->mobile?'1':'');
                    $oStats->is_bot = '';

                    if( ClickAndViews::isBot() ) {
                        $oStats->is_bot = '1';
                    }

                    $oStats->save();
                }

                $target = $oLink->getTarget();

                if( $target !== null ) {

                    $redirect = new RedirectResponse(
                        $target,
                        Response::HTTP_FOUND,
                        ['Cache-Control' => 'no-cache']
                    );

                    $event->setResponse($redirect);
                }
            }
        }
    }
}
