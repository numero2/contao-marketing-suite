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


namespace numero2\MarketingSuiteBundle\Controller;

use Contao\Environment;
use numero2\MarketingSuite\LinkShortenerStatisticsModel;
use numero2\MarketingSuite\Tracking\ClickAndViews;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class LinkShortenerController {


    /**
     * Will be called when a matching route was found and will redirect to the target
     *
     * @param  LinkShortenerModel $_content
     * @param  Request $request
     *
     * @return Response
     */
    public function __invoke($_content, Request $request) {

        $oAgent = NULL;
        $oAgent = Environment::get('agent');

        // save stats for bots but not for backend users (if tracking disabled)
        if( !ClickAndViews::doNotTrack() || ClickAndViews::isBot() ) {

            $oStats = NULL;
            $oStats = new LinkShortenerStatisticsModel();

            $oStats->tstamp = time();
            $oStats->pid = $_content->id;
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

        return new RedirectResponse(
            $_content->getTarget(),
            Response::HTTP_FOUND,
            ['Cache-Control' => 'no-cache']
        );
    }
}
