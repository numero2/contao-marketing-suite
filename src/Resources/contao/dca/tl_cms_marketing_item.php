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

use Contao\CoreBundle\DataContainer\PaletteManipulator;


/**
 * Table tl_cms_marketing_item
 */
$GLOBALS['TL_DCA']['tl_cms_marketing_item'] = [

    'config' => [
        'dataContainer'             => 'Table'
    ,   'ctable'                    => ['tl_cms_content_group', 'tl_content']
    ,   'onsubmit_callback'         => [['\numero2\MarketingSuite\MarketingItem','submitMarketingItem']]
    ,   'onload_callback'           => [['tl_cms_marketing_item', 'loadMarketingItem']]
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
        ,   'panelLayout'           => 'cms_help;filter;search'
        ,   'panel_callback'        => [
                'cms_help' => ['numero2\MarketingSuite\Backend\Help', 'generate']
            ]
        ]
    ,   'label' => [
            'fields'                => ['name', 'type', 'used']
        ,   'showColumns'           => true
        ,   'label_callback'        => ['tl_cms_marketing_item', 'getLabel']
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
                'label'             => &$GLOBALS['TL_LANG']['tl_cms_marketing_item']['edit']
            ,   'href'              => 'table=tl_cms_content_group'
            ,   'icon'              => 'edit.gif'
            ,   'button_callback'   => ['tl_cms_marketing_item', 'editButton']
            ]
        ,   'editheader' => [
                'label'             => &$GLOBALS['TL_LANG']['tl_cms_marketing_item']['editheader']
            ,   'href'              => 'act=edit'
            ,   'icon'              => 'header.svg'
            ,   'button_callback'   => ['tl_cms_marketing_item', 'editHeaderButton']
            ]
        ,   'delete' => [
                'label'             => &$GLOBALS['TL_LANG']['tl_cms_marketing_item']['delete']
            ,   'href'              => 'act=delete'
            ,   'icon'              => 'delete.gif'
            ,   'attributes'        => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"'
            ]
        ,   'toggle' =>[
                'label'             => &$GLOBALS['TL_LANG']['tl_cms_marketing_item']['toggle']
            ,   'icon'              => 'visible.svg'
            ,   'attributes'        => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleVisibility(this,%s)"'
            ,   'button_callback'   => ['tl_cms_marketing_item', 'toggleIcon']
            ]
        ]
    ]
,   'palettes' => [
        '__selector__'              => ['type','ranking']
    ,   'default'                   => '{common_legend},type'
    ,   'a_b_test'                  => '{common_legend},type,name;{a_b_test_legend},content_type,ranking;{publish_legend},active'
    ,   'current_page'              => '{common_legend},type,name;{current_page_legend},content_type,pages;{publish_legend},active'
    ,   'visited_pages'             => '{common_legend},type,name;{publish_legend},active'
    ]
,   'subpalettes' => [
        //'ranking' => 'keyword,start_ranking,intervall'
    ]
