<?php

/**
 * Contao Marketing Suite Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\MarketingSuite\Helper;


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
