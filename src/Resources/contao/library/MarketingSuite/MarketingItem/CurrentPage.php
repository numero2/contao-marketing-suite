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
use Contao\Message;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use numero2\MarketingSuite\Backend\Wizard;
use numero2\MarketingSuite\ContentGroupModel;
use numero2\MarketingSuite\MarketingItemModel;


class CurrentPage extends MarketingItem {


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

        $pages = PageModel::findMultipleByIds(StringUtil::deserialize($arrRow['cms_mi_pages']));

        // remove content type
        $buffer[0] = substr($buffer[0], 0, strpos($buffer[0], '>')+1);

        // display page
        $buffer[0] .= ' ';

        if( $pages ) {
            foreach( $pages as $value ) {
                $buffer[0] .= Image::getHtml(Controller::getPageStatusIcon($value)) . ' ' . $value->title . ' (' . $value->alias . Config::get('urlSuffix') . ')';
            }
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
     *
     * @return array
     */
    public function alterContentHeader( $args, $dc, $objMarketingItem, $objContentParent ) {

        // add content type
        $args[$GLOBALS['TL_LANG']['tl_cms_marketing_item']['content_type'][0]] = $GLOBALS['TL_LANG']['CTE'][$objMarketingItem->content_type][0];

        $requestToken = System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();
        $refererId = System::getContainer()->get('request_stack')->getCurrentRequest()->get('_contao_referer_id');
        $routePrefix = System::getContainer()->getParameter('contao.backend.route_prefix');

        $GLOBALS['TL_MOOTOOLS'][] =
        "<script>
        CMSBackend.override('.tl_content_right .edit', '<a href=\"".$routePrefix."?do=cms_marketing&amp;id=$objMarketingItem->id&amp;act=edit&amp;rt=".$requestToken."&amp;ref=$refererId\" class=\"edit\" title=\"".$GLOBALS['TL_LANG']['tl_cms_marketing_item']['edit'][0]."\"><img src=\"system/themes/flexible/icons/header.svg\" width=\"16\" height=\"16\" alt=\"".$GLOBALS['TL_LANG']['tl_cms_marketing_item']['edit'][0]."\"></a>');
        </script>";

        if( $objMarketingItem && $objMarketingItem->type == 'current_page' && !empty($objMarketingItem->init_step) ) {

            if( Input::get('finish') && Input::get('finish') == "true" ) {
                $objMarketingItem->init_step = '';
                $objMarketingItem->save();

                $this->redirect($this->addToUrl('', true, ['finish']));
            }

            $beWizard = new Wizard();
            $aWizardConfig = [
                'step' => 3
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

        $GLOBALS['TL_DCA']['tl_content']['config']['closed'] = true;
        $GLOBALS['TL_DCA']['tl_content']['config']['notDeletable'] = true;
        $GLOBALS['TL_DCA']['tl_content']['config']['notSortable'] = true;
        $GLOBALS['TL_DCA']['tl_content']['config']['notCreatable'] = true;

        unset($GLOBALS['TL_DCA']['tl_content']['list']['operations']['copy']);
        unset($GLOBALS['TL_DCA']['tl_content']['list']['operations']['cut']);
        unset($GLOBALS['TL_DCA']['tl_content']['list']['operations']['delete']);

        if( Input::get('act') == 'edit' || Input::get('act') == 'editAll' ) {

            $GLOBALS['TL_DCA']['tl_content']['config']['onsubmit_callback'][] = ['\numero2\MarketingSuite\MarketingItem\CurrentPage', 'submitContent'];
            $GLOBALS['TL_DCA']['tl_content']['fields']['pid']['eval']['readonly'] = 'readonly';
            $GLOBALS['TL_DCA']['tl_content']['fields']['type']['eval']['readonly'] = 'readonly';
        }

        if( Input::get('act') == 'edit' && $objContent ) {

            $contents = ContentModel::countBy(['pid=? AND ptable=?'],[$objContent->pid, $objContent->ptable]);

            // only change palette if it's not the default element
            if( !empty($objContent->cms_mi_pages) ) {

                $pm = PaletteManipulator::create()
                    ->addLegend('marketing_suite_legend', 'type_legend', 'before')
                    ->addField(['cms_mi_pages'], 'marketing_suite_legend', 'append')
                ;

                $pm->applyToPalette($objContent->type, 'tl_content');
                $GLOBALS['TL_DCA']['tl_content']['fields']['cms_mi_pages']['eval']['tl_class'] .= ' disabled';
            } else {

                $GLOBALS['TL_DCA']['tl_content']['fields']['cms_helper_top']['step'] = '2';
                $GLOBALS['TL_DCA']['tl_content']['fields']['cms_helper_top']['type'] = $objMarketingItem->type;

                $pm = PaletteManipulator::create()
                    ->addLegend('cms_helper_top_legend', 'type_legend', 'before')
                    ->addField(['cms_helper_top'], 'cms_helper_top_legend', 'append')
                    // ->addLegend('cms_helper_bottom_legend', '', 'after')
                    // ->addField(['cms_helper_bottom'], 'cms_helper_bottom_legend', 'append')
                ;

                $pm->applyToPalette($objContent->type, 'tl_content');

                $GLOBALS['TL_DCA'][$dc->table]['edit']['buttons_callback'][] = ['\numero2\MarketingSuite\Backend\Wizard', 'overrideButtonsWithContinue'];
            }
        }
    }


    /**
     * Handles what happens after a user submits the form
     *
     * @param Contao\DataContainer $dc
     * @param object $objMarketingItem
     */
    public function submitMarketingItem( $dc, $objMarketingItem ) {

        $aPages = StringUtil::deserialize($objMarketingItem->pages);

        $group = ContentGroupModel::findOneByPid($objMarketingItem->id);

        if( !$group ) {
            $group = new ContentGroupModel();
            $group->tstamp = time();
            $group->pid = $objMarketingItem->id;
            $group->name = '';
            $group->active = '1';
            $group->save();
        }

        $contents = ContentModel::findBy(['pid=? AND ptable=?'],[$group->id, 'tl_cms_content_group']);

        // create default content element and redirect to edit
        if( !$contents ) {

            // only go on if save was used
            if( array_key_exists('save', $_POST) ) {

                $content = new ContentModel();
                $content->tstamp = time();
                $content->pid = $group->id;
                $content->ptable = 'tl_cms_content_group';
                $content->type = $objMarketingItem->content_type;
                $content->save();

                $objMarketingItem->init_step = self::switchToEdit($content);
                $objMarketingItem->save();

                $this->redirect(self::switchToEdit($content));
            }

        // add or delete content elements when pages change
        } else {

            // only do something if default was saved
            if( !empty(StringUtil::deserialize($contents->cms_mi_pages)[0]) ){

                // changed content_type
                if( $objMarketingItem->content_type != $contents->type ){

                    foreach( $contents as $value) {
                        $value->type = $objMarketingItem->content_type;
                        $value->invisible = '1';

                        $value->save();
                    }

                    Message::addInfo($GLOBALS['TL_LANG']['tl_content']['cms_msg']['unpublished_content_element']);
                }

                // changed pages
                $availableCEPages = [];
                foreach( $contents as $key => $value) {
                    $availableCEPages[] = StringUtil::deserialize($value->cms_mi_pages)[0];
                }

                $remove = array_diff($availableCEPages, $aPages);
                $new = array_diff($aPages, $availableCEPages);
                $same = array_intersect($aPages, $availableCEPages);

                foreach( $contents as $oContent ) {
                    $page = StringUtil::deserialize($oContent->cms_mi_pages)[0];

                    if( in_array($page, $remove) ){

                        $oContent->delete();

                        Message::addInfo($GLOBALS['TL_LANG']['tl_content']['cms_msg']['removed_content_element']);
                        continue;
                    }

                    if( in_array($page, $same) ){

                        $oContent->sorting = self::getSorting($page, $aPages);
                        $oContent->save();
                        continue;
                    }
                }

                foreach( $new as $value ) {

                    $oContent = clone $contents->current();
                    $oContent->cms_mi_pages = serialize([$value]);
                    $oContent->sorting = self::getSorting($value, $aPages);
                    $oContent->invisible = '1';
                    $oContent->save();

                    Message::addInfo($GLOBALS['TL_LANG']['tl_content']['cms_msg']['added_content_element']);
                }
            }
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

        $group = ContentGroupModel::findOneById($dc->activeRecord->pid);

        $objMarketingItem = MarketingItemModel::findById($group->pid);
        $aPages = StringUtil::deserialize($objMarketingItem->pages);

        $default = ContentModel::findById($dc->activeRecord->id);
        $default->refresh();

        // only do something if it's not the default element
        if( empty($default->cms_mi_pages) ) {

            foreach( $aPages as $key => $value) {

                if( $key == 0 ) {
                    $objContent = $default;
                } else {
                    $objContent = clone $default;
                }

                $objContent->cms_mi_pages = serialize([$value]);
                $objContent->sorting = self::getSorting($value,$aPages);
                $objContent->save();
            }
        }

        // if in setup
        if( !empty($objMarketingItem->init_step) ) {

            $routePrefix = System::getContainer()->getParameter('contao.backend.route_prefix');
            $requestToken = System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();
            $refererId = System::getContainer()->get('request_stack')->getCurrentRequest()->get('_contao_referer_id');

            $objMarketingItem->init_step = $routePrefix.'?do=cms_marketing&amp;table=tl_content&amp;id='.$dc->activeRecord->pid;
            $objMarketingItem->save();

            $this->redirect($routePrefix.'?do=cms_marketing&amp;table=tl_content&amp;id='.$dc->activeRecord->pid.'&amp;rt='.$requestToken.'&ref='.$refererId);
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
     * @return integer
     */
    public function selectContentId( $objContents, $objMI, $objContentParent, $objContent ) {

        global $objPage;

        $views = System::getContainer()->get('marketing_suite.tracking.click_and_views');

        if( $objContents ) {
            foreach( $objContents as $key => $value) {

                $page = StringUtil::deserialize($value->cms_mi_pages)[0];

                if( $page == $objPage->id ) {
                    $views->increaseViewOnMarketingElement($value);
                    return $value->id;
                }
            }
        }

        return null;
    }
}
