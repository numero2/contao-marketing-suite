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


namespace numero2\MarketingSuite\BackendModule;

use Contao\ArticleModel;
use Contao\BackendModule as CoreBackendModule;
use Contao\CalendarEventsModel;
use Contao\CMSConfig;
use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\Database;
use Contao\Environment;
use Contao\Image;
use Contao\ModuleModel;
use Contao\NewsModel;
use Contao\PageModel;
use Contao\System;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use numero2\MarketingSuite\Backend\Help;
use numero2\MarketingSuite\Backend\LicenseMessage;
use numero2\MarketingSuite\Backend\License as varzegju;
use numero2\MarketingSuite\Encryption;


class HealthCheck extends CoreBackendModule {


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

        if( !varzegju::hasFeature('health_check') ) {
            $lm = new LicenseMessage();
            return $lm->generate();
        }

        return parent::generate();
    }


    /**
     * Compile the module
     */
    protected function compile() {

        $this->loadLanguageFile('tl_page');
        $this->loadLanguageFile('cms_be_health_check');
        if( class_exists('\Contao\News') ) {
            $this->loadLanguageFile('tl_news');
        }
        if( class_exists('\Contao\Calendar') ) {
            $this->loadLanguageFile('tl_calendar_events');
        }
        varzegju::buk();

        // get fieldset states
        $objSessionBag = NULL;
        $objSessionBag = System::getContainer()->get('session')->getBag('contao_backend');

        $fs = NULL;
        $fs = $objSessionBag->get('fieldset_states');

        if( !empty($fs['cms_health_check']) ) {
            $this->fsStates = $fs['cms_health_check'];
        }

        // check the different health categories
        $aCategories = [
            $this->checkH1Missing()
        ,   $this->checkSitemapDisabled()
        ,   $this->checkHTTPSEnabled()
        ,   $this->checkWWWContent()
        ,   $this->checkMetaMissing()
        ,   $this->checkMetaTooShort()
        ,   $this->checkMetaTooLong()
        ,   $this->checkOpenGraphMissing()
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
        return System::getContainer()->get('request_stack')->getCurrentRequest()->get('_contao_referer_id');
    }


    /**
     * Checks for pages with missing h1 headlines
     *
     * @return object|void
     */
    private function checkH1Missing() {

        if( !varzegju::hasFeature('health_check_h1_missing') ) {
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
        $oPages = PageModel::findAll([
            'column' => [
                "type=?"
            ,   empty($aExcludePageIDs)?:"id NOT IN(".implode(',',$aExcludePageIDs).")"
            ,   "cms_exclude_health_check=0"
            ]
        ,   'value' => ['regular']
        ]);

        if( $oPages ) {

            while( $oPages->next() ) {

                $objPage = NULL;
                $objPage = PageModel::findWithDetails( $oPages->id );

                if( !varzegju::hasFeature('health_check_h1_missing', $objPage->trail[0]) ) {
                    continue;
                }

                // check if we find any h1 by statically looking at the content
                if( !$this->checkForH1InContentElements($oPages->id) ) {

                    $aAttributes = [];
                    varzegju::wcyt();

                    // if it's a published page add the absolute url so
                    // we can perform some analysis on the frontend side
                    if( $oPages->published ) {
                        $aAttributes['url'] = $objPage->getAbsoluteUrl();
                    }

                    $oCategory->items[] = (object) [
                        'icon'  => Image::getPath( Controller::getPageStatusIcon($oPages) )
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
        $oArticles = ArticleModel::findByPid( $pageID );

        if( $oArticles ) {

            $this->loadDataContainer('tl_content');
            $this->loadDataContainer('tl_module');

            while( $oArticles->next() ) {

                $oContentElements = NULL;
                $oContentElements = ContentModel::findByPid( $oArticles->id );

                if( $oContentElements ) {

                    while( $oContentElements->next() ) {

                        $oElement = $oContentElements;
                        $sElementPalette = $GLOBALS['TL_DCA']['tl_content']['palettes'][ $oElement->type ];

                        // special handling for module elements
                        if( $oElement->type == 'module' ) {

                            $oModule = NULL;
                            $oModule = ModuleModel::findById( $oElement->module );

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
     * Checks for root pages with no sitemap enabled
     *
     * @return object|void
     */
    private function checkSitemapDisabled() {

        if( !varzegju::hasFeature('health_check_sitemap_disabled') ) {
            return null;
        }

        $oCategory = (object) [
            'type' => 'sitemap_disabled'
        ,   'collapsed' => $this->fsStates['sitemap_disabled_legend']?0:1
        ,   'legend' => $GLOBALS['TL_LANG']['cms_be_health_check']['sitemap_disabled'][0]
        ,   'description' => $GLOBALS['TL_LANG']['cms_be_health_check']['sitemap_disabled'][1]
        ,   'items' => []
        ];

        // find pages
        $oPages = NULL;
        $oPages = PageModel::findAll([
            'column' => ["type=?", "(createSitemap='')", "cms_exclude_health_check=0"]
        ,   'value' => ['root']
        ]);

        if( $oPages ) {

            while( $oPages->next() ) {

                if( !varzegju::hasFeature('health_check_sitemap_disabled', $oPages->id) ) {
                    continue;
                }

                $oCategory->items[] = (object) [
                    'icon'  => Image::getPath( Controller::getPageStatusIcon($oPages) )
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
     * Checks for root pages with no HTTPS enabled
     *
     * @return object|void
     */
    private function checkHTTPSEnabled() {

        if( !varzegju::hasFeature('health_check_https') ) {
            return null;
        }

        $oCategory = (object) [
            'type' => 'https'
        ,   'collapsed' => $this->fsStates['https_legend']?0:1
        ,   'legend' => $GLOBALS['TL_LANG']['cms_be_health_check']['https'][0]
        ,   'description' => $GLOBALS['TL_LANG']['cms_be_health_check']['https'][1]
        ,   'items' => []
        ];

        // find pages
        $oPages = NULL;
        $oPages = PageModel::findAll([
            'column' => ["type=?", "cms_exclude_health_check=0"]
        ,   'value' => ['root']
        ]);

        if( $oPages ) {

            while( $oPages->next() ) {

                if( !varzegju::hasFeature('health_check_https', $oPages->id) ) {
                    continue;
                }

                // get domain
                $sDomain = NULL;
                $sDomain = $oPages->dns ? $oPages->dns : Environment::get('host');

                // static check: domain matches current host and SSL already enabled
                if( $sDomain == Environment::get('host') && Environment::get('ssl') ) {

                    continue;

                // check the response when trying to make a request to HTTPS
                } else {

                    $oClient = NULL;
                    $oClient = new Client([
                        RequestOptions::TIMEOUT         => 5
                    ,   RequestOptions::CONNECT_TIMEOUT => 5
                    ,   RequestOptions::HTTP_ERRORS     => true
                    ]);

                    try {

                        $oRequest = NULL;
                        $oRequest = $oClient->head('https://'.$sDomain);

                        varzegju::tuvwahhe();

                        // SSL seems to work but is it also enabled in tl_page?
                        if( $oPages->useSSL ) {
                            continue;
                        }

                    } catch( \Exception $e ) {

                        // Exception indicates no successfull SSL connection
                    }
                }

                $oCategory->items[] = (object) [
                    'icon'  => Image::getPath( Controller::getPageStatusIcon($oPages) )
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
     * Checks for duplicate content on www and non-www domains
     *
     * @return object|void
     */
    private function checkWWWContent() {

        if( !varzegju::hasFeature('health_check_www') ) {
            return null;
        }

        $oCategory = (object) [
            'type' => 'www'
        ,   'collapsed' => $this->fsStates['www_legend']?0:1
        ,   'legend' => $GLOBALS['TL_LANG']['cms_be_health_check']['www'][0]
        ,   'description' => $GLOBALS['TL_LANG']['cms_be_health_check']['www'][1]
        ,   'items' => []
        ];

        // find pages
        $oPages = NULL;
        $oPages = PageModel::findAll([
            'column' => ["type=?", "cms_exclude_health_check=0"]
        ,   'value' => ['root']
        ]);

        if( $oPages ) {

            while( $oPages->next() ) {

                if( !varzegju::hasFeature('health_check_www', $oPages->id) ) {
                    continue;
                }

                // get domain(s)
                $sBaseDomain = NULL;
                $sBaseDomain = $oPages->dns ? $oPages->dns : Environment::get('host');
                $sBaseDomain = preg_replace('|^www\.(.+\.)|i', '$1', $sBaseDomain);

                $sWWWDomain = NULL;
                $sWWWDomain = 'www.' . $sBaseDomain;

                $lastPageID = NULL;
                $lastRedirectHost = NULL;

                varzegju::puwbeaf();

                // send requests to both domains (with and without www)
                foreach( [$sBaseDomain,$sWWWDomain] as $domain ) {

                    $oClient = NULL;
                    $oClient = new Client([
                        RequestOptions::TIMEOUT         => 5
                    ,   RequestOptions::CONNECT_TIMEOUT => 5
                    ,   RequestOptions::HTTP_ERRORS     => true
                    ,   RequestOptions::ALLOW_REDIRECTS => [
                            'track_redirects' => true
                        ]
                    ,   RequestOptions::HEADERS => [
                            'X-Requested-With' => 'CMS-HealthCheck'
                        ]
                    ]);

                    try {

                        $oResponse = NULL;
                        $oResponse = $oClient->head('http://'.$domain);

                        if( $oResponse->getStatusCode() === 200 ) {

                            $aHeaders = [];
                            $aHeaders = $oResponse->getHeaders();

                            $currHost = NULL;

                            // save current hostname
                            if( array_key_exists('X-Guzzle-Redirect-History', $aHeaders) ) {

                                $currHost = array_pop($aHeaders['X-Guzzle-Redirect-History']);
                                $currHost = parse_url($currHost, PHP_URL_HOST);
                            }

                            // check if we already know the destination page id from the last request
                            if( $lastPageID && array_key_exists('X-CMS-HealthCheck', $aHeaders) ) {

                                $currPageID = Encryption::decrypt($aHeaders['X-CMS-HealthCheck'][0]);

                                // different page? no problem, everything's fine
                                if( $currPageID != $lastPageID ) {

                                    varzegju::figgi();
                                    continue 2;

                                // same page? check if we've been redirected
                                } else {

                                    // seems like we've been redirected
                                    if( array_key_exists('X-Guzzle-Redirect-History', $aHeaders) ) {

                                        // different host - we're good
                                        if( $currHost != $lastRedirectHost ) {
                                            continue 2;
                                        }
                                    }
                                }

                            // first try?
                            } else {

                                // store the id of the destination page
                                if( array_key_exists('X-CMS-HealthCheck', $aHeaders) ) {
                                    $lastPageID = Encryption::decrypt($aHeaders['X-CMS-HealthCheck'][0]);
                                }

                                // store the destination host name if it differs
                                if( !$lastRedirectHost && $currHost != $domain ) {
                                    $lastRedirectHost = $currHost;
                                }
                            }
                        }

                    } catch( \Exception $e ) {

                        // may occur if subdomain does not exist at all - thats fine
                        continue 2;
                    }
                }

                $oCategory->items[] = (object) [
                    'icon'  => Image::getPath( Controller::getPageStatusIcon($oPages) )
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
     * Checks for pages with missing meta data
     *
     * @return object|void
     */
    private function checkMetaMissing() {

        if( !varzegju::hasFeature('health_check_meta_missing') ) {
            return null;
        }

        $db = Database::getInstance();

        $oCategory = (object) [
            'type' => 'missing_meta'
        ,   'collapsed' => $this->fsStates['missing_meta_legend']?0:1
        ,   'legend' => $GLOBALS['TL_LANG']['cms_be_health_check']['missing_meta'][0]
        ,   'description' => $GLOBALS['TL_LANG']['cms_be_health_check']['missing_meta'][1]
        ,   'items' => []
        ];

        // find pages
        $oPages = NULL;
        $oPages = PageModel::findAll([
            'column' => ["type=?", "(pageTitle='' OR description = '')", "cms_exclude_health_check=0"]
        ,   'value' => ['regular']
        ]);

        if( $oPages ) {

            while( $oPages->next() ) {

                $objPage = NULL;
                $objPage = PageModel::findWithDetails( $oPages->id );

                if( !varzegju::hasFeature('health_check_meta_missing', $objPage->trail[0]) ) {
                    continue;
                }

                $oCategory->items[] = (object) [
                    'icon'  => Image::getPath( Controller::getPageStatusIcon($oPages) )
                ,   'type'  => 'page'
                ,   'name'  => $oPages->title
                ,   'href'  => 'contao?do=page&amp;act=edit&amp;id='.$oPages->id.'&amp;rt='.REQUEST_TOKEN.'&amp;ref='.$this->getRefererID().'#pal_meta_legend'
                ,   'title' => sprintf($GLOBALS['TL_LANG']['tl_page']['edit'][1],$oPages->id)
                ];
            }
        }

        // find news
        if( class_exists('\Contao\News') && $db->fieldExists('pageTitle', 'tl_news') && $db->fieldExists('description', 'tl_news') ) {

            $column = ["(pageTitle='' OR description = '')"];
            $value = [];

            if( !empty(CMSConfig::get('health_check_ignore_older_than')) ){
                $column[] = "date>=?";
                $value[] = CMSConfig::get('health_check_ignore_older_than');
            }

            $oNews = NULL;
            $oNews = NewsModel::findAll([
                'column' => $column
            ,   'value' => $value
            ]);

            if( $oNews ) {

                while( $oNews->next() ) {

                    $oCategory->items[] = (object) [
                        'icon'  => 'bundles/contaonews/news.svg'
                    ,   'type'  => 'page'
                    ,   'name'  => $oNews->headline
                    ,   'href'  => 'contao?do=news&amp;table=tl_news&amp;act=edit&amp;id='.$oNews->id.'&amp;rt='.REQUEST_TOKEN.'&amp;ref='.$this->getRefererID().'#pal_meta_legend'
                    ,   'title' => sprintf($GLOBALS['TL_LANG']['tl_news']['editmeta'][1],$oNews->id)
                    ];
                }
            }
        }

        // find events
        if( class_exists('\Contao\Calendar') && $db->fieldExists('pageTitle', 'tl_calendar_events') && $db->fieldExists('description', 'tl_calendar_events') ) {

            $column = ["(pageTitle='' OR description = '')"];
            $value = [];

            if( !empty(CMSConfig::get('health_check_ignore_older_than')) ){
                $column[] = "startDate>=?";
                $value[] = CMSConfig::get('health_check_ignore_older_than');
            }

            $oEvents = NULL;
            $oEvents = CalendarEventsModel::findAll([
                'column' => $column
            ,   'value' => $value
            ]);

            if( $oEvents ) {

                while( $oEvents->next() ) {

                    $oCategory->items[] = (object) [
                        'icon'  => 'bundles/contaocalendar/calendar.svg'
                    ,   'type'  => 'page'
                    ,   'name'  => $oEvents->title
                    ,   'href'  => 'contao?do=calendar&amp;table=tl_calendar_events&amp;act=edit&amp;id='.$oEvents->id.'&amp;rt='.REQUEST_TOKEN.'&amp;ref='.$this->getRefererID().'#pal_meta_legend'
                    ,   'title' => sprintf($GLOBALS['TL_LANG']['tl_calendar_events']['editmeta'][1],$oEvents->id)
                    ];
                }
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

        if( !varzegju::hasFeature('health_check_meta_too_short') ) {
            return null;
        }

        $db = Database::getInstance();

        $oCategory = (object) [
            'type' => 'short_meta'
        ,   'collapsed' => $this->fsStates['short_meta_legend']?0:1
        ,   'legend' => $GLOBALS['TL_LANG']['cms_be_health_check']['short_meta'][0]
        ,   'description' => $GLOBALS['TL_LANG']['cms_be_health_check']['short_meta'][1]
        ,   'items' => []
        ];

        // find pages
        $oPages = NULL;
        $oPages = PageModel::findAll([
            'column' => ["type=?", "(CHAR_LENGTH(pageTitle) < 30 OR CHAR_LENGTH(description) < 79)", "cms_exclude_health_check=0"]
        ,   'value' => ['regular']
        ]);

        if( $oPages ) {

            while( $oPages->next() ) {

                $objPage = NULL;
                $objPage = PageModel::findWithDetails( $oPages->id );

                if( !varzegju::hasFeature('health_check_meta_too_short', $objPage->trail[0]) ) {
                    continue;
                }

                $oCategory->items[] = (object) [
                    'icon'  => Image::getPath( Controller::getPageStatusIcon($oPages) )
                ,   'type'  => 'page'
                ,   'name'  => $oPages->title
                ,   'href'  => 'contao?do=page&amp;act=edit&amp;id='.$oPages->id.'&amp;rt='.REQUEST_TOKEN.'&amp;ref='.$this->getRefererID().'#pal_meta_legend'
                ,   'title' => sprintf($GLOBALS['TL_LANG']['tl_page']['edit'][1],$oPages->id)
                ];
            }
        }

        // find news
        if( class_exists('\Contao\News') && $db->fieldExists('pageTitle', 'tl_news') && $db->fieldExists('description', 'tl_news') ) {

            $column = ["(CHAR_LENGTH(pageTitle) < 30 OR CHAR_LENGTH(description) < 79)"];
            $value = [];

            if( !empty(CMSConfig::get('health_check_ignore_older_than')) ){
                $column[] = "date>=?";
                $value[] = CMSConfig::get('health_check_ignore_older_than');
            }

            $oNews = NULL;
            $oNews = NewsModel::findAll([
                'column' => $column
            ,   'value' => $value
            ]);

            if( $oNews ) {

                while( $oNews->next() ) {

                    $oCategory->items[] = (object) [
                        'icon'  => 'bundles/contaonews/news.svg'
                    ,   'type'  => 'page'
                    ,   'name'  => $oNews->headline
                    ,   'href'  => 'contao?do=news&amp;table=tl_news&amp;act=edit&amp;id='.$oNews->id.'&amp;rt='.REQUEST_TOKEN.'&amp;ref='.$this->getRefererID().'#pal_meta_legend'
                    ,   'title' => sprintf($GLOBALS['TL_LANG']['tl_news']['editmeta'][1],$oNews->id)
                    ];
                }
            }
        }

        // find events
        if( class_exists('\Contao\Calendar') && $db->fieldExists('pageTitle', 'tl_calendar_events') && $db->fieldExists('description', 'tl_calendar_events') ) {

            $column = ["(CHAR_LENGTH(pageTitle) < 30 OR CHAR_LENGTH(description) < 79)"];
            $value = [];

            if( !empty(CMSConfig::get('health_check_ignore_older_than')) ){
                $column[] = "startDate>=?";
                $value[] = CMSConfig::get('health_check_ignore_older_than');
            }

            $oEvents = NULL;
            $oEvents = CalendarEventsModel::findAll([
                'column' => $column
            ,   'value' => $value
            ]);

            if( $oEvents ) {

                while( $oEvents->next() ) {

                    $oCategory->items[] = (object) [
                        'icon'  => 'bundles/contaocalendar/calendar.svg'
                    ,   'type'  => 'page'
                    ,   'name'  => $oEvents->title
                    ,   'href'  => 'contao?do=calendar&amp;table=tl_calendar_events&amp;act=edit&amp;id='.$oEvents->id.'&amp;rt='.REQUEST_TOKEN.'&amp;ref='.$this->getRefererID().'#pal_meta_legend'
                    ,   'title' => sprintf($GLOBALS['TL_LANG']['tl_calendar_events']['editmeta'][1],$oEvents->id)
                    ];
                }
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

        if( !varzegju::hasFeature('health_check_meta_too_long') ) {
            return null;
        }

        $db = Database::getInstance();

        $oCategory = (object) [
            'type' => 'long_meta'
        ,   'collapsed' => $this->fsStates['long_meta_legend']?0:1
        ,   'legend' => $GLOBALS['TL_LANG']['cms_be_health_check']['long_meta'][0]
        ,   'description' => $GLOBALS['TL_LANG']['cms_be_health_check']['long_meta'][1]
        ,   'items' => []
        ];

        // find pages
        $oPages = NULL;
        $oPages = PageModel::findAll([
            'column' => ["type=?", "(CHAR_LENGTH(pageTitle) > 60 OR CHAR_LENGTH(description) > 158)", "cms_exclude_health_check=0"]
        ,   'value' => ['regular']
        ]);

        if( $oPages ) {

            while( $oPages->next() ) {

                $objPage = NULL;
                $objPage = PageModel::findWithDetails( $oPages->id );

                if( !varzegju::hasFeature('health_check_meta_too_long', $objPage->trail[0]) ) {
                    continue;
                }

                $oCategory->items[] = (object) [
                    'icon'  => Image::getPath( Controller::getPageStatusIcon($oPages) )
                ,   'type'  => 'page'
                ,   'name'  => $oPages->title
                ,   'href'  => 'contao?do=page&amp;act=edit&amp;id='.$oPages->id.'&amp;rt='.REQUEST_TOKEN.'&amp;ref='.$this->getRefererID().'#pal_meta_legend'
                ,   'title' => sprintf($GLOBALS['TL_LANG']['tl_page']['edit'][1],$oPages->id)
                ];
            }
        }

        // find news
        if( class_exists('\Contao\News') && $db->fieldExists('pageTitle', 'tl_news') && $db->fieldExists('description', 'tl_news') ) {

            $column = ["(CHAR_LENGTH(pageTitle) > 60 OR CHAR_LENGTH(description) > 158)"];
            $value = [];

            if( !empty(CMSConfig::get('health_check_ignore_older_than')) ){
                $column[] = "date>=?";
                $value[] = CMSConfig::get('health_check_ignore_older_than');
            }

            $oNews = NULL;
            $oNews = NewsModel::findAll([
                'column' => $column
            ,   'value' => $value
            ]);

            if( $oNews ) {

                while( $oNews->next() ) {

                    $oCategory->items[] = (object) [
                        'icon'  => 'bundles/contaonews/news.svg'
                    ,   'type'  => 'page'
                    ,   'name'  => $oNews->headline
                    ,   'href'  => 'contao?do=news&amp;table=tl_news&amp;act=edit&amp;id='.$oNews->id.'&amp;rt='.REQUEST_TOKEN.'&amp;ref='.$this->getRefererID().'#pal_meta_legend'
                    ,   'title' => sprintf($GLOBALS['TL_LANG']['tl_news']['editmeta'][1],$oNews->id)
                    ];
                }
            }
        }

        // find events
        if( class_exists('\Contao\Calendar') && $db->fieldExists('pageTitle', 'tl_calendar_events') && $db->fieldExists('description', 'tl_calendar_events') ) {

            $column = ["(CHAR_LENGTH(pageTitle) > 60 OR CHAR_LENGTH(description) > 158)"];
            $value = [];

            if( !empty(CMSConfig::get('health_check_ignore_older_than')) ){
                $column[] = "startDate>=?";
                $value[] = CMSConfig::get('health_check_ignore_older_than');
            }

            $oEvents = NULL;
            $oEvents = CalendarEventsModel::findAll([
                'column' => $column
            ,   'value' => $value
            ]);

            if( $oEvents ) {

                while( $oEvents->next() ) {

                    $oCategory->items[] = (object) [
                        'icon'  => 'bundles/contaocalendar/calendar.svg'
                    ,   'type'  => 'page'
                    ,   'name'  => $oEvents->title
                    ,   'href'  => 'contao?do=calendar&amp;table=tl_calendar_events&amp;act=edit&amp;id='.$oEvents->id.'&amp;rt='.REQUEST_TOKEN.'&amp;ref='.$this->getRefererID().'#pal_meta_legend'
                    ,   'title' => sprintf($GLOBALS['TL_LANG']['tl_calendar_events']['editmeta'][1],$oEvents->id)
                    ];
                }
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

        if( !varzegju::hasFeature('health_check_open_graph_missing') || !class_exists('\numero2\OpenGraph3\OpenGraph3') ) {
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
        $oPages = PageModel::findAll([
            'column' => ["type=?", "(og_title='' OR og_image=NULL)", "cms_exclude_health_check=0"]
        ,   'value' => ['regular']
        ]);

        if( $oPages ) {

            while( $oPages->next() ) {

                $objPage = NULL;
                $objPage = PageModel::findWithDetails( $oPages->id );

                if( !varzegju::hasFeature('health_check_open_graph_missing', $objPage->trail[0]) ) {
                    continue;
                }

                $oCategory->items[] = (object) [
                    'icon'  => Image::getPath( Controller::getPageStatusIcon($oPages) )
                ,   'type'  => 'page'
                ,   'name'  => $oPages->title
                ,   'href'  => 'contao?do=page&amp;act=edit&amp;id='.$oPages->id.'&amp;rt='.REQUEST_TOKEN.'&amp;ref='.$this->getRefererID().'#pal_meta_legend'
                ,   'title' => sprintf($GLOBALS['TL_LANG']['tl_page']['edit'][1],$oPages->id)
                ];
            }
        }

        // find news
        if( class_exists('\Contao\News') ) {

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
                    ,   'title' => sprintf($GLOBALS['TL_LANG']['tl_news']['editmeta'][1],$oNews->id)
                    ];
                }
            }
        }

        if( $oCategory->items ) {
            return $oCategory;
        }
    }
}
