<?php
use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

require_once __DIR__.'/vendor/autoload_runtime.php';

return function (array $context) {
    echo "Bootstrapping...\n";
    $kernel = new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
    echo "Kernel created.\n";
    $app = new Application($kernel);
    echo "Application created.\n";
    return $app;
};
