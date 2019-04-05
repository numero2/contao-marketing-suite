<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2019 Leo Feyer
 *
 * @package   Contao Marketing Suite
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright 2019 numero2 - Agentur für digitales Marketing
 */


namespace numero2\MarketingSuite\DCAHelper;

use Contao\Backend as CoreBackend;
use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\Database;
use Contao\DataContainer;
use Contao\Image;
use Contao\Input;
use Contao\Model;
use Contao\Model\Collection;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use numero2\MarketingSuite\Backend;
use numero2\MarketingSuite\Backend\License as dowcosko;
use numero2\MarketingSuite\ContentGroupModel;
use numero2\MarketingSuite\ConversionItemModel;
use numero2\MarketingSuite\MarketingItem\MarketingItem as MarketingInstance;
use numero2\MarketingSuite\MarketingItemModel;


class MarketingItem extends CoreBackend {


    /**
     * Show marketing item label on all conversion items
     *
     * @param \DataContainer $dc
     */
    public function addLabel( $dc ) {

        $pm = PaletteManipulator::create()
            ->addField(['cms_mi_label'], 'type', 'after')
        ;

        if( dowcosko::hasFeature('conversion_element') && count($GLOBALS['TL_CTE']['conversion_elements']) ) {
            foreach( $GLOBALS['TL_CTE']['conversion_elements'] as $key => $value ) {

                if( !dowcosko::hasFeature('ce_'.$key) ) {
                    unset($GLOBALS['TL_CTE']['conversion_elements'][$key]);
                    continue;
                }

                $pm->applyToPalette($key, 'tl_content');
            }
        }

        if( !dowcosko::hasFeature('marketing_element') ) {
            unset($GLOBALS['TL_CTE']['marketing_suite']['cms_marketing_item']);
        }

        if( !dowcosko::hasFeature('conversion_element') || !count($GLOBALS['TL_CTE']['conversion_elements']) ) {
            unset($GLOBALS['TL_CTE']['marketing_suite']['cms_conversion_item']);
            unset($GLOBALS['TL_CTE']['conversion_elements']);
        }

        if( !count($GLOBALS['TL_CTE']['marketing_suite']) ) {
            unset($GLOBALS['TL_CTE']['marketing_suite']);
        }

        if( !dowcosko::hasFeature('text_cms') ) {
            unset($GLOBALS['TL_CTE']['texts']['text_cms']);
        }
    }


    /**
     * If we are on 'do=cms_marketing' also add information to child record
     * about dynamic content to backend view
     *
     * @param array $arrRow
     */
    public function addType( $arrRow ) {

        $childRecords = $GLOBALS['TL_DCA']['tl_content']['list']['sorting']['child_record_callback'];

        $strBuffer = "";
        // execute old child record
        if( count($GLOBALS['TL_DCA']['tl_content']['list']['sorting']['child_record_callback']) > 2 ) {

            $strClass = $GLOBALS['TL_DCA']['tl_content']['list']['sorting']['child_record_callback'][2];
            $strMethod = $GLOBALS['TL_DCA']['tl_content']['list']['sorting']['child_record_callback'][3];

            $this->import($strClass);
            $strBuffer = $this->$strClass->$strMethod($arrRow);
        }

        // modify
        return self::alterContentChildRecord($arrRow, $strBuffer);
    }


    /**
     * If we are on 'do=cms_marketing' also add information to header about
     * dynamic content to backend view
     *
     * @param array $arrRow
     * @param \DataContainer $dc
     *
     * @return string
     */
    public function addHeader( $arrRow, $dc ) {
        return self::alterContentHeader($arrRow, $dc);
    }


    /**
     * If we are on 'do=cms_marketing' we change palettes
     *
     * @param \DataContainer $dc
     */
    public function addPalette( $dc ) {
        self::alterContentDCA($dc);
    }


