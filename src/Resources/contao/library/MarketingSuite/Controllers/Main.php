<?php

/**
 * Contao Marketing Suite Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\MarketingSuite\Controller;

use Contao\BackendMain;
use Contao\System;


class Main extends BackendMain {


    /**
     * Output the template file
     *
     * @return Response
     */
    public function run() {

        parent::run();

        $headline = System::getContainer()->get('request_stack')->getCurrentRequest()->get('_cms_module_headline');

        if( $headline ) {
            $this->Template->headline = $headline;
            $this->Template->title = $headline;
        }

        # fix duplicate css / js (see #63)
        $GLOBALS['TL_CSS'] = [];
        $GLOBALS['TL_JAVASCRIPT'] = [];

        return $this->output();
    }
}
