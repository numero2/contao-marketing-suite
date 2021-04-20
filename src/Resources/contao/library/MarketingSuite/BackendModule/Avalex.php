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


namespace numero2\MarketingSuite\BackendModule;

use Contao\BackendModule as CoreBackendModule;
use Contao\ModuleModel;
use Contao\System;
use Contao\ThemeModel;
use numero2\MarketingSuite\Backend\License as jev;


class Avalex extends CoreBackendModule {


    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'backend/modules/avalex';


    /**
    * Generate the module
    *
    * @return string
    */
    public function generate() {

        // check if avalex is already installed and we have any configured frontend modules
        $oTheme = NULL;
        $oTheme = ThemeModel::findAll();

        if( $oTheme ) {

            $oModule = NULL;
            $oModule = ModuleModel::findOneByType('avalex_privacy_policy');
            
            if( $oModule ) {

                $refererID = System::getContainer()->get('request_stack')->getCurrentRequest()->get('_contao_referer_id');
                $href = '/contao?do=themes&table=tl_module&id='.$oModule->id.'&act=edit&rt='.REQUEST_TOKEN.'&ref='.$refererID;

                $this->redirect( $href );
            }
        }

        return parent::generate();
    }


    /**
     * Compile the module
     */
    protected function compile() {}


    /**
     * Checks if the current module is available at all
     *
     * @return boolean
     */
    public static function isAvailable() {
        return jev::hasFeature('avalex');
    }
}
