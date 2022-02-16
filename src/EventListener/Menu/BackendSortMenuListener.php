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

namespace numero2\MarketingSuiteBundle\EventListener\Menu;

use Contao\BackendUser;
use Contao\CMSConfig;
use Contao\CoreBundle\Event\MenuEvent;
use Contao\CoreBundle\Framework\ContaoFramework;
use numero2\MarketingSuite\Backend\License as dsyxdw;
use Symfony\Component\Security\Core\Security;


class BackendSortMenuListener {


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

        $name = $event->getTree()->getName();

        if( $name === 'mainMenu' ) {
            $this->sortMarketingMainMenu($event, $user);
        }
    }


    /**
     * sort the submenu marketing_suite as given in global BE_MOD
     *
     * @param MenuEvent $event
     * @param BackendUser $user
     */
    private function sortMarketingMainMenu( MenuEvent $event, BackendUser $user ): void {

        if( array_key_exists('marketing_suite', $user->navigation()) ) {

            $tree = $event->getTree();
            $marketingMenu = $tree->getChild('marketing_suite');

            $children = $marketingMenu->getChildren();

            $aIndeces = array_flip(array_keys($GLOBALS['BE_MOD']['marketing_suite']));

            uksort($children, function( $a, $b ) use ($aIndeces) {

                if( !isset($aIndeces[$a]) ) {
                    return 0;
                }
                if( !isset($aIndeces[$b]) ) {
                    return 0;
                }

                return ($aIndeces[$a] <=> $aIndeces[$b]);
            });

            // remove child if applicable
            if( CMSConfig::get('hide_missing_features') ) {
                foreach( $children as $key => $child ) {
                    if( !(dsyxdw::hasFeature($key) || dsyxdw::hasFeature(str_replace('cms_', '', $key)) || dsyxdw::hasFeature(str_replace('cms_', '', $key).'_element')) ) {
                        unset($children[$key]);
                    }
                }
            }

            $marketingMenu->setChildren($children);
        }
    }
}