    /**
     * Change the edit button if the init was not finished
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
    public function editButton( $row, $href, $label, $title, $icon, $attributes ) {

        if( !empty($row['init_step']) ) {

            $title = $GLOBALS['TL_LANG']['tl_cms_marketing_item']['init']['0'];
            $attributes = '';

            $row['init_step'] .= '&rt='.REQUEST_TOKEN;

            $initUrl = explode('?', $row['init_step']);
            $initUrl[0] = TL_SCRIPT;
            $initUrl = implode('?', $initUrl);

            return '<a href="'.$initUrl.'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ';
        }

        if( $row['type'] !== 'a_b_test' ) {

            $href = 'table=tl_content';
            // for this elements we fake one ContentGroup to avoid id collision in tl_content edit url
            $objSingleGroup = ContentGroupModel::findOneByPid($row['id']);
            unset($row['id']);

            if( !empty($objSingleGroup->id) ){
                $row['id'] = $objSingleGroup->id;
            }
        }

        if( $row['type'] == 'a_b_test_page' ) {
            return '';
        }

        if( empty($row['id']) ) {
            return Image::getHtml('edit_.svg', $label);
        }

        return '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ';
    }


    /**
     * change the back button in the header section
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
    public function editHeaderButton( $row, $href, $label, $title, $icon, $attributes ) {

        if( !empty($row['init_step']) ) {
            return Image::getHtml('header_.svg', $label).' ';
        }

        if( empty($row['id']) ) {
            return Image::getHtml('header_.svg', $label).' ';
        }

        return '<a href="'.$this->addToUrl($href.'&amp;id='.$row['id']).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ';
    }


    /**
     * Return the "toggle visibility" button
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
    public function toggleIcon( $row, $href, $label, $title, $icon, $attributes ) {

        if( \strlen(Input::get('tid')) ) {

            $id = Input::get('tid');
            $active = (Input::get('state') == 1)?'1':'';
            Database::getInstance()->prepare( "UPDATE tl_cms_marketing_item SET active=? WHERE id=?" )->execute($active, $id);

            $this->redirect($this->getReferer());
        }

        $href .= '&amp;tid='.$row['id'].'&amp;state='.$row['active'];

        if( !$row['active'] ) {
            $icon = 'invisible.svg';
        }

        return '<a href="'.$this->addToUrl($href).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label, 'data-state="' . ($row['active'] ? 1 : 0) . '"').'</a> ';
    }


    /**
     * Return the "reset_counter" button
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
    public function resetCounter( $row, $href, $label, $title, $icon, $attributes ) {

        if( \strlen(Input::get('rid')) ) {

            $id = Input::get('rid');

            if( $id == $row['id'] ) {

                if( $row['type'] == 'a_b_test' ) {

                    Database::getInstance()->prepare( "UPDATE tl_cms_content_group SET views=0, clicks=0, reset=? WHERE pid=? AND type=?" )->execute(time(), $id, 'a_b_test');
                } else if( $row['type'] == 'a_b_test_page' ) {

                    Database::getInstance()->prepare( "UPDATE tl_page SET cms_mi_views=0, cms_mi_reset=? WHERE id=? or id=?" )->execute(time(), $row['page_a'], $row['page_b']);
                }
                $this->redirect($this->getReferer());
            }

        }

        $href .= '&amp;rid='.$row['id'];

        if( !in_array($row['type'], ['a_b_test', 'a_b_test_page']) ) {
            return '';
        }

        return '<a href="'.$this->addToUrl($href).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label, 'data-state="' . ($row['active'] ? 1 : 0) . '"').'</a> ';
    }


    /**
     * Generates the labels for the table view
     *
     * @param array $row
     * @param string $label
     * @param \DataContainer $dc
     * @param array $args
     *
     * @return array
     */
    public function getLabel( $row, $label, DataContainer $dc, $args ) {

        if( strpos($row['type'], 'a_b_test') === 0 ) {

            $args[2] = MarketingInstance::getChildInstance($row['type'])->getStatus($row);
        }

        $args[1] = $GLOBALS['TL_LANG']['tl_cms_marketing_item']['types'][$row['type']];

        $count = 0;
        $aElements = [];
        $oContent = ContentModel::findBy(['type=? AND cms_mi_id=?'], ['cms_marketing_item', $row['id']]);

        if( count($oContent) ) {

            $count += count($oContent);
            $aElements[$GLOBALS['TL_LANG']['MOD']['tl_content']] = $oContent;
        }

        $oModule = ModuleModel::findBy(['tl_module.type=? AND tl_module.cms_mi_id=?'], ['cms_marketing_item', $row['id']]);

        if( count($oModule) ) {

            $count += count($oModule);
            $aElements[$GLOBALS['TL_LANG']['MOD']['tl_module']] = $oModule;
        }

        $args[3] = '';

        if( count($aElements) ) {

            $aOverlay = [
                'label' => 'Elemente (' . $count . ')'
            ,   'content' => $aElements
            ];
            $args[3] = Backend::parseWithTemplate('backend/elements/overlay_tree', $aOverlay );

        } else {

            if( !empty($row['page_a']) ) {
                $count +=1;
                $aElements[$GLOBALS['TL_LANG']['tl_cms_marketing_item']['page_a'][0]] = new Collection([PageModel::findOneById($row['page_a'])], 'tl_page');
            }

            if( !empty($row['page_b']) ) {
                $count +=1;
                $aElements[$GLOBALS['TL_LANG']['tl_cms_marketing_item']['page_b'][0]] = new Collection([PageModel::findOneById($row['page_b'])], 'tl_page');
            }
            if( count($aElements) ||true) {

                $aOverlay = [
                    'label' => 'Elemente (' . $count . ')'
                ,   'content' => $aElements
                ];
                $args[3] = Backend::parseWithTemplate('backend/elements/overlay_tree', $aOverlay );
            }
        }

        return $args;
    }


