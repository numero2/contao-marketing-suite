<?php

/**
 * Contao Marketing Suite Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\MarketingSuiteBundle\EventListener\Hooks;

use Contao\Controller;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\ServiceAnnotation\Hook;
use Symfony\Component\HttpFoundation\RequestStack;


class ModuleListener {


    /**
     * @var Symfony\Component\HttpFoundation\RequestStack
     */
    private $requestStack;

    /**
     * @var Contao\CoreBundle\Routing\ScopeMatcher
     */
    protected $scopeMatcher;


    public function __construct( RequestStack $requestStack, ScopeMatcher $scopeMatcher ) {

        $this->requestStack = $requestStack;
        $this->scopeMatcher = $scopeMatcher;
    }

    /**
     * Appends tables of all sub modules to the current backend module
     *
     * @Hook("initializeSystem")
     */
    public function initializeBackendModuleTables() {

        $request = $this->requestStack->getCurrentRequest();
        if( !$request || !$this->scopeMatcher->isBackendRequest($request) ) {
            return;
        }

        foreach( $GLOBALS['CMS_MOD'] as $groupName => $cmsConfig ) {

            $moduleGroup = 'cms_'.$groupName;

            if( !array_key_exists($moduleGroup, $GLOBALS['BE_MOD']['marketing_suite']) ) {
                continue;
            }

            foreach( $cmsConfig as $moduleName => $moduleConfig ) {

                if( !array_key_exists('tables', $GLOBALS['BE_MOD']['marketing_suite'][$moduleGroup]) ) {
                    $GLOBALS['BE_MOD']['marketing_suite'][$moduleGroup]['tables'] = [];
                }

                if( array_key_exists('tables', $moduleConfig) ) {

                    foreach( $moduleConfig['tables'] as $moduleTable ) {

                        if( !in_array($moduleTable, $GLOBALS['BE_MOD']['marketing_suite'][$moduleGroup]['tables']) ) {

                            Controller::loadDataContainer($moduleTable);

                            if( !$GLOBALS['TL_DCA'][$moduleTable]['config']['isAvailable'] ) {
                                continue;
                            }

                            $GLOBALS['BE_MOD']['marketing_suite'][$moduleGroup]['tables'][] = $moduleTable;
                        }
                    }
                }
            }

            if( empty($GLOBALS['BE_MOD']['marketing_suite'][$moduleGroup]['tables']) ) {
                unset($GLOBALS['BE_MOD']['marketing_suite'][$moduleGroup]);
            }
        }
    }
}
