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
namespace numero2\MarketingSuite\BackendModule;

use Contao\CoreBundle\Exception\AccessDeniedException;
use numero2\MarketingSuite\Backend\License;
use numero2\MarketingSuite\Backend\Help;


class HealthCheck extends \BackendModule {


    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'backend/modules/health_check';


    /**
     * Fieldset states
     * @var array
     */
    private $fsStates;


    /**
    * Generate the module
    *
    * @return string
    */
    public function generate() {

        if( !License::hasFeature('health_check') ) {
            throw new AccessDeniedException('This feature is not included in your Marketing Suite package.');
        }

        return parent::generate();
    }


    /**
     * Compile the module
     */
    protected function compile() {

        $this->loadLanguageFile('tl_page');
        $this->loadLanguageFile('cms_be_health_check');
        License::buk();

        // get fieldset states
        $objSessionBag = NULL;
        $objSessionBag = \System::getContainer()->get('session')->getBag('contao_backend');

        $fs = NULL;
        $fs = $objSessionBag->get('fieldset_states');

        if( !empty($fs['cms_health_check']) ) {
            $this->fsStates = $fs['cms_health_check'];
        }

        // check the different health categories
        $aCategories = [
            License::hasFeature('health_check_h1_missing') ? $this->checkH1Missing() : null
        ,   License::hasFeature('health_check_meta_missing') ? $this->checkMetaMissing() : null
        ,   License::hasFeature('health_check_meta_too_short') ? $this->checkMetaTooShort() : null
        ,   License::hasFeature('health_check_meta_too_long') ? $this->checkMetaTooLong() : null
        ,   License::hasFeature('health_check_open_graph_missing') ? $this->checkOpenGraphMissing() : null
        ];

        $aCategories = array_filter($aCategories);

        // initialize help
        $objBEHelp = NULL;
        $objBEHelp = new Help();

        $this->Template->be_help = $objBEHelp->generate();
        $this->Template->categories = $aCategories;

        if( empty($aCategories) ) {
            $this->Template->nothingTodo = $GLOBALS['TL_LANG']['cms_be_health_check']['nothing_to_do'];
        }
    }


    /**
     * Returns the referer id used in links
     *
     * @return string
     */
    private function getRefererID() {
        return \System::getContainer()->get('request_stack')->getCurrentRequest()->get('_contao_referer_id');
    }


    /**
     * Checks for pages with missing h1 headlines
     *
     * @return object|void
     */
    private function checkH1Missing() {

        if( !License::hasFeature('health_check_h1_missing') ) {
            return null;
        }

        $oCategory = (object) [
            'type' => 'missing_h1'
        ,   'collapsed' => $this->fsStates['missing_h1_legend']?0:1
        ,   'legend' => $GLOBALS['TL_LANG']['cms_be_health_check']['missing_h1'][0]
        ,   'description' => $GLOBALS['TL_LANG']['cms_be_health_check']['missing_h1'][1]
        ,   'items' => []
        ];

        $aExcludePageIDs = [];

        // check for news archives to exclude these pages from the check
        if( class_exists('\Contao\News') ) {

            $oArchives = NULL;
            $oArchives = \NewsArchiveModel::findAll();

            if( $oArchives ) {

                while( $oArchives->next() ) {
                    $aExcludePageIDs[] = $oArchives->jumpTo;
                }
            }
        }

        // check for calendars to exclude these pages from the check
        if( class_exists('\Contao\Calendar') ) {

            $oCalendars = NULL;
            $oCalendars = \CalendarModel::findAll();

            if( $oCalendars ) {

                while( $oCalendars->next() ) {
                    $aExcludePageIDs[] = $oCalendars->jumpTo;
                }
            }
        }

        // find pages
        $oPages = NULL;
        $oPages = \PageModel::findAll([
            'column' => [
                "type=?"
            ,   empty($aExcludePageIDs)?:"id NOT IN(".implode(',',$aExcludePageIDs).")"
            ,   "cms_exclude_health_check=0"
            ]
        ,   'value' => ['regular']
        ]);

        if( $oPages ) {

            while( $oPages->next() ) {

                // check if we find any h1 by statically looking at the content
                if( !$this->checkForH1InContentElements($oPages->id) ) {

                    $objPage = NULL;
                    $objPage = \PageModel::findWithDetails( $oPages->id );

                    $aAttributes = [];

                    // if it's a published page add the absolute url so
                    // we can perform some analysis on the frontend side
                    if( $oPages->published ) {
                        $aAttributes['url'] = $objPage->getAbsoluteUrl();
                    }

                    $oCategory->items[] = (object) [
                        'icon'  => \Image::getPath( \Controller::getPageStatusIcon($oPages) )
                    ,   'type'  => 'page'
                    ,   'name'  => $oPages->title
                    ,   'href'  => 'contao?do=article&amp;pn='.$oPages->id.'&amp;rt='.REQUEST_TOKEN.'&amp;ref='.$this->getRefererID()
                    ,   'title' => sprintf($GLOBALS['TL_LANG']['tl_page']['edit'][1],$oPages->id)
                    ,   'attributes' => $aAttributes
                    ];
                }
            }
        }

        if( $oCategory->items ) {
            return $oCategory;
        }
    }


