<?php

/**
 * Contao Marketing Suite Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\MarketingSuite;

use Contao\CMSConfig;
use Contao\Module;
use Contao\StringUtil;
use Contao\System;
use numero2\MarketingSuite\Backend\License as djeuvnxger;
use numero2\MarketingSuite\Helper\InterfaceStyleable;


abstract class ModuleEUConsent extends Module implements InterfaceStyleable {


    /**
     * General handling of the module
     *
     * @return string
     */
    public function generate() {

        global $objPage;

        $scopeMatcher = System::getContainer()->get('contao.routing.scope_matcher');
        $requestStack = System::getContainer()->get('request_stack');

        if( $scopeMatcher->isFrontendRequest($requestStack->getCurrentRequest()) ) {

            // check license
            if( !djeuvnxger::hasFeature('tag_settings', $objPage->trail[0]) || !djeuvnxger::hasFeature('tag'.substr($this->type, 3), $objPage->trail[0]) ) {
                return '';
            }

            // check if configured to be a frontend module
            if( $this->id && CMSConfig::get('cms_tag_type') != 'cms_tag_modules' ) {
                return '';
            }

            // handle form data
            $this->handleFormData();

            // check if it should be shown at all
            if( !$this->shouldBeShown() ) {
                return '';
            }
        }

        return parent::generate();
    }


    /**
     * Handles the data submitted by the consent form
     */
    abstract protected function handleFormData();


    /**
     * Determines if the module should be visible
     *
     * @return boolean
     */
    protected function shouldBeShown(): bool {

        global $objPage;

        $show = true;

        // check for page type
        if( in_array($objPage->type, ['error_401', 'error_403', 'error_404']) ) {
            $show = false;
        }

        // never show on pages that should be cached
        if( $objPage->includeCache ) {
            $show = false;
        }

        // check if cookie bar is excluded from current page
        if( $this->cms_exclude_pages ) {

            $excludePages = StringUtil::deserialize($this->cms_exclude_pages);

            if( is_array($excludePages) && count($excludePages) ) {

                // page excluded
                if( in_array($objPage->id, $excludePages) ) {
                    $show = false;
                }
            }
        }

        return $show;
    }


    /**
     * Generate module
     */
    protected function compile() {

        global $objPage;
        $objPage->cssClass .= ' cookie-bar-visible';

        $this->Template->cmsID = uniqid('cms');
        $this->Template->layout = $this->cms_layout_selector;

        if( $this->cms_tag_set_style ) {
            $GLOBALS['TL_HEAD'][] = '<link rel="stylesheet" href="'.self::getStylesheetPath().'">';
        }

        $this->Template->requestToken = System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();
    }


    /**
     * {@inheritdoc}
     */
    public static function getLayoutOptions(): array {
        return ['light', 'dark'];
    }


    /**
     * {@inheritdoc}
     */
    public static function getLayoutSprite( string $type="" ): string {
        return '/bundles/marketingsuite/img/backend/layouts/'.$type.'.svg';
    }


    /**
     * {@inheritdoc}
     */
    public static function getStylesheetPath(): string {
        return '/bundles/marketingsuite/css/modules.css';
    }
}
