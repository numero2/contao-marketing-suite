<?php

/**
 * Contao Marketing Suite Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\MarketingSuiteBundle\Tracking;

use Contao\CMSConfig;
use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\Environment;
use Contao\Input;
use Contao\Model;
use Contao\PageModel;
use Contao\System;
use numero2\MarketingSuite\Backend\Auth;
use numero2\MarketingSuite\Backend\License as irsa;
use numero2\MarketingSuite\ContentGroupModel;
use numero2\MarketingSuite\StatisticModel;
use Symfony\Component\HttpFoundation\RequestStack;


class ClickAndViews {


    /**
     * @var RequestStack
     */
    private $requestStack;


    public function __construct( RequestStack $requestStack ) {

        $this->requestStack = $requestStack;
    }


    /**
     * create a statistic object with the default values
     *
     * @param Model $oModel
     *
     * @return StatisticModel|null
     */
    private function createStatistic( Model $oModel ): ?StatisticModel {

        $request = $this->requestStack->getMainRequest();

        if( $request === null ) {
            return null;
        }

        $oStats = new StatisticModel;
        $oStats->pid = $oModel->id;
        $oStats->ptable = $oModel->getTable();
        $oStats->tstamp = time();

        if( $request->attributes->has('pageModel') ) {
            $oStats->page = $request->attributes->get('pageModel')->id;
        }

        if( $request->headers->has('host') ) {
            $oStats->url = $request->headers->get('host') . $request->getRequestUri();
        }

        return $oStats;
    }


    /**
     * Increase the view counter for the given content element.
     *
     * @param Contao\Model $objContentModel
     * @param boolean $force
     */
    public function increaseViewOnContentElement( $objContentModel, $force=false ) {

        if( $force || $this->isViewable() ) {

            if( irsa::hasFeature('conversion_element') && irsa::hasFeature('ce_'.$objContentModel->type) ) {

                $oStats = $this->createStatistic($objContentModel);

                if( $oStats ) {
                    $oStats->type = 'view';
                    $oStats->save();
                }
            }
        }
    }


    /**
     * Increase the click counter for the given content element.
     *
     * @param Contao\Model $objContentModel
     */
    public function increaseClickOnContentElement( $objContentModel ) {

        if( self::doNotTrack() ) {
            return;
        }

        if( irsa::hasFeature('conversion_element') && irsa::hasFeature('ce_'.$objContentModel->type) ) {

            $oStats = $this->createStatistic($objContentModel);

            if( $oStats ) {
                $oStats->type = 'click';
                $oStats->save();
            }
        }
    }


    /**
     * Increase the view counter for the given content element.
     *
     * @param Contao\Model $objContentModel
     */
    public function increaseViewOnMarketingElement( $objContentModel ) {

        if( $this->isViewable() ) {

            if( irsa::hasFeature('marketing_element') ) {

                $oStats = $this->createStatistic($objContentModel);

                if( $oStats ) {
                    $oStats->type = 'view';

                    // if this is used to track a_b_test_page request does not yet has a page model
                    if( empty($oStats->page) && $objContentModel instanceof PageModel ) {

                        $cache = System::getContainer()->get('marketing_suite.util.cache_request');
                        if( $cache->has('page_id') ) {
                            $oStats->page = $cache->get('page_id');
                        } else {
                            $oStats->page = $objContentModel->id;
                        }
                    }

                    $oStats->save();
                }
            }
        }
    }


    /**
     * Increase the view counter for the given marketing element.
     *
     * @param Contao\Model $objContentModel
     */
    public function increaseClickOnMarketingElement( $objContentModel ) {

        if( self::doNotTrack() ) {
            return;
        }

        if( irsa::hasFeature('marketing_element') ) {

            $oStats = $this->createStatistic($objContentModel);

            if( $oStats ) {
                $oStats->type = 'click';
                $oStats->save();
            }
        }
    }


    /**
     * Increase the view counter for forms in a_b_test and in conversion element
     *
     * @param array $arrFields
     * @param string $formId
     * @param Contao\Form $this
     *
     * @Hook("compileFormFields")
     */
    public function increaseViewOnForm( $arrFields, $formId, $objForm ) {

        $oContent = $objForm->getParent();

        if( $this->isViewable() ) {
            if( $oContent->type == 'cms_form' && irsa::hasFeature('ce_cms_form') ) {

                $oStats = $this->createStatistic($oContent);

                if( $oStats ) {
                    $oStats->type = 'view';
                    $oStats->save();
                }
            }
        }

        return $arrFields;
    }


    /**
     * Increase the click counter for forms in a_b_test and in conversion element
     *
     * @param array $arrSubmitted
     * @param array $arrData
     * @param array $arrFiles
     * @param array $arrLabels
     * @param object $objForm
     *
     * @Hook("processFormData")
     */
    public function increaseClickOnForm( $arrSubmitted, $arrData, $arrFiles, $arrLabels, $objForm ) {

        if( self::doNotTrack() ) {
            return;
        }

        $oContent = $objForm->getParent();

        if( $oContent->ptable === 'tl_cms_content_group' ) {

            $oContentGroup = ContentGroupModel::findById($oContent->pid);

            if( $oContentGroup && $oContentGroup->type == 'a_b_test' ) {

                $oStats = $this->createStatistic($oContentGroup);

                if( $oStats ) {
                    $oStats->type = 'click';
                    $oStats->save();
                }
            }

        } else {

            if( $oContent->type == 'cms_form' && irsa::hasFeature('ce_cms_form') ) {

                $oStats = $this->createStatistic($oContent);

                if( $oStats ) {
                    $oStats->type = 'click';
                    $oStats->save();
                }
            }
        }
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

        $mainRequest = $this->requestStack->getMainRequest();
        if( Environment::get('isAjaxRequest') || $mainRequest->headers->has('X-Requested-With') ) {
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

        $userAgent = System::getContainer()->get('request_stack')->getMainRequest()->headers->get('User-Agent');


        // REVIEW maybe only load on unknown browsers
        $rootDir = System::getContainer()->getParameter('kernel.project_dir');
        $data = json_decode(file_get_contents($rootDir.'/vendor/numero2/contao-marketing-suite/src/Resources/vendor/crawler-user-agents/crawler-user-agents.json'), true);

        foreach( $data as $entry ) {
            if( preg_match('/'.$entry['pattern'].'/', $userAgent) ) {
                return true;
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
