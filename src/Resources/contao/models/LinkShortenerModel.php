<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2020 Leo Feyer
 *
 * @package   Contao Marketing Suite
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright 2020 numero2 - Agentur für digitales Marketing
 */


namespace numero2\MarketingSuite;

use Contao\Controller;
use Contao\Environment;
use Contao\Model;
use Contao\PageModel;
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

        $oRoot = PageModel::findOneBy(['type=? AND (dns=? OR dns=?)'], ['root', Environment::get('httpHost'), '']);

        if( lskgn::hasFeature('link_shortener', $oRoot->id) ) {

            return self::findOneBy(['(prefix=? OR alias=?) AND domain=?'], [$path, $path, Environment::get('httpHost')]);
        }

        return null;
    }


    /**
     * Finds all active entries
     *
     * @param string $path
     *
     * @return null|LinkShortenerModel
     */
    public static function findAllActive() {

        if( lskgn::hasFeature('link_shortener') ) {

            return self::findOneBy(['active=? OR (active=? AND fallback!=?)'], [1,'','']);
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
        if( $this->active !== '1' ) {

            if( !empty($this->fallback) ) {

                $strTarget = $this->fallback;

            } else {

                return null;
            }
        }

        $strTarget = Controller::replaceInsertTags($strTarget);

        if( !parse_url($strTarget, PHP_URL_HOST) ) {

            $strTarget = \Environment::get('httpHost').'/'.$strTarget;
        }
        if( !parse_url($strTarget, PHP_URL_SCHEME) ) {

            $strTarget = 'http://'.$strTarget;
        }

        return $strTarget;
    }

}
