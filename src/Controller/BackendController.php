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

use Contao\CoreBundle\Controller\AbstractController;
use numero2\MarketingSuite\Controller\Main;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


if( class_exists('\Contao\CoreBundle\Controller\AbstractController') ) {

    /**
     * Handles the Contao back end routes.
     *
     * @Route(defaults={"_scope": "backend", "_token_check": true})
     */
    class BackendController extends AbstractController {

        /**
         * Renders the popup for the cms wizard screen.
         *
         * @return Response
         *
         * @Route("/contao/cms", name="contao_backend_cms_main")
         */
        public function cmsMain() {

            $this->initializeContaoFramework();

            $controller = new Main();

            return $controller->run();
        }
    }

} else {

    /**
     * Handles the Contao back end routes in Contao 4.4. This can be drop after Contao 4.4
     *
     * @Route(defaults={"_scope" = "backend", "_token_check" = true})
     */
     class BackendController extends Controller {
    }
}
