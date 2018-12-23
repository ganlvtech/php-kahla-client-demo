<?php

use GuzzleHttp\Client;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/functions.php';

$config = include __DIR__ . '/config.php';

$client = new Client([
    'base_uri' => 'https://staging.server.kahla.app',
    'cookies' => true,
]);

// Login
$response = $client->request('POST', '/Auth/AuthByPassword', [
    'form_params' => [
        'Email' => (string)$config['username'],
        'Password' => (string)$config['password'],
    ],
]);
$response_body = json_decode($response->getBody(), true);
print_r($response_body);
if ($response_body['code'] !== 0) {
    return;
}


// Find User
$response = $client->request('GET', '/friendship/MyFriends?orderByName=false');
$response_body = json_decode($response->getBody(), true);
print_r($response_body);
foreach ($response_body['items'] as $item) {
    if ($item['displayName'] === (string)$config['send_to']) {
        $conversation_id = $item['conversationId'];
        $aes_key = $item['aesKey'];
        break;
    }
}
if (!isset($conversation_id)) {
    return;
}


// Get Message
$response = $client->request('GET', '/conversation/GetMessage/' . $conversation_id . '?take=15');
$response_body = json_decode($response->getBody(), true);
foreach ($response_body['items'] as &$item) {
    $item['content_decrypted'] = decrypt($item['content'], $aes_key);
}
print_r($response_body);


// Send Message
$content_encrypted = encrypt($config['send_content'], $aes_key);
$response = $client->request('POST', 'https://staging.server.kahla.app/Conversation/SendMessage/' . $conversation_id, [
    'form_params' => [
        'Content' => $content_encrypted,
    ],
]);
$response_body = json_decode($response->getBody(), true);
print_r($response_body);
