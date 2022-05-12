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


namespace numero2\MarketingSuite\Tracking;

use Contao\Database;
use Contao\Environment;
use Contao\Input;
use Contao\System;
use Contao\CMSConfig;
use numero2\MarketingSuite\Backend\License as irsa;
use numero2\MarketingSuite\ContentGroupModel;
use numero2\MarketingSuite\MarketingItemModel;
use numero2\MarketingSuite\Backend\Auth;


class ClickAndViews {


    /**
     * Increase the click counter for the given content element.
     * Do not use the model as this is already modified.
     *
     * @param \Model $objContentModel
     */
    public function increaseClickOnContentElement( $objContentModel ) {

        if( self::doNotTrack() ) {
            return;
        }

        if( irsa::hasFeature('conversion_element') && irsa::hasFeature('ce_'.$objContentModel->type) ) {
            Database::getInstance()->prepare( "UPDATE ".$objContentModel->getTable()." SET cms_ci_clicks=cms_ci_clicks+1 WHERE id=?" )->execute($objContentModel->id);
        }
    }


    /**
     * Increase the view counter for the given content element.
     * Do not use the model as this is already modified.
     *
     * @param \Model $objContentModel
     * @param boolean $force
     */
    public function increaseViewOnContentElement( $objContentModel, $force=false ) {

        if( $force || $this->isViewable() ) {

            if( irsa::hasFeature('conversion_element') && irsa::hasFeature('ce_'.$objContentModel->type) ) {
                Database::getInstance()->prepare( "UPDATE ".$objContentModel->getTable()." SET cms_ci_views=cms_ci_views+1 WHERE id=?" )->execute($objContentModel->id);
            }
        }
    }


    /**
     * Increase the view counter for the given content element.
     * Do not use the model as this is already modified.
     *
     * @param \Model $objContentModel
     */
    public function increaseViewOnMarketingElement( $objContentCount ) {

        if( $this->isViewable() ) {

            if( irsa::hasFeature('marketing_element') ) {

                $db = Database::getInstance();
                if( $db->fieldExists('views', $objContentCount->getTable()) ) {
                    Database::getInstance()->prepare( "UPDATE ".$objContentCount->getTable()." SET views=views+1 WHERE id=?" )->execute($objContentCount->id);
                } else if( $db->fieldExists('cms_mi_views', $objContentCount->getTable()) ) {
                    Database::getInstance()->prepare( "UPDATE ".$objContentCount->getTable()." SET cms_mi_views=cms_mi_views+1 WHERE id=?" )->execute($objContentCount->id);
                }
            }
        }
    }


    /**
     * Increase the click counter for forms in a_b_test and in conversion element
     *
     * @param array $arrSubmitted
     * @param array $arrData
     * @param array $arrFiles
     * @param array $arrLabels
     * @param object $objForm
     */
    public function increaseClickOnForm( $arrSubmitted, $arrData, $arrFiles, $arrLabels, $objForm ) {

        if( self::doNotTrack() ) {
            return;
        }

        $objContent = $objForm->getParent();

        if( $objContent->ptable === 'tl_cms_content_group' ) {

            $objContentGroup = ContentGroupModel::findById($objContent->pid);

            if( $objContentGroup && $objContentGroup->type == 'a_b_test' ) {

                $objMI = MarketingItemModel::findById($objContentGroup->pid);

                if( $objMI && $objMI->type == "a_b_test" ) {

                    if( irsa::hasFeature('me_'.$objMI->type) ) {
                        $objContentGroup->clicks += 1;
                        $objContentGroup->save();
                    }
                }
            }

        } else {

            if( $objContent->type == 'cms_form' && irsa::hasFeature('ce_cms_form') ) {
                $objContent->cms_ci_clicks += 1;
                $objContent->save();
            }
        }
    }


    /**
     * Increase the view counter for forms in a_b_test and in conversion element
     *
     * @param array $arrFields
     * @param string $formId
     * @param \Form $this
     */
    public function increaseViewOnForm( $arrFields, $formId, $objForm ) {

        $objContent = $objForm->getParent();

        if( $this->isViewable() ) {

            if( $objContent->ptable === 'tl_cms_content_group' ) {

                // for all marketing items: views will be counted in the marketing item child class

            } else {

                if( $objContent->type == 'cms_form' && irsa::hasFeature('ce_cms_form') ) {
                    $objContent->cms_ci_views += 1;
                    $objContent->save();
                }
            }
        }

        return $arrFields;
    }


    /**
     * Checks if this view should be counted
     *
     * @return boolean
     */
    protected function isViewable() {

        if( Input::get('follow') || Input::get('close') ) {
            return false;
        }

        if( (Input::get('FORM_SUBMIT') && strpos(Input::get('FORM_SUBMIT'), 'auto_form_') === 0 )
            || (Input::post('FORM_SUBMIT') && strpos(Input::post('FORM_SUBMIT'), 'auto_form_') === 0 ) ) {

            return false;
        }

        $masterRequest = System::getContainer()->get('request_stack')->getMasterRequest();
        if( Environment::get('isAjaxRequest') || $masterRequest->headers->has('X-Requested-With') ) {
            return false;
        }

        if( self::doNotTrack() ) {
            return false;
        }

        return true;
    }


    /**
     * Checks if the current request is a bot
     *
     * @return boolean
     */
    public static function isBot() {

        $oAgent = Environment::get('agent');

        if( $oAgent->browser == "other" ) {

            $data = json_decode(file_get_contents(TL_ROOT.'/vendor/numero2/contao-marketing-suite/src/Resources/vendor/crawler-user-agents/crawler-user-agents.json'), true);

            foreach( $data as $entry ) {
                if( preg_match('/'.$entry['pattern'].'/', $oAgent->string) ) {
                    return true;
                }
            }
        }

        return false;
    }


    /**
     * Checks if the current request should not be tracked
     *
     * @return boolean
     */
    public static function doNotTrack() {

        // prevent tracking for all bots
        if( self::isBot() ) {
            return true;
        }

        // prevent tracking if x-cms-dnt header is set
        $request = System::getContainer()->get('request_stack')->getCurrentRequest();
        if( $request->headers->has('X-CMS-DNT') ) {
            return true;
        }

        // prevent tracking of actively logged in backend users if configured in settings
        if( CMSConfig::get('dnt_backend_users') && Auth::isBackendUserLoggedIn() ) {
            return true;
        }

        return false;
    }
}
