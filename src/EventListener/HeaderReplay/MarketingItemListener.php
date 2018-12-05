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


namespace numero2\MarketingSuite\EventListener\HeaderReplay;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Symfony\Component\HttpFoundation\Request;
use Terminal42\HeaderReplay\Event\HeaderReplayEvent;


class MarketingItemListener {


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
     * @param bool         $disableIpCheck
     */
    public function __construct(ScopeMatcher $scopeMatcher, ContaoFrameworkInterface $framework) {

        $this->scopeMatcher = $scopeMatcher;
        $this->framework = $framework;
    }


    /**
     * Adds "CMS-Marketing-Item-<id>" header to the replay response, so that the reverse proxy gains
     * the ability to vary on it. This is needed so that the reverse proxy generates multiple entries
     * for the same URL when there are marketing items on the page.
     *
     * @param HeaderReplayEvent $event
     */
    public function onReplay(HeaderReplayEvent $event) {

        $request = $event->getRequest();

        if( !$this->scopeMatcher->isFrontendRequest($request) ) {
            return;
        }

        $aItems = $this->getMarketingItem($request);

        if( !$aItems || !count($aItems) ) {
            return;
        }

        ksort($aItems);

        $headers = $event->getHeaders();
        foreach( $aItems as $id => $selected ) {

            $headers->set('CMS-Marketing-Item-'.$id, $selected);
        }
    }


    /**
     * Finds all marketing items for this request with the content that will be diplayed
     *
     * @param Request $request
     *
     * @return bool
     */
    private function getMarketingItem(Request $request) {

        // TODO implement
        $this->framework->initialize();

        return [];
    }
}
