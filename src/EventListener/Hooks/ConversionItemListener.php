<?php

/**
 * Contao Marketing Suite Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\MarketingSuiteBundle\EventListener\Hooks;

use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\PageRegular;
use Contao\StringUtil;
use numero2\MarketingSuiteBundle\EventListener\DataContainer\ConversionItemListener as DCAConversionItem;


class ConversionItemListener {


    /**
     * @var Contao\CoreBundle\InsertTag\InsertTagParser
     */
    protected $insertTagParser;


    public function __construct( InsertTagParser $insertTagParser ) {

        $this->insertTagParser = $insertTagParser;
    }


    /**
     * Generates and adds all script tags to the page
     *
     * @param Contao\PageModel $objPageOriginal
     * @param Contao\LayoutModel $objLayout
     * @param Contao\PageRegular $objPageRegular
     *
     * @Hook("generatePage")
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
            $GLOBALS['TL_BODY'][] = $this->insertTagParser->replace($strContents);
        }
    }
}
