<?php

/**
 * Contao Marketing Suite Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\MarketingSuite\BackendModule;

use Contao\Backend;
use Contao\BackendModule as CoreBackendModule;
use Contao\Controller;
use Contao\EditableDataContainerInterface;
use Contao\Input;
use Contao\InvalidArgumentException;
use Contao\ListableDataContainerInterface;
use Contao\StringUtil;
use Contao\System;
use numero2\MarketingSuite\Backend\Help;
use numero2\MarketingSuite\Backend\License as tabizni;


class Module extends CoreBackendModule {


    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'backend/modules/module';

    /**
     * Backend modules
     * @var array
     */
    protected $arrModules = [];


    /**
    * Generate the module
    *
    * @return string
    */
    public function generate() {

        $this->arrModules = [];
        $this->arrModules = $this->getModules();

        $table = Input::get('table');
        foreach( $GLOBALS['CMS_MOD'] as $groupName => $cmsConfig ) {
            foreach( $cmsConfig as $mod => $modConfig ) {

                if( !array_key_exists('tables', $modConfig) || !is_array($modConfig['tables']) ) {
                    continue;
                }

                if( in_array($table, $modConfig['tables']) ) {
                    Input::setGet('mod', $mod);
                    break;
                }
            }
        }

        // Open module
        if( Input::get('mod') != '' ) {

            $objBEHelp = null;
            $objBEHelp = new Help();

            return $objBEHelp->generate() . $this->getModule(Input::get('mod'));
        }

        return parent::generate();
    }


    /**
     * Compile the module
     */
    protected function compile() {
        $this->Template->modules = $this->arrModules;
    }


    /**
     * Get modules
     *
     * @return array
     */
    protected function getModules() {

        $return = [];

        $moduleGroup = NULL;
        $moduleGroup = Input::get('do');

        $groupName = substr($moduleGroup,4);

        if( empty($GLOBALS['CMS_MOD'][$groupName]) ) {
            throw new InvalidArgumentException('Back end module "' . $groupName . '" is not defined in the CMS_MOD array');
        }

        $refererId = System::getContainer()->get('request_stack')->getCurrentRequest()->get('_contao_referer_id');

        foreach( $GLOBALS['CMS_MOD'][$groupName] as $moduleName => $moduleConfig ) {

            if( !empty($moduleConfig['callback']) ) {
                if( !$moduleConfig['callback']::isAvailable() ) {
                    continue;
                }
            }

            if( !empty($moduleConfig['tables']) && empty(array_intersect($moduleConfig['tables'], $GLOBALS['BE_MOD']['marketing_suite'][$moduleGroup]['tables'])) ) {
                continue;
            }

            $return[$moduleName] = $moduleConfig;

            $return[$moduleName] += [
                'label'         => $GLOBALS['TL_LANG']['CMS'][$moduleName][0]
            ,   'description'   => $GLOBALS['TL_LANG']['CMS'][$moduleName][1]
            ,   'class'         => StringUtil::standardize($moduleName)
            ,   'href'          => '?do=' . $moduleGroup . '&mod=' . $moduleName.'&ref='.$refererId
            ];
        }

        return $return;
    }


    /**
     * Open a module and return it as HTML
     *
     * @param string
     *
     * @return string|null
     */
    protected function getModule( $module ) {

        $this->loadLanguageFile('default');

        $arrModule = [];
        $arrModule = $this->arrModules[$module];
        $dc = $this->objDc;

        tabizni::riz();

        // Redirect with table parameter
        $strTable = Input::get('table');
        if( $strTable == '' && empty($arrModule['callback']) ) {
            Controller::redirect(Backend::addToUrl('table=' . $arrModule['tables'][0]));
        }

        // Add module style sheet
        if( isset($arrModule['stylesheet']) ) {
            foreach( (array)$arrModule['stylesheet'] as $stylesheet ) {
                $GLOBALS['TL_CSS'][] = $stylesheet;
            }
        }

        // Add module javascript
        if( isset($arrModule['javascript']) ) {
            foreach( (array)$arrModule['javascript'] as $javascript ) {
                $GLOBALS['TL_JAVASCRIPT'][] = $javascript;
            }
        }

        // generate headline
        if( Input::get('do') ) {

            $aHeadline = [];
            $aHeadline[] = $GLOBALS['TL_LANG']['MOD'][Input::get('do')][0]??null;

            if( Input::get('mod') ) {
                $aHeadline[] = $GLOBALS['TL_LANG']['CMS'][Input::get('mod')][0]??null;
            }

            if( !empty($strTable) ) {

                $label = $GLOBALS['TL_LANG'][$strTable][Input::get('act')][1]??null;

                if( !empty($label) && strpos($label, '%') !== false ) {
                    $aHeadline[] = sprintf($label, Input::get('id'));
                }
            }

            if( Input::get('key') ) {

                $label = $GLOBALS['TL_LANG'][$strTable][Input::get('key')][1]??null;

                if( !empty($label) && strpos($label, '%') !== false ) {
                    $aHeadline[] = sprintf($label, Input::get('id'));
                }
            }

            // cleanup
            $aHeadline = array_filter($aHeadline, function($value) {
                return !empty($value);
            });

            if( !empty($aHeadline) ) {

                // add markup
                $aHeadline = array_map(function($value) {
                    return '<span>'.$value.'</span>';
                }, $aHeadline);

                $attributes = null;
                $attributes = System::getContainer()->get('request_stack')->getCurrentRequest()->attributes;

                $attributes->set('_cms_module_headline', implode(' ', $aHeadline));
            }
        }

        // Call module callback
        if( !empty($arrModule['callback']) && class_exists($arrModule['callback']) ) {

            $objCallback = new $arrModule['callback']($dc, $arrModule);
            return $objCallback->generate();

        // Custom action (if key is not defined in config.php the default action will be called)
        } elseif( Input::get('key') && isset($arrModule[Input::get('key')]) ) {

            // $objCallback = new $arrModule[Input::get('key')][0]();
            $objCallback = System::importStatic($arrModule[Input::get('key')][0]);
            return $objCallback->{$arrModule[Input::get('key')][1]}($dc, $strTable, $arrModule);

        // Default action
        }  elseif( is_object($dc) ) {

            $act = (string) Input::get('act');

            if( '' === $act || 'paste' === $act || 'select' === $act ) {
                $act = ($dc instanceof ListableDataContainerInterface) ? 'showAll' : 'edit';
            }

            switch ($act) {
                case 'delete':
                case 'show':
                case 'showAll':
                case 'undo':
                    if (!$dc instanceof ListableDataContainerInterface) {
                        System::getContainer()->get('monolog.logger.contao.error')->error('Data container ' . $strTable . ' is not listable');
                        trigger_error('The current data container is not listable', E_USER_ERROR);
                    }
                    break;
                case 'create':
                case 'cut':
                case 'cutAll':
                case 'copy':
                case 'copyAll':
                case 'move':
                case 'edit':
                    if (!$dc instanceof EditableDataContainerInterface) {
                        System::getContainer()->get('monolog.logger.contao.error')->error('Data container ' . $strTable . ' is not editable');
                        trigger_error('The current data container is not editable', E_USER_ERROR);
                    }
                    break;
            }

            // set automatic backlink in DCA
            if( empty($GLOBALS['TL_DCA'][$strTable]['config']['backlink']) && Input::get('do') ) {

                $referer = $this->getReferer(true);
                if( strpos('?', $referer) !== false ) {
                    $GLOBALS['TL_DCA'][$strTable]['config']['backlink'] = explode('?', $referer, 2)[1];
                } else {
                    $GLOBALS['TL_DCA'][$strTable]['config']['backlink'] = $referer;
                }
            }

            $strContent = "";

            // gives the chance to add some content between the helper and the form itself
            // not a core functionality!
            if( !empty($arrModule['pre_form_callback']) ) {

                $objCallback = NULL;
                $objCallback = new $arrModule['pre_form_callback'][0]($dc, $arrModule);
                $strContent .= $objCallback->{$arrModule['pre_form_callback'][1]}();
            }

            $strContent .= $dc->$act();

            return $strContent;
        }

        return null;
    }
}
