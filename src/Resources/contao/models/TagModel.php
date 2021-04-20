<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2020 Leo Feyer
 *
 * @package   Contao Marketing Suite
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright 2020 numero2 - Agentur für digitales Marketing
 */


namespace numero2\MarketingSuite;

use Contao\Database;
use Contao\Model;
use Contao\PageModel;
use Contao\Model\Collection;


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
            $where[] = '('. implode(" OR ", array_pad([], count($values), 'pages like ?')) .')';

            $where[] = 'active=?';
            $values[] = '1';

            $objResult = NULL;
            $objResult = Database::getInstance()->prepare("
                SELECT
                    *
                FROM ".self::$strTable."
                ".($where?"WHERE ".implode(" AND ", $where):'')."
                ORDER BY sorting ASC
            ")->execute($values);

            return self::createCollectionFromDbResult($objResult, self::$strTable);
        }

        return null;
    }


    /**
     * Find groups with information from fallback or saved root
     *
     * @param integer $root
     *
     * @return static|Model\Collection|null A model, model collection or null if the result is empty
     */
    public static function findGroupsWithRootInfo($root) {

        $objResult = NULL;
        $objResult = Database::getInstance()->prepare("
            SELECT *
            FROM ".self::$strTable."
            WHERE type=?
            ORDER BY root_pid ASC, sorting ASC
        ")->execute(['group']);

        $colResult = self::createCollectionFromDbResult($objResult, self::$strTable);

        $aReturn = [];
        if( $colResult ) {

            foreach( $colResult as $oTag ) {

                // load default structur set in be
                if( $oTag->root_pid == 0 ) {
                    $aReturn[$oTag->id] = $oTag;

                // override with for this root
                } else {

                    $otherTag = $aReturn[$oTag->root_pid];

                    $oTag->origin_id = $oTag->id;
                    $oTag->id = $oTag->root_pid;

                    if( $oTag->root == $root ) {

                        $aReturn[$oTag->root_pid] = $oTag;
                    }
                }
            }
        }

        return new Collection($aReturn, self::$strTable);
    }
}
