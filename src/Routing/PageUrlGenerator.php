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


namespace numero2\MarketingSuiteBundle\Routing;

use Symfony\Cmf\Component\Routing\ProviderBasedGenerator;
use Contao\CoreBundle\Routing\PageUrlGenerator as CorePageUrlGenerator;
use Contao\CoreBundle\Routing\Page\PageRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;


// for Contao 4.13 PageUrlGenerator is needed to correctly handle requireItems checkbox
// otherwise this will throw an exception saving a page in backend
if( class_exists('Contao\CoreBundle\Routing\PageUrlGenerator') ) {

    class PageUrlGenerator extends CorePageUrlGenerator {

        public function __construct( RouteProviderInterface $provider, PageRegistry $pageRegistry=null, LoggerInterface $logger=null ) {

            parent::__construct($provider, $pageRegistry, $logger);
        }
    }

} else {

    class PageUrlGenerator extends ProviderBasedGenerator {

        public function __construct( RouteProviderInterface $provider, PageRegistry $pageRegistry=null, LoggerInterface $logger=null ) {

            parent::__construct($provider, $logger);
        }
    }
}
