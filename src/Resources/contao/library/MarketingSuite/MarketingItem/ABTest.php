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


namespace numero2\MarketingSuite\MarketingItem;

use Contao\Config;
use Contao\ContentModel;
use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\Date;
use Contao\Image;
use Contao\Input;
use Contao\Message;
use Contao\StringUtil;
use Contao\System;
use numero2\MarketingSuite\Backend\Wizard;
use numero2\MarketingSuite\ContentGroupModel;
use numero2\MarketingSuite\MarketingItemModel;
use numero2\MarketingSuite\Tracking\ClickAndViews;
use numero2\MarketingSuite\Tracking\Session;


class ABTest extends MarketingItem {


    /**
     * Alter child record of tl_content
     *
     * @param array $arrRow
     * @param string $buffer
     * @param object $objMarketingItem
     * @param object $objContentGroup
     *
     * @return string
     */
    public function alterContentChildRecord( $arrRow, $buffer, $objMarketingItem, $objContentGroup ) {

        if( $arrRow['cms_mi_isMainTracker'] == '1' ) {

            $buffer = explode('</div>', $buffer );

            // highlight marketing item
            $buffer[0] .= '<span class="cms_info"> - Dieses Element wird von dem A/B Test gemessen</span>';

            $buffer = implode('</div>', $buffer );
        }

        return $buffer;
    }


    /**
     * Alter header of tl_content
     *
     * @param array $args
     * @param \DataContainer $dc
     * @param object $objMarketingItem
     * @param object $objContentGroup
     *
     * @return array
     */
    public function alterContentHeader( $args, $dc, $objMarketingItem, $objContentGroup ) {

        $keys = array_keys($args);
        unset($args[$keys[0]]);
        unset($args[$keys[1]]);

        if( $objContentGroup->name ) {
            array_insert($args, 0, [$GLOBALS['TL_LANG']['tl_cms_content_group']['name'][0] => $objContentGroup->name]);
        }

        // only display this button if we have one group
        $groups = ContentGroupModel::countByPid($objMarketingItem->id);
        if( $groups == 1 ) {

            $GLOBALS['TL_MOOTOOLS'][] = "<script>CMSBackend.override('.tl_header .tl_content_right .edit','');</script>";

            $beWizard = new Wizard();
            $aWizardConfig = [
                'step' => 3
            ,   'type' => 'a_b_test'
            ,   'table' => 'tl_cms_content_group'
            ];

            $GLOBALS['TL_MOOTOOLS'][] =
            "<script>
            CMSBackend.prepend('.tl_listing_container.parent_view', '".addslashes($beWizard->generateTopForListing($aWizardConfig))."');
            </script>";

            $GLOBALS['TL_MOOTOOLS'][] =
            "<script>
            CMSBackend.append('.tl_listing_container.parent_view',
                '<div class=\"tl_header cms_helper_bottom_legend\">'
                    +'<div class=\"tl_panel\"><a class=\"tl_submit\" href=\"".self::switchToEdit($objContentGroup)."\">".$GLOBALS['TL_LANG']['MSC']['continue']."</a></div>'
                +'</div>'
            );
            </script>";

            $GLOBALS['TL_MOOTOOLS'][] = "<script>Backend.makeParentViewSortable('ul_".$objContentGroup->id."');</script>";
        }

        if( $objMarketingItem->init_step ) {
            $args[array_reverse($keys)[0]] = $GLOBALS['TL_LANG']['tl_cms_marketing_item']['child_header_label']['a_b_test_info']['default'];
        } else {
            unset($args[array_reverse($keys)[0]]);
        }

        $GLOBALS['TL_DCA']['tl_content']['list']['operations']['delete']['button_callback'] = ['\numero2\MarketingSuite\MarketingItem\ABTest', 'deleteElement'];

        return $args;
    }


