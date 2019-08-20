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


namespace numero2\MarketingSuite\Hooks;

use Contao\CMSConfig;
use Contao\Controller;
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

                // check if this tag was accepted
                // cookie_bar
                $boolAccepted = Input::cookie('cms_cookie') == 'accept';
                // accept_tags
                $boolAccepted |= (Input::cookie('cms_cookies_saved') === "true" && in_array($tag->pid, explode('-', Input::cookie('cms_cookies'))));

                // skip if cookie needed but not cookie_accepted
                if( $tag->enable_on_cookie_accept && !$boolAccepted ) {
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

                    if( $tag->type == "session" ) {
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

                    $aTags[$tag->type.'_'.$tag->id] = $tagTemplate->parse();

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

        $objModel = new ModuleModel();
        $objModel->preventSaving(false);

        Controller::loadDataContainer('tl_cms_tag_settings');
        aczolku::udifuro();

        if( $GLOBALS['TL_DCA']['tl_cms_tag_settings']['fields'] && count($GLOBALS['TL_DCA']['tl_cms_tag_settings']['fields']) ) {

            foreach( $GLOBALS['TL_DCA']['tl_cms_tag_settings']['fields'] as $key => $value ) {

                if( !empty($value['mapping']) ) {
                    $objModel->{$value['mapping']} = CMSConfig::get($key);
                } else {
                    $objModel->{$key} = CMSConfig::get($key);
                }
            }
        }

        if( $objModel->cms_exclude_pages ) {

            $excludePages = deserialize($objModel->cms_exclude_pages);

            if( is_array($excludePages) && count($excludePages) ) {
                if( in_array($objPage->id, $excludePages) ) {
                    return;
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
            $GLOBALS['TL_BODY'][] = $oModule->generate();
        }
    }
}
