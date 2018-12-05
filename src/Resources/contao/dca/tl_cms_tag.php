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
 * Table tl_cms_tag
 */
$GLOBALS['TL_DCA']['tl_cms_tag'] = [

    'config' => [
        'label'                     => Config::get('websiteTitle')
    ,   'dataContainer'             => 'Table'
    ,   'backlink'                  => 'do=cms_settings'
    ,   'isAvailable'               => \numero2\MarketingSuite\Backend\License::hasFeature('tags')
    ,   'onload_callback'           => [
            ['tl_cms_tag', 'setRootType']
        ,   ['tl_cms_tag', 'addDefault']
    ]
    ,   'sql' => [
            'keys' => [
                'id' => 'primary'
            ]
        ]
    ]
,   'list' => [
        'sorting' => [
            'mode'                  => 5
        ,   'icon'                  => 'pagemounts.svg'
        ,   'paste_button_callback' => ['tl_cms_tag', 'pasteTag']
        ,   'panelLayout'           => 'search,limit;filter'
        ]
    ,   'label' => [
            'fields'                => ['name']
        ,   'format'                => '%s'
        ,   'label_callback'        => ['tl_cms_tag', 'getLabel']
        ]
    ,   'global_operations' => [
            'frontend' => [
                'label'             => &$GLOBALS['TL_LANG']['tl_cms_tag']['frontend']
            ,   'href'              => 'table=tl_cms_tag_settings'
            ,   'icon'              => 'modules.svg'
            ,   'attributes'        => 'onclick="Backend.getScrollOffset()"'
            ]
        ,   'all' => [
                'label'             => &$GLOBALS['TL_LANG']['MSC']['all']
            ,   'href'              => 'act=select'
            ,   'class'             => 'header_edit_all'
            ,   'attributes'        => 'onclick="Backend.getScrollOffset()" accesskey="e"'
            ]
        ]
    ,   'operations' => [
            'edit' => [
                'label'             => &$GLOBALS['TL_LANG']['tl_cms_tag']['edit']
            ,   'href'              => 'act=edit'
            ,   'icon'              => 'edit.gif'
            ]
        ,   'cut' => [
                'label'             => &$GLOBALS['TL_LANG']['tl_cms_tag']['cut']
            ,   'href'              => 'act=paste&amp;mode=cut'
            ,   'icon'              => 'cut.svg'
            ,   'attributes'        => 'onclick="Backend.getScrollOffset()"'
            ,   'button_callback'   => ['tl_cms_tag', 'cutTag']
            ]
        ,   'delete' => [
                'label'             => &$GLOBALS['TL_LANG']['tl_cms_tag']['delete']
            ,   'href'              => 'act=delete'
            ,   'icon'              => 'delete.gif'
            ,   'attributes'        => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"'
            ]
        ,   'toggle' =>[
                'label'             => &$GLOBALS['TL_LANG']['tl_cms_tag']['toggle']
            ,   'icon'              => 'visible.svg'
            ,   'attributes'        => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleVisibility(this,%s)"'
            ,   'button_callback'   => ['tl_cms_tag', 'toggleIcon']
            ]
        ]
    ]
,   'palettes' => [
        '__selector__'              => ['type']
    ,   'default'                   => '{common_legend},type,name'
    ,   'group'                     => '{common_legend},type,name;{description_legend},description;{expert_legend:hide},customTpl'
    ,   'session'                   => '{common_legend},type,name;{publish_legend},active,enable_on_cookie_accept'
    ,   'html'                      => '{common_legend},type,name;{tag_legend},html;{expert_legend:hide},customTpl;{publish_legend},pages_scope,pages,active,enable_on_cookie_accept'
    ,   'google_analytics'          => '{common_legend},type,name;{tag_legend},tag,alias;{config_legend},anonymize_ip;{expert_legend:hide},customTpl;{publish_legend},pages_scope,pages,active,enable_on_cookie_accept'
    ,   'facebook_pixel'            => '{common_legend},type,name;{tag_legend},tag;{expert_legend:hide},customTpl;{publish_legend},pages_scope,pages,active,enable_on_cookie_accept'
    ]
,   'fields' => [
        'id' => [
            'sql'         => "int(10) unsigned NOT NULL auto_increment"
        ]
    ,   'pid' => [
            'sql'         => "int(10) unsigned NOT NULL default '0'"
        ]
    ,   'sorting' => [
            'sql'         => "int(10) unsigned NOT NULL default '0'"
        ]
    ,   'tstamp' => [
            'sql'         => "int(10) unsigned NOT NULL default '0'"
        ]
    ,   'type' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_tag']['type']
        ,   'inputType'             => 'select'
        ,   'filter'                => true
        ,   'options_callback'      => [ 'tl_cms_tag', 'getTagTypes']
        ,   'reference'             => &$GLOBALS['TL_LANG']['tl_cms_tag']['types']
        ,   'eval'                  => ['mandatory'=>true, 'maxlength'=>32, 'chosen'=>true, 'submitOnChange'=>true, 'tl_class'=>'w50']
        ,   'sql'                   => "varchar(32) NOT NULL default ''"
        ]
    ,   'name' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_tag']['name']
        ,   'inputType'             => 'text'
        ,   'search'                => true
        ,   'eval'                  => ['mandatory'=>true, 'maxlength'=>64, 'tl_class'=>'w50']
        ,   'sql'                   => "varchar(64) NOT NULL default ''"
        ]
    ,   'description' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_tag']['description']
        ,   'inputType'             => 'textarea'
        ,   'eval'                  => ['rte'=>'tinyMarketing', 'helpwizard'=>true, 'doNotSaveEmpty'=>true]
        ,   'sql'                   => "text NULL"
    ]
    ,   'html' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_tag']['html']
        ,   'inputType'             => 'textarea'
        ,   'eval'                  => ['mandatory'=>true, 'preserveTags'=>true, 'class'=>'monospace', 'rte'=>'ace|html', 'tl_class'=>'clr']
        ,   'sql'                   => "text NULL"
        ]
    ,   'tag' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_tag']['tag']
        ,   'inputType'             => 'text'
        ,   'search'                => true
        ,   'eval'                  => ['mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50']
        ,   'sql'                   => "varchar(255) NOT NULL default ''"
        ]
    ,   'anonymize_ip' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_tag']['anonymize_ip']
        ,   'inputType'             => 'checkbox'
        ,   'eval'                  => ['tl_class'=>'w50']
        ,   'default'               => "1"
        ,   'sql'                   => "char(1) NOT NULL default '1'"
        ]
    ,   'alias' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_tag']['alias']
        ,   'inputType'             => 'text'
        ,   'eval'                  => ['rgxp'=>'alnum', 'maxlength'=>32, 'tl_class'=>'w50']
        ,   'save_callback'         => [ ['tl_cms_tag', 'generateAlias'] ]
        ,   'sql'                   => "varchar(32) NOT NULL default ''"
        ]
    ,   'customTpl' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_tag']['customTpl']
        ,   'inputType'             => 'select'
        ,   'options_callback'      => ['tl_cms_tag', 'getModuleTemplates']
        ,   'eval'                  => ['includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50']
        ,   'sql'                   => "varchar(64) NOT NULL default ''"
        ]
    ,   'pages_scope' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_tag']['pages_scope']
        ,   'inputType'             => 'radio'
        ,   'default'               => 'current_page'
        ,   'options_callback'      => ['tl_cms_tag', 'getPageScopes']
        ,   'eval'                  => ['tl_class'=>'clr w50 no-height']
        ,   'sql'                   => "varchar(64) NOT NULL default ''"
        ]
    ,   'pages' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_tag']['pages']
        ,   'inputType'             => 'pageTree'
        ,   'foreignKey'            => 'tl_page.title'
        ,   'eval'                  => ['mandatory'=>true, 'multiple'=>true, 'fieldType'=>'checkbox', 'orderField'=>'orderPages', 'tl_class'=>'clr']
        ,   'relation'              => ['type'=>'hasMany', 'load'=>'lazy']
        ,   'sql'                   => "text NULL"
        ]
    ,    'orderPages' => [
            'eval'                  => ['doNotShow'=>true]
        ,   'sql'                   => "text NULL"
        ]
    ,   'active' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_tag']['active']
        ,   'inputType'             => 'checkbox'
        ,   'eval'                  => ['tl_class'=>'w50']
        ,   'sql'                   => "char(1) NOT NULL default ''"
        ]
    ,   'enable_on_cookie_accept' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_cms_tag']['enable_on_cookie_accept']
        ,   'inputType'             => 'checkbox'
        ,   'eval'                  => ['tl_class'=>'w50']
        ,   'default'               => "1"
        ,   'sql'                   => "char(1) NOT NULL default '1'"
        ]
    ]
];



