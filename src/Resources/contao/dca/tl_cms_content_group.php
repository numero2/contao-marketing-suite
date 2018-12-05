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
 * Table tl_cms_content_group
 */
$GLOBALS['TL_DCA']['tl_cms_content_group'] = [

    'config' => [
        'dataContainer'             => 'Table'
    ,   'ctable'                    => ['tl_content']
    ,   'ptable'                    => 'tl_cms_marketing_item'
    ,   'onsubmit_callback'         => [['numero2\MarketingSuite\MarketingItem\ABTest', 'submitContentGroup']]
    ,   'onload_callback'           => [['numero2\MarketingSuite\MarketingItem\ABTest', 'loadContentGroup']]
    ,   'closed'                    => true
    ,   'notDeletable'              => true
    ,   'notSortable'               => true
    ,   'notCreatable'              => true
    ,   'switchToEdit'              => true
    ,   'sql' => [
            'keys' => [
                'id' => 'primary'
            ]
        ]
    ]
,   'list' => [
        'sorting' => [
            'mode'                  => 4
        ,   'fields'                => ['id']
        ,   'disableGrouping'       => true
        ,   'panelLayout'           => 'cms_help;filter;search'
        ,   'panel_callback'        => [
                'cms_help' => ['tl_cms_content_group', 'addHelp']
            ]
        ,   'headerFields'          => ['name', 'type', 'ranking']
        ,   'header_callback'       => ['tl_cms_content_group', 'addHeaderInfo']
        ,   'child_record_callback' => ['tl_cms_content_group', 'addCteType']
        ]
    ,   'operations' => [
            'edit' => [
                'label'             => &$GLOBALS['TL_LANG']['tl_cms_content_group']['edit']
            ,   'href'              => 'table=tl_content'
            ,   'icon'              => 'edit.gif'
            ]
        ,   'editheader' => [
                'label'             => &$GLOBALS['TL_LANG']['tl_cms_content_group']['editheader']
            ,   'href'              => 'act=edit'
            ,   'icon'              => 'header.svg'
            ]
        ]
    ]
,   'palettes' => [
        '__selector__'              => ['type']
    ,   'default'                   => '{common_legend},type,name'
    ,   'a_b_test'                  => '{common_legend},type,name'
    ]
,   'fields' => [
        'id' => [
            'sql'         => "int(10) unsigned NOT NULL auto_increment"
        ]
    ,   'pid' => [
            'sql'         => "int(10) unsigned NOT NULL default '0'"
        ]
    ,   'tstamp' => [
            'sql'         => "int(10) unsigned NOT NULL default '0'"
        ]
    ,   'clicks' => [
            'sql'         => "int(10) unsigned NOT NULL default '0'"
        ]
    ,   'views' => [
            'sql'         => "int(10) unsigned NOT NULL default '0'"
        ]
    ,   'type' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_content_group']['type']
        ,   'inputType'             => 'select'
        ,   'filter'                => true
        ,   'options_callback'      => [ 'tl_cms_content_group', 'getTypes']
        ,   'eval'                  => ['mandatory'=>true, 'maxlength'=>32, 'chosen'=>true, 'submitOnChange'=>true, 'readonly'=>'readonly',  'tl_class'=>'w50']
        ,   'sql'                   => "varchar(32) NOT NULL default ''"
        ]
    ,   'name' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_content_group']['name']
        ,   'inputType'             => 'text'
        ,   'search'                => true
        ,   'eval'                  => ['mandatory'=>true, 'maxlength'=>64, 'tl_class'=>'w50']
        ,   'sql'                   => "varchar(64) NOT NULL default ''"
        ]
    ,   'helper_top' => [
            'input_field_callback'     => [ '\numero2\MarketingSuite\Backend\Wizard', 'generateTopForInputField' ]
        ]
    ,   'helper_bottom' => [
            'input_field_callback'     => [ '\numero2\MarketingSuite\Backend\Wizard', 'generateBottomForInputField' ]
        ]
    ]
];


class tl_cms_content_group extends Backend {


    private $elementIndex = 0;


