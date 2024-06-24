<?php

/**
 * Contao Marketing Suite Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\MarketingSuite\MarketingItem;

use Contao\Backend;
use Contao\Input;
use Contao\System;


abstract class MarketingItem extends Backend {


    /**
     * Get Instance of this class based on type
     *
     * @param string $type
     *
     * @return CurrentPage|ABTest|ABTestPage|VisitedPages|null
     */
     public static function getChildInstance( $type ) {

         if( $type == 'a_b_test' ) {
             return new ABTest();
         }

         if( $type == 'a_b_test_page' ) {
             return new ABTestPage();
         }

        if( $type == 'current_page' ) {
            return new CurrentPage();
        }

        if( $type == 'visited_pages' ) {
            return new VisitedPages();
        }

        return null;
    }


    /**
     * Return a string describing the current status of this a_b_test.
     *
     * @param array $arrMI
     *
     * @return string
     */
    public function getStatus( $arrMI ) {
        return '';
    }


    /**
     * Calculates a sorting value for the content element
     *
     * @param integer $id
     * @param array $aOrder
     *
     * @return integer
     */
    protected static function getSorting( $id, $aOrder ) {
        return (array_search($id, $aOrder)+1)*32;
    }


    /**
     * Generates the edit url to the given element
     *
     * @param Contao\Model $obj
     *
     * @return string
     */
    protected static function switchToEdit( $obj ) {

        $routePrefix = System::getContainer()->getParameter('contao.backend.route_prefix');
        $requestToken = System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();
        $refererId = System::getContainer()->get('request_stack')->getCurrentRequest()->get('_contao_referer_id');

        return $routePrefix.'?do='.Input::get('do').'&table='.$obj->getTable().'&id='.$obj->id.'&pid='.$obj->pid.'&mode=1&act=edit&rt='.$requestToken.'&ref='.$refererId;
    }


    /**
     * Alter child record of tl_content
     *
     * @param array $arrRow
     * @param string $buffer
     * @param object $objMI
     * @param object $objCP
     *
     * @return string
     */
    abstract public function alterContentChildRecord( $arrRow, $buffer, $objMI, $objCP );


    /**
     * Alter header of tl_content
     *
     * @param array $args
     * @param Contao\DataContainer $dc
     * @param object $objMI
     * @param object $objCP
     *
     * @return array
     */
    abstract public function alterContentHeader( $args, $dc, $objMI, $objCP );


    /**
     * Alter dca configuration of tl_content
     *
     * @param Contao\DataContainer $dc
     * @param object $objMI
     * @param object $objContent
     * @param object $objCP
     */
    abstract public function alterContentDCA( $dc, $objMI, $objContent, $objCP );


    /**
     * Handles what happens after a user submits the form
     *
     * @param Contao\DataContainer $dc
     * @param object $objMI
     */
    abstract public function submitMarketingItem( $dc, $objMI );


    /**
     * Selects one contentId that should be displayed to the user
     *
     * @param object $objContents
     * @param object $objMI
     * @param object $objCP
     * @param object $objContent
     *
     * @return integer
     */
    abstract public function selectContentId( $objContents, $objMI, $objCP, $objContent );

}
