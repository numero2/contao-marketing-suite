<?php

/**
 * Contao Marketing Suite Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\MarketingSuiteBundle\Routing;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\AbstractPageRouteProvider;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Symfony\Cmf\Component\Routing\Candidates\CandidatesInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;


class RouteProvider extends AbstractPageRouteProvider {


    /**
     * @var numero2\MarketingSuiteBundle\Routing\ABTestPageRouteProvider
     */
    private $abTestPageProvider;

    /**
     * @var numero2\MarketingSuiteBundle\Routing\LinkShortenerRouteProvider
     */
    private $linkShortenerProvider;


    public function __construct( ContaoFramework $framework, CandidatesInterface $candidates, PageRegistry $pageRegistry, ABTestPageRouteProvider $abTestPageProvider, LinkShortenerRouteProvider $linkShortenerProvider ) {

        parent::__construct($framework, $candidates, $pageRegistry);

        $this->abTestPageProvider = $abTestPageProvider;
        $this->linkShortenerProvider = $linkShortenerProvider;
    }


    public function getRouteCollectionForRequest( Request $request ): RouteCollection {

        $this->framework->initialize();

        $collection = new RouteCollection();

        $routes = $this->abTestPageProvider->getRouteCollectionForRequest($request);
        if( $routes->count() ) {
            $collection->addCollection($routes);
        }

        $routes = $this->linkShortenerProvider->getRouteCollectionForRequest($request);

        if( $routes->count() ) {
            $collection->addCollection($routes);
        }

        return $collection;
    }


    /**
     * {@inheritdoc}
     */
    public function getRouteByName( $name ): Route {

        throw new RouteNotFoundException('This router does not support routes by name');
    }


    /**
     * {@inheritdoc}
     */
    public function getRoutesByNames( ?array $names=null ): array {

        return [];
    }
}
