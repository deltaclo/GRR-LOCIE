<?php

require_once __DIR__ . '/../includes/AccessTokenVerifier.php';
require_once __DIR__ . '/../includes/ReturnTarget.php';

use MediaWiki\Extension\GrrAccessGate\AccessTokenVerifier;
use MediaWiki\Extension\GrrAccessGate\ReturnTarget;

function base64UrlEncode( string $value ): string {
	return rtrim( strtr( base64_encode( $value ), '+/', '-_' ), '=' );
}

function issueTestToken(
	string $secret,
	string $audience,
	string $userAgent,
	int $issuedAt,
	int $expiresAt
): string {
	$payload = base64UrlEncode( json_encode( [
		'v' => 1,
		'aud' => $audience,
		'iat' => $issuedAt,
		'exp' => $expiresAt,
		'ua' => hash( 'sha256', $userAgent ),
		'jti' => 'test',
	], JSON_UNESCAPED_SLASHES ) );
	$signature = hash_hmac( 'sha256', $payload, $secret, true );
	return $payload . '.' . base64UrlEncode( $signature );
}

function assertSameValue( $expected, $actual, string $message ): void {
	if ( $expected !== $actual ) {
		fwrite(
			STDERR,
			"ECHEC: {$message}\nAttendu: "
				. var_export( $expected, true )
				. "\nObtenu: "
				. var_export( $actual, true )
				. "\n"
		);
		exit( 1 );
	}
}

$secret = str_repeat( 'a', 64 );
$audience = 'mediawiki-test';
$userAgent = 'GrrAccessGate test';
$now = 1_700_000_000;
$verifier = new AccessTokenVerifier( $secret, $audience, 30, 600 );
$validToken = issueTestToken( $secret, $audience, $userAgent, $now, $now + 120 );

assertSameValue( null, $verifier->verify( $validToken, $userAgent, $now ), 'preuve valide' );
assertSameValue(
	'signature',
	$verifier->verify( $validToken . 'x', $userAgent, $now ),
	'signature modifiée'
);
assertSameValue(
	'user-agent',
	$verifier->verify( $validToken, 'autre navigateur', $now ),
	'navigateur différent'
);
assertSameValue(
	'expired',
	$verifier->verify( $validToken, $userAgent, $now + 121 ),
	'preuve expirée'
);
assertSameValue(
	'/test/mediawiki/index.php?title=Accueil',
	ReturnTarget::fromRequestUrl(
		'/test/mediawiki/index.php?title=Accueil',
		'/test/mediawiki/'
	),
	'URL MediaWiki autorisée'
);
assertSameValue(
	'/test/mediawiki/',
	ReturnTarget::fromRequestUrl( 'https://example.org/admin', '/test/mediawiki/' ),
	'URL extérieure refusée'
);
assertSameValue(
	'/test/mediawiki/',
	ReturnTarget::fromRequestUrl( '/test/mediawiki/%2e%2e/grr/', '/test/mediawiki/' ),
	'traversée de chemin refusée'
);
assertSameValue(
	'/mediawiki/index.php?title=Accueil',
	ReturnTarget::fromRequestUrl(
		'/mediawiki/index.php?title=Accueil',
		'/mediawiki/'
	),
	'URL MediaWiki de production autorisée'
);

fwrite( STDOUT, "OK - tests GrrAccessGate réussis.\n" );
