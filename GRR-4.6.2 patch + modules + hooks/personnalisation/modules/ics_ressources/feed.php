<?php
require_once __DIR__.'/lib/bootstrap.php';
grr_ics_bootstrap(false);
require_once __DIR__.'/lib/ModuleConfig.php';
require_once __DIR__.'/lib/IcsFeed.php';

$room = isset($_GET['room']) ? (int) $_GET['room'] : 0;
$token = isset($_GET['token']) ? (string) $_GET['token'] : '';

GrrIcsFeed::output($room, $token);
