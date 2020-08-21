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


namespace numero2\MarketingSuiteBundle\Routing;

use Contao\CoreBundle\Framework\ContaoFrameworkInterface;
use numero2\MarketingSuite\LinkShortenerModel;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;


class LinkShortenerRouteProvider implements RouteProviderInterface {


    /**
     * @var ContaoFrameworkInterface
     */
    private $framework;


    public function __construct( ContaoFrameworkInterface $framework ) {

        $this->framework = $framework;
    }


    /**
     * {@inheritdoc}
     */
    public function getRouteCollectionForRequest(Request $request) {

        if( !$this->framework->isInitialized() ) {
            $this->framework->initialize(true);
        }

        $alias = urldecode(substr($request->getPathInfo(), 1));

        $oLink = NULL;
        $oLink = LinkShortenerModel::findOneByPath($alias);

        $collection = NULL;
        $collection = new RouteCollection();

        if( $oLink ) {

            foreach( ['prefix', 'alias'] as $field ) {

                if( !strlen($oLink->$field) ) {
                    continue;
                }

                $route = new Route("/".$oLink->$field);
                $route->setDefault(RouteObjectInterface::CONTROLLER_NAME, 'marketing_suite.controller.link_shortener');
                $route->setDefault(RouteObjectInterface::CONTENT_OBJECT, $oLink);
                $route->setHost($oLink->domain);

                // only add route if target is set
                if( $oLink->getTarget() ) {
                    $collection->add("link_shortener.".$oLink->$field.".".$oLink->id, $route);
                }
            }
        }

        return $collection;
    }


    /**
     * {@inheritdoc}
     */
    public function getRouteByName($name) {

        throw new RouteNotFoundException('This router does not support routes by name');
    }


    /**
     * {@inheritdoc}
     */
    public function getRoutesByNames($names) {

        return [];
    }
}
