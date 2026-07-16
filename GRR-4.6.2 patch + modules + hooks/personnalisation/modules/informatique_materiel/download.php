<?php

require_once __DIR__.'/lib/bootstrap.php';
$sessionOk = grr_informatique_materiel_bootstrap(true);
require_once __DIR__.'/lib/Config.php';
require_once __DIR__.'/lib/Repository.php';
require_once __DIR__.'/lib/Security.php';

if (!$sessionOk || !InformatiqueMaterielConfig::isEnabled() || !InformatiqueMaterielSecurity::canAccess()) {
    http_response_code(403);
    exit('Acces refuse');
}

$document = InformatiqueMaterielRepository::document(isset($_GET['id']) ? (int) $_GET['id'] : 0);
if (!$document) {
    http_response_code(404);
    exit('Fichier introuvable');
}

$path = InformatiqueMaterielRepository::documentPath(isset($document['stored_name']) ? $document['stored_name'] : '');
if ($path === '' || !is_file($path)) {
    http_response_code(404);
    exit('Fichier introuvable');
}

$fileName = str_replace(array("\r", "\n", '"'), '', basename((string) $document['original_name']));
$fileName = $fileName === '' ? 'document' : $fileName;
$asciiName = preg_replace('/[^A-Za-z0-9._-]/', '_', $fileName);
$asciiName = $asciiName === '' ? 'document' : $asciiName;

$mimeType = preg_replace('/[\r\n]/', '', trim((string) $document['mime_type']));
if (
    $mimeType === ''
    || !preg_match('~^[a-zA-Z0-9][a-zA-Z0-9!#$&^_.+-]*/[a-zA-Z0-9][a-zA-Z0-9!#$&^_.+-]*$~', $mimeType)
) {
    $mimeType = 'application/octet-stream';
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
