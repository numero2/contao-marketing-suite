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


$GLOBALS['TL_DCA']['tl_module']['config']['onload_callback'][] = ['\numero2\MarketingSuite\DCAHelper\Module', 'addTagVisibilityFields'];


/**
 * Add palettes to tl_module
 */
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'cms_tag_visibility';
$GLOBALS['TL_DCA']['tl_module']['palettes']['cms_marketing_item'] = '{title_legend},name,type;{config_legend},cms_mi_id';
$GLOBALS['TL_DCA']['tl_module']['palettes']['cms_conversion_item'] = '{title_legend},name,type;{config_legend},cms_ci_id';


/**
 * Add subpalettes to tl_module
 */
$GLOBALS['TL_DCA']['tl_module']['subpalettes']['cms_tag_visibility']= 'cms_tag';


/**
 * Add fields to tl_module
 */
$GLOBALS['TL_DCA']['tl_module']['fields']['cms_mi_id'] = [
    'label'             => &$GLOBALS['TL_LANG']['tl_module']['cms_mi_id']
,   'inputType'         => 'select'
,   'foreignKey'        => 'tl_cms_marketing_item.name'
,   'eval'              => ['mandatory'=>true, 'chosen'=>'true', 'inlcudeBlankOption'=>true, 'tl_class'=>'clr w50 wizard']
,   'options_callback'  => ['\numero2\MarketingSuite\DCAHelper\MarketingItem','getAvailableOptions']
,   'wizard'            => [['\numero2\MarketingSuite\DCAHelper\MarketingItem','marketingItemWizard']]
,   'relation'          => ['type'=>'hasOne', 'load'=>'lazy']
,   'sql'               => "int(10) unsigned NOT NULL default '0'"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['cms_ci_id'] = [
    'label'             => &$GLOBALS['TL_LANG']['tl_module']['cms_ci_id']
,   'inputType'         => 'select'
,   'options_callback'  => ['\numero2\MarketingSuite\DCAHelper\ConversionItem', 'getConversionElements']
,   'eval'              => ['mandatory'=>true, 'chosen'=>'true', 'inlcudeBlankOption'=>true, 'tl_class'=>'clr w50 wizard']
,   'wizard'            => [['\numero2\MarketingSuite\DCAHelper\ConversionItem','conversionItemWizard']]
,   'sql'               => "int(10) unsigned NOT NULL default '0'"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['cms_tag_visibility'] = [
    'label'             => &$GLOBALS['TL_LANG']['tl_module']['cms_tag_visibility']
,   'inputType'         => 'checkbox'
,   'eval'              => ['submitOnChange'=>true]
,   'sql'               => "char(1) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['cms_tag'] = [
    'label'             => &$GLOBALS['TL_LANG']['tl_module']['cms_tag']
,   'inputType'         => 'select'
,   'options_callback'  => ['\numero2\MarketingSuite\DCAHelper\Content', 'getContentElementTags']
,   'eval'              => ['mandatory'=>true, 'chosen'=>true, 'includeBlankOption'=>true, 'tl_class'=>'clr w50']
,   'sql'               => "int(10) unsigned NOT NULL default '0'"
];