    /**
     * Return all content elements as array
     *
     * @return array
     */
    public function getContentElements( $dc ) {

        $groups = [];

        if( dowcosko::hasFeature('conversion_element') && count($GLOBALS['TL_CTE']['conversion_elements']) ) {

            foreach( $GLOBALS['TL_CTE']['conversion_elements'] as $key => $value ) {

                if( !dowcosko::hasFeature('ce_'.$key) ) {
                    unset($GLOBALS['TL_CTE']['conversion_elements'][$key]);
                }
            }
        }

        if( !dowcosko::hasFeature('marketing_element') ) {
            unset($GLOBALS['TL_CTE']['marketing_suite']['cms_marketing_item']);
        }

        if( !dowcosko::hasFeature('conversion_element') || !count($GLOBALS['TL_CTE']['conversion_elements']) ) {
            unset($GLOBALS['TL_CTE']['marketing_suite']['cms_conversion_item']);
            unset($GLOBALS['TL_CTE']['conversion_elements']);
        }

        if( !count($GLOBALS['TL_CTE']['marketing_suite']) ) {
            unset($GLOBALS['TL_CTE']['marketing_suite']);
        }

        foreach( $GLOBALS['TL_CTE'] as $k => $v ) {

            if( $dc->activeRecord->type == 'a_b_test' && !in_array($k, ['conversion_elements']) ) {
                continue;
            }

            foreach( array_keys($v) as $kk ) {
                $groups[$k][] = $kk;
            }
        }

        return $groups;
    }


    /**
     * Change palette during onload
     *
     * @param \DataContainer $dc
     * @param  object $objMI
     */
    public function loadMarketingItem( $dc ) {

        $objMI = MarketingItemModel::findById($dc->id);

        $groups = ContentGroupModel::countByPid($objMI->id);

        if( $objMI ) {

            $oMarketingItem = MarketingInstance::getChildInstance($objMI->type);

            if( !($groups || ($objMI->page_a && $objMI->page_b && $objMI->init_step=='')) ) {

                $pm = PaletteManipulator::create()
                    ->addLegend('cms_helper_top_legend', 'common_legend', 'before')
                    ->addField(['helper_top'], 'cms_helper_top_legend', 'append');

                $GLOBALS['TL_DCA'][$dc->table]['fields']['helper_top']['type'] = $objMI->type;
                $GLOBALS['TL_DCA'][$dc->table]['fields']['helper_top']['step'] = 1;

                if( !empty($objMI->type) && $objMI->type !== 'default' ) {
                    $GLOBALS['TL_DCA'][$dc->table]['fields']['helper_top']['show_popup'] = 'backend/wizard/mi_'.$objMI->type;
                }

                if( $objMI->type && $objMI->type !== 'default' ) {

                    $pm ->addLegend('cms_helper_bottom_legend', '', 'after')
                        // ->addField(['helper_bottom'], 'cms_helper_bottom_legend', 'append')
                    ;
                }

                $pm->applyToPalette($objMI->type?:'default', 'tl_cms_marketing_item');

                $GLOBALS['TL_DCA'][$dc->table]['edit']['buttons_callback'][] = ['\numero2\MarketingSuite\Backend\Wizard', 'overrideButtonsWithContinue'];
                $objMI->init_step = $this->addToUrl('');

                $objMI->save();
            }

            if( method_exists($oMarketingItem, 'loadMarketingItem') ) {
                $oMarketingItem->loadMarketingItem($dc, $objMI);
            }
        }
    }


