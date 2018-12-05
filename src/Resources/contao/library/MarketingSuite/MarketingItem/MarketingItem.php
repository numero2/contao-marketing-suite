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
namespace numero2\MarketingSuite\MarketingItem;


abstract class MarketingItem extends \Backend {


    /**
     * get Instance of this class based on type
     *
     * @param  string $type
     *
     * @return object
     */
     public static function getChildInstance($type) {

        if( $type == 'current_page' ) {
            return new CurrentPage();
        }
        if( $type == 'a_b_test' ) {
            return new ABTest();
        }
        if( $type == 'visited_pages' ) {
            return new VisitedPages();
        }

        return null;
    }

    /**
     * calculates a sorting value for the content element
     *
     * @param  integer $id
     * @param  array $aOrder
     *
     * @return integer
     */
    protected static function getSorting($id, $aOrder) {

        return (array_search($id, $aOrder)+1)*32;
    }


    /**
     * generates the edit url to the given element
     *
     * @param  Model $obj
     *
     * @return string
     */
    protected static function switchToEdit($obj) {

        $refererId = \System::getContainer()->get('request_stack')->getCurrentRequest()->get('_contao_referer_id');

        return TL_SCRIPT . '?do='.\Input::get('do').'&table='.$obj->getTable().'&id='.$obj->id.'&pid='.$obj->pid.'&mode=1&act=edit&rt='.REQUEST_TOKEN.'&ref='.$refererId;
    }

    /**
     * Alter child record of tl_content
     *
     * @param  array $arrRow
     * @param  string $buffer
     * @param  object $objMI
     *
     * @return string
     */
    abstract public function alterContentChildRecord($arrRow, $buffer, $objMI, $objCP);


    /**
     * Alter header of tl_content
     *
     * @param  array $args
     * @param  DataContainer $dc
     * @param  object $objMI
     *
     * @return array
     */
    abstract public function alterContentHeader($args, $dc, $objMI, $objCP);


    /**
     * alter dca configuration of tl_content
     *
     * @param  DataContainer $dc
     * @param  object $objMI
     * @param  object $objContent
     *
     * @return none
     */
    abstract public function alterContentDCA($dc, $objMI, $objContent, $objCP);


    /**
     * handles what happens after a user submits the form
     *
     * @param  DataContainer $dc
     * @param  object $objMI
     *
     * @return none
     */
    abstract public function submitMarketingItem($dc, $objMI);


    /**
     * selects one contentId that should be displayed to the user
     *
     * @param  object $objContents
     * @param  object $objMI
     * @param  object $objContent
     *
     * @return integer
     */
    abstract public function selectContentId($objContents, $objMI, $objCP, $objContent);

}
