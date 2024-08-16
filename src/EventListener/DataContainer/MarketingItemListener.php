<?php

/**
 * Contao Marketing Suite Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\MarketingSuiteBundle\EventListener\DataContainer;

use Contao\Backend as CoreBackend;
use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\ServiceAnnotation\Callback;
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
use Exception;
use numero2\MarketingSuite\Backend;
use numero2\MarketingSuite\Backend\License as dowcosko;
use numero2\MarketingSuite\ContentGroupModel;
use numero2\MarketingSuite\ConversionItemModel;
use numero2\MarketingSuite\MarketingItem\MarketingItem as MarketingInstance;
use numero2\MarketingSuite\MarketingItemModel;
use Symfony\Bundle\SecurityBundle\Security;


class MarketingItemListener {


    /**
     * @var Contao\CoreBundle\Csrf\ContaoCsrfTokenManager
     */
    private $csrfTokenManager;

    /**
     * @var Symfony\Bundle\SecurityBundle\Security
     */
    private $security;


    public function __construct( ContaoCsrfTokenManager $csrfTokenManager, Security $security ) {

        $this->csrfTokenManager = $csrfTokenManager;
        $this->security = $security;
    }


    /**
     * Show marketing item label on all conversion items
     *
     * @param Contao\DataContainer $dc
     *
     * @Callback(table="tl_content", target="config.onload")
     */
    public function addLabel( $dc ) {

        $pm = PaletteManipulator::create()
            ->addField(['cms_mi_label'], 'type', 'after')
        ;

        if( dowcosko::hasFeature('conversion_element') && !empty($GLOBALS['TL_CTE']['conversion_elements']) ) {
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

        if( !dowcosko::hasFeature('conversion_element') || empty($GLOBALS['TL_CTE']['conversion_elements']) ) {
            unset($GLOBALS['TL_CTE']['marketing_suite']['cms_conversion_item']);
            unset($GLOBALS['TL_CTE']['conversion_elements']);
        }

        if( empty($GLOBALS['TL_CTE']['marketing_suite']) ) {
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

            $cls = System::importStatic($strClass);
            $strBuffer = $cls->$strMethod($arrRow);
        }

        // modify
        return self::alterContentChildRecord($arrRow, $strBuffer);
    }


    /**
     * If we are on 'do=cms_marketing' also add information to header about
     * dynamic content to backend view
     *
     * @param array $arrRow
     * @param Contao\DataContainer $dc
     *
     * @return string
     */
    public function addHeader( $arrRow, $dc ) {
        return self::alterContentHeader($arrRow, $dc);
    }


    /**
     * If we are on 'do=cms_marketing' we change palettes
     *
     * @param Contao\DataContainer $dc
     *
     * @Callback(table="tl_content", target="config.onload")
     */
    public function addPalette( $dc ) {

        if( Input::get('do') !== 'cms_marketing' ) {
            return;
        }

        // give the change to alter palettes
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
     *
     * @Callback(table="tl_cms_marketing_item", target="list.operations.edit.button")
     */
    public function editButton( $row, $href, $label, $title, $icon, $attributes ) {

        if( !empty($row['init_step']) ) {

            $title = $GLOBALS['TL_LANG']['tl_cms_marketing_item']['init']['0'];
            $attributes = '';

            $row['init_step'] .= '&rt='.$this->csrfTokenManager->getDefaultTokenValue();

            $initUrl = explode('?', $row['init_step']);
            $initUrl[0] = '';
            $initUrl = implode('?', $initUrl);

            return '<a href="'.$initUrl.'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ';
        }

        return '<a href="'.CoreBackend::addToUrl($href.'&amp;id='.$row['id']).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ';
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
     *
     * @Callback(table="tl_cms_marketing_item", target="list.operations.editheader.button")
     */
    public function editHeaderButton( $row, $href, $label, $title, $icon, $attributes ) {

        if( $row['type'] == 'a_b_test_page' ) {
            return '';
        }

        if( !empty($row['init_step']) || empty($row['id']) || !array_key_exists($row['type'], $this->getMarketingItemTypes()) ) {
            return Image::getHtml('children_.svg', $label).' ';
        }

        $idHref = $row['id'];

        if( $row['type'] !== 'a_b_test' ) {

            $href = 'table=tl_content';

            // for this elements we fake one ContentGroup to avoid id collision in tl_content edit url
            $objSingleGroup = ContentGroupModel::findOneByPid($row['id']);

            if( !empty($objSingleGroup->id) ){
                $idHref = $objSingleGroup->id;
            } else {
                $idHref = null;
            }
        }

        if( $idHref === null ) {
            return Image::getHtml('children_.svg', $label).' ';
        }

        return '<a href="'.CoreBackend::addToUrl($href.'&amp;id='.$idHref).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ';
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
     *
     * @Callback(table="tl_cms_marketing_item", target="list.operations.toggle.button")
     */
    public function toggleIcon( $row, $href, $label, $title, $icon, $attributes ) {

        // Check permissions AFTER checking the tid, so hacking attempts are logged
        if( !$this->security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE, 'tl_cms_marketing_item::active') ) {
            return '';
        }

        $href .= '&amp;id=' . $row['id'];

        if( !$row['active'] ) {
            $icon = 'invisible.svg';
        }

        return '<a href="' . CoreBackend::addToUrl($href) . '" title="' . StringUtil::specialchars($title) . '" data-action="contao--scroll-offset#store" onclick="return AjaxRequest.toggleField(this,true)">' . Image::getHtml($icon, $label, 'data-icon="' . Controller::addStaticUrlTo(Image::getPath('visible.svg')) . '" data-icon-disabled="' . Controller::addStaticUrlTo(Image::getPath('invisible.svg')) . '" data-state="' . ($row['active'] ? 1 : 0) . '"') . '</a> ';
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
     *
     * @Callback(table="tl_cms_marketing_item", target="list.operations.reset_counter.button")
     */
    public function resetCounter( $row, $href, $label, $title, $icon, $attributes ) {

        if( \strlen(Input::get('rid')) ) {

            $id = Input::get('rid');

            if( $id == $row['id'] ) {

                if( $row['type'] == 'a_b_test' ) {

                    Database::getInstance()->prepare( "UPDATE tl_cms_content_group SET reset=? WHERE pid=? AND type=?" )->execute(time(), $id, 'a_b_test');

                } else if( $row['type'] == 'a_b_test_page' ) {

                    $reset = time();
                    Database::getInstance()->prepare( "UPDATE tl_page SET cms_mi_reset=? WHERE id=? or id=?" )->execute($reset, $row['page_a'], $row['page_b']);

                    // also reset counters for all conversion items on this page
                    $oPagesUsed = NULL;
                    $oPagesUsed = PageModel::findBy(['id=? OR id=?'], [$row['page_b'], $row['page_a']], ['return'=>'Collection']);

                    if( $oPagesUsed ) {

                        $objCI = NULL;
                        $objCI = ConversionItemModel::findAllOn($oPagesUsed);

                        if( $objCI ) {

                            foreach( $objCI as $oContent ) {

                                $oContent->cms_ci_clicks = 0;
                                $oContent->cms_ci_views = 0;
                                $oContent->cms_ci_reset = $reset;

                                $oContent->save();
                            }
                        }
                    }
                }

                Controller::redirect(System::getReferer());
            }
        }

        $href .= '&amp;rid='.$row['id'];

        if( !in_array($row['type'], ['a_b_test', 'a_b_test_page']) ) {
            return '';
        }

        return '<a href="'.CoreBackend::addToUrl($href).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label, 'data-state="' . ($row['active'] ? 1 : 0) . '"').'</a> ';
    }


    /**
     * Generates the labels for the table view
     *
     * @param array $row
     * @param string $label
     * @param Contao\DataContainer $dc
     * @param array $args
     *
     * @return array
     *
     * @Callback(table="tl_cms_marketing_item", target="list.label.label")
     */
    public function getLabel( $row, $label, DataContainer $dc, $args ) {

        if( strpos($row['type'], 'a_b_test') === 0 ) {

            $args[2] = MarketingInstance::getChildInstance($row['type'])->getStatus($row);
        }

        $args[1] = $GLOBALS['TL_LANG']['tl_cms_marketing_item']['types'][$row['type']]??'';

        $count = 0;
        $aElements = [];
        $oContent = ContentModel::findBy(['type=? AND cms_mi_id=?'], ['cms_marketing_item', $row['id']], ['return'=>'Collection']);

        if( $oContent && $oContent->count() ) {

            $count += $oContent->count();
            $aElements[$GLOBALS['TL_LANG']['MOD']['tl_content']] = $oContent;
        }

        $oModule = ModuleModel::findBy(['tl_module.type=? AND tl_module.cms_mi_id=?'], ['cms_marketing_item', $row['id']], ['return'=>'Collection']);

        if( $oModule && $oModule->count() ) {

            $count += $oModule->count();
            $aElements[$GLOBALS['TL_LANG']['MOD']['tl_module']] = $oModule;
        }

        $args[3] = self::generateUsedOverlay($row, "Elemente (%s)");

        return $args;
    }


    /**
     * Generates a overlay to show where the element is used
     *
     * @param array $aRow
     * @param string $label
     *
     * @return string
     */
    public static function generateUsedOverlay( $aRow, $label ) {

        $strView = '';

        $count = 0;
        $aElements = [];
        $oContent = ContentModel::findBy(['type=? AND cms_mi_id=?'], ['cms_marketing_item', $aRow['id']], ['return'=>'Collection']);

        if( $oContent && $oContent->count() ) {

            $count += $oContent->count();
            $aElements[$GLOBALS['TL_LANG']['MOD']['tl_content']] = $oContent;
        }

        $oModule = ModuleModel::findBy(['tl_module.type=? AND tl_module.cms_mi_id=?'], ['cms_marketing_item', $aRow['id']], ['return'=>'Collection']);

        if( $oModule && $oModule->count() ) {

            $count += $oModule->count();
            $aElements[$GLOBALS['TL_LANG']['MOD']['tl_module']] = $oModule;
        }

        if( count($aElements) ) {

            $aOverlay = [
                'label' => (strpos($label, '%')===false)?$label:sprintf($label, (string)$count)
            ,   'content' => $aElements
            ,   'position' => 'top_right'
            ];
            $strView = Backend::parseWithTemplate('backend/elements/overlay_tree', $aOverlay);

        } else {

            if( !empty($aRow['page_a']) ) {
                $page = PageModel::findOneById($aRow['page_a']);
                if( $page ) {
                    $count +=1;
                    $aElements[$GLOBALS['TL_LANG']['tl_cms_marketing_item']['page_a'][0]] = new Collection([$page], 'tl_page');
                }
            }

            if( !empty($aRow['page_b']) ) {
                $page = PageModel::findOneById($aRow['page_b']);
                if( $page ) {
                    $count +=1;
                    $aElements[$GLOBALS['TL_LANG']['tl_cms_marketing_item']['page_b'][0]] = new Collection([$page], 'tl_page');
                }
            }

            if( count($aElements) ) {

                $aOverlay = [
                    'label' => (strpos($label, '%')===false)?$label:sprintf($label, (string)$count)
                ,   'content' => $aElements
                ,   'position' => 'top_right'
                ];
                $strView = Backend::parseWithTemplate('backend/elements/overlay_tree', $aOverlay );
            }
        }

        return $strView;
    }


    /**
     * Return all content elements as array
     *
     * @param Contao\DataContainer $dc
     *
     * @Callback(table="tl_cms_marketing_item", target="fields.content_type.options")
     */
    public function getContentElements( $dc ) {

        $groups = [];

        if( dowcosko::hasFeature('conversion_element') && !empty($GLOBALS['TL_CTE']['conversion_elements']) ) {

            foreach( $GLOBALS['TL_CTE']['conversion_elements'] as $key => $value ) {

                if( !dowcosko::hasFeature('ce_'.$key) ) {
                    unset($GLOBALS['TL_CTE']['conversion_elements'][$key]);
                }
            }
        }

        if( !dowcosko::hasFeature('marketing_element') ) {
            unset($GLOBALS['TL_CTE']['marketing_suite']['cms_marketing_item']);
        }

        if( !dowcosko::hasFeature('conversion_element') || empty($GLOBALS['TL_CTE']['conversion_elements']) ) {
            unset($GLOBALS['TL_CTE']['marketing_suite']['cms_conversion_item']);
            unset($GLOBALS['TL_CTE']['conversion_elements']);
        }

        if( empty($GLOBALS['TL_CTE']['marketing_suite']) ) {
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
     * @param Contao\DataContainer $dc
     *
     * @Callback(table="tl_cms_marketing_item", target="config.onload")
     */
    public function loadMarketingItem( $dc ) {

        $objMI = null;
        if( $dc->id ?? null ) {
            $objMI = MarketingItemModel::findById($dc->id);
        }

        if( $objMI ) {

            $groups = 0;
            $groups = ContentGroupModel::countByPid($objMI->id);

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
                $objMI->init_step = CoreBackend::addToUrl('');

                $objMI->save();
            }

            $oMarketingItem = null;
            $oMarketingItem = MarketingInstance::getChildInstance($objMI->type);

            if( $oMarketingItem && method_exists($oMarketingItem, 'loadMarketingItem') ) {
                $oMarketingItem->loadMarketingItem($dc, $objMI);
            }
        }
    }


    /**
     * Checks that selected page are uniquely selected for a_b_test_page selection
     *
     * @param string $value
     * @param Contao\DataContainer $dc
     *
     * @return string
     *
     * @Callback(table="tl_cms_marketing_item", target="fields.page_a.save")
     * @Callback(table="tl_cms_marketing_item", target="fields.page_b.save")
     */
    public function checkUniquePageForABTestPage( $value, DataContainer $dc ) {

        $objResult = Database::getInstance()->prepare( "SELECT id FROM $dc->table WHERE ($dc->field=? AND id!=?) OR ".($dc->field=='page_a'?'page_b':'page_a')."=?" )->execute($value, $dc->id, $value);

        if( $objResult->numRows ) {

            throw new Exception($GLOBALS['TL_LANG']['ERR']['unique_page_for_a_b_test_page']);
        }

        return $value;
    }


    /**
     * Checks that selected page has at least one conversion item.
     *
     * @param string $value
     * @param Contao\DataContainer $dc
     *
     * @return string
     *
     * @Callback(table="tl_cms_marketing_item", target="fields.page_a.save")
     * @Callback(table="tl_cms_marketing_item", target="fields.page_b.save")
     */
    public function checkForConversionOnPage( $value, DataContainer $dc ) {

        $objPage = PageModel::findOneById($value);

        $objCI = ConversionItemModel::findAllOn($objPage);

        if( !$objCI ) {

            throw new Exception($GLOBALS['TL_LANG']['ERR']['no_conversion_item_for_a_b_test_page']);
        }

        return $value;
    }


    /**
     * Checks that selected pages have the same root page.
     *
     * @param string $value
     * @param Contao\DataContainer $dc
     *
     * @return string
     *
     * @Callback(table="tl_cms_marketing_item", target="fields.page_a.save")
     * @Callback(table="tl_cms_marketing_item", target="fields.page_b.save")
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

            throw new Exception($GLOBALS['TL_LANG']['ERR']['not_same_root_for_a_b_test_page']);
        }

        return $value;
    }


    /**
     * Checks that select the selected page has not the alias 'index' or '/'.
     *
     * @param string $value
     * @param Contao\DataContainer $dc
     *
     * @return string
     *
     * @Callback(table="tl_cms_marketing_item", target="fields.page_a.save")
     */
    public function checkForNonIndexPage( $value, DataContainer $dc ) {

        $objPage = PageModel::findOneById($value);

        if( !$objPage ) {
            return $value;
        }

        if( in_array($objPage->alias, ['index', '/']) ) {

            throw new Exception($GLOBALS['TL_LANG']['ERR']['non_index_page_for_a_b_test_page']);
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

        System::loadLanguageFile('tl_content');

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
     * @param Contao\DataContainer $dc
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

            System::loadLanguageFile('tl_cms_marketing_item');

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
     * Alter DCA configuration of tl_content
     *
     * @param Contao\DataContainer $dc
     */
    public static function alterContentDCA( DataContainer $dc ) {

        $objContent = null;
        $objMI = null;
        $objContentParent = null;

        if( Input::get('act') == 'edit' ) {

            if( $dc->id ?? null ) {
                $objContent = ContentModel::findById($dc->id);
            }

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

            $oContentGroup = null;
            if( $dc->id ?? null ) {
                $oContentGroup = ContentGroupModel::findById($dc->id);
            }

            if( $oContentGroup ) {
                $objMI = MarketingItemModel::findById($oContentGroup->pid);
            }
        }

        if( !empty($objMI) ) {

            $instance = null;
            $instance = MarketingInstance::getChildInstance($objMI->type);

            if( $instance ) {
                $args = $instance->alterContentDCA($dc, $objMI, $objContent, $objContentParent);
            }
        }
    }


    /**
     * Handles what happens after a user submits the form
     *
     * @param Contao\DataCotainer $dc
     *
     * @Callback(table="tl_cms_marketing_item", target="config.onsubmit")
     */
    public function submitMarketingItem( DataContainer $dc ) {

        if( Input::post('SUBMIT_TYPE') == 'auto' ) {
            return;
        }

        $objMI = null;
        if( $dc->id ?? null ) {
            $objMI = MarketingItemModel::findById($dc->id);
        }

        if( $objMI ) {

            $objMI->refresh();

            $instance = null;
            $instance = MarketingInstance::getChildInstance($objMI->type);

            if( $instance ) {
                $instance->submitMarketingItem($dc, $objMI);
            }
        }
    }


    /**
     * Generate a wizard for the marketing item
     *
     * @param Contao\DataContainer $dc
     *
     * @return string
     */
    public function marketingItemWizard( DataContainer $dc ) {

        if( $dc->activeRecord->cms_mi_id < 1 ) {
            return '';
        }

        $oMI = null;
        $oMI = MarketingItemModel::findOneById($dc->activeRecord->cms_mi_id);

        if( !$oMI || !array_key_exists($oMI->type, $this->getMarketingItemTypes()) ) {
            return '';
        }

        $requestToken = $this->csrfTokenManager->getDefaultTokenValue();

        return ' <a href="'.System::getContainer()->get('router')->generate('contao_backend').'?do=cms_marketing&amp;table=tl_cms_marketing_item&amp;act=edit&amp;id=' . $dc->activeRecord->cms_mi_id . '&amp;popup=1&amp;nb=1&amp;rt=' . $requestToken . '" title="' . sprintf(StringUtil::specialchars($GLOBALS['TL_LANG']['tl_content']['editalias'][1]), $dc->activeRecord->cms_mi_id) . '" onclick="Backend.openModalIframe({\'title\':\'' . StringUtil::specialchars(str_replace("'", "\\'", sprintf($GLOBALS['TL_LANG']['tl_content']['editalias'][1], $dc->activeRecord->cms_mi_id))) . '\',\'url\':this.href});return false">' . Image::getHtml('edit.svg', $GLOBALS['TL_LANG']['tl_content']['editalias'][0]) . '</a>';
    }


    /**
     * Returns a list of options for marketing items
     *
     * @param Contao\DataContainer $dc
     *
     * @return array
     *
     * @Callback(table="tl_content", target="fields.cms_mi_id.options")
     * @Callback(table="tl_module", target="fields.cms_mi_id.options")
     */
    public function getAvailableOptions( DataContainer $dc ) {

        $objItems = null;
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
     *
     * @Callback(table="tl_cms_marketing_item", target="fields.type.options")
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

            if( empty($GLOBALS['TL_LANG']['tl_cms_marketing_item']['types'][$k]) ) {
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
     * @param Contao\DataContainer $dc
     *
     * @return string
     *
     * @Callback(table="tl_cms_marketing_item", target="fields.page_a.load")
     * @Callback(table="tl_cms_marketing_item", target="fields.page_b.load")
     */
    public function addToggleAlwaysUseThis( $value, DataContainer $dc ) {

        $MI = null;
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

            // check that only one can be active
            if( $strAlways ) {

                if( $strAlways != $strToggle ) {
                    $active = '';
                    throw new AccessDeniedException('You cannot set multiple pages to always_use_this.');
                }
            }

            // Update the database
            if( in_array($strToggle, ['page_a', 'page_b']) ) {

                Database::getInstance()->prepare("UPDATE tl_cms_marketing_item SET tstamp=?, always_$strToggle=? WHERE id=?")
                   ->execute(time(), $active? '' : '1', Input::get('id'));

            }

            Controller::redirect(CoreBackend::addToUrl('', true, ['tid', 'state']));
        }

        $href = 'tid='.$dc->field.'&amp;state='.$active;

        if( !$active ) {
            $path = 'bundles/marketingsuite/img/backend/icons/icon_always_use_this_.svg';
            $title = sprintf($GLOBALS['TL_LANG']['tl_cms_marketing_item']['toggle_always_use_this_tooltip'][0], $GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['label'][0] );
            $label = $GLOBALS['TL_LANG']['tl_cms_marketing_item']['toggle_always_use_this'][0];
        } else {
            $path = 'bundles/marketingsuite/img/backend/icons/icon_always_use_this.svg';
            $title = sprintf($GLOBALS['TL_LANG']['tl_cms_marketing_item']['toggle_always_use_this_tooltip'][1], $GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['label'][0] );
            $label = $GLOBALS['TL_LANG']['tl_cms_marketing_item']['toggle_always_use_this'][1];
        }

        if( !$strAlways || $active ) {

            $html= Image::getHtml($path, $label, 'data-state="' . $active . '"').$label;

            $GLOBALS['TL_MOOTOOLS'][] = '<script>
                (function(){
                    var anchor = document.createElement("a");
                    anchor.className = "tl_submit";
                    anchor.href = "'.preg_replace('/&amp;/', '&', CoreBackend::addToUrl($href)).'";
                    anchor.title = "'.StringUtil::specialchars($title).'";
                    anchor.innerHTML = \''.$html.'\';
                    document.querySelector("fieldset .'.$dc->field.' .selector_container p").appendChild(anchor);
                })();
            </script>';
        }

        return $value;
    }


    /**
     * Returns if the DCA should be closed or not
     *
     * @return boolean
     */
    public static function isClosed() {

        return !dowcosko::hasFeature('marketing_element');
    }
}
