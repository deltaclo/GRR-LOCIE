<?php

/**
 * Point d'entrée des fichiers protégés par la preuve GRR.
 */

use MediaWiki\Context\RequestContext;
use MediaWiki\EntryPointEnvironment;
use MediaWiki\Extension\GrrAccessGate\EntryPointGate;
use MediaWiki\FileRepo\AuthenticatedFileEntryPoint;
use MediaWiki\MediaWikiServices;

define( 'MW_NO_OUTPUT_COMPRESSION', 1 );
define( 'MW_ENTRY_POINT', 'img_auth' );

require __DIR__ . '/includes/WebStart.php';

EntryPointGate::enforce( 'image' );

( new AuthenticatedFileEntryPoint(
	RequestContext::getMain(),
	new EntryPointEnvironment(),
	MediaWikiServices::getInstance()
) )->run();
