<?php

/**
 * Contao Marketing Suite Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\MarketingSuite;

use Contao\BackendTemplate;
use Contao\Config;
use Contao\Controller;
use Contao\Image;
use Contao\Model;


class Backend extends Controller {


    /**
     * Parses the template with the given  values
     *
     * @param string $templateCls
     * @param array $arrValues
     *
     * @return string
     */
    public static function parseWithTemplate( $templateCls, $arrValues ) {

        $objTemplate = new BackendTemplate($templateCls);

        $objTemplate->setData($arrValues);

        return $objTemplate->parse();
    }


    /**
     * Generates html code that will show the path where the given object is used
     *
     * @param Contao\Model|array $obj
     * @param integer $limit
     *
     * @return string
     */
    public static function generateReferencePath( $obj, $limit ) {

        if( $limit <= 0 ) {
            return '';
        }

        if( is_array($obj) && $obj['table'] && $obj['ids'] ) {

            $oTable = Model::getClassFromTable($obj['table']);

            $objs = $oTable::findMultipleByIds($obj['ids']);

            if( $objs ) {
                $strReturn = '';
                foreach( $objs as $o ) {
                    $strReturn .= self::generateReferencePath($o, $limit);
                }
                return $strReturn;
            }
            return null;
        }

        $currentTable = $obj::getTable();
        Controller::loadDataContainer($currentTable);

        // find parent element
        $parentTable = null;

        if( !empty($GLOBALS['TL_DCA'][$currentTable]['config']['dynamicPtable']) && $GLOBALS['TL_DCA'][$currentTable]['config']['dynamicPtable'] ) {

            if( !empty($obj->ptable) ) {
                $parentTable = $obj->ptable;
            }

        } else {

            if( !empty($GLOBALS['TL_DCA'][$currentTable]['config']['ptable']) ) {
                $parentTable = $GLOBALS['TL_DCA'][$currentTable]['config']['ptable'];
            }
        }


        if( $parentTable !== null ) {
            $parentTable = Model::getClassFromTable($parentTable);
            if( $parentTable === "Model" ) {
                $parentTable = null;
            }
        }

        $parent = null;
        if( $parentTable && $obj->pid ) {
            $parent = $parentTable::findOneById($obj->pid);
        }

        return (($parent!=null)?self::generateReferencePath($parent, $limit-1):'') . '<span>' . self::generateReferenceItem($obj) . '</span>';
    }


    /**
     * Generates html for one item in the referene path
     *
     * @param Contao\Model $obj
     *
     * @return string
     */
    public static function generateReferenceItem( $obj ) {

        $currentTable = $obj::getTable();

        $icon = null;
        $text = null;
        switch( $currentTable ) {

            case 'tl_content':
                $icon = ($obj->invisible==1?'invisible':'visible').'.svg';
                $text =  $GLOBALS['TL_LANG']['CTE']['alias'][0] .' (ID: ' . $obj->id.')';
                break;

            case 'tl_calendar_events':
            case 'tl_article':
                $icon = ($obj->published==1?'articles':'articles_').'.svg';
                $text = $obj->title;
                break;

            case 'tl_page':
                $icon = Controller::getPageStatusIcon($obj);
                $text = $obj->title . ' (' . $obj->alias . Config::get('urlSuffix') . ')';
                break;

            case 'tl_news':
                $icon = ($obj->published==1?'articles':'articles_').'.svg';
                $text = $obj->headline;
                break;

            case 'tl_news_archive':
                $icon = 'bundles/contaonews/news.svg';
                $text = $obj->title;
                break;

            case 'tl_calendar':
                $icon = 'bundles/contaocalendar/calendar.svg';
                $text = $obj->title;
                break;

            case 'tl_theme':
                $icon = 'themes.svg';
                $text = $obj->name;
                break;

            case 'tl_module':
                $icon = 'modules.svg';
                $text = $obj->name;
                break;

            default:
                $text = $currentTable;
                break;
        }

        return Image::getHtml($icon) . ' ' . $text;
    }
}
