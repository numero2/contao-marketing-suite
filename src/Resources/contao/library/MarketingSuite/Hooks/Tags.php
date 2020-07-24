<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2019 Leo Feyer
 *
 * @package   Contao Marketing Suite
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright 2020 numero2 - Agentur für digitales Marketing
 */


namespace numero2\MarketingSuite\Hooks;

use Contao\CMSConfig;
use Contao\Config;
use Contao\Controller;
use Contao\Environment;
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\LayoutModel;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\PageRegular;
use numero2\MarketingSuite\Backend\License as aczolku;
use numero2\MarketingSuite\ModuleAcceptTags;
use numero2\MarketingSuite\ModuleCookieBar;
use numero2\MarketingSuite\TagModel;


class Tags extends Hooks {


    /**
     * Generates and adds all script tags to the page
     *
     * @param \PageModel $objPageOriginal
     * @param \LayoutModel $objLayout
     * @param \PageRegular $objPageRegular
     */
    public function generateScripts( PageModel $objPageOriginal, LayoutModel $objLayout, PageRegular $objPageRegular ) {

        global $objPage;

        if( !aczolku::hasFeature('tags', $objPage->trail[0]) ) {
            return;
        }

        $objTemplate = NULL;
        $objTemplate = new FrontendTemplate('mod_cms_tags');
        aczolku::ezahew();

        $aTags = [];

        $aTagTypes = [];
        $aTagTypes = self::getAllowedTags();

        if( $aTagTypes && count($aTagTypes) ) {

            foreach( $aTagTypes as $type => $tags ) {

                // as base tracking code should only be printed once per page
                $i = 1;

                foreach( $tags as $key => $tag ) {

                    if( in_array($tag->type, ['session','content_module_element']) ) {
                        continue;
                    }

                    if( empty($tag->customTpl) ) {
                        $tag->customTpl = 'tag_'.$tag->type;
                    }

                    $tagTemplate = NULL;
                    $tagTemplate = new FrontendTemplate($tag->customTpl);

                    $aTag = $tag->row();

                    if( strlen($aTag['html']) ) {
                        $aTag['html'] = self::replaceInsertTags($aTag['html']);
                    }

                    $tagTemplate->setData($aTag);

                    $tagTemplate->typeFirst = false;
                    if( $i == 1 ) {
                        $tagTemplate->typeFirst = true;
                    }

                    $tagTemplate->typeLast = false;
                    if( $i == count($tags) ) {
                        $tagTemplate->typeLast = true;
                    }

                    if( Config::get('debugMode') ) {
                        $aTags[$tag->type.'_'.$tag->id] = '<!-- tag: '.$tag->type.'_'.$tag->id.' -->' . $tagTemplate->parse() . '<!-- endtag -->';
                    } else {
                        $aTags[$tag->type.'_'.$tag->id] = $tagTemplate->parse();
                    }

                    $i += 1;
                }
            }
        }

        $objTemplate->tags = $aTags;

        // make sure we don't index the page if we force showing the consent
        if( Input::get('_cmsscb') ) {
            $objPage->robots = 'noindex,nofollow';
        }

        $GLOBALS['TL_BODY'][] = $objTemplate->parse();
    }


    /**
     * get all allowed tags for the global objPage grouped by type
     *
     * @return array
     */
    public static function getAllowedTags() {

        global $objPage;

        if( !aczolku::hasFeature('tags', $objPage->trail[0]) ) {
            return;
        }

        $aTagTypes = [];

        $objTags = NULL;
        $objTags = TagModel::findAllActiveByPage($objPage->id);

        if( $objTags && count($objTags) ) {

            // prepare which page is allowed on which scope
            $allowed = [
                'current_page' => []
            ,   'current_and_direct_children' => []
            ,   'current_and_all_children' => []
            ];

            foreach( array_reverse($objPage->trail) as $value ) {

                if( $value == $objPage->id ) {
                    $allowed['current_page'][] = $value;
                }
                if( count($allowed['current_page']) && count($allowed['current_and_direct_children'] ) < 2 ) {
                    $allowed['current_and_direct_children'][] = $value;
                }
                if( count($allowed['current_page']) ) {
                    $allowed['current_and_all_children'][] = $value;
                }
            }

            foreach( $objTags as $key => $tag ) {

                // skip on not enough data
                if( empty($tag->pages) || empty($allowed[$tag->pages_scope]) ) {
                    continue;
                }

                if( !aczolku::hasFeature('tags_'.$tag->type, $objPage->trail[0]) ) {
                    continue;
                }

                // skip if cookie needed but not cookie_accepted
                if( $tag->enable_on_cookie_accept && !self::isAccepted($tag->id, $tag->pid) ) {
                    continue;
                }

                $tagPages = deserialize($tag->pages);

                // check all pages if one is allowed
                foreach( $allowed[$tag->pages_scope] as $key => $value ) {

                    if( in_array($value, $tagPages) ) {

                        $aTagTypes[$tag->type][] = $tag;
                        break;
                    }
                }
            }
        }

        return $aTagTypes;
    }