class tl_cms_tag extends Backend {

    /**
     * Return all module templates as array
     *
     * @param DataContainer $dc
     *
     * @return array
     */
    public function getModuleTemplates(DataContainer $dc) {

        return $this->getTemplateGroup('tag_' . $dc->activeRecord->type);
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

        if( \strlen(Input::get('tid')) ) {

            $id = Input::get('tid');
            $active = (Input::get('state') == 1)?'1':'';
            Database::getInstance()->prepare( "UPDATE tl_cms_tag SET active=? WHERE id=?" )->execute($active, $id);

            $this->redirect($this->getReferer());
        }

        $href .= '&amp;tid='.$row['id'].'&amp;state='.$row['active'];

        if( !$row['active'] ) {
            $icon = 'invisible.svg';
        }
        return '<a href="'.$this->addToUrl($href).'" title="'.StringUtil::specialchars($title).'" '.$attributes.'>'.Image::getHtml($icon, $label, 'data-state="' . ($row['active'] ? 1 : 0) . '"').'</a> ';
    }

    /**
     * Make new root level tags group
     *
     * @param DataContainer $dc
     */
    public function setRootType(DataContainer $dc) {

        if( Input::get('act') != 'create' ) {
            return;
        }

        // Insert into
        if( Input::get('pid') == 0 ) {

            $GLOBALS['TL_DCA']['tl_cms_tag']['fields']['type']['default'] = 'group';

        } elseif (Input::get('mode') == 1) {

            $objPage = $this->Database->prepare("SELECT * FROM " . $dc->table . " WHERE id=?")
                                      ->limit(1)
                                      ->execute(Input::get('pid'));

            if( $objPage->pid == 0 ) {
                $GLOBALS['TL_DCA']['tl_cms_tag']['fields']['type']['default'] = 'group';
            }
        }
    }


