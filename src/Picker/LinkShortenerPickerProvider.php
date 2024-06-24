<?php

/**
 * Contao Marketing Suite Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2024, numero2 - Agentur für digitales Marketing GbR
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
    public function getName(): string {
        return 'cmsLinkShortenerPicker';
    }


    /**
     * {@inheritdoc}
     */
    public function supportsContext( $context ): bool {

        if( !ksebgui::hasFeature('link_shortener') ) {
            return false;
        }

        return 'link' === $context;
    }


    /**
     * {@inheritdoc}
     */
    public function supportsValue( PickerConfig $config ): bool {
        return strpos($config->getValue(), '{{cms_link_shortener::') !== false;
    }


    /**
     * {@inheritdoc}
     */
    public function getDcaTable( ?PickerConfig $config = null ): string {
        return 'tl_cms_link_shortener';
    }


    /**
     * {@inheritdoc}
     */
    public function getDcaAttributes( PickerConfig $config ): array {

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
    public function convertDcaValue( PickerConfig $config, $value ): string|int {
        return '{{cms_link_shortener::'.$value.'}}';
    }


    /**
     * {@inheritdoc}
     */
    protected function getRouteParameters( ?PickerConfig $config = null ): array {
        $params = [];
        $params['do'] = 'cms_tools';
        $params['mod'] = 'link_shortener';
        $params['table'] = 'tl_cms_link_shortener';

        return $params;
    }
}
