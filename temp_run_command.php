<?php
putenv('APP_ENV=cli');
$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'cli';
putenv('APP_DEBUG=1');
$_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] = '1';
require 'config/bootstrap.php';
require 'vendor/autoload.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;

$kernel = new App\Kernel('cli', true);
$application = new Application($kernel);
$application->setAutoExit(false);

$input = new ArrayInput([
    'command' => 'app:create-service-users',
    '--password' => 'dgi2025',
]);

$exitCode = $application->run($input, new ConsoleOutput());
echo "exit=".$exitCode."\n";
