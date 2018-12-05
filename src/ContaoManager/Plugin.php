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


namespace numero2\MarketingSuite\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use numero2\MarketingSuite\MarketingSuiteBundle;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;


class Plugin implements BundlePluginInterface {


    /**
     * {@inheritdoc}
     */
    public function getBundles(ParserInterface $parser): array {

        return [
            BundleConfig::create(MarketingSuiteBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class])
        ];
    }
}
