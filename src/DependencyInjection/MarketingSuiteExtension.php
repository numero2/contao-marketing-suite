<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2022 Leo Feyer
 *
 * @package   Contao Marketing Suite
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright 2022 numero2 - Agentur für digitales Marketing
 */


namespace numero2\MarketingSuiteBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;


class MarketingSuiteExtension extends Extension implements PrependExtensionInterface {


    /**
     * {@inheritdoc}
     */
    public function prepend( ContainerBuilder $container ): void {

        $configuration = new Configuration((string) $container->getParameter('kernel.project_dir'));
        $config = $this->processConfiguration($configuration, $container->getExtensionConfig($this->getAlias()));

        $container->setParameter('marketing_suite.disable_update_message', $config['disable_update_message']);
    }


    /**
     * {@inheritdoc}
     */
    public function load( array $configs, ContainerBuilder $container ): void {

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );

        $loader->load('listener.yml');
        $loader->load('parameters.yml');
        $loader->load('services.yml');
        $loader->load('migrations.yml');
    }
}
