<?php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

if (isset($_ENV['DATABASE_URL'])) {
    $_ENV['DATABASE_URL'] = str_replace('postgres://', 'postgresql://', $_ENV['DATABASE_URL']);
}
if (isset($_SERVER['DATABASE_URL'])) {
    $_SERVER['DATABASE_URL'] = str_replace('postgres://', 'postgresql://', $_SERVER['DATABASE_URL']);
}

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
