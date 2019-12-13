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
 * @copyright 2019 numero2 - Agentur für digitales Marketing
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
     * @param \PageModel $objPage
     * @param \LayoutModel $objLayout
     * @param \PageRegular $objPageRegular
     */
    public function generateScripts( PageModel $objPage, LayoutModel $objLayout, PageRegular $objPageRegular ) {

        global $objPage;

        if( !aczolku::hasFeature('tags', $objPage->trail[0]) ) {
            return;
        }

        $objTemplate = NULL;
        $objTemplate = new FrontendTemplate('mod_cms_tags');
        aczolku::ezahew();

        $objTags = NULL;
        $objTags = TagModel::findAllActiveByPage($objPage->id);

        $aTags = [];

        if( $objTags && count($objTags) ) {

            $aTagTypes = [];

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

        $GLOBALS['TL_BODY'][] = $objTemplate->parse();
    }


    /**
     * Generates cookie bar
     *
     * @param \PageModel $objPage
     * @param \LayoutModel $objLayout
     * @param \PageRegular $objPageRegular
     */
    public function generateCookieBar( PageModel $objPage, LayoutModel $objLayout, PageRegular $objPageRegular ) {

        if( !aczolku::hasFeature('tags', $objPage->trail[0]) || !aczolku::hasFeature('tag_settings', $objPage->trail[0]) ) {
            return;
        }

        global $objPage;

        $forceShow = false;
        $forceShow = \Input::get('_cmsscb') ? true : $forceShow;

        $objModel = NULL;
        $objModel = new ModuleModel();
        $objModel->preventSaving(false);

        Controller::loadDataContainer('tl_cms_tag_settings');
        aczolku::udifuro();

        // get settings for cokie bar from config
        if( $GLOBALS['TL_DCA']['tl_cms_tag_settings']['fields'] && count($GLOBALS['TL_DCA']['tl_cms_tag_settings']['fields']) ) {

            foreach( $GLOBALS['TL_DCA']['tl_cms_tag_settings']['fields'] as $key => $value ) {

                if( !empty($value['mapping']) ) {
                    $objModel->{$value['mapping']} = CMSConfig::get($key);
                } else {
                    $objModel->{$key} = CMSConfig::get($key);
                }
            }
        }

        // check if cookie bar is excluded from current page and not forced
        // to show up
        if( $objModel->cms_exclude_pages && !$forceShow ) {

            $excludePages = deserialize($objModel->cms_exclude_pages);

            if( is_array($excludePages) && count($excludePages) ) {

                // page excluded
                if( in_array($objPage->id, $excludePages) ) {

                    // check if consent form has not been submitted
                    if( \Input::post('FORM_SUBMIT') != $objModel->type ) {
                        return;
                    }
                }
            }
        }

        $oModule = NULL;

        if( $objModel->type === 'cms_cookie_bar' ) {
            $oModule = new ModuleCookieBar($objModel);
        } else if( $objModel->type === 'cms_accept_tags' ) {
            $oModule = new ModuleAcceptTags($objModel);
        }

        if( $oModule ) {

            $sModule = "";
            $sModule = $oModule->generate();

            // check if bar / tags should be shown at all
            if( $oModule->shouldBeShown() ) {

                $GLOBALS['TL_BODY'][] = Controller::replaceInsertTags($sModule);
                $objPage->cssClass .= ' cookie-bar-visible';
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
    public static function isAccepted($tagId, $tagPid) {

        $isAccepted = false;

        $moduleType = NULL;
        $moduleType = CMSConfig::get('cms_tag_type');

        // cookie_bar
        if( $moduleType === 'cms_cookie_bar' ) {

            $isAccepted = Input::cookie('cms_cookie') == 'accept';

        // accept_tags
        } else if( $moduleType === 'cms_accept_tags' ) {

            $isAccepted = (Input::cookie('cms_cookies_saved') === "true" && in_array($tagPid, explode('-', Input::cookie('cms_cookies'))));
        }

        return $isAccepted;
    }


    /**
     * Replace a render content element or frontend module with a fallback
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

                    $oTemplate = new \FrontendTemplate($oTag->fallbackTpl?:'ce_optin_fallback');
                    $oTemplate->setData( $oRow->row() );

                    $oTemplate->optinLink = self::generateCookieBarForceLink($cssID); // DEPRECATED
                    $oTemplate->headline = null;
                    $oTemplate->class = 'ce_optin_fallback';
                    $oTemplate->cssID = 'id="'.$cssID.'"';
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
    private function generateCookieBarForceLink( $strElementId="" ) {

        $href = Environment::get('request');

        if( strpos($href, '?') !== FALSE ) {
            $href = substr($href,0,strpos($href, '?'));
        }

        $href = $href . '?_cmsscb=1';

        if( !empty($strElementId) ) {
            $href .= '&amp_cmselid='.$strElementId;
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
                return self::generateCookieBarForceLink($elements[1]);
            break;
        }

        return false;
    }
}
