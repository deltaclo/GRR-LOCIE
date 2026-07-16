<?php

require_once __DIR__.'/lib/bootstrap.php';

grr_gestion_materiel_bootstrap(false);

require_once __DIR__.'/lib/Config.php';
require_once __DIR__.'/lib/Repository.php';
require_once __DIR__.'/lib/Notification.php';

header('Content-Type: text/plain; charset=utf-8');

if (!GestionMaterielConfig::isEnabled()) {
    http_response_code(403);
    echo "Module desactive.\n";
    exit;
}

$token = isset($_GET['token']) ? (string) $_GET['token'] : '';
if (!GestionMaterielConfig::notificationTokenIsValid($token)) {
    http_response_code(403);
    echo "Token invalide ou absent.\n";
    exit;
}

$days = isset($_GET['days']) ? (int) $_GET['days'] : GestionMaterielConfig::upcomingDays();
$days = GestionMaterielConfig::normalizeDays($days);

$result = GestionMaterielNotification::sendDueNotifications($days);

echo "Gestion materiel - notifications\n";
echo "Periode : ".$days." jour".($days > 1 ? "s" : "")."\n";
echo "Envoyees : ".(int) $result['sent']."\n";
echo "Deja envoyees ignorees : ".(int) $result['skipped']."\n";

if (count($result['errors']) > 0) {
    echo "Erreurs :\n";
    foreach ($result['errors'] as $error) {
        echo "- ".$error."\n";
    }
} else {
    echo "Erreurs : 0\n";
}
