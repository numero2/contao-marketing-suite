<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2021 Leo Feyer
 *
 * @package   Contao Marketing Suite
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright 2021 numero2 - Agentur für digitales Marketing
 */

namespace numero2\MarketingSuiteBundle\EventListener\Menu;

use Contao\BackendUser;
use Contao\CoreBundle\Controller\AbstractController;
use Contao\CoreBundle\Event\MenuEvent;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\System;
use numero2\MarketingSuiteBundle\Controller\BackendController;
use Symfony\Component\Security\Core\Security;


class BackendCustomRouteMenuListener {


    /**
     * @var Security
     */
    private $security;

    /**
     * @var ContaoFramework
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

        if( !class_exists('\Contao\CoreBundle\Controller\AbstractController') ) {
            return;
        }

        $name = $event->getTree()->getName();

        if( $name === 'mainMenu' ) {
            $this->addCustomRouteIfNeeded($event, $user);
        }
    }


    /**
     * add custom route if needed in submenu marketing_suite as given in global CMS_MOD
     *
     * @param MenuEvent $event
     * @param BackendUser $user
     */
    private function addCustomRouteIfNeeded( MenuEvent $event, BackendUser $user ): void {

        if( array_key_exists('marketing_suite', $user->navigation()) ) {

            $request = System::getContainer()->get('request_stack')->getCurrentRequest();

            $tree = $event->getTree();
            $marketingMenu = $tree->getChild('marketing_suite');

            $children = $marketingMenu->getChildren();
            $oController = $request->get('_controller');

            foreach( $children as $key => $menuItem ) {
                if( strpos($key, 'cms_') === 0 && array_key_exists(substr($key, 4), $GLOBALS['CMS_MOD']) ) {
                    $menuItem->setUri(str_replace('/contao?', '/contao/cms?', $menuItem->getUri()));

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
