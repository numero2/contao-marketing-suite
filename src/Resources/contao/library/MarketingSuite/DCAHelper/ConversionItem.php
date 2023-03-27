<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2022 Leo Feyer
 *
 * @package   Contao Marketing Suite
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright 2022 numero2 - Agentur für digitales Marketing
 */


namespace numero2\MarketingSuite\DCAHelper;

use Contao\Backend as CoreBackend;
use Contao\ContentModel;
use Contao\Database;
use Contao\DataContainer;
use Contao\Image;
use Contao\Input;
use Contao\ModuleModel;
use Contao\StringUtil;
use Contao\System;
use numero2\MarketingSuite\Backend;
use numero2\MarketingSuite\Backend\License as ehso;
use numero2\MarketingSuite\ConversionItemModel;


class ConversionItem extends CoreBackend {


    /** @var array defines which conversion items can be shown on all pages by the generatePage hook */
    public static $aGlobalTypes = ['cms_overlay'];


    /**
     * Generates the labels for the table view
     *
     * @param array $row
     * @param string $label
     * @param \DataContainer $dc
     * @param array $args
     *
     * @return array
     */
    public function getLabel( $row, $label, DataContainer $dc, $args ) {

        $args[4] = self::generateUsedOverlay($row, "Elemente (%s)");

        return $args;
    }


    /**
     * Generates a overlay to show where the element is used
     *
     * @param array $aRow
     *
     * @return string
     */
    public static function generateUsedOverlay($aRow, $label) {

        System::loadLanguageFile('tl_cms_tag');

        $strView = '';

        $count = 0;
        $canBeMore = false;
        $aElements = [];

        $oContent = ContentModel::findBy(['type=? AND cms_ci_id=?'], ['cms_conversion_item', $aRow['id']], ['return'=>'Collection']);

        if( $oContent && $oContent->count() ) {

            $count += $oContent->count();
            $aElements[$GLOBALS['TL_LANG']['MOD']['tl_content']] = $oContent;
        }

        $oModule = ModuleModel::findBy(['tl_module.type=? AND tl_module.cms_ci_id=?'], ['cms_conversion_item', $aRow['id']], ['return'=>'Collection']);

        if( $oModule && $oModule->count() ) {

            $count += $oModule->count();
            $aElements[$GLOBALS['TL_LANG']['MOD']['tl_module']] = $oModule;
        }

        if( in_array($aRow['type'], self::$aGlobalTypes) ) {

            $aIds = StringUtil::deserialize($aRow['cms_pages']);
            if( $aIds && is_array($aIds) ) {
                if( $aRow['cms_pages_scope'] != '' && $aRow['cms_pages_scope'] != 'none' ) {
                    $count += count($aIds);
                    $aElements[$GLOBALS['TL_LANG']['tl_cms_tag']['page_scopes'][$aRow['cms_pages_scope']]] = [['table'=>'tl_page', 'ids'=>$aIds]];

                    if($aRow['cms_pages_scope'] != 'current_page') {
                        $canBeMore = true;
                    }
                }
            }
        }

        if( count($aElements) ) {

            $aOverlay = [
                'label' => (strpos($label, '%')===false)?$label:sprintf($label, (string)$count.($canBeMore?'+':''))
            ,   'content' => $aElements
            ,   'position' => 'top_right'
            ];
            $strView = Backend::parseWithTemplate('backend/elements/overlay_tree', $aOverlay);
        }

        return $strView;
    }


    /**
     * Return the "reset_counter" button
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
    public function resetCounter( $row, $href, $label, $title, $icon, $attributes ) {

        if( \strlen(Input::get('rid')) ) {

            $id = Input::get('rid');
            Database::getInstance()->prepare( "UPDATE tl_content SET cms_ci_views=0, cms_ci_clicks=0, cms_ci_reset=? WHERE id=?" )->execute(time(), $id);

            $this->redirect($this->getReferer());
        }

        $href .= '&amp;rid='.$row['id'];

        if( empty($GLOBALS['TL_CTE']['conversion_elements']) || !array_key_exists($row['type'], $GLOBALS['TL_CTE']['conversion_elements']) ) {
            return '';
        }

        return '<a href="'.$this->addToUrl($href).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ';
    }


    /**
     * If we are on 'do=cms_conversion' we only show conversion items
     *
     * @param \DataContainer $dc
     */
    public function onlyShowConversionItems( $dc ) {

        $GLOBALS['TL_DCA'][$dc->table]['list']['sorting']['filter'][] = ['ptable=? AND 1=1','tl_cms_conversion_item'];
    }


