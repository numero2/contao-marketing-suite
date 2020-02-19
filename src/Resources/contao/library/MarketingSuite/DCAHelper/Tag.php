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
 * @copyright 2020 numero2 - Agentur für digitales Marketing
 */


namespace numero2\MarketingSuite\DCAHelper;

use Contao\Backend as CoreBackend;
use Contao\Database;
use Contao\DataContainer;
use Contao\Image;
use Contao\Input;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use numero2\MarketingSuite\Backend\License as jopumir;
use numero2\MarketingSuite\TagModel;


class Tag extends CoreBackend {


    /**
     * Sets the correct label for the "Tag" field of the
     * current tag
     *
     * @param \DataContainer $dc
     */
    public function setTagFieldLabel( DataContainer $dc ) {

        if( !$dc || !$dc->id ) {
            return;
        }

        $oTag = NULL;
        $oTag = TagModel::findById($dc->id);

        if( $oTag ) {

            if( array_key_exists('tag_'.$oTag->type, $GLOBALS['TL_LANG']['tl_cms_tag']) ) {
                $GLOBALS['TL_DCA']['tl_cms_tag']['fields']['tag']['label'] = $GLOBALS['TL_LANG']['tl_cms_tag']['tag_'.$oTag->type];
            }
        }
    }


    /**
     * Return all module templates as array
     *
     * @param \DataContainer $dc
     *
     * @return array
     */
    public function getModuleTemplates( DataContainer $dc ) {
        return $this->getTemplateGroup('tag_' . $dc->activeRecord->type);
    }


    /**
     * Return all fallback templates as array
     *
     * @param \DataContainer $dc
     *
     * @return array
     */
    public function getFallbackTemplates( DataContainer $dc ) {
        return $this->getTemplateGroup('ce_optin_');
    }


