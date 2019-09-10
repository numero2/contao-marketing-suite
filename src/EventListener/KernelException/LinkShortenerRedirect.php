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
 * @copyright 2019 numero2 - Agentur für digitales Marketing
 */


namespace numero2\MarketingSuiteBundle\EventListener\KernelException;

use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use numero2\MarketingSuite\LinkShortenerModel;
use numero2\MarketingSuite\LinkShortenerStatisticsModel;
use Symfony\Component\HttpFoundation\Response;


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
    public function onKernelException(GetResponseForExceptionEvent $event) {

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

        if( $event->getException() instanceof PageNotFoundException ) {

            $path = $request->getPathInfo();
            $path = urldecode(substr($path, 1));

            $oLink = NULL;
            $oLink = LinkShortenerModel::findOneByPath($path);

            if( $oLink ) {

                // log request for statistics
                $oAgent = \Environment::get('agent');

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

                if( $oStats->browser == "other" ){

                    $data = json_decode(file_get_contents(TL_ROOT.'/vendor/numero2/contao-marketing-suite/src/Resources/vendor/crawler-user-agents/crawler-user-agents.json'), true);

                    $patterns = array();
                    foreach($data as $entry) {
                        if( preg_match('/'.$entry['pattern'].'/', $oAgent->string) ) {
                            $oStats->is_bot = '1';
                            break;
                        }
                    }
                }

                $oStats->save();

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