,   'fields' => [
        'id' => [
            'sql'         => "int(10) unsigned NOT NULL auto_increment"
        ]
    ,   'tstamp' => [
            'sql'         => "int(10) unsigned NOT NULL default '0'"
        ]
    ,   'init_step' => [
            'sql'         => "varchar(255) NOT NULL default ''"
        ]
    ,   'type' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_marketing_item']['type']
        ,   'inputType'             => 'select'
        ,   'filter'                => true
        ,   'options_callback'      => [ 'numero2\MarketingSuite\MarketingItem', 'getMarketingItemTypes']
        ,   'eval'                  => ['mandatory'=>true, 'maxlength'=>32, 'chosen'=>true, 'submitOnChange'=>true, 'tl_class'=>'w50']
        ,   'sql'                   => "varchar(32) NOT NULL default ''"
        ]
    ,   'name' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_marketing_item']['name']
        ,   'inputType'             => 'text'
        ,   'search'                => true
        ,   'eval'                  => ['mandatory'=>true, 'maxlength'=>64, 'tl_class'=>'w50']
        ,   'sql'                   => "varchar(64) NOT NULL default ''"
        ]
    ,   'content_type' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_marketing_item']['content_type']
        ,   'inputType'             => 'select'
        ,   'search'                => true
        ,   'options_callback'      => ['tl_cms_marketing_item', 'getContentElements']
        ,   'reference'             => &$GLOBALS['TL_LANG']['CTE']
        ,   'eval'                  => ['mandatory'=>true, 'chosen'=>true, 'tl_class'=>'w50']
        ,   'sql'                   => "varchar(64) NOT NULL default ''"
        ]
    ,   'pages' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_marketing_item']['pages']
        ,   'inputType'             => 'pageTree'
        ,   'foreignKey'            => 'tl_page.title'
        ,   'eval'                  => ['mandatory'=>true, 'multiple'=>true, 'fieldType'=>'checkbox', 'orderField'=>'orderPages', 'tl_class'=>'clr']
        ,   'relation'              => ['type'=>'hasMany', 'load'=>'lazy']
        ,   'sql'                   => "text NULL"
        ]
    ,   'orderPages' => [
            'eval'                  => ['doNotShow'=>true]
        ,   'sql'                   => "text NULL"
        ]
    /*
    ,   'ranking' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_marketing_item']['ranking']
        // ,   'inputType'             => 'checkbox'
        ,   'eval'                  => ['submitOnChange'=>true, 'tl_class'=>'clr w50']
        ,   'sql'                   => "char(1) NOT NULL default ''"
        ]
    ,   'keyword' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_marketing_item']['keyword']
        ,   'inputType'             => 'text'
        ,   'eval'                  => ['mandatory'=>true, 'tl_class'=>'w50' ]
        ,   'sql'                   => "varchar(255) NOT NULL default ''"
        ]
    ,   'start_ranking' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_marketing_item']['start_ranking']
        ,   'inputType'             => 'text'
        ,   'default'               => time()
        ,   'eval'                  => ['mandatory'=>true, 'rgxp'=>'date', 'datepicker'=>true, 'tl_class'=>'w50 wizard']
        ,   'sql'                   => "varchar(10) NOT NULL default ''"
    ]
    ,   'intervall' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_marketing_item']['intervall']
        ,   'inputType'             => 'inputUnit'
        ,   'options'               => [
                'day' => $GLOBALS['TL_LANG']['tl_cms_marketing_item']['intervall_units']['day']
            ,   'week' => $GLOBALS['TL_LANG']['tl_cms_marketing_item']['intervall_units']['week']
            ,   'month' => $GLOBALS['TL_LANG']['tl_cms_marketing_item']['intervall_units']['month']
            // ,   'year' => $GLOBALS['TL_LANG']['tl_cms_marketing_item']['stripe_intervall_units']['year']
            ]
        ,   'eval'                  => ['mandatory'=>true, 'rgxp'=>'natural_min_4_weeks', 'tl_class'=>'w50' ]
        ,   'sql'                   => "varchar(255) NOT NULL default ''"
        ]
    */
    ,   'active' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_marketing_item']['active']
        ,   'inputType'             => 'checkbox'
        ,   'default'               => '1'
        ,   'eval'                  => ['tl_class'=>'w50']
        ,   'sql'                   => "char(1) NOT NULL default '1'"
        ]
    ,   'used' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_marketing_item']['used']
        ]
    ,   'helper_top' => [
            'input_field_callback'  => [ '\numero2\MarketingSuite\Backend\Wizard', 'generateTopForInputField' ]
        ]
    ,   'helper_bottom' => [
            'input_field_callback'  => [ '\numero2\MarketingSuite\Backend\Wizard', 'generateBottomForInputField' ]
        ]
    ]
];



class tl_cms_marketing_item extends Backend {


