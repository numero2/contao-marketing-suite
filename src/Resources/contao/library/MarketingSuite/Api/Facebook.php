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
namespace numero2\MarketingSuite\Api;

use numero2\MarketingSuite\Encryption;


class Facebook {


    /**
     * API credentials
     * @var string
     */
    private $appID = NULL;
    private $appSecret = NULL;
    private $accessToken = NULL;


    /**
     * API instance
     * @var \Facebook\Facebook
     */
    private $oFB = NULL;


    /**
     * Constructor
     */
    public function __construct() {

        $this->appID = \CMSConfig::get('cms_fb_app_id');
        $this->appSecret = Encryption::decrypt( \CMSConfig::get('cms_fb_app_secret') );
        $this->accessToken = \CMSConfig::get('cms_fb_token') ? Encryption::decrypt( \CMSConfig::get('cms_fb_token') ) : NULL;

        if( $this->appID && $this->appSecret ) {

            $this->oFB = new \Facebook\Facebook([
                'app_id' => $this->appID
            ,   'app_secret' => $this->appSecret
            ,   'default_graph_version' => 'v3.1'
            ,   'default_access_token' => $this->accessToken
            ]);
        }

        $this->setAccessToken();
    }


    /**
     * Sets the access token after we're redirected
     * to the configured redirect_url
     */
    private function setAccessToken() {

        // check if we're at the correct url and got the code parameter
        if( strpos(\Environment::get('url').\Environment::get('requestUri'), self::getOAuthRedirectUrl()) === false || !\Input::get('code') ) {
            return false;
        }

        if( !$this->oFB ) {
            return false;
        }

        $oHelper = NULL;
        $oHelper = $this->oFB->getRedirectLoginHelper();

        $accessToken = NULL;

        // try to get access token
        try {

            $accessToken = $oHelper->getAccessToken();

        } catch( \Facebook\Exceptions\FacebookResponseException $e ) {
            \System::log('Graph returned an error: ' . $e->getMessage(), __METHOD__, TL_ERROR);

        } catch( \Facebook\Exceptions\FacebookSDKException $e ) {
            \System::log('Facebook SDK returned an error: ' . $e->getMessage(), __METHOD__, TL_ERROR);
        }

        if( $accessToken ) {

            if( !$accessToken->isLongLived() ) {

                // exchange a short-lived access token for a long-lived one
                try {

                    $oAuth2Client = NULL;
                    $oAuth2Client = $this->oFB->getOAuth2Client();

                    $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);

                } catch( \Facebook\Exceptions\FacebookSDKException $e ) {

                    \System::log('Facebook SDK returned an error: ' . $e->getMessage(), __METHOD__, TL_ERROR);
                    return;
                }
            }

            $accessToken = $accessToken->getValue();

            $this->oFB->setDefaultAccessToken( $accessToken );
            \CMSConfig::persist('cms_fb_token', Encryption::encrypt($accessToken));

            \Controller::redirect( self::getOAuthRedirectUrl() );
        }
    }


    /**
     * Returns if we have an access_token
     *
     * @return bool
     */
    public function hasAccessToken() {
        return $this->accessToken ? true : false;
    }


    /**
     * Gets information about the used access_token
     *
     * @return array|false
     */
    public function getTokenInformation() {

        if( !$this->oFB ) {
            return false;
        }

        try {

            $response = $this->oFB->get('/debug_token?input_token='.$this->accessToken);

            $token = $response->getGraphNode()->asArray();

            return $token;

        } catch( \Facebook\Exceptions\FacebookResponseException $e ) {

            // Session has expired, that's expected
            if( $e->getCode() != 190 ) {
                \System::log('Graph returned an error: ' . $e->getMessage(), __METHOD__, TL_ERROR);
            }

            return false;

        } catch( \Facebook\Exceptions\FacebookSDKException $e ) {

            \System::log('Facebook SDK returned an error: ' . $e->getMessage(), __METHOD__, TL_ERROR);
        }

        return false;
    }


    /**
     * Checks if the given app credentials are valid by trying to get
     * information about the app itself
     *
     * @return bool
     */
    public function testCredentials() {

        if( !$this->oFB ) {
            return false;
        }

        try {

            $response = $this->oFB->get(
                '/'.$this->appID
            ,   $this->oFB->getApp()->getAccessToken()
            );

            return true;

        } catch( \Facebook\Exceptions\FacebookResponseException $e ) {

            // Invalid OAuth access token signature, that's expected
            if( $e->getCode() != 190 ) {
                \System::log('Graph returned an error: ' . $e->getMessage(), __METHOD__, TL_ERROR);
                return $e->getMessage();
            }

            return false;

        } catch( \Facebook\Exceptions\FacebookSDKException $e ) {

            \System::log('Facebook SDK returned an error: ' . $e->getMessage(), __METHOD__, TL_ERROR);
        }

        return;
    }


    /**
     * Returns the url used for the OAuth process
     *
     * @return string
     */
    public static function getOAuthRedirectUrl() {

        $url = \Environment::get('url') . \Environment::get('path');
        $url = str_replace('http:','https:',$url);
        $url = $url . '/contao?do=' . \Input::get('do') . '&mod=' . \Input::get('mod') . '&table=' . \Input::get('table');

        return $url;
    }


    /**
     * Generates the login url
     *
     * @return string
     */
    public function getLoginURL() {

        if( !$this->oFB ) {
            return false;
        }

        $oHelper = NULL;
        $oHelper = $this->oFB->getRedirectLoginHelper();

        return $oHelper->getLoginUrl(
            self::getOAuthRedirectUrl()
        ,   ['manage_pages','publish_pages']
        );
    }


    /**
     * Returns a list of all available pages
     *
     * @return array|bool
     */
    public function getPages() {

        if( !$this->oFB ) {
            return false;
        }

        try {

            $response = $this->oFB->get('/me/accounts?fields=id,global_brand_page_name,access_token,picture');
            $response = $response->getGraphEdge();

            $aPages = [];

            do {

                foreach( $response as $page ) {

                    $page = $page->asArray();

                    $aPages[ $page['id'] ] = [
                        'name' => $page['global_brand_page_name']
                    ,   'access_token' => $page['access_token']
                    ,   'picture' => !empty($page['picture']['url']) ? $page['picture']['url'] : NULL
                    ];
                }

            } while( $response = $this->oFB->next($response) );

            return $aPages;

        } catch( \Exception $e ) {

            \System::log('Facebook SDK returned an error: ' . $e->getMessage(), __METHOD__, TL_ERROR);
        }

        return false;
    }


    /**
     * Creates a new post in a page feed
     *
     * @param array $arrData
     * @param string $pageToken
     *
     * @return string|false
     */
    public function createPost( array $arrData=[], $pageToken=NULL ) {

        if( !$this->oFB || empty($arrData) || !$pageToken ) {
            return false;
        }

        try {

            $response = $this->oFB->post(
                '/me/feed'
            ,   $arrData
            ,   $pageToken
            );

            return $response->getGraphNode()->getField('id');

        } catch( \Exception $e ) {

            \System::log('Facebook SDK returned an error: ' . $e->getMessage(), __METHOD__, TL_ERROR);
        }

        return false;
    }


    /**
     * Deletes the post with the given id
     *
     * @param string $postId
     * @param string $pageToken
     *
     * @return bool
     */
    public function deletePost( $postId=NULL, $pageToken=NULL ) {

        if( !$this->oFB || !$postId || !$pageToken ) {
            return false;
        }

        try {

            $response = $this->oFB->delete(
                '/'.$postId
            ,   []
            ,   $pageToken
            );

            return $response->getGraphNode()->getField('success') ? true : false;

        } catch( \Exception $e ) {

            \System::log('Facebook SDK returned an error: ' . $e->getMessage(), __METHOD__, TL_ERROR);
        }

        return false;
    }
}
