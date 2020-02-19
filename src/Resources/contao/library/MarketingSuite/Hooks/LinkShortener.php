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

use numero2\MarketingSuite\LinkShortenerModel;


class LinkShortener extends Hooks {


    /**
     * Replace insert tags for the link shortener
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
    public function replaceLinkShortenerInsertTags($tag, $blnCache, $strCached, $flags, &$tags, $arrCache, $_rit, $_cnt) {

        $elements = explode('::', $tag);

        switch( strtolower($elements[0]) ) {

            case 'cms_link_shortener':

                $oLink = LinkShortenerModel::findOneById($elements[1]);

                if( !$oLink ) {
                    return '';
                }

                $link = 'https://'.$oLink->domain.'/';

                if( !empty($oLink->alias) && in_array('alias', $flags) ) {
                    $link .= $oLink->alias;
                } else if( !empty($oLink->prefix) && in_array('prefix', $flags) ) {
                    $link .= $oLink->prefix;
                } else if( !empty($oLink->alias) ) {
                    $link .= $oLink->alias;
                } else if( !empty($oLink->prefix)) {
                    $link .= $oLink->prefix;
                } else {
                    return '';
                }

                return $link;
            break;
        }

        return false;
    }


    /**
     * Replace insert tag flags for the link shortener
     *
     * @param string $flag
     * @param string $tag
     * @param string $result
     * @param array $flags
     * @param boolean $blnCache
     * @param array $tags
     * @param array $arrCache
     * @param integer $_rit
     * @param integer $_cnt
     *
     * @return string|false
     */
    public function replaceLinkShortenerInsertTagFlags($flag, $tag, $result, $flags, $blnCache, $tags, $arrCache, $_rit, $_cnt) {

        if( strpos($tag, 'cms_link_shortener::') === 0  && in_array($flag, ['prefix', 'alias'])) {
            return $result;
        }

        return false;
    }

}
