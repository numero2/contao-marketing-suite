<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2019 Leo Feyer
 *
 * @package   Contao Marketing Suite
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright 2020 numero2 - Agentur für digitales Marketing
 */


namespace numero2\MarketingSuiteBundle\Picker;

use Contao\CoreBundle\Picker\AbstractPickerProvider;
use Contao\CoreBundle\Picker\DcaPickerProviderInterface;
use Contao\CoreBundle\Picker\PickerConfig;
use numero2\MarketingSuite\Backend\License as ksebgui;


class LinkShortenerPickerProvider extends AbstractPickerProvider implements DcaPickerProviderInterface {


    /**
     * {@inheritdoc}
     */
    public function getName() {
        return 'cmsLinkShortenerPicker';
    }


    /**
     * {@inheritdoc}
     */
    public function supportsContext($context) {

        if( !ksebgui::hasFeature('link_shortener') ) {
            return false;
        }

        return 'link' === $context;
    }


    /**
     * {@inheritdoc}
     */
    public function supportsValue(PickerConfig $config) {
        return strpos($config->getValue(), '{{cms_link_shortener::') !== false;
    }


    /**
     * {@inheritdoc}
     */
    public function getDcaTable() {
        return 'tl_cms_link_shortener';
    }


    /**
     * {@inheritdoc}
     */
    public function getDcaAttributes(PickerConfig $config) {

        $value = $config->getValue();
        $attributes = ['fieldType' => 'radio'];

        if( $source = $config->getExtra('source') ) {
            $attributes['preserveRecord'] = $source;
        }

        if( $this->supportsValue($config) ) {
            $value = str_replace(['{{cms_link_shortener::', '}}'], '', $config->getValue());

            // remove flags
            if( strpos($value, '|') !== false )  {
                $value = explode('|', $value)[0];
            }

            $attributes['value'] = $value;
        }

        return $attributes;
    }


    /**
     * {@inheritdoc}
     */
    public function convertDcaValue(PickerConfig $config, $value) {
        return '{{cms_link_shortener::'.$value.'}}';
    }


    /**
     * {@inheritdoc}
     */
    protected function getRouteParameters(PickerConfig $config = null) {
        $params = [];
        $params['do'] = 'cms_tools';
        $params['mod'] = 'link_shortener';
        $params['table'] = 'tl_cms_link_shortener';

        return $params;
    }
}
