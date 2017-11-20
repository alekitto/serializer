<?php

namespace Kcs\Serializer\Tests\Fixtures\Kernel;

use Kcs\Serializer\Bundle\SerializerBundle;
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

    /**
     * {@inheritdoc}
     */
    protected function initializeContainer()
    {
        $class = $this->getContainerClass();
        $cache = new ConfigCache($this->getCacheDir().'/'.$class.'.php', $this->debug);

        $container = $this->buildContainer();
        $container->compile();
        $this->dumpContainer($cache, $container, $class, $this->getContainerBaseClass());

        require_once $cache->getPath();

        $this->container = new $class();
        $this->container->set('kernel', $this);

        if ($this->container->has('cache_warmer')) {
            $this->container->get('cache_warmer')->warmUp($this->container->getParameter('kernel.cache_dir'));
        }
    }
}
