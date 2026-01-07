<?php
require_once __DIR__.'/vendor/autoload_runtime.php';

use Symfony\Component\HttpClient\HttpClient;

$client = HttpClient::create();
try {
    $response = $client->request('POST', 'http://127.0.0.1:8001/api/extract');
    echo "Status: " . $response->getStatusCode() . "\n";
    echo "Content: " . substr($response->getContent(false), 0, 100) . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
