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

use Contao\Backend;
use Contao\CMSConfig;
use Contao\Controller;
use Contao\Database;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Environment;
use Contao\Input;
use Contao\Message;
use Contao\News;
use Contao\NewsArchiveModel;
use Contao\NewsModel;
use Contao\StringUtil;
use numero2\MarketingSuite\Api\Facebook as FacebookAPI;
use numero2\MarketingSuite\Encryption;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


class Facebook {


    /**
     * Generates markup that is prepended to the form
     *
     * @return string
     */
    public function generatePreForm() {

        $return = self::generateMessages();

        $oFB = NULL;
        $oFB = new FacebookAPI();

        $return .= '<div id="tl_buttons">';

        if( $oFB->hasAccessToken() ) {

            $return .= '<a href="'.Backend::addToUrl('act=revoke').'" class="header_icon" style="background-image: url(bundles/marketingsuite/img/backend/icons/icon_revoke_authentication.svg);" title="'.StringUtil::specialchars($GLOBALS['TL_LANG']['tl_cms_facebook']['invalidate_authentication']).'" onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['tl_cms_facebook']['invalidate_authentication_confirm'] . '\'))return false; Backend.getScrollOffset()">'.$GLOBALS['TL_LANG']['tl_cms_facebook']['invalidate_authentication'].'</a>';
        }

        $return .= '
            <a href="'.Controller::getReferer(true).'" class="header_back" title="'.StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']).'" accesskey="b" onclick="Backend.getScrollOffset()">'.$GLOBALS['TL_LANG']['MSC']['backBT'].'</a>
        </div>';

        return $return;
    }


    /**
    * Generates list of module specific messages
    *
    * @return string
    */
    public function generateMessages() {
        return Message::generate('CMS_FACEBOOK');
    }


    /**
     * Shows a message if opengraph extension is not available
     */
    public function showOpenGraphHint() {

        if( !class_exists('\numero2\OpenGraph3\OpenGraph3') ) {
            Message::addInfo($GLOBALS['TL_LANG']['tl_cms_facebook']['msg']['opengraph_missing']);
        }
    }


    /**
     * Removes the option to publish to facebook if the parent archive has
     * no pages enabled at all
     *
     * @param \DataContainer $dc
     */
    public function checkNewsArchiveHasPages( DataContainer $dc ) {

        $hasPages = false;

        // tl_news
        if( $dc->parentTable == "tl_news_archive" ) {

            $oNews = NULL;
            $oNews = NewsModel::findById( $dc->id );

            $hasPages = $this->getAvailablePagesOptionsForNews($oNews->pid) ? true : false;

        // tl_news_archive
        } else {

            $hasPages = $this->getPagesOptions(true) ? true : false;
        }

        if( !$hasPages ) {

            $GLOBALS['TL_DCA'][$dc->table]['palettes']['default'] = str_replace(
                ['cms_facebook_pages','cms_publish_facebook']
            ,   ''
            ,   $GLOBALS['TL_DCA'][$dc->table]['palettes']['default']
            );
        }
    }


    /**
     * Checks if the access token (if available) is still valid
     *
     * @return boolean
     */
    public function checkTokenStatus() {

        $oFB = NULL;
        $oFB = new FacebookAPI();

        if( $oFB->hasAccessToken() ) {

            $aToken = [];
            $aToken = $oFB->getTokenInformation();

            if( $aToken && $aToken['is_valid'] ) {

                // check when token is going to expire
                if( $aToken['expires_at']->getTimestamp() !== 0 ) {

                    $currDate = new \DateTime();
                    $diff = $currDate->diff($aToken['expires_at']);

                    if( $diff->h < 1 ) {
                        Message::addInfo(sprintf($GLOBALS['TL_LANG']['tl_cms_facebook']['msg']['token_expires_minutes'],$diff->i), 'CMS_FACEBOOK');
                    } else if( $diff->d < 1 ) {
                        Message::addInfo(sprintf($GLOBALS['TL_LANG']['tl_cms_facebook']['msg']['token_expires_hours'],$diff->h), 'CMS_FACEBOOK');
                    } else {
                        Message::addInfo(sprintf($GLOBALS['TL_LANG']['tl_cms_facebook']['msg']['token_expires_days'],$diff->d), 'CMS_FACEBOOK');
                    }
                }

                return true;

            } else {

                Message::addError($GLOBALS['TL_LANG']['tl_cms_facebook']['msg']['token_expired'], 'CMS_FACEBOOK');
                return false;
            }
        }

        return false;
    }