    /**
     * Modifies the headline of the current dataContainer
     */
    public function modifyDCHeadline() {

        $classNames = '';

        if( Input::get('do') == 'cms_conversion' ) {

            $classNames = 'conversion_item';

            if( Input::get('act') == 'edit' ) {
                $classNames .= ' edit';
            }
        }

        if( !empty($classNames) ) {
            $GLOBALS['TL_MOOTOOLS'][] = "<script>document.querySelector('#main_headline').className += ' ".$classNames."';</script>";
        }
    }


    /**
     * Modify the palettes for conversion items that can be shown on multiple pages
     *
     * @param \DataContainer $dc
     */
    public function addPageScopeFields( $dc ) {

        foreach( self::$aGlobalTypes as $palettes ) {

            $GLOBALS['TL_DCA'][$dc->table]['palettes'][$palettes] = str_replace(
                '{invisible_legend:hide}'
            ,   '{invisible_legend:hide},cms_pages_scope,cms_pages'
            ,   $GLOBALS['TL_DCA'][$dc->table]['palettes'][$palettes]
            );
        }
    }


    /**
     * Change palette during onload
     *
     * @param \DataContainer $dc
     * @param object $objMI
     *
     * @return none
     */
    public function generateOneEntryAndRedirect( $dc ) {

        $count = ConversionItemModel::countAll();

        if( !$count ){

            $default = new ConversionItemModel();

            $default->id = 1;
            $default->tstamp = time();
            $default->name = 'default';
            $default->save();
        }

        $refererId = System::getContainer()->get('request_stack')->getCurrentRequest()->get('_contao_referer_id');

        $this->redirect($this->addToUrl('table=tl_content&amp;id=1'));
    }


    /**
     * Return all conversion elements types as array
     *
     * @return array
     */
    public function getConversionElementTypes() {

        $groups = [];

        foreach( $GLOBALS['TL_CTE'] as $k=>$v ) {

            if( $k !== 'conversion_elements' ) {
                continue;
            }

            foreach( array_keys($v) as $kk ) {

                if( !ehso::hasFeature('ce_'.$kk) && $kk != 'default') {
                    continue;
                }

                $groups[$k][] = $kk;
            }
        }

        return $groups;
    }


    /**
     * Return all conversion elements as array
     *
     * @return array
     */
    public function getConversionElements() {

        $objContents = ContentModel::findBy(['ptable=?'], ['tl_cms_conversion_item']);

        $aRet = [];
        foreach( $objContents as $value ) {
            $aRet[$value->id] = sprintf(
                "%s [%s]"
            ,   $value->cms_mi_label
            ,   $GLOBALS['TL_LANG']['CTE'][$value->type][0]
            );
        }

        return $aRet;
    }


    /**
     * Generate a wizard for the conversion item
     *
     * @return string
     */
    public function conversionItemWizard( $dc ) {

        if( $dc->activeRecord->cms_ci_id < 1 ) {
            return '';
        }

        $oCI = ContentModel::findOneById($dc->activeRecord->cms_ci_id);

        if( !$oCI || !array_key_exists($oCI->type, $GLOBALS['TL_CTE']['conversion_elements']) ) {
            return '';
        }

        return ' <a href="'.System::getContainer()->get('router')->generate('contao_backend').'?do=cms_conversion&amp;table=tl_content&amp;act=edit&amp;id=' . $dc->activeRecord->cms_ci_id . '&amp;popup=1&amp;nb=1&amp;rt=' . REQUEST_TOKEN . '" title="' . sprintf(\StringUtil::specialchars($GLOBALS['TL_LANG']['tl_content']['editalias'][1]), $dc->activeRecord->cms_ci_id) . '" onclick="Backend.openModalIframe({\'title\':\'' . StringUtil::specialchars(str_replace("'", "\\'", sprintf($GLOBALS['TL_LANG']['tl_content']['editalias'][1], $dc->activeRecord->cms_ci_id))) . '\',\'url\':this.href});return false">' . Image::getHtml('edit.svg', $GLOBALS['TL_LANG']['tl_content']['editalias'][0]) . '</a>';
    }


    /**
     * Returns if the DCA should be closed or not
     *
     * @return boolean
     */
    public static function isClosed() {

        return !ehso::hasFeature('conversion_element');
    }
}
