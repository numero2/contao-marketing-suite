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


/**
 * Namespace
 */
namespace numero2\MarketingSuite;


class ModuleMarketingItem extends \Module {


    /**
     * Template
     * @var string
     */
    protected $strTemplate = '';


    /**
     * Display a wildcard in the back end
     * @return string
     */
    public function generate() {

        $ce = new ContentMarketingItem($this->objModel);

        return $ce->generate();
    }


    /**
     * Generate module
     */
    protected function compile() {
    }
}
