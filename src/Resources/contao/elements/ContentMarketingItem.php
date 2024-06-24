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

use Contao\BackendTemplate;
use Contao\ContentElement;
use Contao\ContentModel;
use Contao\Controller;
use Contao\Input;
use Contao\System;
use numero2\MarketingSuite\Backend\License as tokanugo;
use numero2\MarketingSuite\MarketingItem\MarketingItem;


class ContentMarketingItem extends ContentElement {


    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'ce_cms_marketing_item';


    /**
     * Generate the content element
     */
    public function generate() {

        global $objPage;

        $request = System::getContainer()->get('request_stack')->getCurrentRequest();
        if( $request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request) ) {

            $objTemplate = new BackendTemplate('be_wildcard');

            $oMarketingItem = MarketingItemModel::findOneById($this->cms_mi_id);

            if( $oMarketingItem ) {

                $this->loadLanguageFile('tl_cms_marketing_item');

                $objTemplate->wildcard = '### '.$GLOBALS['TL_LANG']['CTE']['cms_marketing_item'][0] .' ('. $GLOBALS['TL_LANG']['tl_cms_marketing_item']['types'][$oMarketingItem->type] .')'.' ###';
                $objTemplate->id = $oMarketingItem->id;
                $objTemplate->link = $oMarketingItem->name;

                if( $oMarketingItem->type=="a_b_test" ) {
                    $objTemplate->href = System::getContainer()->get('router')->generate('contao_backend').'?do=cms_marketing&amp;table=tl_cms_content_group&amp;id=' . $oMarketingItem->id;
                } else {
                    $oContentGroup = ContentGroupModel::findOneByPid($oMarketingItem->id);

                    if( $oContentGroup ) {
                        $objTemplate->href = System::getContainer()->get('router')->generate('contao_backend').'?do=cms_marketing&amp;table=tl_content&amp;id=' . $oContentGroup->id;
                    }
                }

            } else {

                $objTemplate->wildcard = '### '.$GLOBALS['TL_LANG']['CTE']['cms_marketing_item'][0].' ###';
            }

            return $objTemplate->parse();
        }

        $objMI = NULL;
        $objMI = MarketingItemModel::findById($this->cms_mi_id);

        if( !$objMI->active ) {
            return '';
        }

        if( !tokanugo::hasFeature('marketing_element', $objPage->trail[0]) || !tokanugo::hasFeature('me_'.$objMI->type, $objPage->trail[0]) ) {
            return '';
        }

        return parent::generate();
    }


    /**
     * Generate the content element
     */
    protected function compile() {

        $objMI = NULL;
        $objMI = MarketingItemModel::findById($this->cms_mi_id);

        if( Input::get('follow') ) {

            $objContent = NULL;
            $objContent = ContentModel::findById(Input::get('follow'));

            if( $objContent && $objContent->ptable === 'tl_cms_content_group' ) {

                $objContentGroup = NULL;
                $objContentGroup = ContentGroupModel::findById($objContent->pid);

                if( $objContentGroup && $objContentGroup->pid === $objMI->id ) {
                    if( !$objContentGroup->always_use_this ) {

                        $tracking = System::getContainer()->get('marketing_suite.tracking.click_and_views');
                        $tracking->increaseClickOnMarketingElement($objContentGroup);
                    }
                }
            }
        }

        $objCP = NULL;
        $objCP = ContentGroupModel::findByPid($objMI->id);

        $oContents = NULL;
        if( $objCP && $objCP->count() === 1 ) {
            $oContents = ContentModel::findPublishedByPidAndTable($objCP->id, 'tl_cms_content_group');
        }
        $selectedContent = NULL;

        $instance = NULL;
        $instance = MarketingItem::getChildInstance($objMI->type);

        if( $instance ) {
            $selectedContent = $instance->selectContentId($oContents, $objMI, $objCP, $this);
        }

        if( $selectedContent ) {

            if( !is_array($selectedContent) ) {

                $selectedContent = [$selectedContent];
            }

            $this->Template->content = '';
            foreach( $selectedContent as $value ) {

                $objContent = ContentModel::findById($value);

                if( $objMI->type === 'a_b_test' && $objContent->cms_mi_isMainTracker == '1' ) {

                    if( Controller::getContentElement($value, $this->strColumn) !== '' ) {

                        $strClass = self::findClass($objContent->type);

                        $objContent->typePrefix = 'ce_';

                        /** @var ContentElement $objElement */
                        $objElement = new $strClass($objContent, $this->strColumn);

                        $this->Template->content .= $objElement->generate();
                    }
                    continue;
                }

                $this->Template->content .= Controller::getContentElement($value, $this->strColumn);
            }

        } else {

            return ;
        }
    }
}
