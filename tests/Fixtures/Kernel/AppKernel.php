<?php

namespace Kcs\Serializer\Tests\Fixtures\Kernel;

use Kcs\Serializer\Bundle\SerializerBundle;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

class AppKernel extends Kernel
{
    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        return [
            new FrameworkBundle(),
            new SerializerBundle(),
            new AppBundle(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config.yml');
    }

    protected function getKernelParameters(): array
    {
        return parent::getKernelParameters() + [
            'kernel.root_dir' => __DIR__,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function initializeContainer()
    {
        $class = $this->getContainerClass();
        $cache = new ConfigCache($this->getCacheDir().'/'.$class.'.php', $this->debug);

        $container = $this->buildContainer();
        $container->register('logger', NullLogger::class);
        $container->compile();
        $this->dumpContainer($cache, $container, $class, $this->getContainerBaseClass());

        if (Kernel::VERSION_ID >= 30400) {
            $this->container = require $cache->getPath();
        } else {
            // Legacy-compiled container.
            // Do not require the same file twice, as this will lead to a fatal error.
            require_once $cache->getPath();
            $this->container = new $class();
        }

        $this->container->set('kernel', $this);

        if ($this->container->has('cache_warmer')) {
            $this->container->get('cache_warmer')->warmUp($this->container->getParameter('kernel.cache_dir'));
        }
    }
}
