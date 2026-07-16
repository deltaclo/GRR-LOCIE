<?php

require_once __DIR__.'/lib/bootstrap.php';
grr_stock_chimique_bootstrap(false);
require_once __DIR__.'/lib/Config.php';
require_once __DIR__.'/lib/Repository.php';
require_once __DIR__.'/lib/Notification.php';

header('Content-Type: text/plain; charset=utf-8');
if (!StockChimiqueConfig::isEnabled()) {
    http_response_code(403);
    exit("Module désactivé.\n");
}
$token = isset($_GET['token']) ? (string) $_GET['token'] : '';
if (!StockChimiqueConfig::notificationTokenIsValid($token)) {
    http_response_code(403);
    exit("Token invalide ou absent.\n");
}
if (!StockChimiqueConfig::notificationsEnabled()) {
    http_response_code(403);
    exit("Notifications désactivées.\n");
}
if (!StockChimiqueConfig::alertsEnabled()) {
    http_response_code(403);
    exit("Alertes désactivées.\n");
}
$result = StockChimiqueNotification::sendPending();
echo "Stock chimique - notifications\n";
echo "Envoyées : ".(int) $result['sent']."\n";
echo "Déjà envoyées : ".(int) $result['skipped']."\n";
echo "Erreurs : ".count($result['errors'])."\n";
foreach ($result['errors'] as $error) {
    echo "- ".$error."\n";
}
