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


namespace numero2\MarketingSuite\Helper;

use Contao\DataContainer;


interface InterfaceStyleable {


    /**
     * Returns a list layout options to choose from
     *
     * @return array
     */
    public static function getLayoutOptions(): array;


    /**
     * Returns the path to an SVG containing a sprite of all layouts for a preview
     *
     * @param string $type The type of the element
     *
     * @return string
     */
    public static function getLayoutSprite( string $type="" ): string;


    /**
     * Returns the path to a stylesheet which will be injected into TL_HEAD
     *
     * @return string
     */
    public static function getStylesheetPath(): string;
}