    /**
     * Will prevent deleting if you are on the 'cms_mi_isMainTracker' element
     *
     * @param array  $row
     * @param string $href
     * @param string $label
     * @param string $title
     * @param string $icon
     * @param string $attributes
     *
     * @return string
     */
    public function deleteElement( $row, $href, $label, $title, $icon, $attributes ) {

        $disabled = $row['cms_mi_isMainTracker'] == '1';

        return $disabled ? Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)) . ' ' : '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ';
    }


    /**
     * Alter dca configuration of tl_content
     *
     * @param \DataContainer $dc
     * @param object $objMarketingItem
     * @param object $objContent
     * @param object $objContentGroup
     */
    public function alterContentDCA( $dc, $objMarketingItem, $objContent, $objContentGroup ) {

        if( Input::get('act') == 'edit' || Input::get('act') == 'editAll' ) {

            $GLOBALS['TL_DCA']['tl_content']['config']['onsubmit_callback'][] = ['\numero2\MarketingSuite\MarketingItem\ABTest', 'submitContent'];

            if( $objContent->cms_mi_isMainTracker === '1' ) {

                $GLOBALS['TL_DCA']['tl_content']['fields']['pid']['eval']['readonly'] = 'readonly';
                $GLOBALS['TL_DCA']['tl_content']['fields']['type']['eval']['readonly'] = 'readonly';

                $GLOBALS['TL_DCA']['tl_content']['fields']['cms_mi_label']['eval']['readonly'] = 'readonly';
                $GLOBALS['TL_DCA']['tl_content']['palettes'][$objContent->type] = str_replace(',cms_mi_label', '', $GLOBALS['TL_DCA']['tl_content']['palettes'][$objContent->type]);

                $groups = ContentGroupModel::countByPid($objMarketingItem->id);

                if( $groups === 1 ) {

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
    }


    /**
     * Handles what happens after a user submits the form
     *
     * @param \DataContainer $dc
     * @param object $objMI
     */
    public function submitMarketingItem( $dc, $objMarketingItem ) {

        $groups = ContentGroupModel::findBy(['pid=?'],[$objMarketingItem->id]);

        // create default content group and redirect to edit
        if( !$groups ){

            $group = new ContentGroupModel();
            $group->tstamp = time();
            $group->pid = $objMarketingItem->id;
            $group->name = '';
            $group->type = 'a_b_test';
            $group->save();

            $content = new ContentModel();
            $content->tstamp = time();
            $content->pid = $group->id;
            $content->cms_mi_isMainTracker = '1';
            $content->ptable = 'tl_cms_content_group';
            $content->type = $objMarketingItem->content_type;
            $content->save();

            $objMarketingItem->init_step = self::switchToEdit($content);
            $objMarketingItem->save();

            $this->redirect(self::switchToEdit($content));
        }

        // changed content_type
        foreach( $groups as $group) {

            $contents = ContentModel::findBy(['pid=? AND ptable=? AND cms_mi_isMainTracker=?'],[$group->id, 'tl_cms_content_group', '1']);

            if( $contents ){

                foreach( $contents as $content ) {

                    // Do nothing if type not changed
                    if( $objMarketingItem->content_type === $content->type ) {
                        break 2;
                    }

                    $content->type = $objMarketingItem->content_type;
                    $content->invisible = '1';

                    $content->save();
                }
            }

            // FIXME won't display this message
            Message::addInfo($GLOBALS['TL_LANG']['tl_content']['cms_msg']['unpublished_content_element']);
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

        if( $objContentParent ) {

            $id = null;

            if( $objMI->auto_winner_after && $objMI->stop_auto_winner < time() ) {

                // find winner
                $aClicks = $objContentParent->fetchEach('clicks');
                arsort($aClicks);

                // if no real winner keep it running
                if( array_values($aClicks)[0] != array_values($aClicks)[1] ) {

                    // select winner and make always use this
                    $winnerId = array_keys($aClicks)[0];

                    foreach( $objContentParent as $value) {

                        if( $value->id == $winnerId ) {

                            $value->always_use_this = '1';
                            $value->save();
                        } else {

                            $value->always_use_this = '';
                            $value->save();
                        }
                    }

                    $objMI->auto_winner_after = '';
                    $objMI->save();
                }
            }

            // check if alway_use_this was selected
            $aAlways = $objContentParent->fetchEach('always_use_this');
            foreach( $aAlways as $key => $value ) {

                if( $value == 1 ) {
                    $id = $key;
                    break;
                }
            }

            if( !$id ) {

                $tracking = new Session();
                $views = new ClickAndViews();

                $aContentViews = $objContentParent->fetchEach('views');

                // if already selected in session tracking
                $id = $tracking->getABTestSelected($objMI->id);
                if( !array_key_exists($id, $aContentViews) ) {

                    // choose content group with less views
                    asort($aContentViews);
                    $id = array_keys($aContentViews)[0];

                    // save selected
                    $tracking->storeABTestSelected($objMI->id, $id);

                    // increase view counter
                    foreach( $objContentParent as $key => $value) {
                        if( $value->id == $id ) {
                            $views->increaseViewOnMarketingElement($value);
                            break;
                        }
                    }
                }
            }

            $oContents = NULL;
            $oContents = ContentModel::findPublishedByPidAndTable($id, 'tl_cms_content_group');
            if( !$oContents ){
                return null;
            }

            $aContentIds = $oContents->fetchEach('id');

            return $aContentIds;
        }

        return null;
    }


    /**
     * Handles what happens after a user submits the child edit form
     *
     * @param \DataContainer $dc
     */
    public function submitContent( $dc ) {

        if( Input::post('SUBMIT_TYPE') == 'auto' ) {
            return;
        }

        $objContent = ContentModel::findById($dc->activeRecord->id);
        $objContent->refresh();

        if( $objContent->cms_mi_isMainTracker === '1' ) {

            $group = ContentGroupModel::findOneById($dc->activeRecord->pid);
            $objMarketingItem = MarketingItemModel::findById($group->pid);

            if( !empty($objMarketingItem->init_step) ) {

                $refererId = System::getContainer()->get('request_stack')->getCurrentRequest()->get('_contao_referer_id');

                $objMarketingItem->init_step = 'contao?do=cms_marketing&amp;table=tl_content&amp;id='.$dc->activeRecord->pid.'&amp;rt='.REQUEST_TOKEN.'&ref='.$refererId;
                $objMarketingItem->save();
                $this->redirect('contao?do=cms_marketing&amp;table=tl_content&amp;id='.$dc->activeRecord->pid.'&amp;rt='.REQUEST_TOKEN.'&ref='.$refererId);
            }
        }
    }


    /**
     * Handles what happens after a user submits the form
     *
     * @param \DataContainer $dc
     */
    public function submitContentGroup( $dc ) {

        if( Input::post('SUBMIT_TYPE') == 'auto' ) {
            return;
        }

        $groups = ContentGroupModel::countByPid($dc->activeRecord->pid);
        $objMI = MarketingItemModel::findById($dc->activeRecord->pid);

        // copy first case and redirect to second case
        if( $groups == 1 ) {

            $default = ContentGroupModel::findById($dc->activeRecord->id);
            $default->refresh();

            $objGroup = clone $default;
            $objGroup->name = '';
            $objGroup->save();

            $contents = ContentModel::findBy(['pid=? and ptable=?'],[$dc->activeRecord->id, 'tl_cms_content_group']);

            foreach( $contents as $value ) {

                $objContent = clone $value;
                $objContent->pid = $objGroup->id;
                $objContent->save();
            }

            $objMI->init_step = self::switchToEdit($objGroup);
            $objMI->save();

            $this->redirect(self::switchToEdit($objGroup));

        } else if( $groups == 2 ) {

            if( $objMI->init_step ) {

                $objMI->init_step = 'contao?do=cms_marketing&amp;table=tl_cms_content_group&amp;id='.$objMI->id;
                $objMI->save();

                $refererId = System::getContainer()->get('request_stack')->getCurrentRequest()->get('_contao_referer_id');

                $this->redirect('contao?do=cms_marketing&amp;table=tl_cms_content_group&amp;id='.$objMI->id.'&amp;rt='.REQUEST_TOKEN.'&ref='.$refererId);
            }
        }
    }


    /**
     * Change settings onload
     *
     * @param \DataContainer $dc
     */
    public function loadContentGroup( $dc ) {

        if( Input::get('act') == 'edit') {

            $group = ContentGroupModel::findOneById($dc->id);
            $groups = null;

            if( $group ) {
                $groups = ContentGroupModel::countByPid($group->pid);
            }

            if( $groups === 1 || ( $groups === 2 && $group->name === '') ) {

                $pm = PaletteManipulator::create()
                ->addLegend('cms_helper_top_legend', 'common_legend', 'before')
                ->addField(['helper_top'], 'cms_helper_top_legend', 'append')
                // ->addLegend('cms_helper_bottom_legend', '', 'after')
                // ->addField(['helper_bottom'], 'cms_helper_bottom_legend', 'append')
                ;
                $pm->applyToPalette('a_b_test', 'tl_cms_content_group');

                $GLOBALS['TL_DCA'][$dc->table]['edit']['buttons_callback'][] = ['\numero2\MarketingSuite\Backend\Wizard', 'overrideButtonsWithContinue'];
            }

            if( $groups === 1 ) {

                $GLOBALS['TL_DCA'][$dc->table]['fields']['helper_top']['step'] = '4';
                $GLOBALS['TL_DCA'][$dc->table]['fields']['helper_top']['type'] = 'a_b_test';

                $GLOBALS['TL_LANG']['tl_cms_content_group']['name'][0] = $GLOBALS['TL_LANG']['tl_cms_content_group']['name']['case_a'];

            } else if( $groups === 2 && $group->name === '' ) {

                $GLOBALS['TL_DCA'][$dc->table]['fields']['helper_top']['step'] = '5';
                $GLOBALS['TL_DCA'][$dc->table]['fields']['helper_top']['type'] = 'a_b_test';

                $GLOBALS['TL_LANG']['tl_cms_content_group']['name'][0] = $GLOBALS['TL_LANG']['tl_cms_content_group']['name']['case_b'];
            }
        } else {

            $objMI = MarketingItemModel::findOneById($dc->id);

            if( $objMI && $objMI->type == 'a_b_test' &&  !empty($objMI->init_step) ) {

                if( Input::get('finish') && Input::get('finish') == "true" ) {
                    $objMI->init_step = '';
                    $objMI->save();

                    $this->redirect($this->addToUrl('', true, ['finish']));
                }

                $beWizard = new Wizard();
                $aWizardConfig = [
                    'step' => 6
                ,   'type' => 'a_b_test'
                ,   'table' => 'tl_cms_content_group'
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
        }
    }


    /**
     * Return a string describing the current status of this a_b_test.
     *
     * @param array $arrMI
     *
     * @return string
     */
    public function getStatus( $arrMI ) {

        $strReturn = "";

        $objAlways = ContentGroupModel::findOneBy(["pid=? AND always_use_this=?"], [$arrMI['id'], 1]);

        if( $arrMI['auto_winner_after'] ) {

            $strAutoWinner = $GLOBALS['TL_LANG']['tl_cms_marketing_item']['list_label']['auto_winner_after_date'];
            $strAutoWinner = sprintf($strAutoWinner, Date::parse(Config::get('datimFormat'), $arrMI['stop_auto_winner']));

            $strReturn = $strAutoWinner;

        } else if( $objAlways ) {

            $strAlways = $GLOBALS['TL_LANG']['tl_cms_marketing_item']['list_label']['always_use_this_name'];
            $strAlways = sprintf($strAlways, $objAlways->name);

            $strReturn = $strAlways;
        }

        return $strReturn;
    }
}
