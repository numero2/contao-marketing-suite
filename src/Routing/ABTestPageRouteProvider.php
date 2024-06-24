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
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\CoreBundle\Routing\RouteProvider;
use Contao\PageModel;
use numero2\MarketingSuite\Backend\License as bhtuwe;
use numero2\MarketingSuite\MarketingItem\ABTestPage;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;


class ABTestPageRouteProvider implements RouteProviderInterface {


    /**
     * @var Contao\CoreBundle\Framework\ContaoFramework
     */
    private $framework;

    /**
     * @var Contao\CoreBundle\Routing\RouteProvider
     */
    private $coreRouteProvide;


    public function __construct( ContaoFramework $framework,RouteProvider $coreRouteProvide ) {

        $this->framework = $framework;
        $this->coreRouteProvide = $coreRouteProvide;
    }


    /**
     * {@inheritdoc}
     */
    public function getRouteCollectionForRequest( Request $request ): RouteCollection {

        if( !$this->framework->isInitialized() ) {
            $this->framework->initialize(true);
        }

        $coreCollection = $this->coreRouteProvide->getRouteCollectionForRequest($request);

        if( !$coreCollection->count() ) {
            return new RouteCollection();
        }

        $route = null;
        foreach( $coreCollection as $name => $r ) {
            $route = $r;
            break;
        }

        if( !($route instanceof PageRoute) ) {
            return new RouteCollection();
        }

        $page = $route->getPageModel();

        if( !bhtuwe::hasFeature('me_a_b_test_page', $page->rootId) ) {
            return new RouteCollection();
        }

        $collection = new RouteCollection();

        $oMI = new ABTestPage();
        $pageSelected = $oMI->selectAorBPage($page);

        $path = $route->getPath();

        if( $pageSelected->id === $page->id ) {

            $routeA = $this->generateRoute($route, $page, '', 'a_test');
            $collection->add($routeA->getRouteKey(), $routeA);

        } else  {

            $pageSelected->preventSaving();
            $pageSelected->alias = $page->alias;

            $routeB = $this->generateRoute($route, $pageSelected, '', 'b_test');
            $collection->add($routeB->getRouteKey(), $routeB);
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


    /**
     * Generate a contao page route based on route and pagemodel
     *
     * @param Contao\CoreBundle\Routing\Page\PageRoute $route
     * @param Contao\PageModel $page
     * @param string $path
     * @param string $routeKeySuffix
     *
     * @return Contao\CoreBundle\Routing\Page\PageRoute
     */
    private function generateRoute( PageRoute $route, PageModel $page, string $path, string $routeKeySuffix ): PageRoute {

        $route = new PageRoute(
            $page,
            $path,
            $route->getDefaults(),
            $route->getRequirements(),
            $route->getOptions(),
            $route->getMethods()
        );

        $route->setRouteKey($route->getRouteKey() .'.'. $routeKeySuffix);

        return $route;
    }
}
