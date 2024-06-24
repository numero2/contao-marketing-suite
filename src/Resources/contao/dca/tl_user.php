<?php

/**
 * Contao Marketing Suite Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


use Contao\CoreBundle\DataContainer\PaletteManipulator;


/**
 * Add palettes to tl_user
 */
PaletteManipulator::create()
    ->addLegend('cms_legend', 'session_legend', PaletteManipulator::POSITION_BEFORE)
    ->addField(['cms_pro_mode_enabled'], 'cms_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('login', 'tl_user');

foreach( ['admin', 'default', 'group', 'extend', 'custom'] as $value ) {

    PaletteManipulator::create()
        ->addLegend('cms_legend', 'theme_legend', PaletteManipulator::POSITION_BEFORE)
        ->addField(['cms_pro_mode_enabled'], 'cms_legend', PaletteManipulator::POSITION_APPEND)
        ->applyToPalette($value, 'tl_user');
}


/**
 * Add fields to tl_user
 */
$GLOBALS['TL_DCA']['tl_user']['fields']['cms_pro_mode_enabled'] = [
    'exclude'       => true
,   'inputType'     => 'checkbox'
,   'eval'          => ['tl_class'=>'w50']
,   'sql'           => "char(1) NOT NULL default ''"
];