    /**
     * Generates the selected EU consent module
     *
     * @param \PageModel $objPage
     * @param \LayoutModel $objLayout
     * @param \PageRegular $objPageRegular
     */
    public function generateEUConsent( PageModel $objPage, LayoutModel $objLayout, PageRegular $objPageRegular ) {

        if( !aczolku::hasFeature('tags', $objPage->trail[0]) || !aczolku::hasFeature('tag_settings', $objPage->trail[0]) ) {
            return;
        }

        // initialize model needed for module
        $objModel = NULL;
        $objModel = new ModuleModel();
        $objModel->preventSaving(false);

        Controller::loadDataContainer('tl_cms_tag_settings');
        aczolku::udifuro();

        // get settings for module from config
        if( $GLOBALS['TL_DCA']['tl_cms_tag_settings']['fields'] && count($GLOBALS['TL_DCA']['tl_cms_tag_settings']['fields']) ) {

            foreach( $GLOBALS['TL_DCA']['tl_cms_tag_settings']['fields'] as $key => $value ) {

                if( !empty($value['mapping']) ) {
                    $objModel->{$value['mapping']} = CMSConfig::get($key);
                } else {
                    $objModel->{$key} = CMSConfig::get($key);
                }
            }
        }

        // find correct module
        if( !empty($GLOBALS['FE_MOD']['marketing_suite'][$objModel->type]) ) {

            $strClass = $GLOBALS['FE_MOD']['marketing_suite'][$objModel->type];

            // init module
            if( $strClass ) {

                $oModule = NULL;
                $oModule = new $strClass($objModel);

                if( $oModule ) {

                    $sModule = "";
                    $sModule = $oModule->generate();

                    // append module to body
                    if( $sModule ) {
                        $GLOBALS['TL_BODY'][] = Controller::replaceInsertTags($sModule);
                    }
                }
            }
        }
    }


    /**
     * Checks if the given tag is accepted by the user
     *
     * @param integer $tagId
     * @param integer $tagPid
     *
     * @return boolean
     */
    public static function isAccepted( $tagId, $tagPid ) {

        $isAccepted = false;

        // cookie_bar
        if( !$isAccepted ) {
            $isAccepted = Input::cookie('cms_cookie') == 'accept';
        }

        // accept_tags
        if( !$isAccepted ) {
            $isAccepted = (Input::cookie('cms_cookies_saved') === "true" && in_array($tagPid, explode('-', Input::cookie('cms_cookies'))));
        }

        return $isAccepted;
    }


    /**
     * Replace a rendered content element or frontend module with a fallback
     * template if configured to be only visible on cookie accept
     *
     * @param ContentModel|ModuleModel $oRow
     * @param string $strBuffer
     * @param ContentElement|Module $oElement
     *
     * @return string
     */
    public function replaceTagContentModuleElement($oRow, $strBuffer, $oElement) {

        global $objPage;

        if( TL_MODE == 'FE' ) {

            // we may have a frontend module referenced by a content element
            // in this case make sure to check the settings of the module itself
            if( !$oRow->cms_tag_visibility && $oElement->type === "module" ) {
                $oRow = ModuleModel::findOneById( $oRow->module );
            }

            // replace buffer if cms_tag_visibility is set and selected tag is accepted
            if( $oRow->cms_tag_visibility ) {

                if( !aczolku::hasFeature('tags', $objPage->trail[0]) ) {
                    return '';
                }

                $oTag = NULL;
                $oTag = TagModel::findOneById($oRow->cms_tag);

                if( !$oTag || !aczolku::hasFeature('tags_'.$oTag->type, $objPage->trail[0]) ) {
                    return '';
                }

                $cssID = '';
                $cssID = $this->_addIdAttribute($strBuffer, $oElement);

                if( !self::isAccepted($oTag->id, $oTag->pid) || !$oTag->active ) {

                    $oTemplate = new FrontendTemplate($oTag->fallbackTpl?:'ce_optin_fallback');
                    $oTemplate->setData( $oRow->row() );

                    $oTemplate->optinLink = self::generateEUConsentForceLink($cssID); // DEPRECATED
                    $oTemplate->headline = null;
                    $oTemplate->class = 'ce_optin_fallback';
                    $oTemplate->cssID = ' id="'.$cssID.'"';
                    $oTemplate->fallback_text = $oTag->fallback_text;

                    $strBuffer = $oTemplate->parse();
                    $strBuffer = str_replace('{{cms_optinlink}}', '{{cms_optinlink::'.$cssID.'}}', $strBuffer);
                }
            }
        }

        return $strBuffer;
    }


