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


namespace numero2\MarketingSuite\DCAHelper;

use Contao\Backend as CoreBackend;
use Contao\Config;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\Date;
use Contao\Image;
use Contao\Input;
use Contao\StringUtil;
use numero2\MarketingSuite\Backend\Help;
use numero2\MarketingSuite\ContentGroupModel;
use numero2\MarketingSuite\MarketingItem\MarketingItem as AbstractMI;
use numero2\MarketingSuite\MarketingItemModel;


class ContentGroup extends CoreBackend {


    private $elementIndex = 0;


    /**
     * Adds help depending on the marketing item type
     *
     * @param  [type]  $dc
     * @return string
     */
    public function addHelp( $dc ) {

        $objMI = NULL;
        $objMI = MarketingItemModel::findById($dc->id);

        if( $objMI ) {

            $oHelp = NULL;
            $oHelp = new Help();

            $oHelp->suffix = $objMI->type;

            return $oHelp->generate();
        }
    }


    /**
     * Add the type of content element
     *
     * @param array $arrRow
     *
     * @return string
     */
    public function addCteType( $arrRow ) {

        $key = 'published';

        $objOtherRow = $this->Database->prepare("SELECT * FROM tl_cms_content_group WHERE pid=? AND id!=?")
            ->limit(1)
            ->execute($arrRow['pid'], $arrRow['id']);

        if( $arrRow['always_use_this'] || $objOtherRow->always_use_this ) {
            $key = $arrRow['always_use_this'] ? 'published' : 'unpublished';
        }

        $class = 'limit_height';

        $objMI = NULL;
        $objMI = MarketingItemModel::findById($arrRow['pid']);

        if( $objMI->ranking ) {

            $intervall = deserialize($objMI->intervall);

            $start = $objMI->start_ranking;
            $change = strtotime('+'.$intervall['value'].' '.$intervall['unit'], $objMI->start_ranking);
            $end = strtotime('+'.(2*$intervall['value']).' '.$intervall['unit'], $objMI->start_ranking);

            $this->$elementIndex+=1;
            if( $this->$elementIndex === 1 ){

                $start = Date::parse(Config::get('dateFormat'), $start);
                $end = Date::parse(Config::get('dateFormat'), $change-86400);
            } else {

                $start = Date::parse(Config::get('dateFormat'), $change);
                $end = Date::parse(Config::get('dateFormat'), $end-86400);
            }

            return '
            <div class="cte_type ' . $key . '">' . $arrRow['name'] . ' <span class="cms_info">(Zeitraum: ' . $start . ' - ' . $end . ')</span></div>'
            // add statistics
            // <div class="' . trim($class) . '">
            // ' . StringUtil::insertTagToSrc($this->getContentElement($objModel)) . '
            // </div>'
            ."\n";
        }

        return '
        <div class="cte_type ' . $key . '">' . $arrRow['name'] . '</div>'
        // add statistics
        // <div class="' . trim($class) . '">
        //     ' . StringUtil::insertTagToSrc($this->getContentElement($objModel)) . '
        // </div>'
        . "\n";
        // list element
        //
    }


    public function addHeaderInfo( $args, $dc ) {

        self::loadLanguageFile('tl_cms_marketing_item');

        $objMI = NULL;
        $objMI = MarketingItemModel::findById($dc->id);

        // add status field for a_b_test
        $args[$GLOBALS['TL_LANG']['tl_cms_marketing_item']['status'][0]] = AbstractMI::getChildInstance($objMI->type)->getStatus($objMI->row());

        if( !$objMI->ranking ) {

            $args[$GLOBALS['TL_LANG']['tl_cms_marketing_item']['child_header_label']['a_b_test_info'][0]] = $GLOBALS['TL_LANG']['tl_cms_marketing_item']['child_header_label']['a_b_test_info'][1];

        } else {

            $args[$GLOBALS['TL_LANG']['tl_cms_marketing_item']['keyword'][0]] = $objMI->keyword;
            $args[$GLOBALS['TL_LANG']['tl_cms_marketing_item']['child_header_label']['a_b_test_info'][0]] = $GLOBALS['TL_LANG']['tl_cms_marketing_item']['child_header_label']['a_b_test_info']['ranking'];
        }

        // override back button
        $GLOBALS['TL_MOOTOOLS'][] = "<script>document.querySelector('a.header_back') && (document.querySelector('a.header_back').href = 'contao?do=cms_marketing');</script>";

        return $args;
    }


    /**
     * Return all types as array
     *
     * @return array
     */
    public function getTypes() {

        self::loadLanguageFile('tl_cms_marketing_item');

        $types = [];

        foreach( $GLOBALS['TL_DCA']['tl_cms_content_group']['palettes'] as $k=>$v ) {

            if( $k == '__selector__' ){
                continue;
            }

            if( empty($GLOBALS['TL_LANG']['tl_cms_marketing_item']['types'][$k]) ){
                $types[$k] = $k;
            } else {
                $types[$k] = $GLOBALS['TL_LANG']['tl_cms_marketing_item']['types'][$k];
            }
        }

        return $types;
    }


    /**
     * Return the "toggle visibility" button
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
    public function toggleAlwaysUseThis( $row, $href, $label, $title, $icon, $attributes ) {

        $active = $row['always_use_this'];

        $objAlways = ContentGroupModel::findOneBy(["pid=? AND always_use_this=?"],[$row['pid'], 1]);

        if( strlen(Input::get('tid')) ) {

            // Set the ID and action
            $intId = Input::get('tid');
            Input::setGet('id', $intId);
            Input::setGet('act', 'toggle');
            $active = Input::get('state') == 1 ? '1' : '';

            $time = time();

            // check that only one can be active
            $objRow = null;

            if( $active ) {

                $objRow = $this->Database->prepare("SELECT * FROM tl_cms_content_group WHERE id=?")
                    ->limit(1)
                    ->execute($intId);

                $objOtherRow = $this->Database->prepare("SELECT * FROM tl_cms_content_group WHERE pid=? AND id!=?")
                    ->limit(1)
                    ->execute($objRow->pid, $intId);

                if( $objOtherRow->always_use_this ) {
                    $active = '';
                    throw new AccessDeniedException('You cannot set multiple content groups to always_use_this.');
                }
            }

            // Update the database
            if( !$active || ($objRow && $objRow->always_use_this != $active) ) {

                $this->Database->prepare("UPDATE tl_cms_content_group SET tstamp=?, always_use_this=? WHERE id=?")
                    ->execute($time, $active, $intId);
            }

            $this->redirect($this->getReferer());
        }

        $href .= '&amp;id='.Input::get('id').'&amp;tid='.$row['id'].'&amp;state='.$active;

        $icond = 'bundles/marketingsuite/img/backend/icons/icon_always_use_this_.svg';

        if( !$active ) {
            $path = $icond;
            $title = sprintf($GLOBALS['TL_LANG']['tl_cms_content_group']['toggle_always_use_this'][0], $row['name']);
            $label = $title;
        } else {
            $path = $icon;
            $title = sprintf($GLOBALS['TL_LANG']['tl_cms_content_group']['toggle_always_use_this'][1], $row['name']);
            $label = $title;
        }

        if( $objAlways && $objAlways->id != $row['id'] ) {
            return '';
        }

        return '<a href="'.$this->addToUrl($href).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($path, $label, 'data-state="' . $active . '"').'</a> ';

    }
}