    /**
     * change the edit button if the init was not finished
     *
     * @param array  $row
     * @param string $href
     * @param string $label
     * @param string $title
     * @param string $icon
     * @param string $attributes
     *
     * @return string
     */
    public function editButton($row, $href, $label, $title, $icon, $attributes) {

        if( !empty($row['init_step']) ) {

            $title = $GLOBALS['tl_cms_marketing_item']['init']['0'];
            $attributes = '';

            $row['init_step'] .= '&rt='.REQUEST_TOKEN;

            return '<a href="'.$row['init_step'].'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ';
        }

        if( $row['type'] !== 'a_b_test' ) {

            $href = 'table=tl_content';
            // for this elements we fake one ContentGroup to avoid id collision in tl_content edit url
            $objSingleGroup = \numero2\MarketingSuite\ContentGroupModel::findOneByPid($row['id']);
            unset($row['id']);

            if( !empty($objSingleGroup->id) ){
                $row['id'] = $objSingleGroup->id;
            }
        }

        if( empty($row['id']) ) {
            return Image::getHtml('edit_.svg', $label);
        }

        return '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ';
    }


    /**
     * change the back button in the header section
     *
     * @param array  $row
     * @param string $href
     * @param string $label
     * @param string $title
     * @param string $icon
     * @param string $attributes
     *
     * @return string
     */
    public function editHeaderButton($row, $href, $label, $title, $icon, $attributes) {

        if( !empty($row['init_step']) ) {

            return Image::getHtml('header_.svg', $label).' ';
        }


        if( empty($row['id']) ) {
            return Image::getHtml('header_.svg', $label).' ';
        }

        return '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ';
    }


    /**
     * Return the "toggle visibility" button
     *
     * @param array  $row
     * @param string $href
     * @param string $label
     * @param string $title
     * @param string $icon
     * @param string $attributes
     *
     * @return string
     */
    public function toggleIcon($row, $href, $label, $title, $icon, $attributes) {

        if (\strlen(Input::get('tid'))) {

            $id = Input::get('tid');
            $active = (Input::get('state') == 1)?'1':'';
            Database::getInstance()->prepare( "UPDATE tl_cms_marketing_item SET active=? WHERE id=?" )->execute($active, $id);

            $this->redirect($this->getReferer());
        }

        $href .= '&amp;tid='.$row['id'].'&amp;state='.$row['active'];

        if( !$row['active'] ) {
            $icon = 'invisible.svg';
        }
        return '<a href="'.$this->addToUrl($href).'" title="'.StringUtil::specialchars($title).$attributes.'>'.Image::getHtml($icon, $label, 'data-state="' . ($row['active'] ? 1 : 0) . '"').'</a> ';
    }


    /**
     * Generates the labels for the table view
     *
     * @param array         $row
     * @param string        $label
     * @param DataContainer $dc
     * @param array         $args
     *
     * @return array
     */
    public function getLabel($row, $label, DataContainer $dc, $args) {

        if( $row['type'] == 'a_b_test' ) {

            $objAlways = \numero2\MarketingSuite\ContentGroupModel::findOneBy(["pid=? AND always_use_this=?"],[$row['id'], 1]);

            if( $objAlways ) {

                $strAlways = $GLOBALS['TL_LANG']['tl_cms_marketing_item']['list_label']['always_use_this_name'];
                $strAlways = sprintf($strAlways, $objAlways->name);

                $args[0] .= '<span style="color:#999;padding-left:3px">['.$strAlways.']</span>';
            }
        }

        $args[1] = $GLOBALS['TL_LANG']['tl_cms_marketing_item']['types'][$row['type']];

        $count = 0;
        $aElements = [];
        $oContent = \ContentModel::findBy(['type=? AND cms_mi_id=?'], ['cms_marketing_item', $row['id']]);

        if( count($oContent) ) {

            $count += count($oContent);
            $aElements[$GLOBALS['TL_LANG']['MOD']['tl_content']] = $oContent;
        }

        $oModule = \ModuleModel::findBy(['tl_module.type=? AND tl_module.cms_mi_id=?'], ['cms_marketing_item', $row['id']]);

        if( count($oModule) ) {

            $count += count($oModule);
            $aElements[$GLOBALS['TL_LANG']['MOD']['tl_module']] = $oModule;
        }

        $args[2] = '';

        if( count($aElements) ) {

            $aOverlay = [
                'label' => 'Elemente (' . $count . ')'
            ,   'content' => $aElements
            ];
            $args[2] = \numero2\MarketingSuite\Backend::parseWithTemplate('backend/elements/overlay_tree', $aOverlay );
        }

        return $args;
    }