    /**
     * Adds an id attribute to the given element markup if necessary
     * and returns the found / generated id
     *
     * @param string $strBuffer
     * @param ContentElement|Module $oElement
     *
     * @return string
     */
    private function _addIdAttribute( &$strBuffer, $oElement ) {

        $firstTag = [];

        $id = '';

        if( preg_match('/<[^\!][^>]*?>/m', $strBuffer, $firstTag) ) {

            $firstTag = $firstTag[0];
            $arrExistingID = [];

            if( preg_match('/id="(.*?)"/', $firstTag, $arrExistingID) ) {

                $id = $arrExistingID[1];

            } else {

                $id = 'cms_' . $oElement->typePrefix . $oElement->id;
                $strBuffer = str_replace($firstTag, substr($firstTag, 0, -1).' id="'.$id.'">', $strBuffer);
            }
        }

        return $id;
    }


    /**
     * Generates a link to the current page with a parameter that forces
     * the cookie bar to show up again
     *
     * @param string Optional id (cssID) of the original element
     *
     * @return string
     */
    private function generateEUConsentForceLink( $strElementId="" ) {

        $href = Environment::get('request');

        if( strpos($href, '?') !== FALSE ) {
            $href = substr($href,0,strpos($href, '?'));
        }

        $href = $href . '?_cmsscb=1';

        if( !empty($strElementId) ) {
            $href .= '&amp;_cmselid='.$strElementId;
        }

        return $href;
    }


    /**
     * Replace insert tags for the tags
     *
     * @param string $tag
     * @param boolean $blnCache
     * @param string $strCached
     * @param array $flags
     * @param array $tags
     * @param array $arrCache
     * @param integer $_rit
     * @param integer $_cnt
     *
     * @return string|false
     */
    public function replaceTagInsertTags($tag, $blnCache, $strCached, $flags, &$tags, $arrCache, $_rit, $_cnt) {

        $elements = explode('::', $tag);
        $strTag = $tags[$_rit+1];

        switch( strtolower($elements[0]) ) {

            case 'ifoptin':

                $show = true;

                if( !aczolku::hasFeature('tags', $objPage->trail[0]) ) {
                    $show = false;
                }

                if( empty($elements[1]) ) {
                    return '';
                }

                $oTag = TagModel::findOneById($elements[1]);

                if( !$oTag || !aczolku::hasFeature('tags_'.$oTag->type, $objPage->trail[0]) ) {
                    $show = false;
                }

                if( !self::isAccepted($oTag->id, $oTag->pid) || !$oTag->active ) {
                    $show = false;
                }

                if( !$show ) {
                    $open = true;
                    for( $i = $_rit; $i<$_cnt; $i+=2 ) {

                        if( $open ) {
                            $tags[$i+1] = ''; // also empty tag else nested would be replaced
                            $tags[$i+2] = '';
                        }

                        if( $tags[$i+3] == 'ifoptin' || stripos($tags[$i+3] , 'ifoptin::') ) {
                            $open = false;

                        }
                        if( $tags[$i+3] == $tag ) {
                            $open = true;
                        }
                    }
                }
                return'';
            break;

            case 'ifnoptin':

                $show = true;

                if( !aczolku::hasFeature('tags', $objPage->trail[0]) ) {
                    $show = false;
                }

                if( empty($elements[1]) ) {
                    return '';
                }

                $oTag = TagModel::findOneById($elements[1]);

                if( !$oTag || !aczolku::hasFeature('tags_'.$oTag->type, $objPage->trail[0]) ) {
                    $show = false;
                }

                if( self::isAccepted($oTag->id, $oTag->pid) && $oTag->active ) {
                    $show = false;
                }

                if( !$show ) {
                    $open = true;
                    for( $i = $_rit; $i<$_cnt; $i+=2 ) {

                        if( $open ) {
                            $tags[$i+1] = ''; // also empty tag else nested would be replaced
                            $tags[$i+2] = '';
                        }

                        if( $tags[$i+3] == 'ifnoptin' || stripos($tags[$i+3] , 'ifnoptin::') ) {
                            $open = false;

                        }
                        if( $tags[$i+3] == $tag ) {
                            $open = true;
                        }
                    }
                }
                return'';
            break;

            case 'cms_optinlink':
                return self::generateEUConsentForceLink($elements[1]);
            break;
        }

        return false;
    }
}
