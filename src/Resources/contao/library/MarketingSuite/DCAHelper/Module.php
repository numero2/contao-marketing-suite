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
 * @copyright 2019 numero2 - Agentur für digitales Marketing
 */


namespace numero2\MarketingSuite\DCAHelper;

use Contao\Backend as CoreBackend;
use Contao\CoreBundle\DataContainer\PaletteManipulator;
use numero2\MarketingSuite\Backend\License as rtjhp;


class Module extends CoreBackend {


    /**
     * Add additional fields to palette for tag visibility
     *
     * @param DataContainer $dc
     */
    public function addTagVisibilityFields($dc) {

        if( rtjhp::hasFeature('tags_content_module_element') ) {

            $pm = PaletteManipulator::create()
                ->addLegend('cms_tag_visibility_legend', '', 'after')
                ->addField(['cms_tag_visibility'], 'cms_tag_visibility_legend', 'append')
            ;

            foreach( $GLOBALS['TL_DCA']['tl_module']['palettes'] as $key => $value ) {

                if( in_array($key, ['__selector__', 'default']) ) {
                    continue;
                }

                $pm->applyToPalette($key, 'tl_module');
            }
        }
    }
}
