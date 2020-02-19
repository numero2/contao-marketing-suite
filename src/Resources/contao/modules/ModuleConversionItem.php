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


namespace numero2\MarketingSuite;

use Contao\Module;


class ModuleConversionItem extends Module {


    /**
     * Template
     * @var string
     */
    protected $strTemplate = '';


    /**
     * Display a wildcard in the back end
     *
     * @return string
     */
    public function generate() {

        $ce = new ContentConversionItem($this->objModel);
        return $ce->generate();
    }


    /**
     * Generate module
     */
    protected function compile() {

    }
}
