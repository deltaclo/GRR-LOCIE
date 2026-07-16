<?php

namespace MediaWiki\Extension\GrrAccessGate;

use MediaWiki\Config\Config;
use MediaWiki\Request\WebRequest;

/**
 * Service commun de validation utilisé par les hooks et les points d'entrée.
 */
class AccessGate {
	private Config $config;

	public function __construct( Config $config ) {
		$this->config = $config;
	}

	public function isEnabled(): bool {
		return (bool)$this->config->get( 'GrrAccessGateEnabled' );
	}

	public function getVerificationError( WebRequest $request ): ?string {
		$cookieName = (string)$this->config->get( 'GrrAccessGateCookieName' );
		$token = $request->getCookie( $cookieName, '', '' );
		if ( !is_string( $token ) || $token === '' ) {
			return 'missing-cookie';
		}

		$userAgent = $request->getHeader( 'User-Agent' );
		$verifier = new AccessTokenVerifier(
			(string)$this->config->get( 'GrrAccessGateSecret' ),
			(string)$this->config->get( 'GrrAccessGateAudience' ),
			(int)$this->config->get( 'GrrAccessGateClockSkew' ),
			(int)$this->config->get( 'GrrAccessGateMaxTokenLifetime' )
		);

		return $verifier->verify( $token, is_string( $userAgent ) ? $userAgent : '' );
	}

	public function getConfigurationError(): ?string {
		$secret = (string)$this->config->get( 'GrrAccessGateSecret' );
		if ( strlen( $secret ) < 64 ) {
			return 'secret';
		}

		$cookieName = (string)$this->config->get( 'GrrAccessGateCookieName' );
		if ( preg_match( '/^[A-Za-z0-9_-]{1,64}$/D', $cookieName ) !== 1 ) {
			return 'cookie-name';
		}

		$audience = (string)$this->config->get( 'GrrAccessGateAudience' );
		if ( preg_match( '/^[A-Za-z0-9._-]{1,64}$/D', $audience ) !== 1 ) {
			return 'audience';
		}

		$authorizeUrl = (string)$this->config->get( 'GrrAccessGateAuthorizeUrl' );
		if ( !$this->isValidLocalUrl( $authorizeUrl ) ) {
			return 'authorize-url';
		}

		$refreshUrl = (string)$this->config->get( 'GrrAccessGateRefreshUrl' );
		if ( !$this->isValidLocalUrl( $refreshUrl ) ) {
			return 'refresh-url';
		}

		$wikiPath = (string)$this->config->get( 'GrrAccessGateWikiPath' );
		if ( !ReturnTarget::isValidWikiPath( $wikiPath ) ) {
			return 'wiki-path';
		}

		$clockSkew = (int)$this->config->get( 'GrrAccessGateClockSkew' );
		$maxLifetime = (int)$this->config->get( 'GrrAccessGateMaxTokenLifetime' );
		$refreshInterval = (int)$this->config->get( 'GrrAccessGateRefreshInterval' );
		if ( $clockSkew < 0 || $clockSkew > 300
			|| $maxLifetime < 1 || $maxLifetime > 600
			|| $refreshInterval < 15 || $refreshInterval > 300
		) {
			return 'token-limits';
		}

		return null;
	}

	public function buildAuthorizeUrl( WebRequest $request ): string {
		$returnTarget = ReturnTarget::fromRequestUrl(
			(string)$request->getRequestURL(),
			(string)$this->config->get( 'GrrAccessGateWikiPath' )
		);
		$authorizeUrl = (string)$this->config->get( 'GrrAccessGateAuthorizeUrl' );
		$separator = str_contains( $authorizeUrl, '?' ) ? '&' : '?';

		return $authorizeUrl . $separator . http_build_query(
			[ 'return' => $returnTarget ],
			'',
			'&',
			PHP_QUERY_RFC3986
		);
	}

	private function isValidLocalUrl( string $url ): bool {
		return $url !== ''
			&& $url[0] === '/'
			&& !str_starts_with( $url, '//' )
			&& preg_match( '/[\x00-\x1F\x7F\\\\#]/', $url ) !== 1;
	}
}
