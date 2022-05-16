<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2022 Leo Feyer
 *
 * @package   Contao Marketing Suite
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright 2022 numero2 - Agentur für digitales Marketing
 */


namespace numero2\MarketingSuite\Api;

use Contao\CMSConfig;
use Contao\Controller;
use Contao\Crypto;
use Contao\Database;
use Contao\Environment;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Exception;
use numero2\MarketingSuite\Backend\License as wegilej;
use numero2\MarketingSuite\Encryption;
use Symfony\Component\HttpClient\HttpClient;


class MarketingSuite {


    /**
     * API credentials
     * @var mixed
     */
    private $baseUrl;


    /**
     * Constructor
     */
    public function __construct() {

        Controller::loadLanguageFile('cms_api_messages');

        $this->baseUrl = System::getContainer()->getParameter('cms_api_endpoint');
    }


    /**
     * Gets the latest verison of the marketing suite bundle via api and stores
     * this and the current time in cmsconfig
     */
    public function getLatestVersion() {

        try {

            $response = null;
            $response = $this->send('/version');

            if( $response->status && $response->status === 'ok' ) {

                // set temporarily
                CMSConfig::set('latest_version', $response->latest_version);

                // save permanently
                CMSConfig::persist('latest_version', $response->latest_version);
            }

        } catch( Exception $e ) {

        }

        // set temporarily
        CMSConfig::set('last_version_check', time());

        // save permanently
        CMSConfig::persist('last_version_check', time());
    }


    /**
     * Checks if the license of the given root page is valid and stores returned data
     *
     * @param string $key The license key
     * @param \PageModel $oRootPage
     *
     * @return boolean
     */
    public function checkLicense( string $key, PageModel $oRootPage ) {

        if( !$key || !$oRootPage || $oRootPage->type !== "root" ) {
            return false;
        }

        $aData = [
            'license' => $key
        ,   'domain' => $oRootPage->dns?:Environment::get('host')
        ];

        if( !empty($oRootPage->cms_root_key) ) {
            $aData['key'] = $oRootPage->cms_root_key;
        }

        $response = null;
        $response = $this->send('/license', $aData);

        if( $response->status && $response->status === 'ok' ) {

            if( !empty($response->key) ) {

                $oCrypto = null;
                $oCrypto = new Crypto($response->key);

                if( !empty($response->sign) && $oCrypto->verify($response->key, $response->sign) ) {

                    $oRootPage->cms_root_key = $response->key;
                    $oRootPage->save();

                    return true;
                }

            } else {

                return true;
            }

        } else {

            if( $response->status === 'error' ) {

                throw new Exception(
                    $response->message
                ,   $response->code
                );
            }
        }

        return false;
    }


    /**
     * Gets the active features for the given root page
     *
     * @param string $key The license key
     * @param \PageModel $oRootPage
     */
    public function getFeatures( string $key, PageModel $oRootPage ) {

        if( !$key || !$oRootPage || $oRootPage->type !== "root" ) {
            return;
        }

        if( empty($oRootPage->cms_root_key) ) {
            return false;
        }

        $aData = [
            'license' => $key
        ,   'domain' => $oRootPage->dns?:Environment::get('host')
        ,   'key' => $oRootPage->cms_root_key
        ];

        $response = null;
        $response = $this->send('/features', $aData);

        if( $response->status && $response->status === 'ok' ) {

            if( !empty($response->data) && !empty($response->sign) ) {

                $oCrypto = null;
                $oCrypto = new Crypto($oRootPage->cms_root_key);

                if( $oCrypto->verify($response->data, $response->sign) ) {

                    $oRootPage->cms_root_data = $response->data;
                    $oRootPage->cms_root_sign = $response->sign;
                    $oRootPage->save();

                    return;
                }

            } else {

                return;
            }

        } else {

            if( $response->status === 'error' ) {

                throw new Exception(
                    $response->message
                ,   $response->code
                );
            }
        }
    }


