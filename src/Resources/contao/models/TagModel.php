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

use Contao\Database;
use Contao\Model;
use Contao\Model\Collection;
use Contao\PageModel;
use Contao\StringUtil;


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
     * @return Contao\Model\Collection|null
     */
    public static function findAllActiveByPage( $pageId ) {

        $oPage = NULL;
        $oPage = PageModel::findWithDetails($pageId);

        if( $oPage && !empty($oPage->id) ) {

            $oTags = null;
            $oTags = self::findBy(['pages IS NOT NULL AND active=?'], ['1'], ['order'=>'sorting ASC']);

            if( $oTags ) {

                $aTags = [];

                foreach( $oTags as $oTag ) {

                    $aPages = StringUtil::deserialize($oTag->pages);

                    if( is_array($aPages) && count(array_intersect($oPage->trail, $aPages)) ){
                        $aTags[] = $oTag;
                    }
                }

                return new Collection($aTags, self::$strTable);
            }
        }

        return null;
    }


    /**
     * Find groups with information from fallback or saved root
     *
     * @param integer $root
     *
     * @return static|Contao\Model\Collection|null A model, model collection or null if the result is empty
     */
    public static function findGroupsWithRootInfo( $root ) {

        $objResult = NULL;
        $objResult = Database::getInstance()->prepare("
            SELECT *
            FROM ".self::$strTable."
            WHERE type=?
            ORDER BY root_pid ASC, sorting ASC
        ")->execute('group');

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
