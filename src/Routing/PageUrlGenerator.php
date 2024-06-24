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

use Contao\CoreBundle\Routing\Page\PageRegistry;
use Contao\CoreBundle\Routing\PageUrlGenerator as CorePageUrlGenerator;
use Psr\Log\LoggerInterface;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;


class PageUrlGenerator extends CorePageUrlGenerator {


    public function __construct( RouteProviderInterface $provider, PageRegistry $pageRegistry=null, LoggerInterface $logger=null ) {

        parent::__construct($provider, $pageRegistry, $logger);
    }
}
