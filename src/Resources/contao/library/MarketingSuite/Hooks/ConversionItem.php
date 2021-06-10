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


namespace numero2\MarketingSuite\Hooks;

use Contao\ContentModel;
use Contao\Controller;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\PageRegular;
use Contao\StringUtil;
use numero2\MarketingSuite\Backend\License as askfho;
use numero2\MarketingSuite\DCAHelper\ConversionItem as DCAConversionItem;


class ConversionItem extends Hooks {


    /**
     * Generates and adds all script tags to the page
     *
     * @param PageModel $objPageOriginal
     * @param LayoutModel $objLayout
     * @param PageRegular $objPageRegular
     */
    public function generateGlobalConversionItems( PageModel $objPageOriginal, LayoutModel $objLayout, PageRegular $objPageRegular ) {

        global $objPage;

        $t = ContentModel::getTable();
        $oContents = NULL;
        $oContents = ContentModel::findBy([$t.'.type in (\''.implode("','",DCAConversionItem::$aGlobalTypes).'\') AND '.$t.'.ptable=? AND '.$t.'.invisible=? AND '.$t.'.cms_pages_scope!=? AND '.$t.'.cms_pages_scope!=?'], ['tl_cms_conversion_item', '', '', 'none']);

        $strContents = "";

        if( $oContents && count($oContents) ) {

            // prepare which page is allowed on which scope
            $allowed = [
                'current_page' => []
            ,   'current_and_direct_children' => []
            ,   'current_and_all_children' => []
            ];

            foreach( array_reverse($objPage->trail) as $value ) {

                if( $value == $objPage->id ) {
                    $allowed['current_page'][] = $value;
                }
                if( count($allowed['current_page']) && count($allowed['current_and_direct_children'] ) < 2 ) {
                    $allowed['current_and_direct_children'][] = $value;
                }
                if( count($allowed['current_page']) ) {
                    $allowed['current_and_all_children'][] = $value;
                }
            }

            foreach( $oContents as $key => $oContent ) {

                // skip on not enough data
                if( empty($oContent->cms_pages) || empty($allowed[$oContent->cms_pages_scope]) ) {
                    continue;
                }

                $oContentPages = StringUtil::deserialize($oContent->cms_pages);

                // check all pages if one is allowed
                foreach( $allowed[$oContent->cms_pages_scope] as $key => $value ) {

                    if( in_array($value, $oContentPages) ) {

                        $strContents .= Controller::getContentElement($oContent);
                        break;
                    }
                }
            }
        }

        if( strlen($strContents) ) {
            $GLOBALS['TL_BODY'][] = Controller::replaceInsertTags($strContents);
        }
    }
}
