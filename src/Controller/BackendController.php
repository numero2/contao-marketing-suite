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


namespace numero2\MarketingSuiteBundle\Controller;

use Contao\CoreBundle\Controller\AbstractController;
use numero2\MarketingSuite\Controller\Main;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


/**
 * Handles the Contao back end routes.
 *
 * @Route(defaults={"_scope": "backend", "_token_check": true})
 */
class BackendController extends AbstractController {


    /**
     * Renders the custom backend main route.
     *
     * @return Response
     *
     * @Route("%contao.backend.route_prefix%/cms", name="contao_backend_cms_main")
     */
    public function cmsMain() {

        $this->initializeContaoFramework();

        $controller = new Main();

        return $controller->run();
    }
}
