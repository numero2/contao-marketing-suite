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
 * Add palettes to tl_user
 */
$GLOBALS['TL_DCA']['tl_user']['palettes']['login'] = str_replace(
    '{session_legend'
,   '{cms_legend},cms_pro_mode_enabled;{session_legend'
,   $GLOBALS['TL_DCA']['tl_user']['palettes']['login']
);

foreach( ['admin', 'default', 'group', 'extend', 'custom'] as $value ) {
    $GLOBALS['TL_DCA']['tl_user']['palettes'][$value] = str_replace(
        '{theme_legend'
    ,   '{cms_legend},cms_pro_mode_enabled;{theme_legend'
    ,   $GLOBALS['TL_DCA']['tl_user']['palettes'][$value]
    );
}


/**
 * Add fields to tl_user
 */
$GLOBALS['TL_DCA']['tl_user']['fields']['cms_pro_mode_enabled'] = [
    'label'         => &$GLOBALS['TL_LANG']['tl_user']['cms_pro_mode_enabled']
,   'exclude'       => true
,   'inputType'     => 'checkbox'
,   'eval'          => ['tl_class'=>'w50']
,   'sql'           => "char(1) NOT NULL default ''"
];