    /**
     * Removes fields from the palette depending on the status
     * of the app / authorization
     */
    public function modifyPalette() {

        // change back button
        $GLOBALS['TL_MOOTOOLS'][] = "<script>document.querySelector('a.header_back').href = 'contao?do=cms_settings';</script>";

        // hide authorization / pages if no app credentials given
        if( !CMSConfig::get('cms_fb_app_id') || !CMSConfig::get('cms_fb_app_secret') ) {

            unset($GLOBALS['TL_DCA']['tl_cms_facebook']['fields']['cms_fb_pages_available']);
            unset($GLOBALS['TL_DCA']['tl_cms_facebook']['fields']['authorization']);

            return;

        } else {

            $oFB = NULL;
            $oFB = new FacebookAPI();

            // check if app credentials are valid
            if( ($res = $oFB->testCredentials()) !== true ) {

                // false = really, just wrong credentials
                if( $res === false ) {

                    Message::addError($GLOBALS['TL_LANG']['tl_cms_facebook']['msg']['credentials_invalid'], 'CMS_FACEBOOK');

                // everything else is an error message from the api
                } else {
                    Message::addError($res, 'CMS_FACEBOOK');
                }

                unset($GLOBALS['TL_DCA']['tl_cms_facebook']['fields']['authorization']);
                unset($GLOBALS['TL_DCA']['tl_cms_facebook']['fields']['fb_pages_available']);
                return;
            }

            // token invalid, hide pages
            if( !$this->checkTokenStatus() ) {
                unset($GLOBALS['TL_DCA']['tl_cms_facebook']['fields']['fb_pages_available']);
            }
        }
    }


    /**
     * Renders the authorization field used in the DCA
     *
     * @return string
     */
    public function generateAuthorizationField() {

        $oFB = NULL;
        $oFB = new FacebookAPI();

        $textInfo = $GLOBALS['TL_LANG']['tl_cms_facebook']['authorization']['initial']['message'];
        $textButton = $GLOBALS['TL_LANG']['tl_cms_facebook']['authorization']['initial']['button'];

        if( $oFB->hasAccessToken() ) {

            $aToken = [];
            $aToken = $oFB->getTokenInformation();

            // never expiring token
            if( $aToken && $aToken['expires_at']->getTimestamp() === 0 ) {
                return false;
            }

            // show renewal dialog if token is about to expire in less than 10 days
            if( $aToken && $aToken['is_valid'] ) {

                $currDate = new \DateTime();
                $diff = $currDate->diff($aToken['expires_at']);

                if( $diff->d <= 10 ) {

                    $textInfo = $GLOBALS['TL_LANG']['tl_cms_facebook']['authorization']['renewal']['message'];
                    $textButton = $GLOBALS['TL_LANG']['tl_cms_facebook']['authorization']['renewal']['button'];
                }
            }
        }

        $strWidget = '<div class="widget facebook-authorization">
            <p>' .$textInfo. '</p>
            <a href="' .$oFB->getLoginURL(). '" class="tl_submit">' .$textButton. '</a>
        </div>';

        return $strWidget;
    }


     /**
      * Generates array of pages for use in checkbox wizard
      *
      * @param boolean $fromConfig
      *
      * @return array
      */
    public function getPagesOptions( $fromConfig=false ) {

        $arrOptions = [];

        // get pages from config
        if( $fromConfig === true ) {

            $aPages = [];
            $aPages = CMSConfig::get('cms_fb_pages_available') ? deserialize(Encryption::decrypt(CMSConfig::get('cms_fb_pages_available'))) : [];

        // get pages via API
        } else {

            $oFB = NULL;
            $oFB = new FacebookAPI();

            $aPages = [];
            $aPages = $oFB->getPages();
        }

        if( $aPages ) {

            foreach( $aPages as $id => $page ) {
                $arrOptions[ $id ] = '<img src="' .$page['picture'] . '" alt="' .$page['name'] . '" /><span class="name">' .$page['name'] . '</span>';
            }
        }

        return $arrOptions;
    }


    /**
     * Generates array of pages for use in checkbox wizar
     *
     * @return array
     */
    public function getAvailablePagesOptions() {
        return self::getPagesOptions(true);
    }


    /**
     * Generates array of pages for use in checkbox wizard
     *
     * @param \DataContainer|integer $dc
     *
     * @return array
     */
    public function getAvailablePagesOptionsForNews( $dc ) {

        $pid = $dc instanceof DataContainer ? $dc->activeRecord->pid : $dc;

        $aPages = [];
        $aPages = self::getPagesOptions(true);

        $oArchive = NULL;
        $oArchive = NewsArchiveModel::findById($pid);

        if( $aPages && $oArchive ) {

            $aEnabled = [];
            $aEnabled = deserialize($oArchive->cms_facebook_pages);

            foreach( $aPages as $id => $page ) {

                if( empty($aEnabled) || !in_array($id, $aEnabled) ) {
                    unset($aPages[$id]);
                }
            }

            return $aPages;
        }

        return false;
    }


    /**
     * Decrypts and returns the id of the selected pages
     *
     * @param string $value
     *
     * @return array
     */
    public function loadAvailablePages( $value ) {

        if( !$value ) {
            return [];
        }

        $value = Encryption::decrypt($value);
        $value = deserialize($value);

        return array_keys($value);
    }


