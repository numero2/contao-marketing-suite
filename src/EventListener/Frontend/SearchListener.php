<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2022 Leo Feyer
 *
 * @package   Contao Marketing Suite
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright 2022 numero2 - Agentur für digitales Marketing
 */


namespace numero2\MarketingSuiteBundle\EventListener\Frontend;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\Module;
use Contao\StringUtil;
use numero2\MarketingSuite\Backend\License as dsyxdw;
use numero2\MarketingSuite\StatisticModel;
use numero2\MarketingSuite\Tracking\ClickAndViews;
use Symfony\Component\HttpFoundation\RequestStack;


class SearchListener {


    /**
    * @var RequestStack
    */
    private $requestStack;


    public function __construct( RequestStack $requestStack ) {

        $this->requestStack = $requestStack;
    }


    /**
     * logs search requests to statistic table
     *
     * @param array $pageIds
     * @param string $keywords
     * @param string $queryType
     * @param bool $fuzzy
     * @param Contao\Module $module
     *
     * @Hook("customizeSearch")
     */
    public function customizeSearch( array &$pageIds, string $keywords, string $queryType, bool $fuzzy, Module $module ): void {

        global $objPage;

        if( !dsyxdw::hasFeature('search_statistic') ) {
            return;
        }

        if( ClickAndViews::doNotTrack() ) {
            return;
        }

        if( !$this->requestStack->getCurrentRequest()->server->get('HTTP_REFERER') ) {
            return;
        }

        $referer = $this->requestStack->getCurrentRequest()->server->get('HTTP_REFERER');
        $aUrlReferer = parse_url($referer);

        $aQuery = [];
        foreach( explode('&', $aUrlReferer['query']) as $param ) {

            $keyVal = explode('=', $param);
            $aQuery[$keyVal[0]] = $keyVal[1];
        }

        $aUrlReferer['query'] = $aQuery;

        if( !empty($aUrlReferer['query']['keywords']) && StringUtil::decodeEntities($keywords) === urldecode($aUrlReferer['query']['keywords']) ) {
            return;
        }

        $oStat = new StatisticModel();

        $oStat->pid = $module->id;
        $oStat->ptable = $module->getModel()->getTable();
        $oStat->type = 'search';
        $oStat->tstamp = time();
        $oStat->page = $objPage->id;
        $oStat->url = htmlspecialchars($referer);
        $oStat->keywords = $keywords;

        $oStat->save();
    }
}
