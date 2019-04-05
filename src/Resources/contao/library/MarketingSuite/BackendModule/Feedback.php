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
 * @copyright 2018 numero2 - Agentur für digitales Marketing
 */


namespace numero2\MarketingSuite\BackendModule;

use Contao\BackendModule as CoreBackendModule;
use numero2\MarketingSuite\Backend\License as bavedmef;
use numero2\MarketingSuite\ContentGroupModel;
use numero2\MarketingSuite\MarketingItemModel;


class Feedback extends CoreBackendModule {


    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'backend/modules/feedback';

    /**
     * URL for the feedback form
     * @var string
     */
    private $redirectUri = "https://contao-marketingsuite.com/beta-feedback.html";


    /**
     * Generate the module
     */
    protected function compile() {

        bavedmef::KSCP();
        $this->redirect($this->redirectUri);
    }


    /**
     * Overwrites href for the module in the navigation
     *
     * @param array $arrModules
     *
     * @return array
     */
    public function setNavigationLink( $arrModules=[] ) {

        if( !empty($arrModules['marketing_suite']) && !empty($arrModules['marketing_suite']['modules']['cms_feedback']) ) {

            $arrModules['marketing_suite']['modules']['cms_feedback']['href'] = $this->redirectUri;

            // only Contao 4.4 allows us to add a target attribute
            if( version_compare(VERSION, '4.5', '<') ) {
                $arrModules['marketing_suite']['modules']['cms_feedback']['href'] .= '" target="_blank';
            }
        }

        return $arrModules;
    }
}
