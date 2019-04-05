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
 * @copyright 2019 numero2 - Agentur für digitales Marketing
 */


namespace numero2\MarketingSuiteBundle\Controller;

use numero2\MarketingSuite\Controller\BackendWizardPopup;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


/**
 * Handles the Contao back end routes.
 *
 * @Route(defaults={"_scope" = "backend", "_token_check" = true})
 */
class BackendController extends Controller {

    /**
     * Renders the popup for the cms wizard screen.
     *
     * @return Response
     *
     * @Route("/contao/cms_wizard_popup", name="contao_backend_cms_wizard_popup")
     */
    public function wizardPopup() {

        $this->container->get('contao.framework')->initialize();

        $controller = new BackendWizardPopup();

        return $controller->run();
    }
}
