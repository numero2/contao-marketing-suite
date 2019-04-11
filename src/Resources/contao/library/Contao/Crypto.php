<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2018 Leo Feyer
 *
 * @package   Contao Marketing Suite Administration
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright 2018 numero2 - Agentur für digitales Marketing
 */


namespace Contao;


class Crypto {


    /**
     * Public key used for encryption
     * @var string
     */
    private $publicKey;


    /**
     * Cipher used
     * @var string
     */
    const CIPHER = "AES-256-CTR";


    /**
     * Constructor
     */
    function __construct( $public=NULL ) {

        if( $public ) {
            $this->publicKey = $this->expandKey($public, "PUBLIC");
        } else {
            $this->publicKey = \System::getContainer()->getParameter('kernel.secret');
        }
    }


    /**
     * Expands the given key
     *
     * @param string $strKey
     * @param string $strType
     *
     * @return string
     */
    protected function expandKey( $strKey, $strType ) {

        $strKey = "-----BEGIN ".$strType." KEY-----\n".chunk_split($strKey,64,"\n")."-----END ".$strType." KEY-----\n";
        return $strKey;
    }


    /**
     * Encrypts the given message
     *
     * @param string $strMessage
     *
     * @return string
     */
    public function encrypt( $strMessage ) {

        $strCrypted = null;
        openssl_public_encrypt($strMessage, $strCrypted, $this->publicKey);

        return $strCrypted;
    }


    /**
     * Encrypts the given message
     *
     * @param string $strMessage
     *
     * @return string
     */
    public function encryptPublic( $strMessage ) {

        if( $strMessage === null || $strMessage === '' ) {
            return '';
        }

        $ivlen = openssl_cipher_iv_length(self::CIPHER);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext_raw = openssl_encrypt($strMessage, self::CIPHER, $this->publicKey, $options=OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $ciphertext_raw, $this->publicKey, $as_binary=true);
        $ciphertext = base64_encode( $iv.$hmac.$ciphertext_raw );

        return $ciphertext;
    }


    /**
     * Decrypts the given message
     *
     * @param string $strCrypted
     *
     * @return string
     */
    public function decryptPublic( $strCrypted ) {

        if( $strCrypted === null || $strCrypted === '' ) {
            return '';
        }

        $c = base64_decode($strCrypted);
        $ivlen = openssl_cipher_iv_length(self::CIPHER);
        $sha2len=32;
        $iv = substr($c, 0, $ivlen);
        $hmac = substr($c, $ivlen, $sha2len);
        $ciphertext_raw = substr($c, $ivlen+$sha2len);

        if( !$iv || !$hmac || !$ciphertext_raw ) {
            return '';
        }

        $original_plaintext = openssl_decrypt($ciphertext_raw, self::CIPHER, $this->publicKey, $options=OPENSSL_RAW_DATA, $iv);
        $calcmac = hash_hmac('sha256', $ciphertext_raw, $this->publicKey, $as_binary=true);

        if( hash_equals($hmac, $calcmac) ) {
            return $original_plaintext;
        }

        return '';
    }


    /**
     * Verifies the given message
     *
     * @param string $strMessage
     * @param string $sign
     *
     * @return boolean
     */
    public function verify( $strMessage, $sign ) {

        $res = openssl_verify($strMessage, base64_decode($sign), $this->publicKey);

        if( $res === 1 ) {
            return true;
        }

        return false;
    }
}
