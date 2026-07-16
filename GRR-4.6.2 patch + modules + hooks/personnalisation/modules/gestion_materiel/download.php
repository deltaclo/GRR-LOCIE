<?php

require_once __DIR__.'/lib/bootstrap.php';

$sessionOk = grr_gestion_materiel_bootstrap(true);

require_once __DIR__.'/lib/Config.php';
require_once __DIR__.'/lib/Repository.php';
require_once __DIR__.'/lib/Rights.php';

if (!$sessionOk || !GestionMaterielConfig::isEnabled()) {
    http_response_code(403);
    exit('Acces refuse');
}

$login = function_exists('getUserName') ? (string) getUserName() : '';
$documentId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$document = GestionMaterielRepository::document($documentId);
if ($login === '' || !$document) {
    http_response_code($login === '' ? 403 : 404);
    exit($login === '' ? 'Acces refuse' : 'Fichier introuvable');
}

$itemId = isset($document['item_id']) ? (int) $document['item_id'] : 0;
if (!GestionMaterielRights::canViewItem($itemId, $login)) {
    http_response_code(403);
    exit('Acces refuse');
}

$path = GestionMaterielRepository::documentPath(
    isset($document['stored_name']) ? $document['stored_name'] : ''
);
if ($path === '' || !is_file($path)) {
    http_response_code(404);
    exit('Fichier introuvable');
}

$fileName = basename((string) $document['original_name']);
$fileName = str_replace(array("\r", "\n", '"'), '', $fileName);
if ($fileName === '') {
    $fileName = 'document';
}

$mimeType = preg_replace('/[\r\n]/', '', trim((string) $document['mime_type']));
if (
    $mimeType === ''
    || !preg_match('~^[a-zA-Z0-9][a-zA-Z0-9!#$&^_.+-]*/[a-zA-Z0-9][a-zA-Z0-9!#$&^_.+-]*$~', $mimeType)
) {
    $mimeType = 'application/octet-stream';
}

$asciiName = preg_replace('/[^A-Za-z0-9._-]/', '_', $fileName);
if ($asciiName === '') {
    $asciiName = 'document';
}

if (function_exists('session_write_close')) {
    session_write_close();
}

header('Content-Type: '.$mimeType);
header('Content-Length: '.filesize($path));
header('Content-Disposition: attachment; filename="'.$asciiName.'"; filename*=UTF-8\'\''.rawurlencode($fileName));
header('X-Content-Type-Options: nosniff');
header('Pragma: private');
header('Cache-Control: private, no-store, no-cache, must-revalidate');

readfile($path);
exit;