    /**
    * Return the paste page button
    *
    * @param DataContainer $dc
    * @param array         $row
    * @param string        $table
    * @param boolean       $cr
    * @param array         $arrClipboard
    *
    * @return string
    */
    public function pasteTag(DataContainer $dc, $row, $table, $cr, $arrClipboard=null) {

        $disableAfter = false;
        $disableInto = false;

        // Disable all buttons if there is a circular reference
        if( $arrClipboard !== false && ($arrClipboard['mode'] == 'cut' && ($cr == 1 || $arrClipboard['id'] == $row['id']) || $arrClipboard['mode'] == 'cutAll' && ($cr == 1 || \in_array($row['id'], $arrClipboard['id']))))
        {
            $disableAfter = true;
            $disableInto = true;
        }

        // only support root level and level 1
        if( Input::get('mode') == 'create' && $row['pid'] != 0 ) {
            $disableInto = true;
        }

        // only support past in same level
        if( Input::get('mode') != 'create' ) {


            $objPage = $this->Database->prepare("SELECT * FROM " . $table . " WHERE id=?")
            ->limit(1)
            ->execute(\Input::get('id'));

            if( $objPage->pid == '0' ) {

                if( $row['pid'] == '0' ) {
                    $disableInto = true;
                } else {
                    $disableInto = true;
                    $disableAfter = true;
                }
            } else {
                if( $row['pid'] == '0' ) {
                    $disableAfter = true;
                } else {
                    $disableInto = true;
                }
            }
        }

        $return = '';

        // Return the buttons
        $imagePasteAfter = Image::getHtml('pasteafter.svg', sprintf($GLOBALS['TL_LANG'][$table]['pasteafter'][1], $row['id']));
        $imagePasteInto = Image::getHtml('pasteinto.svg', sprintf($GLOBALS['TL_LANG'][$table]['pasteinto'][1], $row['id']));

        if ($row['id'] > 0)
        {
            $return = $disableAfter ? Image::getHtml('pasteafter_.svg').' ' : '<a href="'.$this->addToUrl('act='.$arrClipboard['mode'].'&amp;mode=1&amp;pid='.$row['id'].(!\is_array($arrClipboard['id']) ? '&amp;id='.$arrClipboard['id'] : '')).'" title="'.StringUtil::specialchars(sprintf($GLOBALS['TL_LANG'][$table]['pasteafter'][1], $row['id'])).'" onclick="Backend.getScrollOffset()">'.$imagePasteAfter.'</a> ';
        }

        return $return.($disableInto ? Image::getHtml('pasteinto_.svg').' ' : '<a href="'.$this->addToUrl('act='.$arrClipboard['mode'].'&amp;mode=2&amp;pid='.$row['id'].(!\is_array($arrClipboard['id']) ? '&amp;id='.$arrClipboard['id'] : '')).'" title="'.StringUtil::specialchars(sprintf($GLOBALS['TL_LANG'][$table]['pasteinto'][1], $row['id'])).'" onclick="Backend.getScrollOffset()">'.$imagePasteInto.'</a> ');
    }


