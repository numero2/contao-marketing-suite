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


namespace numero2\MarketingSuite\Helper;

use Pdp\Parser;
use Pdp\PublicSuffixListManager;
use Pdp\Rules;

class Domain {


    /**
     * Get the registerable part of a FQDN
     *
     * @param string $sDomain
     *
     * @return string|null
     */
    public static function getRegisterableDomain( $sDomain='' ): ?string {

        // jeremykendall/php-domain-parser ^3.0
        if( class_exists('\Pdp\PublicSuffixListManager') ) {

            $oManager = NULL;
            $oManager = new PublicSuffixListManager();

            $oParser = NULL;
            $oParser = new Parser($oManager->getList());

            $oURL = NULL;
            $oURL = $oParser->parseUrl($sDomain);

            return $oURL->host->registerableDomain;

        // jeremykendall/php-domain-parser ^5.6
        } else if( class_exists('\Pdp\Domain') ) {

            $oRules = NULL;
            $oRules = Rules::createFromPath(TL_ROOT . '/vendor/numero2/contao-marketing-suite/src/Resources/vendor/publicsuffix/public_suffix_list.dat');

            $oDomain = NULL;
            $oDomain = $oRules->resolve($sDomain);

            return $oDomain->getRegistrableDomain();
        }

        return NULL;
    }
}