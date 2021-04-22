<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2021 Leo Feyer
 *
 * @package   Contao Marketing Suite
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright 2021 numero2 - Agentur für digitales Marketing
 */


namespace numero2\MarketingSuite\DCAHelper;

use Contao\BackendModule as CoreBackendModule;
use Contao\Config;
use Contao\Database;
use Contao\Date;
use Contao\Environment;
use Contao\Image;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;


class LinkShortenerStatistics extends CoreBackendModule {


    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'backend/modules/link_shortener_statistics';


    /**
     * Generate the custom filters
     *
     * @return string
     */
    public function generateFilters() {

        $aFilters = [];

        $strBuffer = '<div class="tl_cms_filter tl_subpanel">';

        $period = [
            'name'      => 'period'
        ,   'label'     => $GLOBALS['TL_LANG']['tl_cms_link_shortener_statistics']['filter']['period']
        ,   'inputType' => 'select'
        ,   'value'     => ''
        ,   'options'   => [
                'hour'  => &$GLOBALS['TL_LANG']['tl_cms_link_shortener_statistics']['periods']['hour']
            ,   'day'   => &$GLOBALS['TL_LANG']['tl_cms_link_shortener_statistics']['periods']['day']
            ,   'week'  => &$GLOBALS['TL_LANG']['tl_cms_link_shortener_statistics']['periods']['week']
            ,   'month' => &$GLOBALS['TL_LANG']['tl_cms_link_shortener_statistics']['periods']['month']
            ,   'year'  => &$GLOBALS['TL_LANG']['tl_cms_link_shortener_statistics']['periods']['year']
            ]
        ,   'eval' =>[ 'onchange'=>"Backend.autoSubmit('tl_filter')" ]
        ];

        $strBuffer .= $this->generateFilterInput($period);

        $start = [
            'name'      => 'start'
        ,   'label'     => $GLOBALS['TL_LANG']['tl_cms_link_shortener_statistics']['filter']['start']
        ,   'inputType' => 'text'
        ,   'value'     => ''
        ,   'eval'      => [ 'datePicker'=> true, 'rgxp'=>'datim' ]
        ];

        $strBuffer .= $this->generateFilterInput($start);

        $stop = [
            'name'      => 'stop'
        ,   'label'     => $GLOBALS['TL_LANG']['tl_cms_link_shortener_statistics']['filter']['stop']
        ,   'inputType' => 'text'
        ,   'value'     => ''
        ,   'eval'      => [ 'datePicker'=> true, 'rgxp'=>'datim' ]
        ];

        $strBuffer .= $this->generateFilterInput($stop);

        $strBuffer .= '</div>';

        return $strBuffer;
    }


