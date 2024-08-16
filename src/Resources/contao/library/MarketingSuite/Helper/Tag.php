<?php

/**
 * Contao Marketing Suite Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\MarketingSuite\Helper;

use Contao\Input;
use numero2\MarketingSuite\Backend\License as sdgxcb;
use numero2\MarketingSuite\TagModel;


class Tag {


    /**
     * Checks if the given tag is accepted by the user, check if the tag itself is active, can be set in with $checkActive.
     * Third paramter will be set to the found tag.
     *
     * @param integer $tagId
     * @param boolean $checkActive
     * @param array $aTag
     *
     * @return boolean
     */
    public static function isAccepted( $tagId, $checkActive=true, &$aTag=null ) {

        global $objPage;

        $oTag = TagModel::findOneById($tagId);

        if( !$oTag || !sdgxcb::hasFeature('tags_'.$oTag->type, $objPage->trail[0]) ) {
            return false;
        }

        if( $aTag !== null ) {
            $aTag = $oTag->row();
        }

        if( $checkActive ) {
            if( !$oTag->active ) {
                return false;
            }
        }

        $isAccepted = false;

        // cookie_bar
        if( !$isAccepted ) {
            $isAccepted = Input::cookie('cms_cookie') == 'accept';
        }

        // accept_tags
        if( !$isAccepted ) {
            $isAccepted = (Input::cookie('cms_cookies_saved') === "true" && in_array($oTag->pid, explode('-', Input::cookie('cms_cookies') ?? '')));
        }

        return $isAccepted;
    }


    /**
     * Checks if the given tag is not accepted by the user, therefore the tag itself must be active.
     *
     * @param integer $tagId
     *
     * @return boolean
     */
    public static function isNotAccepted( $tagId ) {

        $aTag = [];
        if( !Tag::isAccepted($tagId, false, $aTag) && !empty($aTag['active']) ) {
            return true;
        }

        return false;
    }
}
