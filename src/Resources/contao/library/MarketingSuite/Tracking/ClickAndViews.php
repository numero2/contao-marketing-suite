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


namespace numero2\MarketingSuite\Tracking;

use Contao\Database;
use Contao\Environment;
use Contao\Input;
use Contao\System;
use numero2\MarketingSuite\Backend\License as irsa;
use numero2\MarketingSuite\ContentGroupModel;
use numero2\MarketingSuite\MarketingItemModel;


class ClickAndViews {


    /**
     * Increase the click counter for the given content element.
     * Do not use the model as this is already modified.
     *
     * @param \Model $objContentModel
     */
    public function increaseClickOnContentElement( $objContentModel ) {

        if( irsa::hasFeature('conversion_element') && irsa::hasFeature('ce_'.$objContentModel->type) ) {
            Database::getInstance()->prepare( "UPDATE ".$objContentModel->getTable()." SET cms_ci_clicks=cms_ci_clicks+1 WHERE id=?" )->execute($objContentModel->id);
        }
    }


    /**
     * Increase the view counter for the given content element.
     * Do not use the model as this is already modified.
     *
     * @param \Model $objContentModel
     */
    public function increaseViewOnContentElement( $objContentModel ) {

        if( $this->isViewable() ) {

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

            if( irsa::hasFeature('ce_form') ) {
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

                // for all marketing items: views will be count in the marketing item child class.

            } else {

                if( irsa::hasFeature('ce_form') ) {
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

        if( Input::get('follow') ) {
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

        return true;
    }
}
