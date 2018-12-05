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


/**
 * Namespace
 */
namespace numero2\MarketingSuite\Widget;

use numero2\MarketingSuite\Backend;


class SnippetPreview {


    /**
     * Maximum length for snippet preview title and description
     * @var integer
     */
    const TITLE_LENGTH = 60;
    const DESCRIPTION_LENGTH = 158;


    /**
     * Displays a dynamic preview of the given meta data
     *
     * @param \DataContainer $dc
     *
     * @return string
     */
    public function generate( \DataContainer $dc ) {

        // if( !\numero2\MarketingSuite\Backend\License::hasFeature('page_snippet_preview') ) {
        //     return '';
        // }

        $objPage = NULL;
        $objPage = \PageModel::findById( $dc->activeRecord->id );

        $title = $dc->activeRecord->pageTitle ? $dc->activeRecord->pageTitle : $dc->activeRecord->title;
        $description = $dc->activeRecord->description;

        if( strlen($title) > self::TITLE_LENGTH ) {
            $title = substr($title,0,self::TITLE_LENGTH) . ' ...';
        }

        if( strlen($description) > self::DESCRIPTION_LENGTH ) {
            $description = substr($description,0,self::DESCRIPTION_LENGTH) . ' ...';
        }

        $aData = [
            'title' => $title
        ,   'uri' => $objPage->getAbsoluteUrl()
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
    public function generateInputField( \DataContainer $dc ) {

        $fieldName = $dc->field;
        $value = $dc->activeRecord->{$fieldName};

        $class = $GLOBALS['TL_DCA']['tl_page']['fields'][$fieldName]['inputType'];

        return '<div class="tl_'.$class.'" contenteditable="true">'.$value.'</div>';
    }
}
