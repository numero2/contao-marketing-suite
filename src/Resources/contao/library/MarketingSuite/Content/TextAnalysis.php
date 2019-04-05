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


namespace numero2\MarketingSuite\Content;

use numero2\MarketingSuite\Backend\License as sefeca;
use Org\Heigl\Hyphenator;
use Org\Heigl\Hyphenator\Options;


class TextAnalysis {


    /**
     * Text to analyze
     * @var string
     */
    private $strText = "";

    /**
     * Public properties
     * @var mixed
     */
    public $syllables = NULL;
    public $syllablesTotal = 0;
    public $sentences = NULL;
    public $stats = NULL;
    public $flesch = NULL;


    /**
     * Constructor
     */
    public function __construct( $text="" ) {

        $this->strText = $this->sanitizeText($text);

        $this->collectStats();
        $this->analyzeSyllables();
        $this->analyzeSentences();
        $this->calculateFleschIndex();
        sefeca::epo();
    }


    /**
     * Sanitizes the given text for further use
     *
     * @return string
     */
    private function sanitizeText( $text="" ) {

        // replace br with newline
        $text = preg_replace('/<br\s?\/?>/i', "\r\n", $text);

        // remove html all together
        $text = strip_tags($text);

        // convert html entites like &nbsp;
        $text = html_entity_decode($text);

        // trim whitespaces
        $text = trim($text);

        return $text;
    }


    /**
     * Analyzes the syllables in the current text
     */
    private function analyzeSyllables() {

        $opts = NULL;
        $opts = new Options();
        $opts->setHyphen('__')
          ->setDefaultLocale('de_DE')
          ->setRightMin(2)
          ->setLeftMin(2)
          ->setWordMin(5)
          ->setFilters('Simple')
          ->setTokenizers(['Whitespace', 'Punctuation']);

        $oHyphenator = new Hyphenator\Hyphenator();
        $oHyphenator->setOptions($opts);

        $sSyllables = "";
        $sSyllables = $oHyphenator->hyphenate($this->strText);

        $syllables = [
            1 => [ 'count' => 0, 'words' => [], 'percentage' => 0 ]
        ,   2 => [ 'count' => 0, 'words' => [], 'percentage' => 0 ]
        ,   3 => [ 'count' => 0, 'words' => [], 'percentage' => 0 ]
        ,   4 => [ 'count' => 0, 'words' => [], 'percentage' => 0 ]
        ,   5 => [ 'count' => 0, 'words' => [], 'percentage' => 0 ]
        ,   6 => [ 'count' => 0, 'words' => [], 'percentage' => 0 ]
        ];

        // split text into single words and count the syllables
        if( is_string($sSyllables) && preg_match_all("|((\b[^\s]+\b)((?<=\.\w).)?)|u", $sSyllables, $matches) ) {

            $total = 0;

            // count syllables
            foreach( $matches[0] as $word ) {

                $numSyllables = substr_count($word, '__') +1;
                $word = str_replace('__', '', $word);

                $total += $numSyllables;

                foreach( array_reverse(array_keys($syllables)) as $length ) {

                    if( $numSyllables >= $length ) {

                        $syllables[$length]['count']++;
                        $syllables[$length]['words'][] = $word;
                        break;
                    }
                }
            }

            $this->syllablesTotal = $total;

            // calculate percentage of each type of syllables
            foreach( array_keys($syllables) as $length ) {

                $amount = $syllables[$length]['count'];
                $syllables[$length]['percentage'] = ($amount / $this->stats['words']) * 100;
            }
        }

        $this->syllables = $syllables;
    }


    /**
     * Collects statistics about the current text
     */
    private function collectStats() {

        $stats = [];

        // count words
        if( preg_match_all("|((\b[^\s]+\b)((?<=\.\w).)?)|u", $this->strText, $matches) ) {
            $stats['words'] = count($matches[0]);
        }

        // count chars
        $stats['chars'] = strlen($this->strText);
        if( preg_match_all("/(?:[^\r\n]|\r(?!\n))/u", $this->strText, $matches) ) {
            $stats['chars'] = count($matches[0]);
        }

        // count sentences
        $stats['sentences'] = 0;
        if( preg_match_all("/\w[.?!](\s|$)/", $this->strText, $matches) ) {
            $stats['sentences'] = count($matches[0]);
        }

        if( !empty($stats) ) {
            $this->stats = $stats;
        }
    }


