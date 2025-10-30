<?php

/**
 * Contao Marketing Suite Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2025, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\MarketingSuite\Helper;

use Contao\System;
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

            $oManager = null;
            $oManager = new PublicSuffixListManager();

            $oParser = null;
            $oParser = new Parser($oManager->getList());

            $oURL = null;
            $oURL = $oParser->parseUrl($sDomain);

            return $oURL->host->registerableDomain;

        // jeremykendall/php-domain-parser ^5.6
        } else if( class_exists('\Pdp\Domain') ) {

            $root = System::getContainer()->getParameter('kernel.project_dir');

            $oRules = null;
            $oRules = Rules::fromPath($root . '/vendor/numero2/contao-marketing-suite/src/Resources/vendor/publicsuffix/public_suffix_list.dat');

            $oDomain = null;
            $oDomain = $oRules->resolve($sDomain);

            return $oDomain->registrableDomain()->value();
        }

        return null;
    }
}