    /**
     * Return the "toggle visibility" button
     *
     * @param array $row
     * @param string $href
     * @param string $label
     * @param string $title
     * @param string $icon
     * @param string $attributes
     *
     * @return string
     */
    public function toggleIcon( $row, $href, $label, $title, $icon, $attributes ) {

        if( \strlen(Input::get('tid')) ) {

            $id = Input::get('tid');
            $active = (Input::get('state') == 1)?'1':'';
            Database::getInstance()->prepare( "UPDATE tl_cms_tag SET active=? WHERE id=?" )->execute($active, $id);

            $this->redirect($this->getReferer());
        }

        if( $row['type'] == 'group' ) {
            return '';
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
     * @param \DataContainer $dc
     */
    public function setRootType( DataContainer $dc ) {

        if( Input::get('act') != 'create' ) {
            return;
        }

        // Insert into
        if( Input::get('pid') == 0 ) {

            $GLOBALS['TL_DCA']['tl_cms_tag']['fields']['type']['default'] = 'group';

        } elseif( Input::get('mode') == 1 ) {

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
    * @param \DataContainer $dc
    * @param array $row
    * @param string $table
    * @param boolean $cr
    * @param array $arrClipboard
    *
    * @return string
    */
    public function pasteTag( DataContainer $dc, $row, $table, $cr, $arrClipboard=NULL ) {

        $disableAfter = false;
        $disableInto = false;

        // Disable all buttons if there is a circular reference
        if( $arrClipboard !== false && ($arrClipboard['mode'] == 'cut' && ($cr == 1 || $arrClipboard['id'] == $row['id']) || $arrClipboard['mode'] == 'cutAll' && ($cr == 1 || \in_array($row['id'], $arrClipboard['id']))) ) {
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
                ->execute(Input::get('id'));

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

        if( $row['id'] > 0 ) {
            $return = $disableAfter ? Image::getHtml('pasteafter_.svg').' ' : '<a href="'.$this->addToUrl('act='.$arrClipboard['mode'].'&amp;mode=1&amp;pid='.$row['id'].(!\is_array($arrClipboard['id']) ? '&amp;id='.$arrClipboard['id'] : '')).'" title="'.StringUtil::specialchars(sprintf($GLOBALS['TL_LANG'][$table]['pasteafter'][1], $row['id'])).'" onclick="Backend.getScrollOffset()">'.$imagePasteAfter.'</a> ';
        }

        return $return.($disableInto ? Image::getHtml('pasteinto_.svg').' ' : '<a href="'.$this->addToUrl('act='.$arrClipboard['mode'].'&amp;mode=2&amp;pid='.$row['id'].(!\is_array($arrClipboard['id']) ? '&amp;id='.$arrClipboard['id'] : '')).'" title="'.StringUtil::specialchars(sprintf($GLOBALS['TL_LANG'][$table]['pasteinto'][1], $row['id'])).'" onclick="Backend.getScrollOffset()">'.$imagePasteInto.'</a> ');
    }


    /**
     * Return the cut page button
     *
     * @param array $row
     * @param string $href
     * @param string $label
     * @param string $title
     * @param string $icon
     * @param string $attributes
     *
     * @return string
     */
    public function cutTag( $row, $href, $label, $title, $icon, $attributes ) {
        return '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ';
    }


    /**
     * Genrates an alias if none is given
     *
     * @param mixed $value
     * @param \DataContainer $dc
     *
     * @return string
     *
     * @throws \Exception
     */
    public function generateAlias( $value, DataContainer $dc ) {

        if( !strlen($value) ) {
            $value = 't'.bin2hex(random_bytes(4));
        }

        $objResult = Database::getInstance()->prepare( "SELECT * from tl_cms_tag WHERE id=? OR alias=?" )->execute($dc->activeRecord->id, $value);

        if( $objResult->numRows > 1 ) {
            throw new \Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $value));
        }

        return $value;
    }


    /**
     * Generates the labels for the table view
     *
     * @param array $row
     * @param string $label
     * @param DataContainer $dc
     * @param array $args
     *
     * @return array
     */
    public function getLabel( $row, $label, DataContainer $dc, $imageAttribute ) {

        $image = 'bundles/marketingsuite/img/backend/icons/';
        $attributes = $imageAttribute;

        if( $row['pid']==0 ) {

            $count = TagModel::countByPid($row['id']);

            $image .= 'icon_tag_group';

        } else {

            $image .= 'icon_tag';
            // REVIEW: maybe add specific icons for different tag types
        }

        $image .= '.svg';

        $attributes .= ' data-icon="'.$image.'" data-icon-disabled="'.$image.'"';

        if( $row['type'] != 'group' ) {

            $label .= '<span style="color:#999;padding-left:3px">['.$GLOBALS['TL_LANG']['tl_cms_tag']['types'][$row['type']].']</span>';
        }

        return Image::getHtml($image, '', $attributes).' '.$label;
    }


    /**
     * Return all tag types as array
     *
     * @return array
     */
    public function getTagTypes( $dc=NULL ) {

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

                if( !jopumir::hasFeature('tags_'.$k) && !in_array($k, ['default', 'group']) ) {
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
            'current_and_all_children' => $GLOBALS['TL_LANG']['tl_cms_tag']['page_scopes']['current_and_all_children']
        ,   'current_and_direct_children' => $GLOBALS['TL_LANG']['tl_cms_tag']['page_scopes']['current_and_direct_children']
        ,   'current_page' => $GLOBALS['TL_LANG']['tl_cms_tag']['page_scopes']['current_page']
        ];

        return $types;
    }


    /**
     * Add our default data to this table, if this is fresh
     *
     * @return array
     */
    public function addDefault() {

        $oDB = Database::getInstance();

        if( $oDB->getNextId('tl_cms_tag')!=1 || TagModel::countAll() ) {
            return;
        }

        System::loadLanguageFile('cms_default');

        $oTag = new TagModel();
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


    /**
     * Unset enable_on_cookie_accept for session tags
     *
     * @param DataContainer $dc
     */
    public function unsetEnableOnCookieAcceptForSession( $dc ) {

        $oTags = NULL;
        $oTags = TagModel::findBy(['type=? AND enable_on_cookie_accept!=?'], ['session','']);

        if( $oTags ) {

            foreach( $oTags as $oTag ) {
                $oTag->enable_on_cookie_accept = '';
                $oTag->save();
            }
        }
    }


    /**
     * performs a sanity chack for the field pages_scope and pages
     *
     * @param  string $varValue
     * @param  Datacontainer $dc
     *
     * @return string
     */
    public function sanityCheckPageScopeWithPages( $varValue, Datacontainer $dc ) {

        if( Input::post('pages_scope') == "current_page" ) {

            $oPages = PageModel::findMultipleByIds(deserialize($varValue));

            if( $oPages ) {
                foreach( $oPages as $oPage ) {
                    if( $oPage->type == 'root' ) {
                        throw new \Exception($GLOBALS['TL_LANG']['ERR']['no_root_pages_for_pagescope_current']);
                    }
                    if( in_array($oPage->type, ['forward', 'redirect']) ) {
                        throw new \Exception($GLOBALS['TL_LANG']['ERR']['no_forward_redirect_pages_for_pagescope_current']);
                    }
                }
            }
        }

        return $varValue;
    }
}