    /**
     * Analyzes the length of sentences
     */
    private function analyzeSentences() {

        $aSentences = [];

        // split text into sentences
        if( preg_match_all("/[^.?!][.?!](\s|$)/u", $this->strText, $matches, PREG_OFFSET_CAPTURE) ) {

            foreach( $matches[0] as $i => $match ) {

                $start = 0;
                $length = ($match[1]+strlen($match[0])-1);

                if( $i ) {

                    $start = ($matches[0][($i-1)][1]+strlen($matches[0][($i-1)][0]));
                    $length = ($match[1]+strlen($match[0])-1) - $start;
                }

                $aSentences[] = substr(
                    $this->strText
                ,   $start
                ,   $length
                );
            }
        }

        $stats = [
             0 => [ 'count' => 0, 'words' => [], 'percentage' => 0 ]
        ,   14 => [ 'count' => 0, 'words' => [], 'percentage' => 0 ]
        ,   19 => [ 'count' => 0, 'words' => [], 'percentage' => 0 ]
        ,   25 => [ 'count' => 0, 'words' => [], 'percentage' => 0 ]
        ,   31 => [ 'count' => 0, 'words' => [], 'percentage' => 0 ]
        ];

        if( !empty($aSentences) ) {

            foreach( $aSentences as $sentence ) {

                if( preg_match_all("|((\b[^\s]+\b)((?<=\.\w).)?)|u", $sentence, $matches, PREG_OFFSET_CAPTURE) ) {

                    $numWords = 0;
                    $numWords = count($matches[0]);

                    foreach( array_reverse(array_keys($stats)) as $length ) {

                        if( $numWords >= $length ) {

                            $stats[$length]['count']++;

                            // get first and last word of sentence
                            $firstWord = $matches[0][0][0];
                            $lastWord = trim( substr( $sentence, $matches[0][$numWords-1][1] ) );

                            // get minimum length between first and last word
                            $numChars = 0;
                            foreach( $matches[0] as $i => $m ) {
                                $numChars += strlen($m[0]);
                            }

                            $stats[$length]['words'][] = [ $firstWord, $lastWord, $numChars ];

                            break;
                        }
                    }
                }
            }

            // calculate percentage of each type of syllables
            foreach( array_keys($stats) as $length ) {

                $amount = $stats[$length]['count'];
                $stats[$length]['percentage'] = ($amount / $this->stats['sentences']) * 100;
            }
        }

        $this->sentences = $stats;
    }


    /**
     * Calculates the Flesch index
     */
    private function calculateFleschIndex() {

        if( !empty($this->sentences) && !empty($this->stats['words']) ) {

            $sum = array_sum(array_column($this->sentences, 'count'));

            $asl = ($sum) ? $this->stats['words'] / $sum : 0;
            $asw = ($this->stats && $this->stats['words']) ? $this->syllablesTotal / $this->stats['words'] : 0;

            // TODO: Choose calculation based on language, the following is specific for
            // german texts
            $this->flesch = 180 - $asl - (58.5*$asw);
        }
    }


    /**
     * Converts the given string into mostly a hexadecimal representation
     * but also supporting unicode characters
     *
     * @param string $str
     *
     * @return string
     */
    private static function strToUnicodeHex( $str=NULL ) {

        // json_encode will take care of all unicode characters
        $str = substr( json_encode($str), 1, -1);

        $str = str_split($str);
        $len = count($str);

        for( $i=0; $i<$len; $i++ ) {

            // skip encoded unicode characters
            if( $str[$i] == '\\' && $str[$i+1] == 'u' ) {
                $i +=6;
            }

            $str[$i] = '\x'.str_pad(dechex(ord($str[$i])), 2, "0", STR_PAD_LEFT);
        }

        $str = implode($str);

        return $str;
    }
}
