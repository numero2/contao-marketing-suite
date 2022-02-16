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

use Contao\Template;


trait TraitContentElementStyleable {


    /**
     * {@inheritdoc}
     */
    public static function getLayoutOptions(): array {
        return ['light','dark'];
    }


    /**
     * {@inheritdoc}
     */
    public static function getLayoutSprite( string $type="" ): string {
        return 'bundles/marketingsuite/img/backend/layouts/'.$type.'.svg';
    }


    /**
     * {@inheritdoc}
     */
    public static function getStylesheetPath(): string {
        return 'bundles/marketingsuite/css/elements.css';
    }


    /**
     * Injects the stylesheet either into the given template or directly into TL_HEAD
     *
     * @param Contao\Template $template
     */
    private function injectStylesheet( ?Template $template=null ): void {

        $link = '<link rel="stylesheet" href="'.self::getStylesheetPath().'">';

        if( $template ) {
            $template->styleSheet = $link;
        } else {
            $GLOBALS['TL_HEAD'][] = $link;
        }
    }
}