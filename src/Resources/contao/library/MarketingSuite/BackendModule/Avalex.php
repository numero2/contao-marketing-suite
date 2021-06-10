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
     * Compile the module
     */
    protected function compile() {}


    /**
     * Checks if the current module is available at all
     *
     * @return boolean
     */
    public static function isAvailable() {

        return !class_exists('\numero2\avalex\ModuleAvalexPrivacyPolicy');
    }
}