    /**
     * Return all content elements as array
     *
     * @return array
     */
    public function getContentElements( $dc ) {

        $groups = array();


        if( \numero2\MarketingSuite\Backend\License::hasFeature('conversion_element') && count($GLOBALS['TL_CTE']['conversion_elements']) ) {
            foreach( $GLOBALS['TL_CTE']['conversion_elements'] as $key => $value ) {

                if( !\numero2\MarketingSuite\Backend\License::hasFeature('ce_'.$key) ) {
                    unset($GLOBALS['TL_CTE']['conversion_elements'][$key]);
                }
            }
        }

        if( !\numero2\MarketingSuite\Backend\License::hasFeature('marketing_element') ) {
            unset($GLOBALS['TL_CTE']['marketing_suite']['cms_marketing_item']);
        }

        if( !\numero2\MarketingSuite\Backend\License::hasFeature('conversion_element') || !count($GLOBALS['TL_CTE']['conversion_elements']) ) {
            unset($GLOBALS['TL_CTE']['marketing_suite']['cms_conversion_item']);
            unset($GLOBALS['TL_CTE']['conversion_elements']);
        }

        if( !count($GLOBALS['TL_CTE']['marketing_suite']) ) {
            unset($GLOBALS['TL_CTE']['marketing_suite']);
        }

        foreach( $GLOBALS['TL_CTE'] as $k => $v ) {

            if( $dc->activeRecord->type == 'a_b_test' && !in_array($k, ['conversion_elements']) ) {
                continue;
            }

            foreach( array_keys($v) as $kk ) {

                $groups[$k][] = $kk;
            }
        }

        return $groups;
    }

    /**
     * change palette during onload
     *
     * @param  DataContainer $dc
     * @param  object $objMI
     *
     * @return none
     */
    public function loadMarketingItem($dc) {

        $objMI = \numero2\MarketingSuite\MarketingItemModel::findById($dc->id);

        $groups = \numero2\MarketingSuite\ContentGroupModel::countByPid($objMI->id);

        if( $objMI && !$groups ){

            $pm = PaletteManipulator::create()
                ->addLegend('cms_helper_top_legend', 'common_legend', 'before')
                ->addField(['helper_top'], 'cms_helper_top_legend', 'append')
            ;

            $GLOBALS['TL_DCA'][$dc->table]['fields']['helper_top']['type'] = $objMI->type;
            $GLOBALS['TL_DCA'][$dc->table]['fields']['helper_top']['step'] = 1;

            if( $objMI->type && $objMI->type !== 'default' ) {

                $pm ->addLegend('cms_helper_bottom_legend', '', 'after')
                    // ->addField(['helper_bottom'], 'cms_helper_bottom_legend', 'append')
                ;
            }

            $pm->applyToPalette($objMI->type?:'default', 'tl_cms_marketing_item');

            $GLOBALS['TL_DCA'][$dc->table]['edit']['buttons_callback'][] = ['\numero2\MarketingSuite\Backend\Wizard', 'overrideButtonsWithContinue'];

            $objMI->init_step = $this->addToUrl('');
            $objMI->save();
        }

    }
}