    /**
     * Send usage data to the Marketing Suite Server
     */
    public function sendUsageData() {

        $aData = [
            "fingerprint" => $this->generateFingerprint()
        ,   "tstamp" => time()
        ,   "testmode" => CMSConfig::get('testmode')
        ,   "cms_version" => CMS_VERSION
        ,   "cto_version" => VERSION.'.'.BUILD
        ];

        $db = Database::getInstance();
        $aData['data'] = [];

        // tl_page num roots with key and without
        $result = $db->prepare("
            SELECT
                cms_root_license='' AS has_license,
                count(1) AS count
            FROM tl_page
            WHERE type=?
            GROUP BY cms_root_license=''
        ")->execute('root');

        if( $result->numRows ) {

            $aResult = $result->fetchAllAssoc();

            $aData['data']['tl_page']['roots_without_license'] = 0;
            $aData['data']['tl_page']['roots_has_license'] = 0;

            foreach( $aResult as $value ) {

                $key = $value['has_license'] == '0'?'roots_without_license':'roots_has_license';

                $aData['data']['tl_page'][$key] = $value['count'];
            }
        }

        // tl_cms_marketing_item num ele and which
        if( wegilej::hasFeature('marketing_element') ) {

            $result = $db->prepare("
                SELECT
                    type,
                    active,
                    init_step='' AS init_done,
                    count(1) AS count
                FROM tl_cms_marketing_item
                GROUP BY type, active, init_step=''
            ")->execute();

            if( $result->numRows ) {

                $aResult = $result->fetchAllAssoc();

                $mi = System::importStatic('\numero2\MarketingSuite\DCAHelper\MarketingItem');
                $types = $mi->getMarketingItemTypes();

                foreach( $types as $key => $value ) {
                    if( $key == 'default' ) {
                        continue;
                    }
                    $aData['data']['marketing_element'][$key] = [];
                }

                foreach( $aResult as $value ) {

                    $key_1 = $value['type'];
                    $key_2 = $value['active']=='1'?'active':'inactive';
                    $key_3 = $value['init_done']=='1'?'init_done':'init';

                    $aData['data']['marketing_element'][$key_1][$key_2][$key_3] = $value['count'];
                }
            }
        } else {
            $aData['data']['marketing_element'] = false;
        }

        // tl_cms_conversion_item num ele and which and ptable
        if( wegilej::hasFeature('conversion_element') ) {

            $mi = System::importStatic('\numero2\MarketingSuite\DCAHelper\ConversionItem');
            $types = $mi->getConversionElementTypes()['conversion_elements'];

            $result = $db->prepare("
                SELECT
                    type,
                    ptable,
                    invisible='1' AS invisible,
                    count(1) AS count
                FROM tl_content
                WHERE type in ('".implode('\',\'', $types)."')
                GROUP BY type, ptable, invisible='1'
            ")->execute();

            if( $result->numRows ) {

                $aResult = $result->fetchAllAssoc();

                foreach( $types as $key => $value ) {
                    if( $key == 'default' ) {
                        continue;
                    }
                    $aData['data']['conversion_element'][$value] = [];
                }

                foreach( $aResult as $value ) {

                    $key_1 = $value['type'];
                    $key_2 = $value['ptable'];
                    $key_3 = $value['invisible']=='1'?'invisible':'visible';

                    $aData['data']['conversion_element'][$key_1][$key_2][$key_3] = $value['count'];
                }
            }
        } else {
            $aData['data']['conversion_element'] = false;
        }

        // tl_cms_tags num with type
        if( wegilej::hasFeature('tags') ) {

            $result = $db->prepare("
                SELECT
                    type,
                    active='1' AS active,
                    enable_on_cookie_accept='' AS always_on,
                    count(1) AS count
                FROM tl_cms_tag
                GROUP BY type, enable_on_cookie_accept='', active='1'
            ")->execute();

            if( $result->numRows ) {

                $aResult = $result->fetchAllAssoc();

                foreach( $aResult as $value ) {

                    $key_1 = $value['type'];
                    $key_2 = $value['always_on']=='1'?'always_on':'need_accept';
                    $key_3 = $value['active']=='1'?'active':'inactive';

                    $aData['data']['tags'][$key_1][$key_2][$key_3] = $value['count'];
                }
            }
        } else {
            $aData['data']['tags'] = false;
        }

        // tl_cms_tag_settings type
        if( wegilej::hasFeature('tag_settings') ) {
            $aData['data']['tag_settings']['type'] = CMSConfig::get('cms_tag_type');
        } else {
            $aData['data']['tag_settings'] = false;
        }

        // tl_cms_facebook is setup
        if( wegilej::hasFeature('news_publish_facebook') ) {

            $pages = CMSConfig::get('cms_fb_pages_available') ? StringUtil::deserialize(Encryption::decrypt(CMSConfig::get('cms_fb_pages_available'))) : null;

            if( is_array($pages) ) {

                $aData['data']['fb_setup']['page_count'] = count($pages);

                $result = $db->prepare("
                    SELECT
                        ISnull(cms_facebook_pages) AS has_no_pages,
                        count(1) AS count
                    FROM tl_news_archive
                    GROUP BY ISnull(cms_facebook_pages)
                ")->execute();

                if( $result->numRows ) {

                    $aResult = $result->fetchAllAssoc();

                    foreach( $aResult as $value ) {

                        $key = $value['has_no_pages']=='1'?'has_no_pages':'has_pages';

                        $aData['data']['fb_setup']['tl_news_archiv'][$key] = $value['count'];
                    }
                }

                $result = $db->prepare("
                    SELECT
                        cms_publish_facebook='' AS no_publish_facebook,
                        count(1) AS count
                    FROM tl_news
                    GROUP BY cms_publish_facebook=''
                ")->execute();

                if( $result->numRows ) {

                    $aResult = $result->fetchAllAssoc();

                    foreach( $aResult as $value ) {

                        $key = $value['no_publish_facebook']=='1'?'no_publish_facebook':'publish_facebook';

                        $aData['data']['fb_setup']['tl_news'][$key] = $value['count'];
                    }
                }

            } else {

                $aData['data']['fb_setup']['page_count'] = 0;
            }

        } else {
            $aData['data']['fb_setup'] = false;
        }


        // link shortener
        if( wegilej::hasFeature('link_shortener') ) {

            $result = $db->prepare("
                SELECT
                    active='1' AS active,
                    count(1) AS count
                FROM tl_cms_link_shortener
                GROUP BY active='1'
            ")->execute();

            if( $result->numRows ) {

                $aResult = $result->fetchAllAssoc();

                foreach( $aResult as $value ) {

                    $key = $value['active']=='1'?'active':'inactive';

                    $aData['data']['link_shortener'][$key] = $value['count'];
                }
            }
        } else {
            $aData['data']['link_shortener'] = false;
        }

        $this->send('/egasu', $aData);
    }


    /**
     * Generates a unique identifier that is not reversible to any installation or account
     *
     * @return string
     */
    protected function generateFingerprint() {

        $fingerprint = System::getContainer()->getParameter('secret');
        $md5 = str_repeat(md5($fingerprint), 2);
        $sha = hash('sha256', $fingerprint);

        $fingerprint = md5($md5 ^ $sha);

        return $fingerprint;
    }


    /**
     * Send request to the API
     *
     * @param string $uri
     * @param array  $aData
     *
     * @return string
     */
    private function send( $uri=null, $aData=null ) {

        $client = null;
        $client = HttpClient::create([
            'headers' => [
                'user-agent' => 'Contao Marketig Suite '.CMS_VERSION
            ,   'accept' => 'application/json'
            ]
        ,   'timeout' => 5
        ,   'max_duration' => 5
        ]);

        $url = $this->baseUrl . $uri;

        try {

            $response = null;

            if( $aData === null ) {

                $response = $client->request('GET', $url);

            } else {

                $response = $client->request('POST', $url, [
                    'headers' => [
                        'content-type' => 'application/json; charset=utf-8'
                    ]
                ,   'body' => json_encode($aData)
                ]);
            }

        } catch( Exception $e ) {

            // if SSL connection fails retry using HTTP
            if( stripos($e->getMessage(), 'ssl') !== false && stripos($this->baseUrl,'https://') !== false ) {

                System::log('SSL Exception while retrieving data from Marketing Suite Server, retrying with HTTP (' . $e->getMessage() . ')', __METHOD__, TL_ERROR);

                $this->baseUrl = str_replace('https://', 'http://', $this->baseUrl);
                return $this->send($uri,$aData);
            }

            throw new Exception(
                $e->getMessage()
            ,   1000
            );
        }

        $jsonResponse = json_decode( $response->getContent(false) );

        if( json_last_error() === JSON_ERROR_NONE ) {

            return $jsonResponse;

        } else {

            System::log('Received invalid data from Marketing Suite Server', __METHOD__, TL_ERROR);

            throw new Exception(
                'Received invalid data from Marketing Suite Server'
            ,   1000
            );
        }
    }
}
