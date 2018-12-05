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
 * Namespace
 */
namespace numero2\MarketingSuite;


class ConversionItem extends \System {


    /**
     * Return all conversion elements types as array
     *
     * @return array
     */
    public function getConversionElementTypes() {

        $groups = array();

        foreach( $GLOBALS['TL_CTE'] as $k=>$v ) {

            if( $k !== 'conversion_elements' ) {
                continue;
            }

            foreach( array_keys($v) as $kk ) {

                if( !\numero2\MarketingSuite\Backend\License::hasFeature('ce_'.$kk) && $kk != 'default') {
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

        $objContents = \ContentModel::findBy(['ptable=?'], ['tl_cms_conversion_item']);

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

        return ($dc->activeRecord->cms_ci_id < 1) ? '' : ' <a href="contao/main.php?do=cms_conversion&amp;table=tl_content&amp;act=edit&amp;id=' . $dc->activeRecord->cms_ci_id . '&amp;popup=1&amp;nb=1&amp;rt=' . REQUEST_TOKEN . '" title="' . sprintf(\StringUtil::specialchars($GLOBALS['TL_LANG']['tl_content']['editalias'][1]), $dc->activeRecord->cms_ci_id) . '" onclick="Backend.openModalIframe({\'title\':\'' . \StringUtil::specialchars(str_replace("'", "\\'", sprintf($GLOBALS['TL_LANG']['tl_content']['editalias'][1], $dc->activeRecord->cms_ci_id))) . '\',\'url\':this.href});return false">' . \Image::getHtml('edit.svg', $GLOBALS['TL_LANG']['tl_content']['editalias'][0]) . '</a>';
    }
}