    /**
     * Checks if the given page does contain any H1 headlines
     * by analysing it's content elements
     *
     * @param int $pageID
     *
     * @return bool
     */
    private function checkForH1InContentElements( $pageID ) {

        $oArticles = NULL;
        $oArticles = \ArticleModel::findByPid( $pageID );

        if( $oArticles ) {

            $this->loadDataContainer('tl_content');
            $this->loadDataContainer('tl_module');

            while( $oArticles->next() ) {

                $oContentElements = NULL;
                $oContentElements = \ContentModel::findByPid( $oArticles->id );

                if( $oContentElements ) {

                    while( $oContentElements->next() ) {

                        $oElement = $oContentElements;
                        $sElementPalette = $GLOBALS['TL_DCA']['tl_content']['palettes'][ $oElement->type ];

                        // special handling for module elements
                        if( $oElement->type == 'module' ) {

                            $oModule = NULL;
                            $oModule = \ModuleModel::findById( $oElement->module );

                            $oElement = $oModule;
                            $sElementPalette = $GLOBALS['TL_DCA']['tl_module']['palettes'][ $oElement->type ];
                        }

                        // check headline element
                        if( $oElement->headline && preg_match("/(,|)headline(,|;)/", $sElementPalette) ) {

                            $headline = deserialize($oElement->headline);

                            if( $headline['unit'] == 'h1' && !empty($headline['value']) ) {
                                return true;
                            }
                        }

                        // check text element
                        if( $oElement->text && preg_match("/(,|)text(,|;)/", $sElementPalette) ) {

                            if( preg_match('|<h1>.*</h1>|', $oElement->text) ) {
                                return true;
                            }
                        }

                        // check html element
                        if( $oElement->html && preg_match("/(,|)html(,|;)/", $sElementPalette) ) {

                            if( preg_match('|<h1>.*</h1>|', $oElement->html) ) {
                                return true;
                            }
                        }
                    }
                }
            }

            return false;
        }

        return true;
    }


    /**
     * Checks for pages with missing meta data
     *
     * @return object|void
     */
    private function checkMetaMissing() {

        if( !License::hasFeature('health_check_meta_missing') ) {
            return null;
        }

        $oCategory = (object) [
            'type' => 'missing_meta'
        ,   'collapsed' => $this->fsStates['missing_meta_legend']?0:1
        ,   'legend' => $GLOBALS['TL_LANG']['cms_be_health_check']['missing_meta'][0]
        ,   'description' => $GLOBALS['TL_LANG']['cms_be_health_check']['missing_meta'][1]
        ,   'items' => []
        ];

        // find pages
        $oPages = NULL;
        $oPages = \PageModel::findAll([
            'column' => ["type=?", "(pageTitle='' OR description = '')"]
        ,   'value' => ['regular']
        ]);

        if( $oPages ) {

            while( $oPages->next() ) {

                $oCategory->items[] = (object) [
                    'icon'  => \Image::getPath( \Controller::getPageStatusIcon($oPages) )
                ,   'type'  => 'page'
                ,   'name'  => $oPages->title
                ,   'href'  => 'contao?do=page&amp;act=edit&amp;id='.$oPages->id.'&amp;rt='.REQUEST_TOKEN.'&amp;ref='.$this->getRefererID().'#pal_meta_legend'
                ,   'title' => sprintf($GLOBALS['TL_LANG']['tl_page']['edit'][1],$oPages->id)
                ];
            }
        }

        if( $oCategory->items ) {
            return $oCategory;
        }
    }


    /**
     * Checks for pages with too short meta data
     *
     * @return object|void
     */
    private function checkMetaTooShort() {

        if( !License::hasFeature('health_check_meta_too_short') ) {
            return null;
        }

        $oCategory = (object) [
            'type' => 'short_meta'
        ,   'collapsed' => $this->fsStates['short_meta_legend']?0:1
        ,   'legend' => $GLOBALS['TL_LANG']['cms_be_health_check']['short_meta'][0]
        ,   'description' => $GLOBALS['TL_LANG']['cms_be_health_check']['short_meta'][1]
        ,   'items' => []
        ];

        // find pages
        $oPages = NULL;
        $oPages = \PageModel::findAll([
            'column' => ["type=?", "(CHAR_LENGTH(pageTitle) < 30 OR CHAR_LENGTH(description) < 79)"]
        ,   'value' => ['regular']
        ]);

        if( $oPages ) {

            while( $oPages->next() ) {

                $oCategory->items[] = (object) [
                    'icon'  => \Image::getPath( \Controller::getPageStatusIcon($oPages) )
                ,   'type'  => 'page'
                ,   'name'  => $oPages->title
                ,   'href'  => 'contao?do=page&amp;act=edit&amp;id='.$oPages->id.'&amp;rt='.REQUEST_TOKEN.'&amp;ref='.$this->getRefererID().'#pal_meta_legend'
                ,   'title' => sprintf($GLOBALS['TL_LANG']['tl_page']['edit'][1],$oPages->id)
                ];
            }
        }

        if( $oCategory->items ) {
            return $oCategory;
        }
    }


