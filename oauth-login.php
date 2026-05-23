<?php
session_start();

$provider = $_GET['provider'] ?? '';
$providers = ['google', 'facebook'];

if (!in_array($provider, $providers)) {
    header('Location: Login.php');
    exit;
}

$oauth_config = require __DIR__ . '/config/oauth.php';
if (empty($oauth_config[$provider]['client_id']) && empty($oauth_config[$provider]['app_id'])) {
    header('Location: Login.php?error=oauth_not_configured');
    exit;
}

$config = $oauth_config[$provider];
$redirect_uri = $oauth_config['redirect_base'] . '/oauth-callback';

$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;
$_SESSION['oauth_provider'] = $provider;

$params = [
    'state' => $state,
    'redirect_uri' => $redirect_uri,
];

switch ($provider) {
    case 'google':
        $params['client_id'] = $config['client_id'];
        $params['response_type'] = 'code';
        $params['scope'] = $config['scope'];
        $params['access_type'] = 'online';
        $params['prompt'] = 'select_account';
        $auth_url = $config['auth_url'] . '?' . http_build_query($params);
        break;

    case 'facebook':
        $params['client_id'] = $config['app_id'];
        $params['response_type'] = 'code';
        $params['scope'] = $config['scope'];
        $auth_url = $config['auth_url'] . '?' . http_build_query($params);
        break;

}

header('Location: ' . $auth_url);
exit;