    /**
     * Adds help depending on the marketing item type
     *
     * @param  [type]  $dc
     * @return string
     */
    public function addHelp( $dc ) {

        $objMI = NULL;
        $objMI = \numero2\MarketingSuite\MarketingItemModel::findById($dc->id);

        if( $objMI ) {

            $oHelp = NULL;
            $oHelp = new \numero2\MarketingSuite\Backend\Help();

            $oHelp->suffix = $objMI->type;

            return $oHelp->generate();
        }
    }


    /**
     * Add the type of content element
     *
     * @param array $arrRow
     *
     * @return string
     */
    public function addCteType($arrRow) {

        $key = $arrRow['invisible'] ? 'unpublished' : 'published';
        $class = 'limit_height';

        $objMI = NULL;
        $objMI = \numero2\MarketingSuite\MarketingItemModel::findById($arrRow['pid']);

        if( $objMI->ranking ) {

            $intervall = deserialize($objMI->intervall);

            $start = $objMI->start_ranking;
            $change = strtotime('+'.$intervall['value'].' '.$intervall['unit'], $objMI->start_ranking);
            $end = strtotime('+'.(2*$intervall['value']).' '.$intervall['unit'], $objMI->start_ranking);

            $this->$elementIndex+=1;
            if( $this->$elementIndex === 1 ){

                $start = \Date::parse(\Config::get('dateFormat'), $start);
                $end = \Date::parse(\Config::get('dateFormat'), $change-86400);
            } else {

                $start = \Date::parse(\Config::get('dateFormat'), $change);
                $end = \Date::parse(\Config::get('dateFormat'), $end-86400);
            }

            return '
            <div class="cte_type ' . $key . '">' . $arrRow['name'] . ' <span class="cms_info">(Zeitraum: ' . $start . ' - ' . $end . ')</span></div>'
            // add statistics
            // <div class="' . trim($class) . '">
            // ' . StringUtil::insertTagToSrc($this->getContentElement($objModel)) . '
            // </div>'
            ."\n";
        }

        return '
        <div class="cte_type ' . $key . '">' . $arrRow['name'] . '</div>'
        // add statistics
        // <div class="' . trim($class) . '">
        //     ' . StringUtil::insertTagToSrc($this->getContentElement($objModel)) . '
        // </div>'
        . "\n";
        // list element
        //
    }


    public function addHeaderInfo($args, $dc) {

        self::loadLanguageFile('tl_cms_marketing_item');

        $objMI = NULL;
        $objMI = \numero2\MarketingSuite\MarketingItemModel::findById($dc->id);

        if( !$objMI->ranking ) {

            $args[$GLOBALS['TL_LANG']['tl_cms_marketing_item']['child_header_label']['a_b_test_info'][0]] = $GLOBALS['TL_LANG']['tl_cms_marketing_item']['child_header_label']['a_b_test_info'][1];

        } else {

            $args[$GLOBALS['TL_LANG']['tl_cms_marketing_item']['keyword'][0]] = $objMI->keyword;
            $args[$GLOBALS['TL_LANG']['tl_cms_marketing_item']['child_header_label']['a_b_test_info'][0]] = $GLOBALS['TL_LANG']['tl_cms_marketing_item']['child_header_label']['a_b_test_info']['ranking'];
        }

        // override back button
        $GLOBALS['TL_MOOTOOLS'][] = "<script>document.querySelector('a.header_back').href = 'contao?do=cms_marketing';</script>";

        return $args;
    }


    /**
     * Return all types as array
     *
     * @return array
     */
    public function getTypes() {

        self::loadLanguageFile('tl_cms_marketing_item');

        $types = [];

        foreach( $GLOBALS['TL_DCA']['tl_cms_content_group']['palettes'] as $k=>$v ) {

            if( $k == '__selector__' ){
                continue;
            }

            if( empty($GLOBALS['TL_LANG']['tl_cms_marketing_item']['types'][$k]) ){

                $types[$k] = $k;
            } else {

                $types[$k] = $GLOBALS['TL_LANG']['tl_cms_marketing_item']['types'][$k];
            }
        }

        return $types;
    }

}
