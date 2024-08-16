<?php

/**
 * Contao Marketing Suite Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\MarketingSuiteBundle\EventListener\Menu;

use Contao\BackendUser;
use Contao\CoreBundle\Controller\AbstractController;
use Contao\CoreBundle\Event\MenuEvent;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\System;
use Symfony\Bundle\SecurityBundle\Security;


class BackendCustomRouteMenuListener {


    /**
     * @var Symfony\Bundle\SecurityBundle\Security
     */
    private $security;

    /**
     * @var Contao\CoreBundle\Framework\ContaoFramework
     */
    private $framework;


    public function __construct( Security $security, ContaoFramework $framework ) {

        $this->security = $security;
        $this->framework = $framework;
    }


    public function __invoke( MenuEvent $event ): void {

        $user = $this->security->getUser();

        if( !$user instanceof BackendUser ) {
            return;
        }

        if( !class_exists(AbstractController::class) ) {
            return;
        }

        $name = $event->getTree()->getName();

        if( $name === 'mainMenu' ) {
            $this->addCustomRouteIfNeeded($event, $user);
        }
    }


    /**
     * Add custom route if needed in submenu marketing_suite as given in global CMS_MOD
     *
     * @param Contao\CoreBundle\Event\MenuEvent $event
     * @param Contao\BackendUser $user
     */
    private function addCustomRouteIfNeeded( MenuEvent $event, BackendUser $user ): void {

        if( array_key_exists('marketing_suite', $user->navigation()) ) {

            $request = System::getContainer()->get('request_stack')->getCurrentRequest();
            $routePrefix = System::getContainer()->getParameter('contao.backend.route_prefix');

            $tree = $event->getTree();
            $marketingMenu = $tree->getChild('marketing_suite');

            $children = $marketingMenu->getChildren();
            $oController = $request->get('_controller');

            foreach( $children as $key => $menuItem ) {
                if( strpos($key, 'cms_') === 0 && array_key_exists(substr($key, 4), $GLOBALS['CMS_MOD']) ) {
                    $menuItem->setUri(str_replace($routePrefix.'?', $routePrefix.'/cms?', $menuItem->getUri()));
                }
            }

            // pretend we're on the "core" route so Contao will store the correct referer (needed for working backlinks)
            if( $request->attributes->get('_route') == 'contao_backend_cms_main' ) {
                $request->attributes->set('_route', 'contao_backend');
            }

            $marketingMenu->setChildren($children);
        }
    }
}
