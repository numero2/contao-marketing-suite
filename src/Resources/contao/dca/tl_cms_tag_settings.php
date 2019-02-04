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
 * Table tl_cms_tag_settings
 */
$GLOBALS['TL_DCA']['tl_cms_tag_settings'] = [

    'config' => [
        'dataContainer'             => 'CMSFile'
    ,   'isAvailable'               => \numero2\MarketingSuite\Backend\License::hasFeature('tag_settings')
    ,   'closed'                    => true
    ]
,   'palettes' => [
        '__selector__' => ['cms_tag_type', 'cms_tag_override_label']
    ,   'default' => "{title_legend},cms_tag_type"
    ,   'cms_cookie_bar' => "{title_legend},cms_tag_type;{config_legend:hide},cms_tag_override_label,cms_tag_reject_label,cms_exclude_pages;{style_legend:hide},cms_tag_font_color,cms_tag_background_color,cms_tag_accept_font,cms_tag_accept_background,cms_tag_reject_font,cms_tag_reject_background;{template_legend:hide},cms_tag_customTpl;{expert_legend:hide},cms_tag_cssID"
    ,   'cms_accept_tags' => "{title_legend},cms_tag_type;{config_legend:hide},cms_tag_override_label,cms_exclude_pages;{style_legend:hide},cms_tag_font_color,cms_tag_background_color,cms_tag_accept_font,cms_tag_accept_background,cms_tag_reject_font,cms_tag_reject_background;{template_legend:hide},cms_tag_customTpl;{expert_legend:hide},cms_tag_cssID"
    ]
,   'subpalettes' => [
        'cms_tag_override_label' => 'cms_tag_accept_label,cms_tag_text'
    ]
,   'fields' => [
        'cms_tag_type' => [
            'label'            => &$GLOBALS['TL_LANG']['tl_cms_tag_settings']['type']
        ,   'default'          => 'default'
        ,   'inputType'        => 'select'
        ,   'options_callback' => [ 'tl_cms_tag_settings', 'getFronendTypes']
        ,   'reference'        => &$GLOBALS['TL_LANG']['tl_cms_tag_settings']['types']
        ,   'eval'             => [ 'chosen'=>true, 'submitOnChange'=>true, 'tl_class'=>'w50' ]
        ,   'mapping'          => 'type'
        ]
    ,   'cms_tag_override_label' => [
            'label'            => &$GLOBALS['TL_LANG']['tl_cms_tag_settings']['cms_tag_override_label']
        ,   'inputType'        => 'checkbox'
        ,   'eval'             => [ 'tl_class'=>'w50', 'submitOnChange'=>true ]
        ]
    ,   'cms_tag_accept_label' => [
            'label'            => &$GLOBALS['TL_LANG']['tl_cms_tag_settings']['cms_tag_accept_label']
        ,   'inputType'        => 'text'
        ,   'eval'             => [ 'mandatory'=>true, 'maxlength'=>64, 'tl_class'=>'w50' ]
        ]
    ,   'cms_tag_reject_label' => [
            'label'            => &$GLOBALS['TL_LANG']['tl_cms_tag_settings']['cms_tag_reject_label']
        ,   'inputType'        => 'text'
        ,   'eval'             => [ 'maxlength'=>64, 'tl_class'=>'clr w50' ]
        ]
    ,   'cms_tag_text' => [
            'label'            => &$GLOBALS['TL_LANG']['tl_cms_tag_settings']['cms_tag_text']
        ,   'inputType'        => 'textarea'
        ,   'eval'             => [ 'mandatory'=>true, 'rte'=>'tinyMarketing', 'tl_class'=>'clr' ]
        ]
    ,   'cms_tag_customTpl' => [
            'label'            => &$GLOBALS['TL_LANG']['tl_cms_tag_settings']['cms_tag_customTpl']
        ,   'inputType'        => 'select'
        ,   'options_callback' => ['tl_cms_tag_Settings', 'getModuleTemplates']
        ,   'eval'             => ['includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50']
        ,   'mapping'          => 'customTpl'
        ]
    ,   'cms_tag_cssID' => [
            'label'            => &$GLOBALS['TL_LANG']['tl_cms_tag_settings']['cms_tag_cssID']
        ,   'inputType'        => 'text'
        ,   'eval'             => ['multiple'=>true, 'size'=>2, 'tl_class'=>'w50']
        ,   'mapping'          => 'cssID'
        ]
    ,   'cms_tag_font_color' => [
            'label'            => &$GLOBALS['TL_LANG']['tl_cms_tag_settings']['cms_tag_font_color']
        ,   'inputType'        => 'text'
        ,   'eval'             => ['maxlength'=>6, 'colorpicker'=>true, 'isHexColor'=>true, 'decodeEntities'=>true, 'tl_class'=>'w50 wizard']
        ,   'mapping'          => 'fontcolor'
        ]
    ,   'cms_tag_background_color' => [
            'label'            => &$GLOBALS['TL_LANG']['tl_cms_tag_settings']['cms_tag_background_color']
        ,   'inputType'        => 'text'
        ,   'eval'             => ['maxlength'=>6, 'colorpicker'=>true, 'isHexColor'=>true, 'decodeEntities'=>true, 'tl_class'=>'w50 wizard']
        ,   'mapping'          => 'bgcolor'
        ]
    ,   'cms_tag_accept_font' => [
            'label'            => &$GLOBALS['TL_LANG']['tl_cms_tag_settings']['cms_tag_accept_font']
        ,   'inputType'        => 'text'
        ,   'eval'             => ['maxlength'=>6, 'colorpicker'=>true, 'isHexColor'=>true, 'decodeEntities'=>true, 'tl_class'=>'w50 wizard']
        ,   'mapping'          => 'acceptfont'
        ]
    ,   'cms_tag_accept_background' => [
            'label'            => &$GLOBALS['TL_LANG']['tl_cms_tag_settings']['cms_tag_accept_background']
        ,   'inputType'        => 'text'
        ,   'eval'             => ['maxlength'=>6, 'colorpicker'=>true, 'isHexColor'=>true, 'decodeEntities'=>true, 'tl_class'=>'w50 wizard']
        ,   'mapping'          => 'acceptcolor'
        ]
    ,   'cms_tag_reject_font' => [
            'label'            => &$GLOBALS['TL_LANG']['tl_cms_tag_settings']['cms_tag_reject_font']
        ,   'inputType'        => 'text'
        ,   'eval'             => ['maxlength'=>6, 'colorpicker'=>true, 'isHexColor'=>true, 'decodeEntities'=>true, 'tl_class'=>'w50 wizard']
        ,   'mapping'          => 'rejectfont'
        ]
    ,   'cms_tag_reject_background' => [
            'label'            => &$GLOBALS['TL_LANG']['tl_cms_tag_settings']['cms_tag_reject_background']
        ,   'inputType'        => 'text'
        ,   'eval'             => ['maxlength'=>6, 'colorpicker'=>true, 'isHexColor'=>true, 'decodeEntities'=>true, 'tl_class'=>'w50 wizard']
        ,   'mapping'          => 'rejectcolor'
        ]
    ,   'cms_exclude_pages' => [
            'label'            => &$GLOBALS['TL_LANG']['tl_cms_tag_settings']['cms_exclude_pages']
        ,   'inputType'        => 'pageTree'
        ,   'foreignKey'       => 'tl_page.title'
        ,   'eval'             => ['fieldType'=>'checkbox', 'multiple'=>true, 'tl_class'=>'clr']
        ]
    ]
];


class tl_cms_tag_settings extends Backend {


    /**
     * Return all module templates as array
     *
     * @param \DataContainer $dc
     *
     * @return array
     */
    public function getModuleTemplates( \DataContainer $dc ) {

        return $this->getTemplateGroup('mod_' . \Config::get('cms_tag_type'));
    }


    /**
     * Return all types as array
     *
     * @return array
     */
    public function getFronendTypes( \DataContainer $dc ) {

        $types = [];

        foreach( $GLOBALS['TL_DCA']['tl_cms_tag_settings']['palettes'] as $k=>$v ) {

            if( $k == '__selector__' ) {
                continue;
            }
            if( !\numero2\MarketingSuite\Backend\License::hasFeature('tag'.substr($k, 3)) && $k != 'default') {
                continue;
            }

            $types[$k] = $k;
        }

        return $types;
    }
}
