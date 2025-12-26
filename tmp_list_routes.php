<?php
require 'config/bootstrap.php';
require 'vendor/autoload.php';
$k=new App\Kernel('prod', false);
$k->boot();
$router=$k->getContainer()->get('router');
foreach ($router->getRouteCollection()->all() as $n=>$r){ if(str_contains($n,'courrier')) echo $n."\n"; }
