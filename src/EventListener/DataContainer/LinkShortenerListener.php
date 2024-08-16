<?php

/**
 * Contao Marketing Suite Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\MarketingSuiteBundle\EventListener\DataContainer;

use Contao\ArrayUtil;
use Contao\Backend;
use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\Database;
use Contao\DataContainer;
use Contao\Environment;
use Contao\Image;
use Contao\Input;
use Contao\Message;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Exception;
use numero2\MarketingSuite\Backend\License as safsewzk;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpClient\HttpClient;


class LinkShortenerListener {


    /**
     * @var Contao\CoreBundle\InsertTag\InsertTagParser
     */
    private $insertTagParser;

    /**
     * @var Symfony\Bundle\SecurityBundle\Security
     */
    private $security;


    public function __construct( InsertTagParser $insertTagParser, Security $security ) {

        $this->insertTagParser = $insertTagParser;
        $this->security = $security;
    }


    /**
     * Returns all valid domains
     *
     * @param Contao\DataContainer $dc
     *
     * @return array
     *
     * @Callback(table="tl_cms_link_shortener", target="fields.domain.options")
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

            ArrayUtil::arrayInsert($aPages, 0, [$domain => $domain]);
        }

        if( $hasEmptyLicense ) {

            // add current domain
            $domain = Environment::get('httpHost');

            if( !in_array($domain, $aPages) ) {
                ArrayUtil::arrayInsert($aPages, 0, [$domain => $domain]);
            }
        }

        if( empty($aPages) && Input::get('id') ) {
            Message::addError($GLOBALS['TL_LANG']['ERR']['link_shortener_no_https_domains']);
        }

        return $aPages;
    }


    /**
     * Generates the label for the overview
     *
     * @param array $row
     * @param string $label
     * @param Contao\DataContainer $dc
     * @param string $imageAttribute
     * @param boolean $blnReturnImage
     * @param boolean $blnProtected
     *
     * @return string
     *
     * @Callback(table="tl_cms_link_shortener", target="list.label.label")
     */
    public function labelCallback( $row, $label, DataContainer $dc=null, $imageAttribute='', $blnReturnImage=false, $blnProtected=false ) {

        System::loadLanguageFile('tl_cms_link_shortener_statistics');

        $strHtml = '<div class="cms_flex">';
        $strHtml .= '<span>';

        $strTarget = $this->insertTagParser->replace($row['target']);
        $id = null;
        if( preg_match("/{{link_url::([0-9]*)}}/", $row['target'], $id) ) {

            $objPage = PageModel::findOneById($id[1]);
            if( $objPage ) {
                $strTarget = $objPage->title . ' (' . $objPage->alias . Config::get('urlSuffix') . ')';
            }
        }

        if( $row['active'] && ($row['stop'] === '' || $row['stop'] > time()) ) {

            $strHtml .= $strTarget;
        } else {

            $strFallback = '404 Not Found';

            if( strlen($row['fallback']) ) {

                $strFallback = $this->insertTagParser->replace($row['fallback']);
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
     * @param Contao\DataContainer $dc
     *
     * @return string
     *
     * @Callback(table="tl_cms_link_shortener", target="fields.domain.load")
     * @Callback(table="tl_cms_link_shortener", target="fields.prefix.load")
     * @Callback(table="tl_cms_link_shortener", target="fields.alias.load")
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
     *
     * @Callback(table="tl_cms_link_shortener", target="list.operations.toggle.button")
     */
    public function toggleIcon( $row, $href, $label, $title, $icon, $attributes ) {

        // Check permissions AFTER checking the tid, so hacking attempts are logged
        if( !$this->security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE, 'tl_cms_link_shortener::active') ) {
            return '';
        }

        $href .= '&amp;id=' . $row['id'];

        if( !$row['active'] ) {
            $icon = 'invisible.svg';
        }

        return '<a href="' . Backend::addToUrl($href) . '" title="' . StringUtil::specialchars($title) . '" data-action="contao--scroll-offset#store" onclick="return AjaxRequest.toggleField(this,true)">' . Image::getHtml($icon, $label, 'data-icon="' . Controller::addStaticUrlTo(Image::getPath('visible.svg')) . '" data-icon-disabled="' . Controller::addStaticUrlTo(Image::getPath('invisible.svg')) . '" data-state="' . ($row['active'] ? 1 : 0) . '"') . '</a> ';
    }


    /**
     * Add random id to the field
     *
     * @param string $value
     * @param Contao\DataContainer $dc
     *
     * @return string
     *
     * @Callback(table="tl_cms_link_shortener", target="fields.prefix.save")
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
     * @param Contao\DataContainer $dc
     *
     * @return string
     *
     * @Callback(table="tl_cms_link_shortener", target="fields.prefix.save")
     * @Callback(table="tl_cms_link_shortener", target="fields.alias.save")
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
     * @param Contao\DataContainer $dc
     *
     * @return string
     *
     * @Callback(table="tl_cms_link_shortener", target="fields.prefix.save")
     * @Callback(table="tl_cms_link_shortener", target="fields.alias.save")
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
     *
     * @Callback(table="tl_cms_link_shortener", target="list.operations.reset_counter.button")
     */
    public function resetCounter( $row, $href, $label, $title, $icon, $attributes ) {

        if( strlen(Input::get('rid')) ) {

            $id = Input::get('rid');

            if( $id == $row['id'] ) {

                Database::getInstance()->prepare("DELETE FROM tl_cms_link_shortener_statistics WHERE pid=?")->execute($id);
                Controller::redirect(System::getReferer());
            }
        }

        $href .= '&amp;rid='.$row['id'];

        return '<a href="'.Backend::addToUrl($href).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label, '').'</a> ';
    }
}
