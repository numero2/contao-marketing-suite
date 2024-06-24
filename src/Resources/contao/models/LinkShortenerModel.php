<?php

/**
 * Contao Marketing Suite Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\MarketingSuite;

use Contao\Environment;
use Contao\Model;
use Contao\PageModel;
use Contao\System;
use numero2\MarketingSuite\Backend\License as lskgn;


class LinkShortenerModel extends Model {


    /**
     * Table name
     * @var string
     */
    protected static $strTable = 'tl_cms_link_shortener';


    /**
     * finds one entry by the given path
     *
     * @param string $path
     *
     * @return null|LinkShortenerModel
     */
    public static function findOneByPath($path) {

        $oRoot = PageModel::findOneBy(['tl_page.type=? AND (tl_page.dns=? OR tl_page.dns=?)'], ['root', Environment::get('httpHost'), '']);

        if( $oRoot && lskgn::hasFeature('link_shortener', $oRoot->id) ) {
            return self::findOneBy(['(prefix=? OR alias=?) AND domain=?'], [$path, $path, Environment::get('httpHost')]);
        }

        return null;
    }


    /**
     * returns the resolved target url for the current entity
     *
     * @return string
     */
    public function getTarget() {

        $strTarget = $this->target;

        if( $this->active !== '1' || ($this->stop !== '' && $this->stop <= time()) ) {

            if( !empty($this->fallback) ) {

                $strTarget = $this->fallback;

            } else {

                return null;
            }
        }

        $insertTagParser = System::getContainer()->get('contao.insert_tag.parser');
        $strTarget = $insertTagParser->replace($strTarget);

        if( !parse_url($strTarget, PHP_URL_HOST) ) {

            $strTarget = Environment::get('httpHost') . $strTarget;
        }
        if( !parse_url($strTarget, PHP_URL_SCHEME) ) {

            $strTarget = 'http://'.$strTarget;
        }

        return $strTarget;
    }

}
