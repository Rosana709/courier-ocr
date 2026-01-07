<?php
require_once __DIR__.'/vendor/autoload_runtime.php';

use Symfony\Component\HttpClient\HttpClient;

$client = HttpClient::create();
try {
    // We need to simulate the multi-part file upload to /smart/extract
    // But we need a session. Since we can't easily get a session, let's just check if the route exists and how it responds when not logged in.
    $response = $client->request('POST', 'http://127.0.0.1:8000/smart/extract');
    echo "Status: " . $response->getStatusCode() . "\n";
    echo "Content Start: " . substr($response->getContent(false), 0, 200) . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
