<?php

use App\Kernel;

require_once dirname(__DIR__).'/config/bootstrap.php';

// Créer le kernel
$kernel = new Kernel($_SERVER['APP_ENV'] ?? 'dev', (bool) ($_SERVER['APP_DEBUG'] ?? true));

// Créer une requête
$request = Symfony\Component\HttpFoundation\Request::createFromGlobals();

// Traiter la requête
$response = $kernel->handle($request);

// Envoyer la réponse
$response->send();

// Terminer
$kernel->terminate($request, $response);
