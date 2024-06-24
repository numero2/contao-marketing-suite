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

use Contao\Config;
use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\Image;
use Contao\Input;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use numero2\MarketingSuite\Backend;
use numero2\MarketingSuite\Backend\Wizard;
use numero2\MarketingSuite\ContentGroupModel;


class VisitedPages extends MarketingItem {


    /**
     * Alter child record of tl_content
     *
     * @param array $arrRow
     * @param string $buffer
     * @param object $objMarketingItem
     * @param object $objContentParent
     *
     * @return string
     */
    public function alterContentChildRecord( $arrRow, $buffer, $objMarketingItem, $objContentParent ) {

        $buffer = explode('</div>', $buffer);

        $strType = $arrRow['cms_mi_pages_criteria'];
        if( !empty($GLOBALS['TL_LANG']['tl_content']['cms_mi_pages_criterias'][$strType]) ) {
            $strType = $GLOBALS['TL_LANG']['tl_content']['cms_mi_pages_criterias'][$strType];
        }

        $pages = PageModel::findMultipleByIds(StringUtil::deserialize($arrRow['cms_mi_pages']));

        if( $arrRow['cms_mi_pages_criteria'] == 'always' ){

            $buffer[0] .= ' ('.$strType.')';
        } else {

            $strPages = '';

            if( $pages ) {
                foreach( $pages as $value ) {
                    $strPages .= Image::getHtml(Controller::getPageStatusIcon($value)) . ' ' . $value->title . ' (' . $value->alias . Config::get('urlSuffix') . ')<br>';
                }
            }

            $aOverlay = [
                'label' => '('.$strType.')'
            ,   'content' => $strPages
            ];

            $buffer[0] .= Backend::parseWithTemplate('backend/elements/overlay', $aOverlay );
        }

        $buffer = implode('</div>', $buffer);

        return $buffer;
    }


    /**
     * Alter header of tl_content
     *
     * @param array $args
     * @param Contao\DataContainer $dc
     * @param object $objMarketingItem
     * @param object $objContentParent
     */
    public function alterContentHeader( $args, $dc, $objMarketingItem, $objContentParent ) {

        $routePrefix = System::getContainer()->getParameter('contao.backend.route_prefix');
        $requestToken = System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();
        $refererId = System::getContainer()->get('request_stack')->getCurrentRequest()->get('_contao_referer_id');
        $routePrefix = System::getContainer()->getParameter('contao.backend.route_prefix');

        $GLOBALS['TL_MOOTOOLS'][] =
        "<script>
        CMSBackend.override('.tl_content_right .edit', '<a href=\"".$routePrefix."?do=cms_marketing&amp;id=$objMarketingItem->id&amp;act=edit&amp;rt=".$requestToken."&amp;ref=$refererId\" class=\"edit\" title=\"".$GLOBALS['TL_LANG']['tl_cms_marketing_item']['edit'][0]."\"><img src=\"system/themes/flexible/icons/header.svg\" width=\"16\" height=\"16\" alt=\"".$GLOBALS['TL_LANG']['tl_cms_marketing_item']['edit'][0]."\"></a>');
        </script>";

        if( $objMarketingItem && $objMarketingItem->type == 'visited_pages' && !empty($objMarketingItem->init_step) ) {

            if( Input::get('finish') && Input::get('finish') == "true" ) {
                $objMarketingItem->init_step = '';
                $objMarketingItem->save();

                $this->redirect($this->addToUrl('', true, ['finish']));
            }

            $beWizard = new Wizard();
            $aWizardConfig = [
                'step' => 2
            ,   'type' => $objMarketingItem->type
            ,   'table' => 'tl_content'
            ];

            $GLOBALS['TL_MOOTOOLS'][] =
            "<script>
                CMSBackend.prepend('.tl_listing_container.parent_view', '".addslashes($beWizard->generateTopForListing($aWizardConfig))."');
            </script>";

            $GLOBALS['TL_MOOTOOLS'][] =
            "<script>
            CMSBackend.append('.tl_listing_container.parent_view',
                '<div class=\"tl_header cms_helper_bottom_legend\">'
                    +'<div class=\"tl_panel\"><a class=\"tl_submit\" href=\"".$this->addToUrl('finish=true')."\">".$GLOBALS['TL_LANG']['MSC']['finish']."</a></div>'
                +'</div>'
            );
            </script>";
        }

        return $args;
    }


    /**
     * Alter dca configuration of tl_content
     *
     * @param Contao\DataContainer $dc
     * @param object $objMarketingItem
     * @param object $objContent
     * @param object $objContentParent
     */
    public function alterContentDCA( $dc, $objMarketingItem, $objContent, $objContentParent ) {

        if( Input::get('act') == 'edit' ) {

            $GLOBALS['TL_DCA']['tl_content']['config']['onsubmit_callback'][] = ['\numero2\MarketingSuite\MarketingItem\VisitedPages', 'submitContent'];
            $GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'][] = ['\numero2\MarketingSuite\MarketingItem\VisitedPages', 'loadContent'];
        }

        // only change palette during edit
        if( Input::get('act') == 'edit' && $objContent ) {

            // $objContent
            if( $objContent->cms_mi_pages_criteria == 'always' ) {

                $pm = PaletteManipulator::create()
                    ->addLegend('marketing_suite_legend', 'type_legend', 'before')
                    ->addField(['cms_mi_pages_criteria'], 'marketing_suite_legend', 'append')
                ;
            } else {

                $pm = PaletteManipulator::create()
                    ->addLegend('marketing_suite_legend', 'type_legend', 'before')
                    ->addField(['cms_mi_pages_criteria', 'cms_mi_pages'], 'marketing_suite_legend', 'append')
                ;
            }

            $pm->applyToPalette($objContent->type, 'tl_content');
        }
    }


