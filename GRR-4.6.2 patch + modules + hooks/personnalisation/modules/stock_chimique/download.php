<?php

require_once __DIR__.'/lib/bootstrap.php';
$sessionOk = grr_stock_chimique_bootstrap(true);
require_once __DIR__.'/lib/Config.php';
require_once __DIR__.'/lib/Repository.php';
require_once __DIR__.'/lib/Security.php';

if (!$sessionOk || !StockChimiqueConfig::isEnabled() || !StockChimiqueSecurity::canAccess()) {
    http_response_code(403);
    exit('Accès refusé');
}

$document = StockChimiqueRepository::document(isset($_GET['id']) ? (int) $_GET['id'] : 0);
if (!$document) {
    http_response_code(404);
    exit('Fichier introuvable');
}
$path = StockChimiqueRepository::documentPath($document['stored_name']);
if ($path === '' || !is_file($path)) {
    http_response_code(404);
    exit('Fichier introuvable');
}
$fileName = str_replace(array("\r", "\n", '"'), '', basename((string) $document['original_name']));
$fileName = $fileName === '' ? 'document' : $fileName;
$asciiName = preg_replace('/[^A-Za-z0-9._-]/', '_', $fileName);
$mime = preg_replace('/[\r\n]/', '', trim((string) $document['mime_type']));
if (!preg_match('~^[a-zA-Z0-9][a-zA-Z0-9!#$&^_.+-]*/[a-zA-Z0-9][a-zA-Z0-9!#$&^_.+-]*$~', $mime)) {
    $mime = 'application/octet-stream';
}
if (function_exists('session_write_close')) {
    session_write_close();
}
header('Content-Type: '.$mime);
header('Content-Length: '.filesize($path));
header('Content-Disposition: attachment; filename="'.$asciiName.'"; filename*=UTF-8\'\''.rawurlencode($fileName));
header('X-Content-Type-Options: nosniff');
header('Content-Security-Policy: sandbox');
header('Cache-Control: private, no-store, no-cache, must-revalidate');
readfile($path);
exit;

