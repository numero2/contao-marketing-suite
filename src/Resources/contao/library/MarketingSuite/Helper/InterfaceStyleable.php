<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2019 Leo Feyer
 *
 * @package   Contao Marketing Suite
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright 2020 numero2 - Agentur für digitales Marketing
 */


namespace numero2\MarketingSuite\Helper;


interface styleable {


    /**
     * Generates the Stylsheet based on the settings in cms_style
     *
     * @return string
     */
    public function generateStylesheet();


    /**
     * Enables a preview mode where the element should be rendered containing
     * some default values
     *
     * @param boolean $active
     */
    public function setStylePreview( $active=true );

}