    /**
     * Checks for pages with too long meta data
     *
     * @return object|void
     */
    private function checkMetaTooLong() {

        if( !License::hasFeature('health_check_meta_too_long') ) {
            return null;
        }

        $oCategory = (object) [
            'type' => 'long_meta'
        ,   'collapsed' => $this->fsStates['long_meta_legend']?0:1
        ,   'legend' => $GLOBALS['TL_LANG']['cms_be_health_check']['long_meta'][0]
        ,   'description' => $GLOBALS['TL_LANG']['cms_be_health_check']['long_meta'][1]
        ,   'items' => []
        ];

        // find pages
        $oPages = NULL;
        $oPages = \PageModel::findAll([
            'column' => ["type=?", "(CHAR_LENGTH(pageTitle) > 60 OR CHAR_LENGTH(description) > 158)"]
        ,   'value' => ['regular']
        ]);

        if( $oPages ) {

            while( $oPages->next() ) {

                $oCategory->items[] = (object) [
                    'icon'  => \Image::getPath( \Controller::getPageStatusIcon($oPages) )
                ,   'type'  => 'page'
                ,   'name'  => $oPages->title
                ,   'href'  => 'contao?do=page&amp;act=edit&amp;id='.$oPages->id.'&amp;rt='.REQUEST_TOKEN.'&amp;ref='.$this->getRefererID().'#pal_meta_legend'
                ,   'title' => sprintf($GLOBALS['TL_LANG']['tl_page']['edit'][1],$oPages->id)
                ];
            }
        }

        if( $oCategory->items ) {
            return $oCategory;
        }
    }


    /**
     * Checks for pages with missing opengraph data
     *
     * @return object|void
     */
    private function checkOpenGraphMissing() {

        if( !License::hasFeature('health_check_open_graph_missing') || !class_exists('numero2\OpenGraph3\OpenGraph3') ) {
            return null;
        }

        $oCategory = (object) [
            'type' => 'missing_opengraph'
        ,   'collapsed' => $this->fsStates['missing_opengraph_legend']?0:1
        ,   'legend' => $GLOBALS['TL_LANG']['cms_be_health_check']['missing_opengraph'][0]
        ,   'description' => $GLOBALS['TL_LANG']['cms_be_health_check']['missing_opengraph'][1]
        ,   'items' => []
        ];

        // find pages
        $oPages = NULL;
        $oPages = \PageModel::findAll([
            'column' => ["type=?", "(og_title='' OR og_image=NULL)"]
        ,   'value' => ['regular']
        ]);

        if( $oPages ) {

            while( $oPages->next() ) {

                $oCategory->items[] = (object) [
                    'icon'  => \Image::getPath( \Controller::getPageStatusIcon($oPages) )
                ,   'type'  => 'page'
                ,   'name'  => $oPages->title
                ,   'href'  => 'contao?do=page&amp;act=edit&amp;id='.$oPages->id.'&amp;rt='.REQUEST_TOKEN.'&amp;ref='.$this->getRefererID().'#pal_meta_legend'
                ,   'title' => sprintf($GLOBALS['TL_LANG']['tl_page']['edit'][1],$oPages->id)
                ];
            }
        }

        // find news
        $oNews = NULL;
        $oNews = \NewsModel::findAll([
            'column' => ["(og_title='' OR og_image=NULL)"]
        ]);

        if( $oNews ) {

            $this->loadLanguageFile('tl_news');

            while( $oNews->next() ) {

                $oCategory->items[] = (object) [
                    'icon'  => 'bundles/contaonews/news.svg'
                ,   'type'  => 'news'
                ,   'name'  => $oNews->headline
                ,   'href'  => 'contao?do=news&amp;table=tl_news&amp;act=edit&amp;id='.$oNews->id.'&amp;rt='.REQUEST_TOKEN.'&amp;ref='.$this->getRefererID().'#pal_opengraph_legend'
                ,   'title' => sprintf($GLOBALS['TL_LANG']['tl_news']['edit'][1],$oNews->id)
                ];
            }
        }

        if( $oCategory->items ) {
            return $oCategory;
        }
    }
}