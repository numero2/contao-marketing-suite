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
 * Table tl_cms_conversion_item
 */
$GLOBALS['TL_DCA']['tl_cms_conversion_item'] = [

    'config' => [
        'dataContainer'             => 'Table'
    ,   'ctable'                    => ['tl_content']
    ,   'onload_callback'           => [ ['tl_cms_conversion_item', 'generateOneEntryAndRedirect'] ]
    ,   'switchToEdit'              => true
    ,   'sql' => [
            'keys' => [
                'id' => 'primary'
            ]
        ]
    ]
,   'list' => [
        'sorting' => [
            'mode'                  => 2
        ,   'fields'                => ['name']
        ,   'flag'                  => 1
        ,   'panelLayout'           => 'filter;search'
        ]
    ,   'label' => [
            'fields'                => ['name', 'type']
        ,   'showColumns'           => true
        ]
    ,   'global_operations' => [
            'all' => [
                'label'             => &$GLOBALS['TL_LANG']['MSC']['all']
            ,   'href'              => 'act=select'
            ,   'class'             => 'header_edit_all'
            ,   'attributes'        => 'onclick="Backend.getScrollOffset()" accesskey="e"'
            ]
        ]
    ,   'operations' => [
            'edit' => [
                'label'             => &$GLOBALS['TL_LANG']['tl_cms_conversion_item']['edit']
            ,   'href'              => 'table=tl_content'
            ,   'icon'              => 'edit.gif'
            ]
        ,   'delete' => [
                'label'             => &$GLOBALS['TL_LANG']['tl_cms_conversion_item']['delete']
            ,   'href'              => 'act=delete'
            ,   'icon'              => 'delete.gif'
            ,   'attributes'        => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"'
            ]
        ]
    ]
,   'palettes' => [
        '__selector__'              => ['type']
    ,   'default'                   => '{common_legend},name'
    ]
,   'fields' => [
        'id' => [
            'sql'         => "int(10) unsigned NOT NULL auto_increment"
        ]
    ,   'tstamp' => [
            'sql'         => "int(10) unsigned NOT NULL default '0'"
        ]
    ,   'name' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_conversion_item']['name']
        ,   'inputType'             => 'text'
        ,   'search'                => true
        ,   'eval'                  => ['mandatory'=>true, 'maxlength'=>64, 'tl_class'=>'w50']
        ,   'sql'                   => "varchar(64) NOT NULL default ''"
        ]
    ]
];



class tl_cms_conversion_item extends Backend {


    /**
     * Return all content elements as array
     *
     * @return array
     */
    public function getContentElements( $dc ) {

        $groups = array();

        foreach( $GLOBALS['TL_CTE'] as $k => $v ) {

            foreach( array_keys($v) as $kk ) {

                if( $dc->activeRecord->type == 'a_b_test' && !in_array($kk, ['text_cms_cta', 'hyperlink', 'form']) ) {
                    continue;
                }

                $groups[$k][] = $kk;
            }
        }

        return $groups;
    }


    /**
     * Change palette during onload
     *
     * @param  DataContainer $dc
     * @param  object $objMI
     *
     * @return none
     */
    public function generateOneEntryAndRedirect( $dc ) {

        $count = \numero2\MarketingSuite\ConversionItemModel::countAll();

        if( !$count ){

            $default = new \numero2\MarketingSuite\ConversionItemModel();

            $default->id = 1;
            $default->tstamp = time();
            $default->name = 'default';
            $default->save();
        }

        $refererId = \System::getContainer()->get('request_stack')->getCurrentRequest()->get('_contao_referer_id');

        $this->redirect($this->addToUrl('table=tl_content&amp;id=1'));
    }
}