    /**
     * Checks that selected page are uniquely selected for a_b_test_page selection
     *
     * @param string $value
     * @param DataContainer $dc
     *
     * @return string
     */
    public function checkUniquePageForABTestPage( $value, DataContainer $dc ) {

        $objResult = Database::getInstance()->prepare( "SELECT id FROM $dc->table WHERE ($dc->field=? AND id!=?) OR ".($dc->field=='page_a'?'page_b':'page_a')."=?" )->execute($value, $dc->id, $value);

        if( $objResult->numRows ) {

            throw new \Exception($GLOBALS['TL_LANG']['ERR']['unique_page_for_a_b_test_page']);
        }

        return $value;
    }


    /**
     * Checks that selected page has at least one conversion item.
     *
     * @param string $value
     * @param DataContainer $dc
     *
     * @return string
     */
    public function checkForConversionOnPage( $value, DataContainer $dc ) {

        $objPage = PageModel::findOneById($value);

        $objCI = ConversionItemModel::findAllOn($objPage);

        if( !$objCI ) {

            throw new \Exception($GLOBALS['TL_LANG']['ERR']['no_conversion_item_for_a_b_test_page']);
        }

        return $value;
    }


    /**
     * Checks that selected pages have the same root page.
     *
     * @param string $value
     * @param DataContainer $dc
     *
     * @return string
     */
    public function checkForSameRootPage( $value, DataContainer $dc ) {

        $page_a = $_POST['page_a'];
        $page_b = $_POST['page_b'];

        $objPageA = PageModel::findWithDetails($page_a);
        $objPageB = PageModel::findWithDetails($page_b);

        if( !$objPageA || !$objPageB ) {
            return $value;
        }

        if( $objPageA->trail[0] != $objPageB->trail[0] ) {

            throw new \Exception($GLOBALS['TL_LANG']['ERR']['not_same_root_for_a_b_test_page']);
        }

        return $value;
    }


