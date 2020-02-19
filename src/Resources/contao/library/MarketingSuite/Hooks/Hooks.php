<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2020 Leo Feyer
 *
 * @package   Contao Marketing Suite
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright 2020 numero2 - Agentur für digitales Marketing
 */


namespace numero2\MarketingSuite\Hooks;

use Contao\CMSConfig;
use Contao\Controller;
use Contao\CoreBundle\Exception\InternalServerErrorHttpException;
use Contao\CoreBundle\Exception\NoContentResponseException;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\Dbafs;
use Contao\DC_CMSFile;
use Contao\FilesModel;
use Contao\Input;
use Contao\StringUtil;
use Contao\Widget;
use numero2\MarketingSuite\Widget\ElementStyle;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;


class Hooks extends \Backend {


    /**
     * Validate a custom regular expression
     *
     * @param string $strRgxp
     * @param mixed $varValue
     * @param \Widget $objWidget
     *
     * @return bool
     */
    public static function validateRgxp( $strRgxp, $varValue, Widget $objWidget ) {

        switch( $strRgxp ) {

            case 'natural_min_4_weeks':

                self::validateRgxp('natural', $varValue, $objWidget);

                $aMin = [
                    'day' => 28
                ,   'week' => 4
                ,   'month' => 1
                ];

                $unit = array_keys($aMin)[0];

                if( !empty($objWidget->value['unit']) ){
                    $unit = $objWidget->value['unit'];
                }
                $postUnit = Input::post($objWidget->name);

                if( !empty($postUnit['unit']) ){
                    $unit = $postUnit['unit'];
                }

                if( $varValue < $aMin[$unit] ) {
                    $objWidget->addError($GLOBALS['TL_LANG']['ERR']['natural_min_4_weeks']);
                }

                if( $varValue <= 0 ) {
                    $objWidget->addError($GLOBALS['TL_LANG']['ERR']['natural_greater_0']);
                }

                return true;
                break;

            case 'cms_url':

                if( preg_match('/^{{[^{}]+}}$/', $varValue) ) {
                    return true;
                }

                $varValue = Controller::replaceInsertTags($varValue);

                $parsed = parse_url($varValue);

                if( !is_array($parsed) ) {
                    $objWidget->addError(sprintf($GLOBALS['TL_LANG']['ERR']['cms_url_parse_failed'], $varValue));
                }

                if( !isset($parsed['scheme']) ) {
                    $objWidget->addError(sprintf($GLOBALS['TL_LANG']['ERR']['cms_url_scheme_missing'], $varValue));
                }
                if( !isset($parsed['host']) ) {
                    $objWidget->addError(sprintf($GLOBALS['TL_LANG']['ERR']['cms_url_host_missing'], $varValue));
                }

                return true;

        }

        return false;
    }


    /**
     * Perform pre action hook
     *
     * @param string $strAction
     */
    public function executePreActions( $strAction ) {

        if( $strAction === 'updateElementPreview' ) {

            $oElement = NULL;
            $oElement = new ElementStyle();

            $dc = (object) [
                'table' => \Input::get('table')
            ,   'activeRecord' => (object) [
                    'id' => Input::post('id')
                ,   'type' => Input::post('type')
                ]
            ];

            $sMarkup = '';
            $sMarkup = $oElement->generatePreview( $dc, $_POST );

            $oResponse = NULL;
            $oResponse = new Response( Controller::replaceOldBePaths($sMarkup) );

            throw new ResponseException($oResponse);
        }
    }


    /**
     * This will be called when the system will be initialized.
     */
    public static function initializeSystem() {

        //initialize CMSConfig
        CMSConfig::getInstance();
    }


