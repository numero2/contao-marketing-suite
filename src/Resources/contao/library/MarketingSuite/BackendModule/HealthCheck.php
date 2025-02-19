<?php

/**
 * Contao Marketing Suite Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2025, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\MarketingSuite\BackendModule;

use Contao\ArticleModel;
use Contao\BackendModule as CoreBackendModule;
use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\CMSConfig;
use Contao\ContentModel;
use Contao\Controller;
use Contao\Database;
use Contao\Environment;
use Contao\Image;
use Contao\Message;
use Contao\ModuleModel;
use Contao\NewsArchiveModel;
use Contao\NewsModel;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Exception;
use numero2\MarketingSuite\Backend\Help;
use numero2\MarketingSuite\Backend\License as varzegju;
use numero2\MarketingSuite\Backend\LicenseMessage;
use numero2\MarketingSuite\Encryption;
use numero2\MarketingSuite\Widget\SnippetPreview;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;


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

        // add testmode info
        if( CMSConfig::get('testmode') ) {
            Message::addInfo($GLOBALS['TL_LANG']['cms_be_health_check']['testmode_enabled']);
        }

        // get fieldset states
        $objSessionBag = null;
        $objSessionBag = System::getContainer()->get('request_stack')->getSession()->getBag('contao_backend');

        $fs = null;
        $fs = $objSessionBag->get('fieldset_states');

        if( !empty($fs['cms_health_check']) ) {
            $this->fsStates = $fs['cms_health_check'];
        }

        // check the different health categories
        $aCategories = [
            $this->checkH1Missing()
        ,   $this->checkHTTPSEnabled()
        ,   $this->checkWWWContent()
        ,   $this->checkMetaMissing()
        ,   $this->checkMetaTooShort()
        ,   $this->checkMetaTooLong()
        ,   $this->checkOpenGraphMissing()
        ];

        // used to drop empty check categories
        $aCategories = array_filter($aCategories);

        // initialize help
        $objBEHelp = null;
        $objBEHelp = new Help();

        $this->Template->be_help = $objBEHelp->generate();
        $this->Template->categories = $aCategories;

        // add messages
        if( Message::hasMessages() ) {
            $this->Template->message = Message::generate();
        }

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
     * Returns the referer id used in links
     *
     * @return string
     */
    private function getRequestToken() {
        return System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();
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
        ,   'collapsed' => empty($this->fsStates['missing_h1_legend'])
        ,   'legend' => $GLOBALS['TL_LANG']['cms_be_health_check']['missing_h1'][0]
        ,   'description' => $GLOBALS['TL_LANG']['cms_be_health_check']['missing_h1'][1]
        ,   'items' => []
        ];

        $aExcludePageIDs = [];

        // check for news archives to exclude these pages from the check
        if( class_exists('\Contao\News') ) {

            $oArchives = null;
            $oArchives = NewsArchiveModel::findAll();

            if( $oArchives ) {

                while( $oArchives->next() ) {
                    $aExcludePageIDs[] = $oArchives->jumpTo;
                }
            }
        }

        // check for calendars to exclude these pages from the check
        if( class_exists('\Contao\Calendar') ) {

            $oCalendars = null;
            $oCalendars = CalendarModel::findAll();

            if( $oCalendars ) {

                while( $oCalendars->next() ) {
                    $aExcludePageIDs[] = $oCalendars->jumpTo;
                }
            }
        }

        // find pages
        $oPages = null;
        $oPages = PageModel::findAll([
            'column' => [
                "type=?"
            ,   empty($aExcludePageIDs)?:"id NOT IN(".implode(',',$aExcludePageIDs).")"
            ,   "cms_exclude_health_check=0"
            ]
        ,   'value' => ['regular']
        ]);

        if( $oPages ) {

            $routePrefix = System::getContainer()->getParameter('contao.backend.route_prefix');

            while( $oPages->next() ) {

                // check if we find any h1 by statically looking at the content
                if( !$this->checkForH1InContentElements($oPages->id) ) {

                    $objPage = null;
                    $objPage = $oPages->current()->loadDetails();

                    if( !varzegju::hasFeature('health_check_h1_missing', $objPage->trail[0]) ) {
                        continue;
                    }

                    $aAttributes = [];
                    varzegju::wcyt();

                    // if it's a published page add the absolute url so
                    // we can perform some analysis on the frontend side
                    if( $oPages->published ) {
                        $aAttributes['url'] = $objPage->getAbsoluteUrl();
                    }

                    if( CMSConfig::get('testmode') && count($oCategory->items) == 1 ) {
                        break;
                    }

                    $oCategory->items[] = (object) [
                        'icon'  => Controller::addStaticUrlTo(Image::getPath(Controller::getPageStatusIcon($oPages)))
                    ,   'type'  => 'page'
                    ,   'name'  => $oPages->title
                    ,   'href'  => $routePrefix . '?do=article&amp;pn='.$oPages->id.'&amp;rt='.$this->getRequestToken().'&amp;ref='.$this->getRefererID()
                    ,   'title' => sprintf($GLOBALS['TL_LANG']['DCA']['edit'], $oPages->id)
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

        $oArticles = null;
        $oArticles = ArticleModel::findByPid( $pageID );

        if( $oArticles ) {

            $this->loadDataContainer('tl_content');
            $this->loadDataContainer('tl_module');

            while( $oArticles->next() ) {

                if( !$oArticles->published ) {
                    continue;
                }

                $oContentElements = null;
                $oContentElements = ContentModel::findByPid( $oArticles->id );

                if( $oContentElements ) {

                    while( $oContentElements->next() ) {

                        $oElement = $oContentElements;
                        $sElementPalette = $GLOBALS['TL_DCA']['tl_content']['palettes'][ $oElement->type ];

                        if( $oElement->invisible ) {
                            continue;
                        }

                        // special handling for module elements
                        if( $oElement->type == 'module' ) {

                            $oModule = null;
                            $oModule = ModuleModel::findById( $oElement->module );

                            $oElement = $oModule;
                            $sElementPalette = $GLOBALS['TL_DCA']['tl_module']['palettes'][ $oElement->type ] ?? null;

                            if( $sElementPalette === null ) {
                                continue;
                            }
                        }

                        // check headline element
                        if( $oElement->headline && preg_match("/(,|)headline(,|;)/", $sElementPalette) ) {

                            $headline = StringUtil::deserialize($oElement->headline);

                            if( $headline['unit'] == 'h1' && !empty($headline['value']) ) {
                                return true;
                            }
                        }

                        // check text element
                        if( $oElement->text && preg_match("/(,|)text(,|;)/", $sElementPalette) ) {

                            if( preg_match_all('/<h1[^>]*>(.+)<\/h1>/s', $oElement->text) ) {
                                return true;
                            }
                        }

                        // check html element
                        if( $oElement->html && preg_match("/(,|)html(,|;)/", $sElementPalette) ) {

                            if( preg_match_all('/<h1[^>]*>(.+)<\/h1>/s', $oElement->html) ) {
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
        ,   'collapsed' => empty($this->fsStates['https_legend'])
        ,   'legend' => $GLOBALS['TL_LANG']['cms_be_health_check']['https'][0]
        ,   'description' => $GLOBALS['TL_LANG']['cms_be_health_check']['https'][1]
        ,   'items' => []
        ];

        // find pages
        $oPages = null;
        $oPages = PageModel::findAll([
            'column' => ["type=?", "cms_exclude_health_check=0"]
        ,   'value' => ['root']
        ]);

        if( $oPages ) {

            $routePrefix = System::getContainer()->getParameter('contao.backend.route_prefix');

            while( $oPages->next() ) {

                if( !varzegju::hasFeature('health_check_https', $oPages->id) ) {
                    continue;
                }

                // get domain
                $sDomain = null;
                $sDomain = $oPages->dns ? $oPages->dns : Environment::get('host');

                // static check: domain matches current host and SSL already enabled
                if( $sDomain == Environment::get('host') && Environment::get('ssl') ) {

                    continue;

                // check the response when trying to make a request to HTTPS
                } else {

                    try {

                        $client = null;
                        $client = HttpClient::create([
                            'headers' => [
                                'user-agent' => 'Contao Marketig Suite '.CMS_VERSION
                            ]
                        ,   'timeout' => 5
                        ,   'max_duration' => 5
                        ,   'verify_peer' => true
                        ,   'verify_host' => true
                        ]);

                        $response = null;
                        $response = $client->request('HEAD', 'https://'.$sDomain);

                        // respones are lazy.
                        // make sure to call its destructor right within our try/catch
                        unset($response);

                        varzegju::tuvwahhe();

                        // SSL seems to work but is it also enabled in tl_page?
                        if( $oPages->useSSL ) {
                            continue;
                        }

                    } catch( Exception | HttpExceptionInterface | TransportExceptionInterface $e ) {
                        // Exception indicates no successfull SSL connection
                    }
                }

                if( CMSConfig::get('testmode') && count($oCategory->items) == 1 ) {
                    break;
                }

                $oCategory->items[] = (object) [
                    'icon'  => Controller::addStaticUrlTo(Image::getPath(Controller::getPageStatusIcon($oPages)))
                ,   'type'  => 'page'
                ,   'name'  => $oPages->title
                ,   'href'  => $routePrefix . '?do=page&amp;act=edit&amp;id='.$oPages->id.'&amp;rt='.$this->getRequestToken().'&amp;ref='.$this->getRefererID().'#pal_meta_legend'
                ,   'title' => sprintf($GLOBALS['TL_LANG']['DCA']['edit'], $oPages->id)
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
        ,   'collapsed' => empty($this->fsStates['www_legend'])
        ,   'legend' => $GLOBALS['TL_LANG']['cms_be_health_check']['www'][0]
        ,   'description' => $GLOBALS['TL_LANG']['cms_be_health_check']['www'][1]
        ,   'items' => []
        ];

        // find pages
        $oPages = null;
        $oPages = PageModel::findAll([
            'column' => ["type=?", "cms_exclude_health_check=0"]
        ,   'value' => ['root']
        ]);

        if( $oPages ) {

            $routePrefix = System::getContainer()->getParameter('contao.backend.route_prefix');

            while( $oPages->next() ) {

                if( !varzegju::hasFeature('health_check_www', $oPages->id) ) {
                    continue;
                }

                // get domain(s)
                $sBaseDomain = null;
                $sBaseDomain = $oPages->dns ? $oPages->dns : Environment::get('host');
                $sBaseDomain = preg_replace('|^www\.(.+\.)|i', '$1', $sBaseDomain);

                $sWWWDomain = null;
                $sWWWDomain = 'www.' . $sBaseDomain;

                $lastPageID = null;
                $lastRedirectHost = null;

                varzegju::puwbeaf();

                $aDomains = [
                    ['host'=>$sBaseDomain, 'id'=>null]
                ,   ['host'=>$sWWWDomain, 'id'=>null]
                ];

                // send requests to both domains (with and without www)
                foreach( $aDomains as $i => $data ) {

                    $client = null;
                    $client = HttpClient::create([
                        'headers' => [
                            'user-agent' => 'Contao Marketig Suite '.CMS_VERSION
                        ,   'x-requested-with' => 'CMS-HealthCheck'
                        ]
                    ,   'timeout' => 5
                    ,   'max_duration' => 5
                    ,   'verify_peer' => false
                    ,   'verify_host' => false
                    ]);

                    try {

                        $response = null;
                        $response = $client->request('HEAD', 'http://'.$data['host']);

                        if( $response->getStatusCode() === 200 ) {

                            $aHeaders = [];
                            $aHeaders = $response->getHeaders();

                            // save redirected hostname
                            if( $response->getInfo()['redirect_count'] > 0 ) {

                                $currHost = $response->getInfo()['url'];
                                $currHost = parse_url($currHost, PHP_URL_HOST);

                                $aDomains[$i]['host'] = $currHost;
                            }

                            // save page id
                            if( array_key_exists('X-CMS-HealthCheck', $aHeaders) ) {
                                $aDomains[$i]['id'] = Encryption::decrypt($aHeaders['X-CMS-HealthCheck'][0]);
                            }
                        }

                        unset($response);

                    // may occur if subdomain does not exist at all - thats fine
                    } catch( Exception | HttpExceptionInterface | TransportExceptionInterface $e ) {

                        continue 2;
                    }
                }

                // different hosts point to same site - duplicate content
                if( $aDomains[0]['host'] != $aDomains[1]['host'] && $aDomains[0]['id'] == $aDomains[1]['id'] ) {

                    if( CMSConfig::get('testmode') && count($oCategory->items) == 1 ) {
                        break;
                    }

                    $oCategory->items[] = (object) [
                        'icon'  => Controller::addStaticUrlTo(Image::getPath(Controller::getPageStatusIcon($oPages)))
                    ,   'type'  => 'page'
                    ,   'name'  => $oPages->title
                    ,   'href'  => $routePrefix . '?do=page&amp;act=edit&amp;id='.$oPages->id.'&amp;rt='.$this->getRequestToken().'&amp;ref='.$this->getRefererID().'#pal_meta_legend'
                    ,   'title' => sprintf($GLOBALS['TL_LANG']['DCA']['edit'], $oPages->id)
                    ];
                }
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
        $routePrefix = System::getContainer()->getParameter('contao.backend.route_prefix');

        $oCategory = (object) [
            'type' => 'missing_meta'
        ,   'collapsed' => empty($this->fsStates['missing_meta_legend'])
        ,   'legend' => $GLOBALS['TL_LANG']['cms_be_health_check']['missing_meta'][0]
        ,   'description' => $GLOBALS['TL_LANG']['cms_be_health_check']['missing_meta'][1]
        ,   'items' => []
        ];

        // find pages
        $oPages = null;
        $oPages = PageModel::findAll([
            'column' => ["type=?", "(pageTitle='' OR description = '')", "cms_exclude_health_check=0"]
        ,   'value' => ['regular']
        ]);

        if( $oPages ) {

            while( $oPages->next() ) {

                $objPage = null;
                $objPage = PageModel::findWithDetails( $oPages->id );

                if( !varzegju::hasFeature('health_check_meta_missing', $objPage->trail[0]) ) {
                    continue;
                }

                if( CMSConfig::get('testmode') && count($oCategory->items) == 1 ) {
                    break;
                }

                $oCategory->items[] = (object) [
                    'icon'  => Controller::addStaticUrlTo(Image::getPath(Controller::getPageStatusIcon($oPages)))
                ,   'type'  => 'page'
                ,   'name'  => $oPages->title
                ,   'href'  => $routePrefix . '?do=page&amp;act=edit&amp;id='.$oPages->id.'&amp;rt='.$this->getRequestToken().'&amp;ref='.$this->getRefererID().'#pal_meta_legend'
                ,   'title' => sprintf($GLOBALS['TL_LANG']['DCA']['edit'], $oPages->id)
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

            $oNews = null;
            $oNews = NewsModel::findAll([
                'column' => $column
            ,   'value' => $value
            ]);

            if( $oNews ) {

                while( $oNews->next() ) {

                    if( CMSConfig::get('testmode') && count($oCategory->items) == 1 ) {
                        break;
                    }

                    $oCategory->items[] = (object) [
                        'icon'  => 'bundles/contaonews/news.svg'
                    ,   'type'  => 'page'
                    ,   'name'  => $oNews->headline
                    ,   'href'  => $routePrefix . '?do=news&amp;table=tl_news&amp;act=edit&amp;id='.$oNews->id.'&amp;rt='.$this->getRequestToken().'&amp;ref='.$this->getRefererID().'#pal_meta_legend'
                    ,   'title' => sprintf($GLOBALS['TL_LANG']['DCA']['children'], $oNews->id)
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

            $oEvents = null;
            $oEvents = CalendarEventsModel::findAll([
                'column' => $column
            ,   'value' => $value
            ]);

            if( $oEvents ) {

                while( $oEvents->next() ) {

                    if( CMSConfig::get('testmode') && count($oCategory->items) == 1 ) {
                        break;
                    }

                    $oCategory->items[] = (object) [
                        'icon'  => 'bundles/contaocalendar/calendar.svg'
                    ,   'type'  => 'page'
                    ,   'name'  => $oEvents->title
                    ,   'href'  => $routePrefix . '?do=calendar&amp;table=tl_calendar_events&amp;act=edit&amp;id='.$oEvents->id.'&amp;rt='.$this->getRequestToken().'&amp;ref='.$this->getRefererID().'#pal_meta_legend'
                    ,   'title' => sprintf($GLOBALS['TL_LANG']['DCA']['children'], $oEvents->id)
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
        $routePrefix = System::getContainer()->getParameter('contao.backend.route_prefix');

        $oCategory = (object) [
            'type' => 'short_meta'
        ,   'collapsed' => empty($this->fsStates['short_meta_legend'])
        ,   'legend' => $GLOBALS['TL_LANG']['cms_be_health_check']['short_meta'][0]
        ,   'description' => $GLOBALS['TL_LANG']['cms_be_health_check']['short_meta'][1]
        ,   'items' => []
        ];

        // find pages
        $oPages = null;
        $oPages = PageModel::findAll([
            'column' => ["type=?", "(CHAR_LENGTH(pageTitle) < ".SnippetPreview::TITLE_MIN_LENGTH." OR CHAR_LENGTH(description) < ".SnippetPreview::DESCRIPTION_MIN_LENGTH.")", "cms_exclude_health_check=0"]
        ,   'value' => ['regular']
        ]);

        if( $oPages ) {

            while( $oPages->next() ) {

                $objPage = null;
                $objPage = PageModel::findWithDetails( $oPages->id );

                if( !varzegju::hasFeature('health_check_meta_too_short', $objPage->trail[0]) ) {
                    continue;
                }

                if( CMSConfig::get('testmode') && count($oCategory->items) == 1 ) {
                    break;
                }

                $oCategory->items[] = (object) [
                    'icon'  => Controller::addStaticUrlTo(Image::getPath(Controller::getPageStatusIcon($oPages)))
                ,   'type'  => 'page'
                ,   'name'  => $oPages->title
                ,   'href'  => $routePrefix . '?do=page&amp;act=edit&amp;id='.$oPages->id.'&amp;rt='.$this->getRequestToken().'&amp;ref='.$this->getRefererID().'#pal_meta_legend'
                ,   'title' => sprintf($GLOBALS['TL_LANG']['DCA']['edit'], $oPages->id)
                ];
            }
        }

        // find news
        if( class_exists('\Contao\News') && $db->fieldExists('pageTitle', 'tl_news') && $db->fieldExists('description', 'tl_news') ) {

            $column = ["(CHAR_LENGTH(pageTitle) < ".SnippetPreview::TITLE_MIN_LENGTH." OR CHAR_LENGTH(description) < ".SnippetPreview::DESCRIPTION_MIN_LENGTH.")"];
            $value = [];

            if( !empty(CMSConfig::get('health_check_ignore_older_than')) ){
                $column[] = "date>=?";
                $value[] = CMSConfig::get('health_check_ignore_older_than');
            }

            $oNews = null;
            $oNews = NewsModel::findAll([
                'column' => $column
            ,   'value' => $value
            ]);

            if( $oNews ) {

                while( $oNews->next() ) {

                    if( CMSConfig::get('testmode') && count($oCategory->items) == 1 ) {
                        break;
                    }

                    $oCategory->items[] = (object) [
                        'icon'  => 'bundles/contaonews/news.svg'
                    ,   'type'  => 'page'
                    ,   'name'  => $oNews->headline
                    ,   'href'  => $routePrefix . '?do=news&amp;table=tl_news&amp;act=edit&amp;id='.$oNews->id.'&amp;rt='.$this->getRequestToken().'&amp;ref='.$this->getRefererID().'#pal_meta_legend'
                    ,   'title' => sprintf($GLOBALS['TL_LANG']['DCA']['children'], $oNews->id)
                    ];
                }
            }
        }

        // find events
        if( class_exists('\Contao\Calendar') && $db->fieldExists('pageTitle', 'tl_calendar_events') && $db->fieldExists('description', 'tl_calendar_events') ) {

            $column = ["(CHAR_LENGTH(pageTitle) < ".SnippetPreview::TITLE_MIN_LENGTH." OR CHAR_LENGTH(description) < ".SnippetPreview::DESCRIPTION_MIN_LENGTH.")"];
            $value = [];

            if( !empty(CMSConfig::get('health_check_ignore_older_than')) ){
                $column[] = "startDate>=?";
                $value[] = CMSConfig::get('health_check_ignore_older_than');
            }

            $oEvents = null;
            $oEvents = CalendarEventsModel::findAll([
                'column' => $column
            ,   'value' => $value
            ]);

            if( $oEvents ) {

                while( $oEvents->next() ) {

                    if( CMSConfig::get('testmode') && count($oCategory->items) == 1 ) {
                        break;
                    }

                    $oCategory->items[] = (object) [
                        'icon'  => 'bundles/contaocalendar/calendar.svg'
                    ,   'type'  => 'page'
                    ,   'name'  => $oEvents->title
                    ,   'href'  => $routePrefix . '?do=calendar&amp;table=tl_calendar_events&amp;act=edit&amp;id='.$oEvents->id.'&amp;rt='.$this->getRequestToken().'&amp;ref='.$this->getRefererID().'#pal_meta_legend'
                    ,   'title' => sprintf($GLOBALS['TL_LANG']['DCA']['children'], $oEvents->id)
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
        $routePrefix = System::getContainer()->getParameter('contao.backend.route_prefix');

        $oCategory = (object) [
            'type' => 'long_meta'
        ,   'collapsed' => empty($this->fsStates['long_meta_legend'])
        ,   'legend' => $GLOBALS['TL_LANG']['cms_be_health_check']['long_meta'][0]
        ,   'description' => $GLOBALS['TL_LANG']['cms_be_health_check']['long_meta'][1]
        ,   'items' => []
        ];

        // find pages
        $oPages = null;
        $oPages = PageModel::findAll([
            'column' => ["type=?", "(CHAR_LENGTH(pageTitle) > ".SnippetPreview::TITLE_MAX_LENGTH." OR CHAR_LENGTH(description) > ".SnippetPreview::DESCRIPTION_MAX_LENGTH.")", "cms_exclude_health_check=0"]
        ,   'value' => ['regular']
        ]);

        if( $oPages ) {

            while( $oPages->next() ) {

                $objPage = null;
                $objPage = PageModel::findWithDetails( $oPages->id );

                if( !varzegju::hasFeature('health_check_meta_too_long', $objPage->trail[0]) ) {
                    continue;
                }

                if( CMSConfig::get('testmode') && count($oCategory->items) == 1 ) {
                    break;
                }

                $oCategory->items[] = (object) [
                    'icon'  => Controller::addStaticUrlTo(Image::getPath(Controller::getPageStatusIcon($oPages)))
                ,   'type'  => 'page'
                ,   'name'  => $oPages->title
                ,   'href'  => $routePrefix . '?do=page&amp;act=edit&amp;id='.$oPages->id.'&amp;rt='.$this->getRequestToken().'&amp;ref='.$this->getRefererID().'#pal_meta_legend'
                ,   'title' => sprintf($GLOBALS['TL_LANG']['DCA']['edit'], $oPages->id)
                ];
            }
        }

        // find news
        if( class_exists('\Contao\News') && $db->fieldExists('pageTitle', 'tl_news') && $db->fieldExists('description', 'tl_news') ) {

            $column = ["(CHAR_LENGTH(pageTitle) > ".SnippetPreview::TITLE_MAX_LENGTH." OR CHAR_LENGTH(description) > ".SnippetPreview::DESCRIPTION_MAX_LENGTH.")"];
            $value = [];

            if( !empty(CMSConfig::get('health_check_ignore_older_than')) ){
                $column[] = "date>=?";
                $value[] = CMSConfig::get('health_check_ignore_older_than');
            }

            $oNews = null;
            $oNews = NewsModel::findAll([
                'column' => $column
            ,   'value' => $value
            ]);

            if( $oNews ) {

                while( $oNews->next() ) {

                    if( CMSConfig::get('testmode') && count($oCategory->items) == 1 ) {
                        break;
                    }

                    $oCategory->items[] = (object) [
                        'icon'  => 'bundles/contaonews/news.svg'
                    ,   'type'  => 'page'
                    ,   'name'  => $oNews->headline
                    ,   'href'  => $routePrefix . '?do=news&amp;table=tl_news&amp;act=edit&amp;id='.$oNews->id.'&amp;rt='.$this->getRequestToken().'&amp;ref='.$this->getRefererID().'#pal_meta_legend'
                    ,   'title' => sprintf($GLOBALS['TL_LANG']['DCA']['children'], $oNews->id)
                    ];
                }
            }
        }

        // find events
        if( class_exists('\Contao\Calendar') && $db->fieldExists('pageTitle', 'tl_calendar_events') && $db->fieldExists('description', 'tl_calendar_events') ) {

            $column = ["(CHAR_LENGTH(pageTitle) > ".SnippetPreview::TITLE_MAX_LENGTH." OR CHAR_LENGTH(description) > ".SnippetPreview::DESCRIPTION_MAX_LENGTH.")"];
            $value = [];

            if( !empty(CMSConfig::get('health_check_ignore_older_than')) ){
                $column[] = "startDate>=?";
                $value[] = CMSConfig::get('health_check_ignore_older_than');
            }

            $oEvents = null;
            $oEvents = CalendarEventsModel::findAll([
                'column' => $column
            ,   'value' => $value
            ]);

            if( $oEvents ) {

                while( $oEvents->next() ) {

                    if( CMSConfig::get('testmode') && count($oCategory->items) == 1 ) {
                        break;
                    }

                    $oCategory->items[] = (object) [
                        'icon'  => 'bundles/contaocalendar/calendar.svg'
                    ,   'type'  => 'page'
                    ,   'name'  => $oEvents->title
                    ,   'href'  => $routePrefix . '?do=calendar&amp;table=tl_calendar_events&amp;act=edit&amp;id='.$oEvents->id.'&amp;rt='.$this->getRequestToken().'&amp;ref='.$this->getRefererID().'#pal_meta_legend'
                    ,   'title' => sprintf($GLOBALS['TL_LANG']['DCA']['children'], $oEvents->id)
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

        $routePrefix = System::getContainer()->getParameter('contao.backend.route_prefix');

        $oCategory = (object) [
            'type' => 'missing_opengraph'
        ,   'collapsed' => empty($this->fsStates['missing_opengraph_legend'])
        ,   'legend' => $GLOBALS['TL_LANG']['cms_be_health_check']['missing_opengraph'][0]
        ,   'description' => $GLOBALS['TL_LANG']['cms_be_health_check']['missing_opengraph'][1]
        ,   'items' => []
        ];

        // find pages
        $oPages = null;
        $oPages = PageModel::findAll([
            'column' => ["type=?", "(og_title='' OR og_image=NULL)", "cms_exclude_health_check=0"]
        ,   'value' => ['regular']
        ]);

        if( $oPages ) {

            while( $oPages->next() ) {

                $objPage = null;
                $objPage = PageModel::findWithDetails( $oPages->id );

                if( !varzegju::hasFeature('health_check_open_graph_missing', $objPage->trail[0]) ) {
                    continue;
                }

                if( CMSConfig::get('testmode') && count($oCategory->items) == 1 ) {
                    break;
                }

                $oCategory->items[] = (object) [
                    'icon'  => Controller::addStaticUrlTo(Image::getPath(Controller::getPageStatusIcon($oPages)))
                ,   'type'  => 'page'
                ,   'name'  => $oPages->title
                ,   'href'  => $routePrefix . '?do=page&amp;act=edit&amp;id='.$oPages->id.'&amp;rt='.$this->getRequestToken().'&amp;ref='.$this->getRefererID().'#pal_meta_legend'
                ,   'title' => sprintf($GLOBALS['TL_LANG']['DCA']['edit'], $oPages->id)
                ];
            }
        }

        // find news
        if( class_exists('\Contao\News') ) {

            $oNews = null;
            $oNews = NewsModel::findAll([
                'column' => ["(og_title='' OR og_image=NULL)"]
            ]);

            if( $oNews ) {

                $this->loadLanguageFile('tl_news');

                while( $oNews->next() ) {

                    if( CMSConfig::get('testmode') && count($oCategory->items) == 1 ) {
                        break;
                    }

                    $oCategory->items[] = (object) [
                        'icon'  => 'bundles/contaonews/news.svg'
                    ,   'type'  => 'news'
                    ,   'name'  => $oNews->headline
                    ,   'href'  => $routePrefix . '?do=news&amp;table=tl_news&amp;act=edit&amp;id='.$oNews->id.'&amp;rt='.$this->getRequestToken().'&amp;ref='.$this->getRefererID().'#pal_opengraph_legend'
                    ,   'title' => sprintf($GLOBALS['TL_LANG']['DCA']['children'], $oNews->id)
                    ];
                }
            }
        }

        if( $oCategory->items ) {
            return $oCategory;
        }
    }
}