    /**
     * Checks that select the selected page has not the alias 'index' or '/'.
     *
     * @param string $value
     * @param DataContainer $dc
     *
     * @return string
     */
    public function checkForNonIndexPage( $value, DataContainer $dc ) {

        $objPage = PageModel::findOneById($value);

        if( !$objPage ) {
            return $value;
        }

        if( in_array($objPage->alias, ['index', '/']) ) {

            throw new \Exception($GLOBALS['TL_LANG']['ERR']['non_index_page_for_a_b_test_page']);
        }

        return $value;
    }


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
    public static function alterContentHeader( $args, DataContainer $dc ) {

        $group = null;
        $objMI = null;

        if( in_array(Input::get('mode'), ["copy", "cut"]) ) {

            $content = ContentModel::findOneById($dc->id);
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
    public static function alterContentDCA( DataContainer $dc ) {

        $objContent = null;
        $objMI = null;
        if( Input::get('act') == 'edit' ) {

            $objContent = ContentModel::findById($dc->id);

            if( $objContent ) {
                $parent_class = Model::getClassFromTable($objContent->ptable);
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
    public function submitMarketingItem( DataContainer $dc ) {

        if( Input::post('SUBMIT_TYPE') == 'auto' ) {
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
    public function marketingItemWizard( DataContainer $dc ) {

        return ($dc->activeRecord->cms_mi_id < 1) ? '' : ' <a href="contao/main.php?do=cms_marketing&amp;table=tl_cms_marketing_item&amp;act=edit&amp;id=' . $dc->activeRecord->cms_mi_id . '&amp;popup=1&amp;nb=1&amp;rt=' . REQUEST_TOKEN . '" title="' . sprintf(\StringUtil::specialchars($GLOBALS['TL_LANG']['tl_content']['editalias'][1]), $dc->activeRecord->cms_mi_id) . '" onclick="Backend.openModalIframe({\'title\':\'' . \StringUtil::specialchars(str_replace("'", "\\'", sprintf($GLOBALS['TL_LANG']['tl_content']['editalias'][1], $dc->activeRecord->cms_mi_id))) . '\',\'url\':this.href});return false">' . \Image::getHtml('edit.svg', $GLOBALS['TL_LANG']['tl_content']['editalias'][0]) . '</a>';
    }


    /**
     * Returns a list of options for marketing items
     *
     * @param \DataContainer $dc
     *
     * @return array
     */
    public function getAvailableOptions( DataContainer $dc ) {

        $objItems = NULL;
        $objItems = MarketingItemModel::findBy(["init_step=?"], ['']);

        if( $objItems ) {

            Controller::loadLanguageFile('tl_cms_marketing_item');
            $aOptions = [];

            while( $objItems->next() ) {

                if( $objItems->type === 'a_b_test_page' ) {
                    continue;
                }

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

        Controller::loadDataContainer('tl_cms_marketing_item');

        $types = [];

        foreach( $GLOBALS['TL_DCA']['tl_cms_marketing_item']['palettes'] as $k=>$v ) {

            if( $k == '__selector__' ) {
                continue;
            }

            if( !dowcosko::hasFeature('me_'.$k) && $k != 'default') {
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


    /**
     * Add the toggle always use this button to the current field
     *
     * @param string $value
     * @param \DataContainer $dc
     *
     * @return string
     */
    public function addToggleAlwaysUseThis($value, DataContainer $dc) {

        $MI = MarketingItemModel::findOneById(Input::get('id'));
        $strAlways = "";

        if( $MI && $MI->always_page_a ) {

            $strAlways = "page_a";
        } else if( $MI && $MI->always_page_b ) {

            $strAlways = "page_b";
        }

        $active = $strAlways==$dc->field?'1':'';

        if( strlen(Input::get('tid'))  ) {

            // Set the ID and action
            $strToggle = Input::get('tid');
            Input::setGet('act', 'toggle');
            $active = Input::get('state') == 1 ? '1' : '';

            $time = time();

            // check that only one can be active
            if( $strAlways ) {

                if( $strAlways != $strToggle ) {
                    $active = '';
                    throw new AccessDeniedException('You cannot set multiple pages to always_use_this.');
                }
            }

            // Update the database
            if( in_array($strToggle, ['page_a', 'page_b']) ) {

                $this->Database->prepare("UPDATE tl_cms_marketing_item SET tstamp=?, always_$strToggle=? WHERE id=?")
                   ->execute($time, $active? '' : '1', Input::get('id'));

            }

            $this->redirect($this->addToUrl('', true, ['tid','state']));
        }

        $href = 'tid='.$dc->field.'&amp;state='.$active;

        $icon = 'bundles/marketingsuite/img/backend/icons/icon_always_use_this.svg';
        $icond = 'bundles/marketingsuite/img/backend/icons/icon_always_use_this_.svg';

        if( !$active ) {
            $path = $icond;
            $title = sprintf($GLOBALS['TL_LANG']['tl_cms_marketing_item']['toggle_always_use_this_tooltip'][0], $GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['label'][0] );
            $label = $GLOBALS['TL_LANG']['tl_cms_marketing_item']['toggle_always_use_this'][0];
        } else {
            $path = $icon;
            $title = sprintf($GLOBALS['TL_LANG']['tl_cms_marketing_item']['toggle_always_use_this_tooltip'][1], $GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['label'][0] );
            $label = $GLOBALS['TL_LANG']['tl_cms_marketing_item']['toggle_always_use_this'][1];
        }

        if( !$strAlways || $active ) {

            $html= '<a class="tl_submit" href="'.$this->addToUrl($href).'" title="'.StringUtil::specialchars($title).'">'.Image::getHtml($path, $label, 'data-state="' . $active . '"').$label.'</a>';

            $GLOBALS['TL_MOOTOOLS'][] = '<script>CMSBackend.append("fieldset .'.$dc->field.' .selector_container p", \''.$html.'\');</script>';
        }

        return $value;
    }
}
