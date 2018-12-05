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
 * Add palettes to tl_page
 */
$GLOBALS['TL_DCA']['tl_page']['palettes']['regular'] = str_replace(
    ',description;'
,   ',description,snippet_preview;'
,   $GLOBALS['TL_DCA']['tl_page']['palettes']['regular']
);
$GLOBALS['TL_DCA']['tl_page']['palettes']['root'] = str_replace(
    ';{protected_legend'
,   ';{cms_legend:hide},cms_root_license,cms_refresh_license;{protected_legend'
,   $GLOBALS['TL_DCA']['tl_page']['palettes']['root']
);


/**
 * Add fields to tl_page
 */
$GLOBALS['TL_DCA']['tl_page']['fields'] = array_merge(
    $GLOBALS['TL_DCA']['tl_page']['fields']
,   [
        'cms_root_license' => [
            'label'                 => &$GLOBALS['TL_LANG']['tl_page']['cms_root_license']
        ,   'inputType'             => 'text'
        ,   'exclude'               => true
        ,   'save_callback'         => [ ['tl_page_cms', 'saveLicense'] ]
        ,   'load_callback'         => [ ['tl_page_cms', 'checkLicense'] ]
        ,   'eval'                  => ['maxlength'=>255, 'doNotCopy'=>true, 'tl_class'=>'w50 clr']
        ,   'sql'                   => "varchar(255) NOT NULL default ''"
        ]
    ,   'cms_refresh_license' => [
            'label' => &$GLOBALS['TL_LANG']['tl_page']['cms_refresh_license']
        ,   'input_field_callback'  => ['tl_page_cms', 'refreshLicense']
        ]
    ,   'cms_root_key' => [
            'eval'                  => ['doNotShow'=>true, 'doNotCopy'=>true]
        ,   'sql'                   => "blob NULL"
        ]
    ,   'cms_root_data' => [
            'eval'                  => ['doNotShow'=>true, 'doNotCopy'=>true]
        ,   'sql'                   => "blob NULL"
        ]
    ,   'cms_root_sign' => [
            'eval'                  => ['doNotShow'=>true, 'doNotCopy'=>true]
        ,   'sql'                   => "blob NULL"
        ]
    ]
);


// disable snippet-preview in multi edit mode
if( \Input::get('act') != 'editAll' ) {

    if( \numero2\MarketingSuite\Backend\License::hasFeature('page_snippet_preview') ) {
        $GLOBALS['TL_DCA']['tl_page']['fields']['snippet_preview'] = [
            'label' => &$GLOBALS['TL_LANG']['tl_page']['snippet_preview']
        ,   'input_field_callback'  => ['\numero2\MarketingSuite\Widget\SnippetPreview', 'generate']
        ];

        $GLOBALS['TL_DCA']['tl_page']['fields']['pageTitle']['label'][0] .= '<span class="snippet-count" data-template="'.$GLOBALS['TL_LANG']['MSC']['snippet_count'].'"></span>';
        $GLOBALS['TL_DCA']['tl_page']['fields']['pageTitle']['eval']['tl_class'] .= ' snippet';
        $GLOBALS['TL_DCA']['tl_page']['fields']['pageTitle']['eval']['data-snippet-length'] = \numero2\MarketingSuite\Widget\SnippetPreview::TITLE_LENGTH;
        $GLOBALS['TL_DCA']['tl_page']['fields']['pageTitle']['wizard'][] = ['\numero2\MarketingSuite\Widget\SnippetPreview', 'generateInputField'];

        $GLOBALS['TL_DCA']['tl_page']['fields']['description']['label'][0] .= '<span class="snippet-count" data-template="'.$GLOBALS['TL_LANG']['MSC']['snippet_count'].'"></span>';
        $GLOBALS['TL_DCA']['tl_page']['fields']['description']['eval']['tl_class'] .= ' snippet';
        $GLOBALS['TL_DCA']['tl_page']['fields']['description']['eval']['data-snippet-length'] = \numero2\MarketingSuite\Widget\SnippetPreview::DESCRIPTION_LENGTH;
        $GLOBALS['TL_DCA']['tl_page']['fields']['description']['wizard'][] = ['\numero2\MarketingSuite\Widget\SnippetPreview', 'generateInputField'];
    }
}


class tl_page_cms extends Backend {


