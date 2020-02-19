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


namespace numero2\MarketingSuite\Controller;

use Contao\Backend;
use Contao\BackendTemplate;
use Contao\Config;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\Environment;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Symfony\Component\HttpFoundation\Response;


class BackendWizardPopup extends Backend {


    /**
     * Initialize the controller
     *
     * 1. Import the user
     * 2. Call the parent constructor
     * 3. Authenticate the user
     * 4. Load the language files
     * DO NOT CHANGE THIS ORDER!
     */
    public function __construct() {

        $this->import('BackendUser', 'User');
        parent::__construct();

        if( !System::getContainer()->get('security.authorization_checker')->isGranted('ROLE_USER') ) {
            throw new AccessDeniedException('Access denied');
        }

        System::loadLanguageFile('default');
    }

    /**
     * Run the controller and parse the template
     *
     * @return Response
     */
    public function run() {

        $template = urldecode(Input::get('do'));
        $table = urldecode(Input::get('table'));

        System::loadLanguageFile('cms_be_wizard');

        if( $table ) {
            System::loadLanguageFile($table);
        }

        $objTemplate = new BackendTemplate($template);
        $objTemplate->setData( $_GET );

        $objTemplate->theme = Backend::getTheme();
        $objTemplate->base = Environment::get('base');
        $objTemplate->language = $GLOBALS['TL_LANGUAGE'];
        $objTemplate->title = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['wizard_popup_title']);
        $objTemplate->charset = Config::get('characterSet');

        return $objTemplate->getResponse();
    }
}
