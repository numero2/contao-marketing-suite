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


namespace numero2\MarketingSuite\Widget;

use Contao\DataContainer;
use Contao\PageModel;
use Contao\Model;
use Contao\Environment;
use numero2\MarketingSuite\Backend;
use numero2\MarketingSuite\Backend\License as jebto;


class SnippetPreview {


    /**
     * Maximum length for snippet preview title and description
     * @var integer
     */
    const TITLE_MIN_LENGTH = 30;
    const TITLE_MAX_LENGTH = 60;
    const DESCRIPTION_MIN_LENGTH = 79;
    const DESCRIPTION_MAX_LENGTH = 158;


    /**
     * Displays a dynamic preview of the given meta data
     *
     * @param \DataContainer $dc
     *
     * @return string
     */
    public function generate( DataContainer $dc ) {

        if( !jebto::hasFeature('page_snippet_preview') ) {
            return '';
        }

        $objPage = NULL;
        if( $dc->table == 'tl_page' ) {
            $objPage = PageModel::findById( $dc->activeRecord->id );
        } else {
            $parentTable = Model::getClassFromTable($dc->table);

            if( $parentTable !== "Model" ) {

                $objElement = $parentTable::findOneById($dc->activeRecord->id);
                $objArchive = $objElement->getRelated('pid');

                if( !empty($objArchive->jumpTo) ) {
                    $objPage = PageModel::findById( $objArchive->jumpTo );
                }
            }
        }

        $title = $dc->activeRecord->pageTitle ?  : ($dc->activeRecord->title ?  : $dc->activeRecord->headline);
        $description = $dc->activeRecord->description;

        if( strlen($title) > self::TITLE_MAX_LENGTH ) {
            $title = substr($title,0,self::TITLE_MAX_LENGTH) . ' ...';
        }

        if( strlen($description) > self::DESCRIPTION_MAX_LENGTH ) {
            $description = substr($description,0,self::DESCRIPTION_MAX_LENGTH) . ' ...';
        }

        $aData = [
            'title' => $title
        ,   'uri' => $objPage?$objPage->getAbsoluteUrl():(Environment::get('base').'...')
        ,   'description' => $description
        ,   'headline' => $GLOBALS['TL_LANG'][$dc->table]['snippet_preview'][0]
        ];

        return Backend::parseWithTemplate('backend/widgets/snippet_preview', $aData);
    }


    /**
     * Generate a "fake" input field with some highlighting functionality
     *
     * @param \DataContainer $dc
     *
     * @return string
     */
    public function generateInputField( DataContainer $dc ) {

        $fieldName = $dc->field;
        $value = $dc->activeRecord->{$fieldName};

        $type = $GLOBALS['TL_DCA'][$dc->table]['fields'][$fieldName]['inputType'];

        if( $type === "textarea" ) {
            $type = "text";
        }

        return '<div class="tl_'.$type.'" contenteditable="true">'.$value.'</div>';
    }


    /**
     * Adds html mark up to the label to show live count
     *
     * @param string $value
     * @param \DataContainer $dc
     *
     * @return string
     */
    public function addSnippetCount($value, DataContainer $dc) {

        $GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['label'][0] .= '<span class="snippet-count" data-template="'.$GLOBALS['TL_LANG']['MSC']['snippet_count'].'"></span>';

        return $value;
    }
}
