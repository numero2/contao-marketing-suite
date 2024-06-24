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

use Contao\Crypto;
use Contao\System;


class Encryption extends System {


    /**
     * Encrypt a value
     *
     * @param string $strValue The value to encrypt
     *
     * @return string The encrypted value
     */
    public static function encrypt( $strValue ) {

        $crypto = new Crypto();
        return $crypto->encryptPublic($strValue);
    }


    /**
     * Decrypt a value
     *
     * @param string $strValue The value to decrypt
     *
     * @return string The decrypted value or null
     */
    public static function decrypt( $strValue ) {

        $crypto = new Crypto();
        return $crypto->decryptPublic($strValue);
    }
}