    /**
     * Handles what happens after a user submits the child edit form
     *
     * @param Contao\DataContainer $dc
     */
    public function submitContent( $dc ) {

        if( Input::post('SUBMIT_TYPE') == 'auto' ) {
            return;
        }

        $content = ContentModel::findOneById($dc->activeRecord->id);

        if( $content && $content->cms_mi_pages_criteria == 'always' ) {

            $lastContent = ContentModel::findOneBy(['pid=? AND ptable=?'], [$content->pid, $content->ptable], ['order'=>'sorting DESC']);

            if( $lastContent && $lastContent->id != $content->id ) {

                $content->sorting = $lastContent->sorting + 32;
                $content->save();
            }
        }

        // $group = \numero2\MarketingSuite\ContentGroupModel::findOneById($dc->activeRecord->pid);
        // $objMI = \numero2\MarketingSuite\MarketingItemModel::findById($group->pid);
    }


    /**
     * Handles what happens after a user submits the child edit form
     *
     * @param Contao\DataContainer $dc
     */
    public function loadContent( $dc ) {

        // $group = \numero2\MarketingSuite\ContentGroupModel::findOneById($dc->activeRecord->pid);
        // $objMI = \numero2\MarketingSuite\MarketingItemModel::findById($group->pid);
    }


    /**
     * Handles what happens after a user submits the form
     *
     * @param Contao\DataContainer $dc
     * @param object $objMarketingItem
     */
    public function submitMarketingItem( $dc, $objMarketingItem ) {

        $group = ContentGroupModel::findOneByPid($objMarketingItem->id);

        if( !$group ) {
            $group = new ContentGroupModel();
            $group->tstamp = time();
            $group->pid = $objMarketingItem->id;
            $group->name = '';
            $group->active = '1';
            $group->save();

            $routePrefix = System::getContainer()->getParameter('contao.backend.route_prefix');
            $requestToken = System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();
            $refererId = System::getContainer()->get('request_stack')->getCurrentRequest()->get('_contao_referer_id');
            $routePrefix = System::getContainer()->getParameter('contao.backend.route_prefix');

            $objMarketingItem->init_step = $routePrefix . '?do=cms_marketing&amp;table=tl_content&amp;id='.$group->id;
            $objMarketingItem->save();

            $this->redirect($routePrefix . '?do=cms_marketing&amp;table=tl_content&amp;id='.$group->id.'&amp;rt='.$requestToken.'&ref='.$refererId);
        }
    }


    /**
     * Selects one contentId that should be displayed to the user
     *
     * @param object $objContents
     * @param object $objMI
     * @param object $objContentParent
     * @param object $objContent
     *
     * @return integer|null
     */
    public function selectContentId( $objContents, $objMI, $objContentParent, $objContent ) {

        global $objPage;

        if( !$objContents ) {
            return null;
        }

        $tracking = System::getContainer()->get('marketing_suite.tracking.session');
        $views = System::getContainer()->get('marketing_suite.tracking.click_and_views');

        $aVisitedPages = $tracking->getVisitedPages();

        foreach( $objContents as $key => $value ) {

            $pages = StringUtil::deserialize($value->cms_mi_pages);
            if( !is_array($pages) ){
                $pages = [];
            }

            if( count($aVisitedPages) ) {
                // remove current page id if this is also the last id
                if( $aVisitedPages[0] == $objPage->id ) {
                    array_shift($aVisitedPages);
                }
            }

            $same = array_intersect($pages, $aVisitedPages);

            if( $value->cms_mi_pages_criteria == 'one' ) {

                if( count($same) >= 1 ) {
                    $views->increaseViewOnMarketingElement($value);
                    return $value->id;
                }

            } else if( $value->cms_mi_pages_criteria == 'all' ) {

                if( count($same) == count($pages) ) {
                    $views->increaseViewOnMarketingElement($value);
                    return $value->id;
                }

            } else if( $value->cms_mi_pages_criteria == 'first' ) {

                if( count($pages) > 0 ) {

                    $first = array_reverse($aVisitedPages)[0];

                    if( in_array($first, $pages) ) {
                        $views->increaseViewOnMarketingElement($value);
                        return $value->id;
                    }
                }

            } else if( $value->cms_mi_pages_criteria == 'last' ) {

                if( count($pages) > 0 ) {

                    $last = $aVisitedPages[0];

                    if( in_array($last, $pages) ) {
                        $views->increaseViewOnMarketingElement($value);
                        return $value->id;
                    }
                }

            } else if( $value->cms_mi_pages_criteria == 'always' ) {

                $views->increaseViewOnMarketingElement($value);
                return $value->id;
            }
        }

        return null;
    }


    /**
     * Return all pages criterias as array
     *
     * @return array
     */
    public function getPagesCriteria() {

        self::loadLanguageFile('tl_cms_marketing_item');

        $types = [
            'all' => $GLOBALS['TL_LANG']['tl_content']['cms_mi_pages_criterias']['all']
        ,   'one' => $GLOBALS['TL_LANG']['tl_content']['cms_mi_pages_criterias']['one']
        ,   'first' => $GLOBALS['TL_LANG']['tl_content']['cms_mi_pages_criterias']['first']
        ,   'last' => $GLOBALS['TL_LANG']['tl_content']['cms_mi_pages_criterias']['last']
        ,   'always' => $GLOBALS['TL_LANG']['tl_content']['cms_mi_pages_criterias']['always']
        ];

        return $types;
    }
}
