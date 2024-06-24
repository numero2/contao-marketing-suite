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

use Contao\ArticleModel;
use Contao\ContentModel;
use Contao\Model;
use Contao\Model\Collection;
use Contao\PageModel;


class ConversionItemModel extends Model {


    /**
     * Table name
     * @var string
     */
    protected static $strTable = 'tl_cms_conversion_item';


    /**
     * List all inline conversion items on the given page or article
     *
     * @param Collection|PageModel|ArticleModel $objModels
     *
     * @return Collection|ConentModel|null
     */
    public static function findAllOn($objModels) {

        if( $objModels === null ) {
            return $objModels;
        }

        if( $objModels instanceof Collection ) {

            if( $objModels->current() instanceof ContentModel ) {
                return $objModels;
            }

            $result = [];
            foreach( $objModels as $objModel ) {

                $children = self::findAllOn($objModel);
                if( $children ) {

                    if( $children instanceof Collection ) {

                        foreach( $children as $child ) {
                            $result[] = $child;
                        }
                    } else {
                        $result[] = $children;
                    }
                }
            }

            if( count($result) ) {

                $colResult = new Collection( $result, $result[0]->getTable());
                return self::findAllOn($colResult);

            } else {

                return null;
            }

        } else if( $objModels instanceof PageModel ) {

            $t = ArticleModel::getTable();
            $objChildren = NULL;
            $objChildren = ArticleModel::findBy([$t.'.pid=? AND '.$t.'.published=?'], [$objModels->id, '1']);

            return self::findAllOn($objChildren);

        } else if( $objModels instanceof ArticleModel ) {

            $types = "'".implode("','", array_keys($GLOBALS['TL_CTE']['conversion_elements']))."'";

            $t = ContentModel::getTable();
            $objChildren = NULL;
            $objChildren = ContentModel::findBy([$t.'.ptable=? AND '.$t.'.pid=? AND '.$t.'.type IN ('.$types.') AND '.$t.'.invisible=?'], ['tl_article', $objModels->id, '']);

            return self::findAllOn($objChildren);
        }
    }
}
