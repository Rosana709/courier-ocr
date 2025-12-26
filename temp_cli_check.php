<?php
putenv('APP_ENV=prod');
$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'prod';
putenv('APP_DEBUG=0');
$_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] = '0';
require 'config/bootstrap.php';
require 'vendor/autoload.php';
$kernel = new App\Kernel('prod', false);
$kernel->boot();
$loader = $kernel->getContainer()->get('console.command_loader');
$count = 0;
foreach ($loader->getNames() as $name) { $count++; }
echo "commands:".$count."\n";
