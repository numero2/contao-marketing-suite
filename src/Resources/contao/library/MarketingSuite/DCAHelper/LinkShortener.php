<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2022 Leo Feyer
 *
 * @package   Contao Marketing Suite
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright 2022 numero2 - Agentur für digitales Marketing
 */


namespace numero2\MarketingSuite\DCAHelper;

use Contao\Backend as CoreBackend;
use Contao\Config;
use Contao\Database;
use Contao\DataContainer;
use Contao\Environment;
use Contao\Image;
use Contao\Input;
use Contao\Message;
use Contao\PageModel;
use Contao\StringUtil;
use Exception;
use numero2\MarketingSuite\Backend\License as safsewzk;
use Symfony\Component\HttpClient\HttpClient;


class LinkShortener extends CoreBackend {


    /**
     * Returns all valid domains
     *
     * @param DataContainer $dc
     *
     * @return array
     */
    public function getAvailableDomains( DataContainer $dc ) {

        $objPages = PageModel::findBy(['type=? AND useSSL=?'], ['root', '1']);
        $aPages = [];
        $hasEmptyLicense = false;

        if( $objPages ) {

            foreach( $objPages as $key => $objPage ) {

                if( !safsewzk::hasFeature('link_shortener', $objPage->id) ) {
                    continue;
                }

                if( empty($objPage->dns) ) {
                    $hasEmptyLicense = true;
                    continue;
                }

                $aPages[$objPage->dns] = $objPage->dns;
            }
        }

        // add current value
        $domain = $dc->activeRecord?$dc->activeRecord->{$dc->field}:null;
        if( strlen($domain) && !in_array($domain, $aPages) ) {

            array_insert($aPages, 0, [$domain => $domain]);
        }

        if( $hasEmptyLicense ) {

            // add current domain
            $domain = Environment::get('httpHost');

            if( !in_array($domain, $aPages) ) {
                array_insert($aPages, 0, [$domain => $domain]);
            }
        }

        if( empty($aPages) && Input::get('id') ) {

            if( version_compare(VERSION, '4.11', '<') ) {
                Message::addError($GLOBALS['TL_LANG']['ERR']['link_shortener_no_https_domains']);
            } else {
                Message::addError($GLOBALS['TL_LANG']['ERR']['link_shortener_no_https_domains_411']);
            }
        }

        return $aPages;
    }


