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

use Contao\ContentModel;
use Contao\DataContainer;
use numero2\MarketingSuite\Backend;
use numero2\MarketingSuite\ContentButton;


class ButtonListener {


    /**
     * Renders a container for a live preview of the button
     *
     * @param Contao\DataContainer $dc
     *
     * @return string
     */
    public function generatePreview( DataContainer $dc ) {

        $url = NULL;
        $url = $dc->activeRecord->url;

        $data = [];
        if( !empty($url) ) {

            $objModel = ContentModel::findById($dc->activeRecord->id);
            $ceButton = new ContentButton($objModel);

            $strButton = $ceButton->generate();

            $style = $GLOBALS['TL_HEAD'][0];
            unset($GLOBALS['TL_HEAD']);

            $data['id'] = $dc->activeRecord->id;
            $data['style'] = $style;
            $data['style'] = str_replace('<style>', '<style id="custom">', $data['style']);
            $data['style'] = addslashes($data['style']);
            $data['style'] = str_replace(["\r", "\n", "\r\n"], "", $data['style']);

            $data['button'] = $strButton;
            $data['button'] = addslashes($data['button']);
            $data['button'] = str_replace(["\r", "\n", "\r\n"], "", $data['button']);

            $data['framescript'] = "

                window.addEventListener('message', function(e) {

                    if( e.data ) {

                        var style = document.querySelector('head style#custom');
                        if( style ) {
                            style.innerHTML = e.data
                        }
                    }

                    var data = {height: document.querySelector('body > div').clientHeight};
                    window.parent.postMessage(data, '*');
                }, false);

                window.addEventListener('resize', function(e) {
                    var data = {height: document.querySelector('body > div').clientHeight};
                    window.parent.postMessage(data, '*');
                });

            ";
            $data['framescript'] = str_replace(["\r", "\n", "\r\n"], "", $data['framescript']);
        }

        // Review template does not exist and maybe never called
        return Backend::parseWithTemplate('backend/widgets/button_preview', $data);
    }
}