    /**
     * generates the input for the given dca array
     *
     * @param array $aDca
     *
     * @return string
     */
    protected function generateFilterInput($aDca) {

        $strClass = $GLOBALS['BE_FFL'][$aDca['inputType']];
        if( !class_exists($strClass) ) {
            return '';
        }

        $arrSession = $this->Session->getData();

        $strBuffer = '<div class="filter '.$aDca['name'].'">';
        $strBuffer .= '<strong>'.$aDca['label'].':</strong>';

        $objWidget = new $strClass($strClass::getAttributesFromDca($aDca, $aDca['name']));
        $objWidget->value = (string) ($arrSession[$this->name][$aDca['name']]??'');

        if( !empty($aDca['eval']['datePicker']) && !empty($aDca['eval']['rgxp']) && strlen($objWidget->value) ) {
            $objWidget->value = Date::parse(\Config::get($aDca['eval']['rgxp'].'Format'), $objWidget->value);
        }

        if( Input::post('FORM_SUBMIT') == 'tl_filters' ) {

            if( Input::post('filter_reset') == '1' ) {
                $arrSession[$this->name] = [];
                $this->Session->setData( $arrSession );
                $this->redirect(Environment::get('request'));
            }

            $objWidget->validate();
            if( !$objWidget->hasErrors() ) {

                if( $aDca['eval']['datePicker'] && $aDca['eval']['rgxp'] && strlen($objWidget->value) ) {

                    $oDate = new Date($objWidget->value, Config::get($aDca['eval']['rgxp'].'Format'));
                    $arrSession[$this->name][$aDca['name']] = $oDate->timestamp;
                } else {

                    $arrSession[$this->name][$aDca['name']] = $objWidget->value;
                }
                $this->Session->setData( $arrSession );
            }
        }

        $strBuffer .= $objWidget->generate();

        if( !empty($aDca['eval']['datePicker']) && $aDca['eval']['datePicker'] ) {

            $rgxp = $aDca['eval']['rgxp'];
            $format = Date::formatToJs(Config::get($rgxp.'Format'));

            switch( $rgxp ) {
                case 'datim':
                    $time = ",\n        timePicker: true";
                    break;

                case 'time':
                    $time = ",\n        pickOnly: \"time\"";
                    break;

                default:
                    $time = '';
                    break;
            }

            $strBuffer .= ' ' . Image::getHtml('assets/datepicker/images/icon.svg', '', 'title="'.StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['datepicker']).'" id="toggle_' . $aDca['name'] . '" style="cursor:pointer"') . '
            <script>
            window.addEvent("domready", function() {
                new Picker.Date($("ctrl_' . $aDca['name'] . '"), {
                    draggable: false,
                    toggle: $("toggle_' . $aDca['name'] . '"),
                    format: "' . $format . '",
                    positionOffset: {x:-211,y:-209}' . $time . ',
                    pickerClass: "datepicker_bootstrap",
                    useFadeInOut: !Browser.ie,
                    startDay: ' . $GLOBALS['TL_LANG']['MSC']['weekOffset'] . ',
                    titleFormat: "' . $GLOBALS['TL_LANG']['MSC']['titleFormat'] . '"
                });
            });
            </script>';
        }

        $strBuffer .= '</div>';

