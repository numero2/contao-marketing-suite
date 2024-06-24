<?php

/**
 * Contao Marketing Suite Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\MarketingSuiteBundle\Controller;

use numero2\MarketingSuite\LinkShortenerStatisticsModel;
use numero2\MarketingSuiteBundle\Tracking\ClickAndViews;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use UAParser\Parser;


class LinkShortenerController {


    /**
     * Will be called when a matching route was found and will redirect to the target
     *
     * @param numero2\MarketingSuite\LinkShortenerModel $_content
     * @param Symfony\Component\HttpFoundation\Request $request
     *
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function __invoke( $_content, Request $request ) {

        $userAgent = null;
        if( $request->headers->has('user-agent') ) {

            $userAgent = $request->headers->get('user-agent');

            $parser = Parser::create();
            $oUserAgent = $parser->parse($userAgent);

            // save stats for bots but not for backend users (if tracking disabled)
            if( !ClickAndViews::doNotTrack() || ClickAndViews::isBot() ) {

                $oStats = NULL;
                $oStats = new LinkShortenerStatisticsModel();

                $oStats->tstamp = time();
                $oStats->pid = $_content->id;
                $oStats->referer = $request->headers->get('referer') ?? '';
                $oStats->unique_id = md5($request->getClientIp().$userAgent);
                $oStats->user_agent = $userAgent;
                $oStats->os = $oUserAgent->os->family;
                $oStats->browser = $oUserAgent->ua->family;
                $oStats->device = $oUserAgent->device->family;
                $oStats->is_bot = '';

                if( ClickAndViews::isBot() ) {
                    $oStats->is_bot = '1';
                }

                $oStats->save();
            }
        }

        return new RedirectResponse(
            $_content->getTarget(),
            Response::HTTP_FOUND,
            ['Cache-Control' => 'no-cache']
        );
    }
}
