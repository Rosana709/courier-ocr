<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function getCacheDir(): string
    {
        return $this->getProjectDir().'/var/cache_local/'.$this->environment;
    }

    /**
     * Override pour éviter l'échec de Symfony quand is_writable() retourne systématiquement faux
     * dans certains environnements (ex: Windows sandbox). On teste l'écriture réelle sur le disque.
     */
    protected function buildContainer(): ContainerBuilder
    {
        foreach (['cache' => $this->getCacheDir(), 'build' => $this->getBuildDir(), 'logs' => $this->getLogDir()] as $name => $dir) {
            if (!is_dir($dir)) {
                if (false === @mkdir($dir, 0777, true) && !is_dir($dir)) {
                    throw new \RuntimeException(sprintf('Unable to create the "%s" directory (%s).', $name, $dir));
                }
            } else {
                $probe = rtrim($dir, '\\/').'/.writetest';
                $canWrite = @file_put_contents($probe, '') !== false;
                if ($canWrite) {
                    @unlink($probe);
                } else {
                    throw new \RuntimeException(sprintf('Unable to write in the "%s" directory (%s).', $name, $dir));
                }
            }
        }

        $container = $this->getContainerBuilder();
        $container->addObjectResource($this);
        $this->prepareContainer($container);
        $this->registerContainerConfiguration($this->getContainerLoader($container));

        return $container;
    }
}
