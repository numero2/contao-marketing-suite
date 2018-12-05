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


class ContentConversionItem extends \ContentElement {


    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'ce_cms_conversion_item';


    /**
     * Generate the content element
     */
    public function generate() {

        global $objPage;

        if( TL_MODE == 'BE' ) {

            $objTemplate = new \BackendTemplate('be_wildcard');

            $oContent = \ContentModel::findOneById($this->cms_ci_id);

            if( $oContent ) {

                $objTemplate->wildcard = '### '. \Patchwork\Utf8::strtoupper($GLOBALS['TL_LANG']['CTE']['cms_conversion_item'][0] .' ('. $GLOBALS['TL_LANG']['CTE'][$oContent->type][0] .')').' ###';
                $objTemplate->id = $oContent->id;
                $objTemplate->link = $oContent->cms_mi_label;
                $objTemplate->href = 'contao/main.php?do=cms_conversion&amp;table=tl_content&amp;act=edit&amp;id=' . $oContent->id;

            } else {

                $objTemplate->wildcard = '### '. \Patchwork\Utf8::strtoupper($GLOBALS['TL_LANG']['CTE']['cms_conversion_item'][0]) .' ###';
            }

            return $objTemplate->parse();
        }

        $oContent = \ContentModel::findOneById($this->cms_ci_id);

        if( !$oContent || !\numero2\MarketingSuite\Backend\License::hasFeature('conversion_element', $objPage->trail[0]) || !\numero2\MarketingSuite\Backend\License::hasFeature('ce_'.$oContent->type, $objPage->trail[0]) ) {
            return '';
        }

        return parent::generate();
    }


    /**
     * Generate the content element
     */
    protected function compile() {

        $strEle = \Controller::getContentElement($this->cms_ci_id, $this->strColumn);
        if( $strEle === '' ) {
            return ;
        }

        $this->Template->content = $strEle;
    }
}
