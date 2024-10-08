<?php

/**
 * Contao Marketing Suite Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\MarketingSuite\Widget;

use Contao\BackendUser;
use Contao\CalendarEventsModel;
use Contao\Controller;
use Contao\CoreBundle\Exception\RouteParametersException;
use Contao\DataContainer;
use Contao\Events;
use Contao\News;
use Contao\NewsModel;
use Contao\PageModel;
use Contao\System;
use Exception;
use numero2\MarketingSuite\Backend;
use numero2\MarketingSuite\Backend\License as jebto;


class SnippetPreview extends Controller {


    /**
     * Maximum length for snippet preview title and description
     * @var integer
     */
    const TITLE_MIN_LENGTH = 30;
    const TITLE_MAX_LENGTH = 60;
    const DESCRIPTION_MIN_LENGTH = 79;
    const DESCRIPTION_MAX_LENGTH = 158;


    /**
     * Displays a dynamic preview of the given meta data
     *
     * @param Contao\DataContainer $dc
     * @throws Exception If DCA is not compatible with preview
     *
     * @return string
     */
    public function generate( DataContainer $dc ) {

        if( !jebto::hasFeature('page_snippet_preview') ) {
            return '';
        }

        $this->import(BackendUser::class, 'User');

        $aData = [
            'id' => (int) $dc->activeRecord->id
        ,   'urlSuffix' => $dc->activeRecord->urlSuffix??''
        ,   'fieldSuffix' => substr($dc->inputName, \strlen($dc->field))
        ,   'titleMinLength' => (int) self::TITLE_MIN_LENGTH
        ,   'titleMaxLength' => (int) self::TITLE_MAX_LENGTH
        ,   'descriptionMinLength' => (int) self::DESCRIPTION_MIN_LENGTH
        ,   'descriptionMaxLength' => (int) self::DESCRIPTION_MAX_LENGTH
        ,   'lengthLabel' => $GLOBALS['TL_LANG']['MSC']['snippet_count']
        ,   'headline' => $GLOBALS['TL_LANG']['MSC']['snippet_preview'][0]
        ,   'tip' => $GLOBALS['TL_LANG']['MSC']['snippet_preview'][1]
        ,   'labelTooShort' => $GLOBALS['TL_LANG']['MSC']['snippet_length_too_short']
        ,   'labelTooLong' => $GLOBALS['TL_LANG']['MSC']['snippet_length_too_long']
        ,   'labelOptimal' => $GLOBALS['TL_LANG']['MSC']['snippet_length_optimal']
        ];

        if( method_exists($this, 'buildData_'.$dc->table) ) {

            // render nothing if requireItem is activated
            if( ($dc && $dc->activeRecord && ($dc->activeRecord->requireItem??null)) || $this->{'buildData_'.$dc->table}($aData,$dc) === false ) {
                return '';
            }

            if( strlen($aData['title']) > $aData['titleMaxLength'] ) {
                $aData['title'] = substr($aData['title'], 0, $aData['titleMaxLength']) . '...';
            }

            if( strlen($aData['description'] ?? '') > $aData['descriptionMaxLength'] ) {
                $aData['description'] = substr($aData['description'], 0, $aData['descriptionMaxLength']) . '...';
            }

            if( strlen($aData['url']) ) {

                $aData['url'] = str_replace('https://', '', $aData['url']);
                $aData['url'] = str_replace('http://', '', $aData['url']);
                $aData['url'] = str_replace('/', ' › ', $aData['url']);
                $aData['url'] = urldecode($aData['url']);
            }

            // add explanation for title tag settings
            if( $aData['titleTag'] && $this->User->cms_pro_mode_enabled != 1 ) {

                $routePrefix = System::getContainer()->getParameter('contao.backend.route_prefix');
                $requestToken = System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();
                $refererId = System::getContainer()->get('request_stack')->getCurrentRequest()->get('_contao_referer_id');

                $aData['titleTagExplanation'] = sprintf(
                    $GLOBALS['TL_LANG']['MSC']['snippet_titletag_explanation']
                ,   $routePrefix.'?do=themes&amp;table=tl_layout&amp;id=' .$aData['layoutId']. '&amp;act=edit&amp;rt=' .$requestToken. '&amp;ref=' .$refererId. '#ctrl_titleTag'
                );
            }

            // add explanation for noindex
            if( $dc && $dc->activeRecord && strpos($dc->activeRecord->robots, 'noindex') !== FALSE ) {
                $aData['noIndexExplanation'] = $GLOBALS['TL_LANG']['MSC']['snippet_noindex_explanation'];
            }

            return Backend::parseWithTemplate('backend/widgets/snippet_preview', $aData);

        } else {

            throw new Exception("Table ".$dc->table." not supported in snippet preview");
        }
    }


    /**
     * Parses the given title tag
     *
     * @param string $strTag
     *
     * @return string
     */
    private function parseTitleTag( $objRefPage ) {

        global $objPage;

        $oLayout = null;
        $oLayout = $objRefPage->getRelated('layout');

        $strTag = $oLayout?$oLayout->titleTag:null;
        $strTag = $strTag ?: '{{page::pageTitle}} - {{page::rootPageTitle}}';
        $strTag = str_replace('{{page::pageTitle}}', '##TITLE##', $strTag);

        // overwrite global $objPage temporarily for insert tags resolving
        $objOrigPage = $objPage;
        $objPage = $objRefPage;

        // parse insert tags...
        $oInsertTags = System::getContainer()->get('contao.insert_tag.parser');
        $strTag = $oInsertTags->replace($strTag);

        // ... and revert global $objpage
        $objPage = $objOrigPage;

        return ($strTag!='##TITLE##') ? $strTag : null;
    }


    /**
     * Generate data for table tl_page
     *
     * @param array $aData
     * @param Contao\DataContainer $dc
     *
     * @return array
     */
    private function buildData_tl_page( &$aData, DataContainer $dc ): bool {

        $oPage = null;
        $oPage = PageModel::findById($dc->activeRecord->id);

        if( $oPage ) {
            $oPage->loadDetails();
        }

        $sURL = "";
        try {
            $sURL = $oPage->getAbsoluteUrl();
            $sURL = urldecode($sURL);
        } catch( RouteParametersException $exception ) {
            return false;
        }

        list($baseUrl) = explode($oPage->alias ?: $oPage->id, $sURL);

        $oLayout = null;
        $oLayout = $oPage->getRelated('layout');

        $aData += [
            'title' => $dc->activeRecord->pageTitle ?: $dc->activeRecord->title
        ,   'url' => $sURL
        ,   'description' => $dc->activeRecord->description
        ,   'baseUrl' => $baseUrl
        ,   'titleField' => 'ctrl_pageTitle'.$aData['fieldSuffix']
        ,   'titleFieldFallback' => 'ctrl_title'.$aData['fieldSuffix']
        ,   'aliasField' => 'ctrl_alias'.$aData['fieldSuffix']
        ,   'descriptionField' => 'ctrl_description'.$aData['fieldSuffix']
        ,   'titleTag' => $this->parseTitleTag($oPage)
        ,   'layoutId' => $oLayout?$oPage->getRelated('layout')->id:null
        ];

        return true;
    }


    /**
     * Generate data for table tl_news
     *
     * @param array $aData
     * @param Contao\DataContainer $dc
     *
     * @return array
     */
    private function buildData_tl_news( &$aData, DataContainer $dc ) {

        $oNews = null;
        $oNews = NewsModel::findById($dc->activeRecord->id);

        $oPage = null;
        $oPage = $oNews->getRelated('pid')->getRelated('jumpTo');

        if( $oPage ) {
            $oPage->loadDetails();
        }

        $sURL = "";
        $sURL = News::generateNewsUrl($oNews, false, true);
        $sURL = urldecode($sURL);

        list($baseUrl) = explode($oNews->alias ?: $oNews->id, $sURL);

        $aData += [
            'title' => $oNews->pageTitle ?: $oNews->headline
        ,   'url' => $sURL
        ,   'description' => $oNews->description ?: strip_tags($oNews->teaser)
        ,   'baseUrl' => $baseUrl
        ,   'titleField' => 'ctrl_pageTitle'.$aData['fieldSuffix']
        ,   'titleFieldFallback' => 'ctrl_headline'.$aData['fieldSuffix']
        ,   'aliasField' => 'ctrl_alias'.$aData['fieldSuffix']
        ,   'descriptionField' => 'ctrl_description'.$aData['fieldSuffix']
        ,   'descriptionFieldFallback' => 'ctrl_teaser'.$aData['fieldSuffix']
        ];

        if( $oPage ) {

            $aData += [
                'titleTag' => $this->parseTitleTag($oPage)
            ,   'layoutId' => $oPage->getRelated('layout')->id
            ,   'urlSuffix' => $oPage->urlSuffix??''
            ];
        }
    }


    /**
     * Generate data for table tl_calendar_events
     *
     * @param array $aData
     * @param Contao\DataContainer $dc
     *
     * @return array
     */
    private function buildData_tl_calendar_events( &$aData, DataContainer $dc ) {

        $oEvent = null;
        $oEvent = CalendarEventsModel::findById($dc->activeRecord->id);

        $oPage = null;
        $oPage = $oEvent->getRelated('pid')->getRelated('jumpTo');

        if( $oPage ) {
            $oPage->loadDetails();
        }

        $sURL = "";
        $sURL = Events::generateEventUrl($oEvent, true);
        $sURL = urldecode($sURL);

        list($baseUrl) = explode($oEvent->alias ?: $oEvent->id, $sURL);

        $aData += [
            'title' => $oEvent->pageTitle ?: $oEvent->title
        ,   'url' => $sURL
        ,   'description' => $oEvent->description ?: strip_tags($oEvent->teaser)
        ,   'baseUrl' => $baseUrl
        ,   'titleField' => 'ctrl_pageTitle'.$aData['fieldSuffix']
        ,   'titleFieldFallback' => 'ctrl_title'.$aData['fieldSuffix']
        ,   'aliasField' => 'ctrl_alias'.$aData['fieldSuffix']
        ,   'descriptionField' => 'ctrl_description'.$aData['fieldSuffix']
        ,   'descriptionFieldFallback' => 'ctrl_teaser'.$aData['fieldSuffix']
        ];

        if( $oPage ) {

            $aData += [
                'titleTag' => $this->parseTitleTag($oPage)
            ,   'layoutId' => $oPage->getRelated('layout')->id
            ,   'urlSuffix' => $oPage->urlSuffix??''
            ];
        }
    }
}
