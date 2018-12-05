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
namespace numero2\MarketingSuite;

use numero2\MarketingSuite\MarketingItem\MarketingItem as MarketingInstance;


class MarketingItem extends \System {


    /**
     * Alter child record of tl_content
     *
     * @param array $arrRow
     * @param string $buffer
     *
     * @return string
     */
    public static function alterContentChildRecord( $arrRow, $buffer ) {

        self::loadLanguageFile('tl_content');

        $group = ContentGroupModel::findOneById($arrRow['pid']);
        if( $group ) {

            $objMI = MarketingItemModel::findById($group->pid);
            if( $objMI ) {

                $instance = MarketingInstance::getChildInstance($objMI->type);

                if( $instance ) {
                    $buffer = $instance->alterContentChildRecord($arrRow, $buffer, $objMI, $group);
                }
            }
        }

        return $buffer;
    }


    /**
     * Alter header of tl_content
     *
     * @param array $args
     * @param \DataContainer $dc
     *
     * @return array
     */
    public static function alterContentHeader( $args, \DataContainer $dc ) {

        $group = null;
        $objMI = null;

        if( in_array(\Input::get('mode'), ["copy", "cut"]) ) {

            $content = \ContentModel::findOneById($dc->id);
            if( $content ) {
                $group = ContentGroupModel::findOneById($content->pid);
                if( $group ) {
                    $objMI = MarketingItemModel::findById($group->pid);
                }
            }

        } else {

            $group = ContentGroupModel::findOneById($dc->id);
            if( $group ) {
                $objMI = MarketingItemModel::findById($group->pid);
            }
        }


        if( $objMI ) {

            self::loadLanguageFile('tl_cms_marketing_item');

            // type description
            $pType = $objMI->type;

            foreach( $GLOBALS['TL_DCA']['tl_cms_marketing_item']['list']['label']['fields'] as $value ) {

                if( $value === 'type' ) {
                    $args[$GLOBALS['TL_LANG']['tl_cms_marketing_item'][$value][0]] = $GLOBALS['TL_LANG']['tl_cms_marketing_item']['types'][$objMI->{$value}];
                    continue;
                }

                $args[$GLOBALS['TL_LANG']['tl_cms_marketing_item'][$value][0]] = $objMI->{$value};
            }

            $args[$GLOBALS['TL_LANG']['tl_cms_marketing_item']['child_header_label'][$pType.'_info'][0]] = $GLOBALS['TL_LANG']['tl_cms_marketing_item']['child_header_label'][$pType.'_info'][1];

            $instance = MarketingInstance::getChildInstance($objMI->type);

            if( $instance ) {
                $args = $instance->alterContentHeader($args, $dc, $objMI, $group);
            }
        }

        return $args;
    }


    /**
     * Alter dca configuration of tl_content
     *
     * @param \DataContainer $dc
     */
    public static function alterContentDCA( \DataContainer $dc ) {

        $objContent = null;
        $objMI = null;
        if( \Input::get('act') == 'edit' ) {

            $objContent = \ContentModel::findById($dc->id);

            if( $objContent ) {
                $parent_class = \Model::getClassFromTable($objContent->ptable);
                $objContentParent = $parent_class::findById($objContent->pid);

                if( $objContentParent instanceof MarketingItemModel ) {
                    $objMi = $objContentParent;
                } else {
                    $objMI = MarketingItemModel::findById($objContentParent->pid);
                }
            }
        } else {

            $objMI = MarketingItemModel::findById($dc->id);
        }

        if( !empty($objMI) ) {

            $instance = MarketingInstance::getChildInstance($objMI->type);

            if( $instance ) {
                $args = $instance->alterContentDCA($dc, $objMI, $objContent, $objContentParent);
            }

        }
    }


    /**
     * handles what happens after a user submits the form
     *
     * @param \DataCotainer $dc
     */
    public function submitMarketingItem( \DataContainer $dc ) {

        if( \Input::post('SUBMIT_TYPE') == 'auto' ) {
            return;
        }

        $objMI = MarketingItemModel::findById($dc->id);

        if( $objMI ) {
            $objMI->refresh();

            $instance = MarketingInstance::getChildInstance($objMI->type);

            if( $instance ) {
                $instance->submitMarketingItem($dc, $objMI);
            }
        }
    }


    /**
     * Generate a wizard for the marketing item
     *
     * @param \DataContainer $dc
     *
     * @return string
     */
    public function marketingItemWizard( \DataContainer $dc ) {

        return ($dc->activeRecord->cms_mi_id < 1) ? '' : ' <a href="contao/main.php?do=cms_marketing&amp;table=tl_cms_marketing_item&amp;act=edit&amp;id=' . $dc->activeRecord->cms_mi_id . '&amp;popup=1&amp;nb=1&amp;rt=' . REQUEST_TOKEN . '" title="' . sprintf(\StringUtil::specialchars($GLOBALS['TL_LANG']['tl_content']['editalias'][1]), $dc->activeRecord->cms_mi_id) . '" onclick="Backend.openModalIframe({\'title\':\'' . \StringUtil::specialchars(str_replace("'", "\\'", sprintf($GLOBALS['TL_LANG']['tl_content']['editalias'][1], $dc->activeRecord->cms_mi_id))) . '\',\'url\':this.href});return false">' . \Image::getHtml('edit.svg', $GLOBALS['TL_LANG']['tl_content']['editalias'][0]) . '</a>';
    }


    /**
     * Returns a list of options for marketing items
     *
     * @param \DataContainer $dc
     *
     * @return array
     */
    public function getAvailableOptions( \DataContainer $dc ) {

        $objItems = NULL;
        $objItems = MarketingItemModel::findBy(["init_step=?"], ['']);

        if( $objItems ) {

            \Controller::loadLanguageFile('tl_cms_marketing_item');
            $aOptions = [];

            while( $objItems->next() ) {

                $aOptions[ $objItems->id ] = sprintf(
                    "%s [%s]"
                ,   $objItems->name
                ,   $GLOBALS['TL_LANG']['tl_cms_marketing_item']['types'][$objItems->type]
                );
            }

            return $aOptions;
        }

        return [];
    }


    /**
     * Return all types as array
     *
     * @return array
     */
    public function getMarketingItemTypes() {

        \Controller::loadDataContainer('tl_cms_marketing_item');

        $types = [];

        foreach( $GLOBALS['TL_DCA']['tl_cms_marketing_item']['palettes'] as $k=>$v ) {

            if( $k == '__selector__' ) {
                continue;
            }

            if( !\numero2\MarketingSuite\Backend\License::hasFeature('me_'.$k) && $k != 'default') {
                continue;
            }

            if( empty($GLOBALS['TL_LANG']['tl_cms_marketing_item']['types'][$k]) ){

                $types[$k] = $k;
            } else {

                $types[$k] = $GLOBALS['TL_LANG']['tl_cms_marketing_item']['types'][$k];
            }
        }

        return $types;
    }
}