    /**
     * Generates the label for the overview
     *
     * @param array $row
     * @param string $label
     * @param DataContainer $dc
     * @param string $imageAttribute
     * @param boolean $blnReturnImage
     * @param boolean $blnProtected
     *
     * @return string
     */
    public function labelCallback( $row, $label, DataContainer $dc=null, $imageAttribute='', $blnReturnImage=false, $blnProtected=false ) {

        $this->loadLanguageFile('tl_cms_link_shortener_statistics');

        $strHtml = '<div class="cms_flex">';
        $strHtml .= '<span>';

        $strTarget = $this->replaceInserttags($row['target']);
        $id = null;
        if( preg_match("/{{link_url::([0-9]*)}}/", $row['target'], $id) ) {

            $objPage = PageModel::findOneById($id[1]);
            $strTarget = $objPage->title . ' (' . $objPage->alias . Config::get('urlSuffix') . ')';
        }

        if( $row['active'] && ($row['stop'] === '' || $row['stop'] > time()) ) {

            $strHtml .= $strTarget;
        } else {

            $strFallback = '404 Not Found';

            if( strlen($row['fallback']) ) {

                $strFallback = $this->replaceInserttags($row['fallback']);
            }

            $id = null;
            if( preg_match("/{{link_url::([0-9]*)}}/", $row['fallback'], $id) ) {

                $objPage = PageModel::findOneById($id[1]);
                $strFallback = $objPage->title . ' (' . $objPage->alias . Config::get('urlSuffix') . ')';
            }

            $strHtml .= "<s>".$strTarget."</s> -> ". $strFallback;
        }

        if( strlen($row['description']) ) {
            $strHtml .= "<br><i>".$row['description'] ."</i>";
        }

        $strHtml .= '</span>';
        $strHtml .= '<div class="cms_right">';

        $db = Database::getInstance();
        // count requests
        $objResult = $db->prepare("
            SELECT count(1) AS count
            FROM tl_cms_link_shortener_statistics
            WHERE pid=?
        ")->execute($row['id']);

        $label = $GLOBALS['TL_LANG']['tl_cms_link_shortener_statistics']['requests'];
        $strHtml .= '<span>'.$label.': <strong>'.$objResult->count.'</strong></span>';

        if( $objResult->count > 0 ) {
            // count unique requests
            $objResult = $db->prepare("
                SELECT DISTINCT unique_id
                FROM tl_cms_link_shortener_statistics
                WHERE pid=?
            ")->execute($row['id']);

            $label = $GLOBALS['TL_LANG']['tl_cms_link_shortener_statistics']['unique_requests'];
            $strHtml .= ' - <span>'.$label.': <strong>'.$objResult->numRows.'</strong></span>';

            // count bot requests
            $objResult = $db->prepare("
                SELECT count(1) AS bot_count
                FROM tl_cms_link_shortener_statistics
                WHERE pid=? AND is_bot=?
            ")->execute($row['id'], 1);

            $label = $GLOBALS['TL_LANG']['tl_cms_link_shortener_statistics']['bot_requests'];
            $strHtml .= ' - <span>'.$label.': <strong>'.$objResult->bot_count.'</strong></span>';
        }

        $strHtml .= "</div>";
        $strHtml .= "</div>";

        return $strHtml;
    }


    /**
     * Lock the input if it was given before
     *
     * @param string $value
     * @param DataContainer $dc
     *
     * @return string
     */
    public function lockIfNotEmpty( $value, DataContainer $dc ) {

        if( strlen($value) && $dc->activeRecord->tstamp > 0 ) {
            $GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['readonly'] = 'readonly';

            if( $GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['inputType'] == 'radio' ) {
                $GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['tl_class'] .= ' readonly';
            }
        }

        return $value;
    }


    /**
     * Return the "toggle visibility" button
     *
     * @param array $row
     * @param string $href
     * @param string $label
     * @param string $title
     * @param string $icon
     * @param string $attributes
     *
     * @return string
     */
    public function toggleIcon( $row, $href, $label, $title, $icon, $attributes ) {

        if( strlen(Input::get('tid')) ) {

            $id = Input::get('tid');
            $active = (Input::get('state') == 1)?'1':'';
            Database::getInstance()->prepare( "UPDATE tl_cms_link_shortener SET active=? WHERE id=?" )->execute($active, $id);

            $this->redirect($this->getReferer());
        }

        $href .= '&amp;tid='.$row['id'].'&amp;state='.$row['active'];

        if( !$row['active'] ) {
            $icon = 'invisible.svg';
        }

        return '<a href="'.$this->addToUrl($href).'" title="'.StringUtil::specialchars($title).'" '.$attributes.'>'.Image::getHtml($icon, $label, 'data-state="' . ($row['active'] ? 1 : 0) . '"').'</a> ';
    }


    /**
     * Add random id to the field
     *
     * @param string $value
     * @param DataContainer $dc
     *
     * @return string
     */
    public function addRandomId( $value, DataContainer $dc ) {

        if( !empty($dc->activeRecord->domain) ) {

            $chars = array_merge(range(0,9), range('a', 'z'));
            $length = 6;

            $unique = "";
            for( $i=0; $i < $length; $i++) {
                $unique .= $chars[mt_rand(0, count($chars) - 1)];
            }

            $value .= (strlen($value)?'/':'').$unique;
        }

        return $value;
    }


    /**
     * Check that the given value is unique
     *
     * @param string $value
     * @param DataContainer $dc
     *
     * @return string
     */
    public function checkUnique( $value, DataContainer $dc ) {

        $domain = $dc->activeRecord->domain;
        if( Input::post('domain') ) {
            $domain = Input::post('domain');
        }

        // remove leading / and double //
        $value = preg_replace("/\/+/", '/', $value);
        $value = preg_replace("/^\//", '', $value);

        $suffix = Config::get('urlSuffix');

        if( $value == "/" or preg_match('/.*\/index'.$suffix.'$/', $value) ) {
            throw new Exception($GLOBALS['TL_LANG']['ERR']['link_shortener_page_already_exist']);
        }

        if( empty($domain) || empty($value) ) {
            return $value;
        }

        $objResult = Database::getInstance()->prepare("
                SELECT
                    *
                FROM $dc->table
                WHERE domain=? AND (prefix=? OR alias=?) AND id!=?
            ")->execute($domain, $value, $value, $dc->activeRecord->id);

        if( $objResult && $objResult->numRows ) {
            throw new Exception($GLOBALS['TL_LANG']['ERR']['link_shortener_alias_already_exist']);
        }

        return $value;
    }


    /**
     * Check if this url is already in use
     *
     * @param string $value
     * @param DataContainer $dc
     *
     * @return string
     */
    public function checkPageAlreadyExists( $value, DataContainer $dc ) {

        $domain = $dc->activeRecord->domain;
        if( !empty(Input::post('domain')) ) {
            $domain = Input::post('domain');
        }

        if( empty($domain) || empty($value) ) {
            return $value;
        }

        $client = null;
        $client = HttpClient::create([
            'headers' => [
                'user-agent' => 'Contao Marketig Suite '.CMS_VERSION
            ]
        ,   'timeout' => 5
        ,   'max_duration' => 5
        ,   'max_redirects' => 0
        ,   'verify_peer' => false
        ,   'verify_host' => false
        ]);

        $code = 0;

        try {

            $response = null;
            $response = $client->request('HEAD', 'https://'.$domain.'/'.$value);

            $code = $response->getStatusCode();

        } catch( Exception $e ) {
        }

        if( (int)$code == 200 ) {
            throw new Exception($GLOBALS['TL_LANG']['ERR']['link_shortener_page_already_exist']);
        }

        return $value;
    }


    /**
     * Return the "reset_counter" button
     *
     * @param array  $row
     * @param string $href
     * @param string $label
     * @param string $title
     * @param string $icon
     * @param string $attributes
     *
     * @return string
     */
    public function resetCounter( $row, $href, $label, $title, $icon, $attributes ) {

        if( strlen(Input::get('rid')) ) {

            $id = Input::get('rid');

            if( $id == $row['id'] ) {

                Database::getInstance()->prepare( "DELETE FROM tl_cms_link_shortener_statistics WHERE pid=?")->execute($id);
                $this->redirect($this->getReferer());
            }
        }

        $href .= '&amp;rid='.$row['id'];

        return '<a href="'.$this->addToUrl($href).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label, '').'</a> ';
    }
}
