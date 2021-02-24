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

use Contao\BackendTemplate;
use Contao\ContentElement;
use Contao\ContentModel;
use Contao\Controller;
use Contao\Environment;
use Contao\Input;
use numero2\MarketingSuite\Backend\License as tokanugo;
use numero2\MarketingSuite\MarketingItem\MarketingItem;
use Patchwork\Utf8;


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

        if( TL_MODE == 'BE' ) {

            $objTemplate = new BackendTemplate('be_wildcard');

            $oMarketingItem = MarketingItemModel::findOneById($this->cms_mi_id);

            if( $oMarketingItem ) {

                $this->loadLanguageFile('tl_cms_marketing_item');

                $objTemplate->wildcard = '### '.Utf8::strtoupper($GLOBALS['TL_LANG']['CTE']['cms_marketing_item'][0] .' ('. $GLOBALS['TL_LANG']['tl_cms_marketing_item']['types'][$oMarketingItem->type] .')').' ###';
                $objTemplate->id = $oMarketingItem->id;
                $objTemplate->link = $oMarketingItem->name;

                if( $oMarketingItem->type=="a_b_test" ) {
                    $objTemplate->href = 'contao/main.php?do=cms_marketing&amp;table=tl_cms_content_group&amp;id=' . $oMarketingItem->id;
                } else {
                    $oContentGroup = ContentGroupModel::findOneByPid($oMarketingItem->id);

                    if( $oContentGroup ) {
                        $objTemplate->href = 'contao/main.php?do=cms_marketing&amp;table=tl_content&amp;id=' . $oContentGroup->id;
                    }
                }

            } else {

                $objTemplate->wildcard = '### '.Utf8::strtoupper($GLOBALS['TL_LANG']['CTE']['cms_marketing_item'][0]).' ###';
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
                        $objContentGroup->clicks +=1;
                        $objContentGroup->save();
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

        if( $selectedContent ){

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

                        if( $objContent->cta_link ) {
                            $objContent->cta_link = Controller::addToUrl('&follow='.$value, false);
                        }
                        if( $objContent->url ) {
                            $objContent->url = Controller::addToUrl('&follow='.$value, false);
                        }

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