        return $strBuffer;
    }


    /**
     * generates the data für the overview panel
     *
     * @return array
     */
    public function generateOverview() {

        $arrSession = $this->Session->getData();

        $aCondition = $this->getSQLConditions(['period']);
        $aTemplateData = [];

        // count requests
        $objResult = $this->database->prepare("
            SELECT count(1) AS count
            FROM tl_cms_link_shortener_statistics
            WHERE ".$aCondition['where']."
        ")->execute($aCondition['value']);

        $label = $GLOBALS['TL_LANG']['tl_cms_link_shortener_statistics']['requests'];
        $aTemplateData['numbers'][$label] = $objResult->count;

        // count unique requests
        $objResult = $this->database->prepare("
            SELECT DISTINCT unique_id
            FROM tl_cms_link_shortener_statistics
            WHERE ".$aCondition['where']."
        ")->execute($aCondition['value']);

        $label = $GLOBALS['TL_LANG']['tl_cms_link_shortener_statistics']['unique_requests'];
        $aTemplateData['numbers'][$label] = $objResult->numRows;

        $aBotCondition = $aCondition['value'];
        $aBotCondition[] = 1;

        // count bot requests
        $objResult = $this->database->prepare("
            SELECT count(1) AS bot_count
            FROM tl_cms_link_shortener_statistics
            WHERE ".$aCondition['where']." AND is_bot=?
        ")->execute($aBotCondition);

        $label = $GLOBALS['TL_LANG']['tl_cms_link_shortener_statistics']['bot_requests'];
        $aTemplateData['numbers'][$label] = $objResult->bot_count;

        $aGroupBy = [];
        $period = ($arrSession[$this->name]['period']??'')?:'hour';
        switch( $period ) {

            case 'hour':
                $aGroupBy[] = "HOUR(FROM_UNIXTIME(tstamp))";
            case 'day':
                $aGroupBy[] = "DAY(FROM_UNIXTIME(tstamp))";
            case 'month':
                $aGroupBy[] = "MONTH(FROM_UNIXTIME(tstamp))";
            case 'year':
                $aGroupBy[] = "YEAR(FROM_UNIXTIME(tstamp))";
                break;

            case 'week':
                $aGroupBy[] = "WEEK(FROM_UNIXTIME(tstamp))";
                $aGroupBy[] = "YEAR(FROM_UNIXTIME(tstamp))";
                break;

            default:
                $aGroupBy[] = "HOUR(FROM_UNIXTIME(tstamp))";
                $aGroupBy[] = "DAY(FROM_UNIXTIME(tstamp))";
                $aGroupBy[] = "MONTH(FROM_UNIXTIME(tstamp))";
                $aGroupBy[] = "YEAR(FROM_UNIXTIME(tstamp))";
                break;
        }

        $aLabelFormat= [
            'hour'  => "d.m.Y H:00"
        ,   'day'   => "d.m.Y"
        ,   'week'  => "\K\W W Y"
        ,   'month' => "F Y"
        ,   'year'  => "Y"
        ];

        $time = new \DateTime();
        if( !empty($arrSession[$this->name]['stop']) ) {
            $time->setTimestamp($arrSession[$this->name]['stop']);
        }

        $aMaxResult = [
            'hour'  => $time->format('G')+1
        ,   'day'   => 7
        ,   'week'  => 5
        ,   'month' => 12
        ,   'year'  => 3
        ];

        $maxResults = $aMaxResult[$period];
        $objResult = $this->database->prepare("
            SELECT count(1) AS count, MIN(tstamp) as tstamp
            FROM tl_cms_link_shortener_statistics
            WHERE ".$aCondition['where']."
            GROUP BY ". implode(', ', $aGroupBy)."
            ORDER BY tstamp DESC
        ")->limit($maxResults)->execute($aCondition['value']);

        $aTemplateData['graph']['label'] = $GLOBALS['TL_LANG']['tl_cms_link_shortener_statistics']['requests'];

        $aData = [];
        while( $objResult->next() ) {

            $aData[Date::parse($aLabelFormat[$period], $objResult->tstamp)] = $objResult->count;
        }

        if( $period == "month" ) {
            $time->setDate($time->format('Y'), $time->format('m'), 1);
        }

        $periodInterval = \DateInterval::createFromDateString('1 '.$period);

        for( $i = 0; $i < $maxResults; $i++ ) {

            $label = Date::parse($aLabelFormat[$period], $time->format('U'));
            $aTemplateData['graph']['labels'][] = $label;

            if( array_key_exists($label, $aData) ) {
                $aTemplateData['graph']['values'][] = $aData[$label];
            } else {
                $aTemplateData['graph']['values'][] = 0;
            }

            $time->sub($periodInterval);
        }

        $aTemplateData['graph']['labels'] = array_reverse($aTemplateData['graph']['labels']);
        $aTemplateData['graph']['values'] = array_reverse($aTemplateData['graph']['values']);

        // remove zeros
        $firstNotZero = 0;
        for( $i = 0; $i < count($aTemplateData['graph']['labels']); $i++) {

            if( $aTemplateData['graph']['values'][$i] === 0 ) {
                $firstNotZero = $i+1;
            } else {
                break;
            }
        }
        if( $firstNotZero > 0 ) {
            $aTemplateData['graph']['labels'] = array_slice($aTemplateData['graph']['labels'],$firstNotZero);
            $aTemplateData['graph']['values'] = array_slice($aTemplateData['graph']['values'],$firstNotZero);
        }

        return $aTemplateData;
    }


    /**
     * generate the data für the more detailed information grouot into tabs
     *
     * @return array
     */
    public function generateTabs() {

        $arrSession = $this->Session->getData();

        $aCondition = $this->getSQLConditions(['period']);
        $aTemplateData = [];

        // referer
        $objResult = $this->database->prepare("
            SELECT count(1) AS count, referer
            FROM tl_cms_link_shortener_statistics
            WHERE ".$aCondition['where']."
            GROUP BY referer
            ORDER BY referer ASC
        ")->execute($aCondition['value']);

        if( $objResult ) {

            $aList = [];
            while( $objResult->next() ) {

                $entry = [
                    "count" => $objResult->count,
                    "label" => $objResult->referer
                ];

                if( !strlen($objResult->referer) ) {
                    $entry['label'] = $GLOBALS['TL_LANG']['tl_cms_link_shortener_statistics']['empty'];
                }

                $aList[] = $entry;
            }

            $label = $GLOBALS['TL_LANG']['tl_cms_link_shortener_statistics']['referer'][0];
            $aTemplateData[$label] = $aList;

            // referer domain
            if( count($aList) ) {
                $aDomains = [];
                foreach( $aList as $key => $value ) {

                    $domain = parse_url($value['label'], PHP_URL_HOST);
                    if( $domain ) {

                        $domainParts = array_reverse(explode('.', $domain));
                        $mainDomain = strtolower($domainParts[1].'.'.$domainParts[0]);

                        if( array_key_exists($mainDomain, $aDomains) ) {

                            $aDomains[$mainDomain]['count'] += $value['count'];
                            $aDomains[$mainDomain]['subitems'][] = [
                                "count" => $value['count'],
                                "label" => $value['label']
                            ];

                        } else {

                            $entry = [
                                "count" => $value['count'],
                                "label" => $mainDomain,
                                "subitems" => [[
                                    "count" => $value['count'],
                                    "label" => $value['label']
                                ]]
                            ];

                            $aDomains[$mainDomain] = $entry;
                        }
                    }
                }

                $label = $GLOBALS['TL_LANG']['tl_cms_link_shortener_statistics']['referer_domain'][0];
                ksort($aDomains);
                $aTemplateData[$label] = $aDomains;
            }
        }

        // bot
        $objResult = $this->database->prepare("
            SELECT count(1) AS count, user_agent
            FROM tl_cms_link_shortener_statistics
            WHERE ".$aCondition['where']." AND is_bot='1'
            GROUP BY user_agent
            ORDER BY user_agent ASC
        ")->execute($aCondition['value']);

        if( $objResult ) {

            $aBots = json_decode(file_get_contents(TL_ROOT.'/vendor/numero2/contao-marketing-suite/src/Resources/vendor/crawler-user-agents/crawler-user-agents.json'), true);

            $aList = [];
            while( $objResult->next() ) {

                $pattern = null;

                foreach( $aBots as $entry ) {
                    if( preg_match('/'.$entry['pattern'].'/', $objResult->user_agent) ) {
                        $pattern = $entry['pattern'];
                        break;
                    }
                }

                $pattern = StringUtil::standardize($pattern?:$objResult->user_agent,true);

                if( array_key_exists($pattern, $aList) ) {

                    $aList[$pattern]['count'] += $objResult->count;
                } else {

                    $entry = [
                        "count" => $objResult->count,
                        "label" => $pattern?:$objResult->user_agent
                    ];

                    $aList[$pattern] = $entry;
                }
            }

            $label = $GLOBALS['TL_LANG']['tl_cms_link_shortener_statistics']['bots'];
            $aTemplateData[$label] = $aList;
        }

        // browser
        $objResult = $this->database->prepare("
            SELECT count(1) AS count, browser
            FROM tl_cms_link_shortener_statistics
            WHERE ".$aCondition['where']."
            GROUP BY browser
            ORDER BY browser ASC
        ")->execute($aCondition['value']);

        if( $objResult ) {

            $aList = [];
            while( $objResult->next() ) {

                $entry = [
                    "count" => $objResult->count,
                    "label" => $objResult->browser
                ];

                if( !empty($GLOBALS['TL_LANG']['tl_cms_link_shortener_statistics']['browser_names'][$objResult->browser]) ) {

                    $entry["label"] = $GLOBALS['TL_LANG']['tl_cms_link_shortener_statistics']['browser_names'][$objResult->browser];
                }

                $aList[] = $entry;
            }

            $label = $GLOBALS['TL_LANG']['tl_cms_link_shortener_statistics']['browser'][0];
            $aTemplateData[$label] = $aList;
        }

        // os
        $objResult = $this->database->prepare("
            SELECT count(1) AS count, os
            FROM tl_cms_link_shortener_statistics
            WHERE ".$aCondition['where']."
            GROUP BY os
            ORDER BY os ASC
        ")->execute($aCondition['value']);

        if( $objResult ) {

            $aList = [];
            while( $objResult->next() ) {

                $entry = [
                    "count" => $objResult->count,
                    "label" => $objResult->os
                ];

                if( !empty($GLOBALS['TL_LANG']['tl_cms_link_shortener_statistics']['os_names'][$objResult->os]) ) {

                    $entry["label"] = $GLOBALS['TL_LANG']['tl_cms_link_shortener_statistics']['os_names'][$objResult->os];
                }

                $aList[] = $entry;
            }

            $label = $GLOBALS['TL_LANG']['tl_cms_link_shortener_statistics']['os'][0];
            $aTemplateData[$label] = $aList;
        }

        // device
        $objResult = $this->database->prepare("
            SELECT count(1) AS count, is_mobile
            FROM tl_cms_link_shortener_statistics
            WHERE ".$aCondition['where']."
            GROUP BY is_mobile
            ORDER BY is_mobile ASC
        ")->execute($aCondition['value']);

        if( $objResult ) {

            $aList = [];
            while( $objResult->next() ) {

                $entry = [
                    "count" => $objResult->count,
                    "label" => $GLOBALS['TL_LANG']['tl_cms_link_shortener_statistics']['devices'][$objResult->is_mobile=='1'?'mobile':'desktop']
                ];


                $aList[] = $entry;
            }

            $label = $GLOBALS['TL_LANG']['tl_cms_link_shortener_statistics']['device'];
            $aTemplateData[$label] = $aList;
        }

        return $aTemplateData;
    }


    /**
     * generates the sql where condition and vlaues from the session data
     *
     * @param array $aIgnore which data in the session must be ignored
     *
     * @return array containing where string and value array
     */
    protected function getSQLConditions($aIgnore=[]) {

        $where = "pid=?";
        $value = [Input::get('id')];

        $arrSession = $this->Session->getData();

        if( !empty($arrSession[$this->name]) ){

            foreach( $arrSession[$this->name] as $FilterKey => $filterVal) {

                if( in_array($FilterKey, $aIgnore) || !strlen($filterVal) ) {
                    continue;
                }

                switch( $FilterKey ) {

                    case 'start':
                        $where .= ' AND ?<=tstamp';
                        $value[] = $filterVal;
                        break;

                    case 'stop':
                        $where .= ' AND tstamp<=?';
                        $value[] = $filterVal;
                        break;

                    default:
                        break;

                }

            }
        }

        return ['where'=>$where, 'value'=>$value];
    }


    /**
     * generates the module
     *
     * @return string
     */
    public function compile() {

        $this->loadLanguageFile('tl_cms_link_shortener_statistics');
        $this->name = Input::get('key');
        $this->database = Database::getInstance();

        // add chart.js library
        $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/marketingsuite/vendor/chartjs/Chart.bundle' .(System::getContainer()->get('kernel')->isDebug()?'':'.min'). '.js';
        $this->Template->action = $this->addToUrl('');

        $this->Template->filter = $this->generateFilters();
        $this->Template->overview = $this->generateOverview();
        $this->Template->tabs = $this->generateTabs();

        $this->Template->backURL = $this->getReferer(true);

        return $this->Template->parse();
    }
}
