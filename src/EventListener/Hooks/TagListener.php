<?php

/**
 * Contao Marketing Suite Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\MarketingSuiteBundle\EventListener\Hooks;

use Contao\CMSConfig;
use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\Environment;
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\LayoutModel;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\PageRegular;
use Contao\StringUtil;
use Contao\System;
use numero2\MarketingSuite\Backend\License as aczolku;
use numero2\MarketingSuite\Helper\Tag;
use numero2\MarketingSuite\TagModel;
use Symfony\Component\HttpFoundation\RequestStack;


class TagListener {


    /**
     * @var Symfony\Component\HttpFoundation\RequestStack
     */
    private $requestStack;

    /**
     * @var Contao\CoreBundle\Routing\ScopeMatcher
     */
    protected $scopeMatcher;

    /**
     * @var Contao\CoreBundle\InsertTag\InsertTagParser
     */
    protected $insertTagParser;


    public function __construct( RequestStack $requestStack, ScopeMatcher $scopeMatcher, InsertTagParser $insertTagParser ) {

        $this->requestStack = $requestStack;
        $this->scopeMatcher = $scopeMatcher;
        $this->insertTagParser = $insertTagParser;
    }


    /**
     * Generates and adds all script tags to the page
     *
     * @param Contao\PageModel $pageModel
     * @param Contao\LayoutModel $layout
     * @param Contao\PageRegular $pageRegular
     *
     * @Hook("generatePage")
     */
    public function generateScripts( PageModel $pageModel, LayoutModel $layout, PageRegular $pageRegular ) {

        if( !aczolku::hasFeature('tags', $pageModel->trail[0]) ) {
            return;
        }

        $template = new FrontendTemplate('mod_cms_tags');
        aczolku::ezahew();

        $aTags = [];

        $aTagTypes = [];
        $aTagTypes = self::getAllowedTags();

        if( $aTagTypes && count($aTagTypes) ) {

            $aTypeTotal = [];

            // count how often a tag type is used
            foreach( $aTagTypes as $type => $tags ) {

                foreach( $tags as $key => $tag ) {

                    if( empty($aTypeTotal[$tag->type]) ) {
                        $aTypeTotal[$tag->type] = 0;
                    }

                    $aTypeTotal[$tag->type] += 1;
                }
            }

            $aTypeCount = [];

            // render script tags
            foreach( $aTagTypes as $type => $tags ) {

                foreach( $tags as $key => $tag ) {

                    if( in_array($tag->type, ['session', 'content_module_element']) ) {
                        continue;
                    }

                    if( empty($tag->customTpl) ) {
                        $tag->customTpl = 'tag_'.$tag->type;
                    }

                    $tagTemplate = NULL;
                    $tagTemplate = new FrontendTemplate($tag->customTpl);

                    $aTag = $tag->row();

                    if( strlen($aTag['html']) ) {
                        $aTag['html'] = $this->insertTagParser->replace($aTag['html']);
                    }

                    $tagTemplate->setData($aTag);

                    // add marker for the first of it's type
                    $tagTemplate->typeFirst = false;
                    if( empty($aTypeCount[$tag->type]) ) {
                        $tagTemplate->typeFirst = true;
                        $aTypeCount[$tag->type] = 0;
                    }
                    $aTypeCount[$tag->type] += 1;

                    // add marker for the last of it's type
                    $tagTemplate->typeLast = false;
                    if( $aTypeCount[$tag->type] == $aTypeTotal[$tag->type] ) {
                        $tagTemplate->typeLast = true;
                    }

                    if( Config::get('debugMode') ) {
                        $aTags[$tag->type.'_'.$tag->id] = '<!-- tag: '.$tag->type.'_'.$tag->id.' -->' . $tagTemplate->parse() . '<!-- endtag -->';
                    } else {
                        $aTags[$tag->type.'_'.$tag->id] = $tagTemplate->parse();
                    }
                }
            }
        }

        $template->tags = $aTags;

        // make sure we don't index the page if we force showing the consent
        if( Input::get('_cmsscb') ) {

            $responseContext = System::getContainer()->get('contao.routing.response_context_accessor')->getResponseContext();

            if( $responseContext && $responseContext->has(HtmlHeadBag::class) ) {

                /** @var HtmlHeadBag $htmlHeadBag */
                $htmlHeadBag = $responseContext->get(HtmlHeadBag::class);
                $htmlHeadBag->setMetaRobots('noindex,nofollow');
            }
        }

        $GLOBALS['TL_BODY'][] = $template->parse();
    }


    /**
     * get all allowed tags for the global objPage grouped by type
     *
     * @return array
     */
    public static function getAllowedTags() {

        $request = System::getContainer()->get('request_stack')->getMainRequest();
        $pageModel = $request->get('pageModel');

        if( !($pageModel instanceof PageModel) || !aczolku::hasFeature('tags', $pageModel->trail[0]) ) {
            return;
        }

        $aTagTypes = [];

        $objTags = NULL;
        $objTags = TagModel::findAllActiveByPage($pageModel->id);

        if( $objTags && count($objTags) ) {

            // prepare which page is allowed on which scope
            $allowed = [
                'current_page' => []
            ,   'current_and_direct_children' => []
            ,   'current_and_all_children' => []
            ];

            foreach( array_reverse($pageModel->trail) as $value ) {

                if( $value == $pageModel->id ) {
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

                if( !aczolku::hasFeature('tags_'.$tag->type, $pageModel->trail[0]) ) {
                    continue;
                }

                // skip if we're on a page that should be cached
                if( $pageModel->includeCache && $tag->enable_on_cookie_accept ) {
                    continue;
                }

                // skip if cookie needed but not cookie_accepted
                if( $tag->enable_on_cookie_accept && !Tag::isAccepted($tag->id) ) {
                    continue;
                }

                $tagPages = StringUtil::deserialize($tag->pages);

                // check all pages if one is allowed
                foreach( $allowed[$tag->pages_scope] as $key => $value ) {

                    if( in_array($value, $tagPages) ) {

                        $aTagTypes[$tag->pid][] = $tag;
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
     * @param Contao\PageModel $objPage
     * @param Contao\LayoutModel $layout
     * @param Contao\PageRegular $pageRegular
     *
     * @Hook("generatePage")
     */
    public function generateEUConsent( PageModel $objPage, LayoutModel $layout, PageRegular $pageRegular ) {

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
                        $GLOBALS['TL_BODY'][] = $this->insertTagParser->replace($sModule);
                    }
                }
            }
        }
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
     *
     * @Hook("getContentElement")
     * @Hook("getFrontendModule")
     */
    public function replaceTagContentModuleElement($oRow, $strBuffer, $oElement) {

        System::loadLanguageFile('cms_default');

        $request = $this->requestStack->getCurrentRequest();
        $pageModel = $request->get('pageModel');

        if( $request && $this->scopeMatcher->isFrontendRequest($request) ) {

            // we may have a frontend module referenced by a content element
            // in this case make sure to check the settings of the module itself
            if( !$oRow->cms_tag_visibility && $oElement->type === "module" ) {
                $oRow = ModuleModel::findOneById($oRow->module);
            }

            // replace buffer if cms_tag_visibility is set and selected tag is accepted
            if( $oRow->cms_tag_visibility ) {

                if( !aczolku::hasFeature('tags', $pageModel->trail[0]) ) {
                    return '';
                }

                $oTag = null;
                $oTag = TagModel::findOneById($oRow->cms_tag);

                if( !$oTag || !aczolku::hasFeature('tags_'.$oTag->type, $pageModel->trail[0]) ) {
                    return '';
                }

                $cssID = '';
                $cssID = $this->_addIdAttribute($strBuffer, !empty($oElement->id)?$oElement:$oRow);

                if( !Tag::isAccepted($oRow->cms_tag) ) {

                    $oTemplate = new FrontendTemplate($oTag->fallbackTpl?:'ce_optin_fallback');
                    $oTemplate->setData( $oRow->row() );

                    $oTemplate->headline = null;
                    $oTemplate->class = 'ce_optin_fallback '.$oRow->cms_tag_fallback_css_class;

                    $oTemplate->cssID = '';
                    if( $cssID ) {
                        $oTemplate->cssID = ' id="'.$cssID.'"';
                    }

                    $oTemplate->optinlink = '{{cms_optinlink'. (!empty($cssID)?'::'.$cssID:'').'}}';
                    $oTemplate->fallback_text = str_replace('{{cms_optinlink', '{{cms_optinlink::'.$cssID.'', $oTag->fallback_text);
                    $oTemplate->origin = $oElement;

                    if( !$oTemplate->fallback_text ) {
                        $oTemplate->class .= ' default';
                    }

                    $oTemplate->class = str_replace(' ', '', $oTemplate->class);
                    $strBuffer = $oTemplate->parse();
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
     *
     * @Hook("replaceInsertTags")
     */
    public function replaceTagInsertTags($tag, $blnCache, $strCached, $flags, &$tags, $arrCache, &$_rit, $_cnt) {

        $request = $this->requestStack->getMainRequest();
        $pageModel = $request->get('pageModel');

        $elements = explode('::', $tag);

        switch( strtolower($elements[0]) ) {

            case 'ifoptin':

                $show = true;

                if( !aczolku::hasFeature('tags', $pageModel->trail[0]) ) {
                    $show = false;
                }

                if( empty($elements[1]) ) {
                    return '';
                }

                if( !Tag::isAccepted($elements[1]) ) {
                    $show = false;
                }

                if( !$show ) {

                    for( $i = $_rit; $i<$_cnt; $i+=2 ) {

                        if( !array_key_exists($i+2, $tags) ) {
                            break;
                        }

                        $tags[$i+2] = '';

                        if( !array_key_exists($i+3, $tags) ) {
                            $_rit = $i;
                            break;
                        }

                        // found closing tag
                        if( strtolower(substr($tags[$i+3], 0, 7)) == 'ifoptin' ) {
                            $_rit = $i;

                            // hidde more text with the same insert tag as our result will be cached
                            // and we won't be called again for this insert tag
                            $open = false;
                            for( $i=$_rit+2; $i+2<$_cnt; $i+=2 ) {

                                if( $tags[$i+1] === $tag ) {
                                    $open = true;
                                }

                                if( $open ) {
                                    $tags[$i+2] = '';
                                }

                                if( strtolower(substr($tags[$i+1], 0, 7)) == 'ifoptin' ) {
                                    $open = false;
                                }
                            }
                            break;
                        }
                    }
                }
                return'';
            break;

            case 'ifnoptin':

                $show = true;

                if( !aczolku::hasFeature('tags', $pageModel->trail[0]) ) {
                    $show = false;
                }

                if( empty($elements[1]) ) {
                    return '';
                }

                $aTag = [];
                if( !Tag::isNotAccepted($elements[1]) ) {
                    $show = false;
                }

                if( !$show ) {

                    for( $i = $_rit; $i<$_cnt; $i+=2 ) {

                        if( !array_key_exists($i+2, $tags) ) {
                            break;
                        }

                        $tags[$i+2] = '';

                        if( !array_key_exists($i+3, $tags) ) {
                            $_rit = $i;
                            break;
                        }

                        // found closing tag
                        if( strtolower(substr($tags[$i+3], 0, 8)) == 'ifnoptin' ) {
                            $_rit = $i;

                            // hidde more text with the same insert tag as our result will be cached
                            // and we won't be called again for this insert tag
                            $open = false;
                            for( $i=$_rit+2; $i+2<$_cnt; $i+=2 ) {

                                if( $tags[$i+1] === $tag ) {
                                    $open = true;
                                }

                                if( $open ) {
                                    $tags[$i+2] = '';
                                }

                                if( strtolower(substr($tags[$i+1], 0, 8)) == 'ifnoptin' ) {
                                    $open = false;
                                }
                            }
                            break;
                        }
                    }
                }
                return'';
            break;

            case 'cms_optinlink':
                return self::generateEUConsentForceLink($elements[1] ?? '');
            break;
        }

        return false;
    }
}