    /**
     * Return the cut page button
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
    public function cutTag($row, $href, $label, $title, $icon, $attributes) {
        // return ($this->User->hasAccess($row['type'], 'alpty') && $this->User->isAllowed(BackendUser::CAN_EDIT_PAGE_HIERARCHY, $row)) ? '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ' : Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)).' ';
        return '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ';
    }


    /**
     * genrates an alias if none is given
     *
     * @param mixed         $value
     * @param DataContainer $dc
     *
     * @return string
     *
     * @throws Exception
     */
    public function generateAlias( $value, DataContainer $dc ) {

        if( !strlen($value) ) {
            $value = 't'.bin2hex(random_bytes(4));
        }

        $objResult = Database::getInstance()->prepare( "SELECT * from tl_cms_tag WHERE id=? OR alias=?" )->execute($dc->activeRecord->id, $value);

        if( $objResult->numRows > 1 ){

            throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $value));
        }

        return $value;
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
    public function getLabel($row, $label, DataContainer $dc, $imageAttribute) {

        $image = 'bundles/marketingsuite/img/backend/icons/';
        $attributes = $imageAttribute;

        if( $row['pid']==0 ) {

            $count = numero2\MarketingSuite\TagModel::countByPid($row['id']);

            $image .= 'icon_tag_group';

        } else {

            $image .= 'icon_tag';
            // REVIEW: maybe add specific icons for different tag types
        }

        $image .= '.svg';

        if( $row['type'] != 'group' ) {

            $label .= '<span style="color:#999;padding-left:3px">['.$GLOBALS['TL_LANG']['tl_cms_tag']['types'][$row['type']].']</span>';
        }

        return \Image::getHtml($image, '', $attributes).' '.$label;
    }


    /**
     * Return all tag types as array
     *
     * @return array
     */
    public function getTagTypes($dc=null) {

        $types = [];

        $aRootTypes = ['group'];

        $pid = $dc->activeRecord->pid;

        foreach( $GLOBALS['TL_DCA']['tl_cms_tag']['palettes'] as $k=>$v ) {

            if( $k == '__selector__' ) {
                continue;
            }

            // change available types based on pid, pid == 0 means root level
            if( $k == 'default' || $dc == null
                || ( $pid == '0' && in_array($k, $aRootTypes) )
                || ( $pid != '0' && !in_array($k, $aRootTypes) )
            ) {

                if( !\numero2\MarketingSuite\Backend\License::hasFeature('tags_'.$k) && $k != 'default' ) {
                    continue;
                }

                $types[$k] = $k;
            }
        }

        return $types;
    }


    /**
     * Return all tag types as array
     *
     * @return array
     */
    public function getPageScopes() {

        $types = [
            'current_page' => $GLOBALS['TL_LANG']['tl_cms_tag']['page_scopes']['current_page']
        ,   'current_and_direct_children' => $GLOBALS['TL_LANG']['tl_cms_tag']['page_scopes']['current_and_direct_children']
        ,   'current_and_all_children' => $GLOBALS['TL_LANG']['tl_cms_tag']['page_scopes']['current_and_all_children']
        ];

        return $types;
    }


    /**
     * Add our default data to this table, if this is fresh
     *
     * @return array
     */
    public function addDefault() {

        $oDB = \Database::getInstance();

        if( $oDB->getNextId('tl_cms_tag')!=1 || \numero2\MarketingSuite\TagModel::countAll() ) {
            return;
        }

        \System::loadLanguageFile('cms_default');

        $oTag = new \numero2\MarketingSuite\TagModel();
        $oTag->tstamp = time();
        $oTag->sorting = 32;
        $oTag->anonymize_ip = '1';
        $oTag->enable_on_cookie_accept = '1';
        $oTag->pid = '0';
        $oTag->type = 'group';

        $defaultData = $GLOBALS['TL_LANG']['cms_tag_default'];

        if( is_array($defaultData) && count($defaultData) ) {
            foreach( $defaultData as $dataKey => $data ) {

                $current =  clone $oTag;
                foreach( $data as $key => $value ) {
                    $current->{$key}  = $value;
                }

                $current->save();

                if( $dataKey == 0 ) {
                    $sessionCookie =  clone $oTag;

                    $sessionCookie->name = 'Session-Cookie';
                    $sessionCookie->enable_on_cookie_accept = '';
                    $sessionCookie->pid = $current->id;
                    $sessionCookie->type = 'session';

                    $sessionCookie->save();
                }

                $oTag->sorting *= 2;
            }
        }

        return $types;
    }
}