    /**
     * Saves the license and empties dependent fields on change
     *
     * @param mixed $value
     * @param \DataContainer $dc
     *
     * @return string
     *
     * @throws \Exception
     */
    public function saveLicense( $value, DataContainer $dc ) {

        // new license key, drop old data
        if( $value != $dc->activeRecord->cms_root_license ) {

            $objResult = \Database::getInstance()->prepare("UPDATE tl_page SET cms_root_data=NULL, cms_root_key=NULL, cms_root_sign=NULL where id=?")
                ->execute($dc->activeRecord->id);

            $dc->activeRecord->cms_root_data = NULL;
            $dc->activeRecord->cms_root_key = NULL;
            $dc->activeRecord->cms_root_sign = NULL;

            // check license
            if( !empty($value) && (empty($dc->activeRecord->cms_root_data) || empty($dc->activeRecord->cms_root_key) || empty($dc->activeRecord->cms_root_sign) )  ) {

                $objPage = NULL;
                $objPage = \PageModel::findOneById($dc->activeRecord->id);

                if( $objPage ) {

                    $oAPI = NULL;
                    $oAPI = new \numero2\MarketingSuite\Api\MarketingSuite();

                    try {

                        if( $oAPI->checkLicense($value, $objPage) ) {
                            $oAPI->getFeatures($value, $objPage);
                        }

                    } catch( \Exception $e ) {
                        $this->handleLicenseCheckException($e,true);
                    }
                }
            }
        }

        return $value;
    }


    /**
     * Checks if the license is still valid
     *
     * @param mixed $value
     * @param DataContainer $dc
     *
     * @return string
     */
    public function checkLicense( $value, DataContainer $dc ) {

        \numero2\MarketingSuite\Backend\License::sepcop();

        if( $value && !\Input::post('cms_root_license') ) {

            $objPage = NULL;
            $objPage = \PageModel::findOneById($dc->activeRecord->id);

            if( $objPage ) {

                $oAPI = NULL;
                $oAPI = new \numero2\MarketingSuite\Api\MarketingSuite();

                try {

                    if( $oAPI->checkLicense($value, $objPage) ) {

                        \Message::addNew( sprintf(
                            $GLOBALS['TL_LANG']['cms_api_messages']['license_valid']
                        ,   \Date::parse(\Config::get('datimFormat'), time())
                        ));
                    }

                } catch( \Exception $e ) {

                    $this->handleLicenseCheckException($e);
                }
            }
        }

        return $value;
    }


    /**
     * Handles the given exception
     *
     * @param \Exception $e Exception as returned by API
     * @param bool $throw Re-throw the given exception
     *
     * @throws \Exception
     */
    private function handleLicenseCheckException( \Exception $e, $throw=false ) {

        switch( $e->getCode() ) {

            case 1000:
                \Message::addInfo($GLOBALS['TL_LANG']['cms_api_messages']['errors']['connection_error']);
            break;

            default:

                $msg = $GLOBALS['TL_LANG']['cms_api_messages']['errors'][$e->getCode()]?:$GLOBALS['TL_LANG']['cms_api_messages']['errors']['unknown_error'];

                if( $throw ) {

                    throw new \Exception(
                        $msg
                    ,   $e->getCode()
                    );

                } else {
                    \Message::addError($msg);
                }

            break;
        }
    }


    /**
     * generates a button that will refresh the license data if it was clicked.
     *
     * @param  \DataContainer $dc
     *
     * @return string html markup for the button
     */
    public function refreshLicense( $dc ) {

        $label = $GLOBALS['TL_DCA']['tl_page']['fields']['cms_refresh_license']['label'];
        $objPage = \PageModel::findOneById($dc->activeRecord->id);

        if( \Input::get('cms_license') && \Input::get('cms_license') == 'refresh' ) {

            $oAPI = NULL;
            $oAPI = new \numero2\MarketingSuite\Api\MarketingSuite();

            try {

                if( $objPage && !empty($objPage->cms_root_license) ) {
                    if( $oAPI->checkLicense($objPage->cms_root_license, $objPage) ) {
                        $oAPI->getFeatures($objPage->cms_root_license, $objPage);
                    }
                }

            } catch( \Exception $e ) {
                $this->handleLicenseCheckException($e,false);
            }

            $this->redirect($this->addToUrl('', true, ['cms_license']));
        }

        if( !$objPage || empty($objPage->cms_root_license) ) {
            return '';
        }

        return
            '<div class="w50 widget">'
                .'<h3>'.$label[0].'</h3>'
                .'<a class ="button" href="'.$this->addToUrl('cms_license=refresh').'">'.$label['button'].'</a>'
                .'<p class="tl_help tl_tip" title="">'.$label[1].'</p>'
            .'</div>';
    }
}
