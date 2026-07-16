<?php
require_once __DIR__.'/lib/bootstrap.php';

$sessionOk = grr_suivi_bootstrap(true);

require_once __DIR__.'/lib/Config.php';
require_once __DIR__.'/lib/Repository.php';
require_once __DIR__.'/lib/Rights.php';

if (!$sessionOk) {
    http_response_code(403);
    exit('Acces refuse');
}

$login = function_exists('getUserName') ? (string) getUserName() : '';
if (!SuiviDemandesRights::canAccessModule($login)) {
    http_response_code(403);
    exit('Acces refuse');
}

$attachmentId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$attachment = SuiviDemandesRepository::attachmentById($attachmentId);
if (!$attachment) {
    http_response_code(404);
    exit('Fichier introuvable');
}

$demand = SuiviDemandesRepository::findById((int) $attachment['demande_id']);
if (!$demand || !SuiviDemandesRights::canViewAttachment($login, $demand, $attachment)) {
    http_response_code(403);
    exit('Acces refuse');
}

$path = SuiviDemandesRepository::attachmentPath($attachment['stored_name']);
if ($path === '' || !is_file($path)) {
    http_response_code(404);
    exit('Fichier introuvable');
}

$fileName = basename((string) $attachment['original_name']);
$fileName = str_replace(array("\r", "\n", '"'), '', $fileName);
if ($fileName === '') {
    $fileName = 'piece_jointe';
}

$mimeType = trim((string) $attachment['mime_type']);
if ($mimeType === '') {
    $mimeType = 'application/octet-stream';
}

header('Content-Type: '.$mimeType);
header('Content-Length: '.filesize($path));
header('Content-Disposition: attachment; filename="'.$fileName.'"');
header('X-Content-Type-Options: nosniff');
header('Pragma: private');
header('Cache-Control: private, no-store, no-cache, must-revalidate');

readfile($path);
exit;
