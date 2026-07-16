<?php

/**
 * Point d'entrée REST protégé par la preuve GRR.
 */

use MediaWiki\Context\RequestContext;
use MediaWiki\EntryPointEnvironment;
use MediaWiki\Extension\GrrAccessGate\EntryPointGate;
use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\EntryPoint;

define( 'MW_REST_API', true );
define( 'MW_ENTRY_POINT', 'rest' );

require __DIR__ . '/includes/WebStart.php';

EntryPointGate::enforce( 'rest' );

( new EntryPoint(
	EntryPoint::getMainRequest(),
	RequestContext::getMain(),
	new EntryPointEnvironment(),
	MediaWikiServices::getInstance()
) )->run();
