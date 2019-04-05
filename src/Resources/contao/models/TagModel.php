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


namespace numero2\MarketingSuite;

use Contao\Database;
use Contao\Model;
use Contao\PageModel;


class TagModel extends Model {


    /**
     * Table name
     * @var string
     */
    protected static $strTable = 'tl_cms_tag';


    /**
     * Find active tags for given page
     *
     * @param int $pageId ID of the page
     *
     * @return \Model\Collection|false
     */
    public static function findAllActiveByPage( $pageId ) {

        $oPage = NULL;
        $oPage = PageModel::findWithDetails($pageId);

        if( $oPage && !empty($oPage->id) ) {

            $values = array_map(function($n) { return '%"'.$n.'"%'; }, $oPage->trail);
            $where[] = '('. implode(" OR ", array_pad([], count($values), 'orderPages like ?')) .')';

            $where[] = 'active=?';
            $values[] = '1';

            $objResult = NULL;
            $objResult = Database::getInstance()->prepare("
                SELECT
                    *
                FROM ".self::$strTable."
                ".($where?"WHERE ".implode(" AND ", $where):'')."
                ORDER BY type ASC, id ASC
            ")->execute($values);

            return self::createCollectionFromDbResult($objResult, self::$strTable);
        }

        return null;
    }
}
