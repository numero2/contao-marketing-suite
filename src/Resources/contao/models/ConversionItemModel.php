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

            $result = array();
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

        }

        if( $objModels instanceof PageModel ) {

            $objChildren = ArticleModel::findBy(['pid=? AND published=?'], [$objModels->id, '1']);
            return self::findAllOn($objChildren);
        }

        if( $objModels instanceof ArticleModel ) {

            $types = "'".implode("','", array_keys($GLOBALS['TL_CTE']['conversion_elements']))."'";
            $objChildren = ContentModel::findBy(['ptable=? AND pid=? AND type IN ('.$types.') AND invisible=?'], ['tl_article', $objModels->id, '']);

            return self::findAllOn($objChildren);
        }

    }
}