    /**
     * Will provide the useful postActionHook features from the core for our
     * own DC_CMSFile data container
     *
     * @param string $strAction
     * @param \DC_CMSFile $dc
     *
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @throws \Contao\CoreBundle\Exception\ResponseException
     * @throws \Contao\CoreBundle\Exception\NoContentResponseException
     * @throws \Contao\CoreBundle\Exception\InternalServerErrorHttpException
     */
    public function postActionHookForDC_CMSFile( $strAction, $dc ) {

        // only use when DC_CMSFile
        if( !$dc instanceof DC_CMSFile ) {
            return ;
        }

        switch( $strAction ) {

            // Load nodes of the file tree
            case 'loadFiletree':

                $varValue = null;
                $strField = $dc->field = Input::post('name');

                // Call the load_callback
                if( \is_array($GLOBALS['TL_DCA'][$dc->table]['fields'][$strField]['load_callback']) ) {

                    foreach( $GLOBALS['TL_DCA'][$dc->table]['fields'][$strField]['load_callback'] as $callback ) {

                        if( \is_array($callback) ) {

                            $this->import($callback[0]);
                            $varValue = $this->{$callback[0]}->{$callback[1]}($varValue, $dc);

                        } else if( \is_callable($callback) ) {

                            $varValue = $callback($varValue, $dc);
                        }
                    }
                }

                /** @var FileSelector $strClass */
                $strClass = $GLOBALS['BE_FFL']['fileSelector'];

                /** @var FileSelector $objWidget */
                $objWidget = new $strClass($strClass::getAttributesFromDca($GLOBALS['TL_DCA'][$dc->table]['fields'][$strField], $dc->field, $varValue, $strField, $dc->table, $dc));

                // Load a particular node
                if( Input::post('folder', true) != '' )  {
                    throw new ResponseException($this->convertToResponse($objWidget->generateAjax(Input::post('folder', true), Input::post('field'), (int) Input::post('level'))));
                }

                throw new ResponseException($this->convertToResponse($objWidget->generate()));

            // Reload the page/file picker
            case 'reloadPagetree':
            case 'reloadFiletree':

                $intId = Input::get('id');
                $strField = $dc->inputName = Input::post('name');

                // Handle the keys in "edit multiple" mode
                if( Input::get('act') == 'editAll' ) {

                    $intId = preg_replace('/.*_([0-9a-zA-Z]+)$/', '$1', $strField);
                    $strField = preg_replace('/(.*)_[0-9a-zA-Z]+$/', '$1', $strField);
                }

                $dc->field = $strField;

                // The field does not exist
                if( !isset($GLOBALS['TL_DCA'][$dc->table]['fields'][$strField]) ) {

                    $this->log('Field "' . $strField . '" does not exist in DCA "' . $dc->table . '"', __METHOD__, TL_ERROR);
                    throw new BadRequestHttpException('Bad request');
                }

                $objRow = null;
                $varValue = null;

                // Load the value
                if( Input::get('act') != 'overrideAll' ) {
                    $varValue = CMSConfig::get($strField);
                }

                // Call the load_callback
                if( \is_array($GLOBALS['TL_DCA'][$dc->table]['fields'][$strField]['load_callback']) ) {

                    foreach( $GLOBALS['TL_DCA'][$dc->table]['fields'][$strField]['load_callback'] as $callback ) {

                        if( \is_array($callback) ) {

                            $this->import($callback[0]);
                            $varValue = $this->{$callback[0]}->{$callback[1]}($varValue, $dc);

                        } else if( \is_callable($callback) ) {

                            $varValue = $callback($varValue, $dc);
                        }
                    }
                }

                // Set the new value
                $varValue = Input::post('value', true);
                $strKey = ($strAction == 'reloadPagetree') ? 'pageTree' : 'fileTree';

                // Convert the selected values
                if( $varValue != '' ) {

                    $varValue = StringUtil::trimsplit("\t", $varValue);

                    // Automatically add resources to the DBAFS
                    if( $strKey == 'fileTree' ) {

                        foreach( $varValue as $k=>$v ) {

                            $v = rawurldecode($v);

                            if( Dbafs::shouldBeSynchronized($v) ) {

                                $objFile = FilesModel::findByPath($v);

                                if( $objFile === null ) {

                                    $objFile = Dbafs::addResource($v);
                                }

                                $varValue[$k] = $objFile->uuid;
                            }
                        }
                    }

                    $varValue = serialize($varValue);
                }

                /** @var FileTree|PageTree $strClass */
                $strClass = $GLOBALS['BE_FFL'][$strKey];

                /** @var FileTree|PageTree $objWidget */
                $objWidget = new $strClass($strClass::getAttributesFromDca($GLOBALS['TL_DCA'][$dc->table]['fields'][$strField], $dc->inputName, $varValue, $strField, $dc->table, $dc));

                throw new ResponseException($this->convertToResponse($objWidget->generate()));

            // Toggle subpalettes
            case 'toggleSubpalette':

                $this->import('BackendUser', 'User');

                // Check whether the field is a selector field and allowed for regular users (thanks to Fabian Mihailowitsch) (see #4427)
                if( !\is_array($GLOBALS['TL_DCA'][$dc->table]['palettes']['__selector__']) || !\in_array(Input::post('field'), $GLOBALS['TL_DCA'][$dc->table]['palettes']['__selector__']) || ($GLOBALS['TL_DCA'][$dc->table]['fields'][Input::post('field')]['exclude'] && !$this->User->hasAccess($dc->table . '::' . Input::post('field'), 'alexf')) ) {

                    $this->log('Field "' . Input::post('field') . '" is not an allowed selector field (possible SQL injection attempt)', __METHOD__, TL_ERROR);
                    throw new BadRequestHttpException('Bad request');
                }

                $val = ((Input::post('state') == 1) ? true : false);
                CMSConfig::persist(Input::post('field'), $val);

                if( Input::post('load') ) {

                    CMSConfig::set(Input::post('field'), $val);

                    throw new ResponseException($this->convertToResponse($dc->edit(false, Input::post('id'))));
                }

                throw new NoContentResponseException();

            // DropZone file upload
            case 'fileupload':

                $dc->move(true);
                throw new InternalServerErrorHttpException();
        }
    }


    /**
     * Convert a string to a response object
     *
     * @param string $str
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function convertToResponse( $str ) {
        return new Response( Controller::replaceOldBePaths($str) );
    }
}