    /**
     * Extends the selected pages with name, picture and access_token
     * before encrypting it for storage in config
     *
     * @param string $value
     *
     * @return string|bool
     */
    public function saveAvailablePages( $value ) {

        $oFB = NULL;
        $oFB = new FacebookAPI();

        $aPages = [];
        $aPages = $oFB->getPages();

        if( $aPages && $value ) {

            $aSelectedPages = deserialize($value);
            $aParsedPages = [];

            foreach( $aPages as $id => $page ) {

                if( !in_array($id, $aSelectedPages) ) {
                    continue;
                }

                $aParsedPages[ $id ] = $page;
            }

            return Encryption::encrypt( serialize($aParsedPages) );
        }

        return false;
    }


    /**
     * Returns a list of simple tokens for use in backend help
     *
     * @return array
     */
    public static function parseSimpleTokens() {

        return [
            'oauth_redirect_uri' => FacebookAPI::getOAuthRedirectUrl()
        ];
    }


    /**
     * Revokes the current authentication
     *
     * @param boolean $force
     */
    public function revokeAuthentication( $force=false ) {

        if( Input::get('act') != 'revoke' && $force !== true ) {
            return;
        }

        // add message
        Message::addInfo($GLOBALS['TL_LANG']['tl_cms_facebook']['msg']['authentication_revoked'], 'CMS_FACEBOOK');

        // remove tokens from config
        CMSConfig::remove('cms_fb_token');
        CMSConfig::remove('cms_fb_pages_available');

        Controller::redirect( Backend::addToUrl('act=') );
    }


    /**
     * Marks the current news to be published to Facebook depending on the
     * checkbox's value
     *
     * @param string $value
     * @param \DC_Table $dc
     *
     * @return string
     */
    public function queueForPublishing( $value, DC_Table $dc ) {

        if( $value && $dc->activeRecord->cms_publish_facebook ) {
            Database::getInstance()->prepare("UPDATE ".$dc->table." SET cms_facebook_queue_publish = 1 WHERE id = ? ")->execute($dc->activeRecord->id);
        } else {
            Database::getInstance()->prepare("UPDATE ".$dc->table." SET cms_facebook_queue_publish = '' WHERE id = ? ")->execute($dc->activeRecord->id);
        }

        return $value;
    }


    /**
     * Publishes / Updates the post(s) on the selected page(s)
     *
     * @param string $value
     * @param \DC_Table $dc
     *
     * @return string
     * @throws \Exception if publishing failed
     */
    public function publishUpdatePost( $value, DC_Table $dc ) {

        if( !$dc->activeRecord->cms_facebook_queue_publish ) {
            return $value;
        }

        $aPagesSelected = [];
        $aPagesSelected = $dc->activeRecord->cms_facebook_pages ? deserialize($dc->activeRecord->cms_facebook_pages) : NULL;

        if( !$aPagesSelected || !count($aPagesSelected) ) {
            return $value;
        }

        $oFB = NULL;
        $oFB = new FacebookAPI();

        $aPages = [];
        $aPages = CMSConfig::get('cms_fb_pages_available') ? deserialize(Encryption::decrypt(CMSConfig::get('cms_fb_pages_available'))) : [];

        $aPosts = [];
        $aPosts = $dc->activeRecord->cms_facebook_posts ? deserialize($dc->activeRecord->cms_facebook_posts) : NULL;

        // delete previous posts because they can't be updated
        if( !empty($aPosts) ) {

            foreach( $aPosts as $postId ) {

                $pageId = explode('_', $postId)[0];
                $oFB->deletePost($postId,$aPages[$pageId]['access_token']);
            }
        }

        if( $aPages ) {

            $oNews = NULL;
            $oNews = NewsModel::findById( $dc->activeRecord->id );

            // prepare the post data
            $arrPostData = [];
            $arrPostData = $this->preparePostData( $oNews );

            if( $arrPostData ) {

                $aPostIds = [];

                // create new post on each selected page
                foreach( $aPagesSelected as $pageId ) {

                    $postId = NULL;
                    $postId = $oFB->createPost($arrPostData,$aPages[$pageId]['access_token']);

                    if( $postId ) {

                        $aPostIds[] = $postId;

                    } else {

                        Message::addError(sprintf(
                            $GLOBALS['TL_LANG']['tl_cms_facebook']['msg']['publish_failed']
                        ,   $aPages[$pageId]['name']
                        ));
                    }
                }

                // store post id in database
                // reset publish marker
                if( $aPostIds ) {
                    Database::getInstance()->prepare("UPDATE tl_news SET cms_facebook_posts = ?, cms_facebook_queue_publish = '' WHERE id = ?")->execute( serialize($aPostIds), $dc->activeRecord->id );
                }
            }
        }

        return $value;
    }


    /**
     * Prepares data for generating the post
     *
     * @param \NewsModel $oNews
     *
     * @return array
     */
    private function preparePostData( NewsModel $oNews ) {

        $arrData = [];

        // generate link
        $href = News::generateNewsUrl( $oNews, false, true );

        if( strpos($href, 'http') === FALSE ) {
            $href = Environment::get('url') . '/' . $href;
        }

        $arrData['link'] = $href;

        if( $oNews->start && $oNews->start > time() ) {

            $arrData['published'] = false;
            $arrData['scheduled_publish_time'] = $oNews->start;

        } else {

            $arrData['published'] = true;
        }

        return $arrData;
    }
}
