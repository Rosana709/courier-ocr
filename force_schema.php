<?php
require __DIR__.'/vendor/autoload.php';
use Symfony\Component\Dotenv\Dotenv;
use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
(new Dotenv())->loadEnv(__DIR__.'/.env');
$kernel = new Kernel('dev', true);
$kernel->boot();
$application = new Application($kernel);
$application->setAutoExit(false);
$input = new ArrayInput(['command' => 'doctrine:schema:update', '--dump-sql' => true]);
$output = new BufferedOutput();
$application->run($input, $output);
$sql = $output->fetch();
foreach(explode(';', $sql) as $query) {
    if (strpos($query, 'historique_action') !== false) {
        echo trim($query) . ";\n";
    }
}
