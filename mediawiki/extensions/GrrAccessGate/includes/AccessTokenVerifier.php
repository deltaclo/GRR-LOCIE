<?php

namespace MediaWiki\Extension\GrrAccessGate;

/**
 * Vérifie les preuves HMAC émises par le module GRR mediawiki_auth.
 *
 * La méthode retourne null quand la preuve est valide, sinon un code court
 * utilisable dans les journaux. La preuve elle-même ne doit jamais être
 * journalisée.
 */
class AccessTokenVerifier {
	private string $secret;
	private string $audience;
	private int $clockSkew;
	private int $maxLifetime;

	public function __construct(
		string $secret,
		string $audience,
		int $clockSkew = 30,
		int $maxLifetime = 600
	) {
		$this->secret = $secret;
		$this->audience = $audience;
		$this->clockSkew = max( 0, $clockSkew );
		$this->maxLifetime = max( 1, $maxLifetime );
	}

	/**
	 * @return string|null Code d'erreur, ou null si la preuve est valide.
	 */
	public function verify( string $token, string $userAgent, ?int $now = null ): ?string {
		if ( substr_count( $token, '.' ) !== 1 ) {
			return 'format';
		}

		[ $encodedPayload, $encodedSignature ] = explode( '.', $token, 2 );
		$signature = $this->base64UrlDecode( $encodedSignature );
		if ( $signature === null ) {
			return 'signature-encoding';
		}

		$expectedSignature = hash_hmac( 'sha256', $encodedPayload, $this->secret, true );
		if ( !hash_equals( $expectedSignature, $signature ) ) {
			return 'signature';
		}

		$json = $this->base64UrlDecode( $encodedPayload );
		if ( $json === null ) {
			return 'payload-encoding';
		}

		$payload = json_decode( $json, true );
		if ( !is_array( $payload ) ) {
			return 'payload-json';
		}

		foreach ( [ 'v', 'aud', 'iat', 'exp', 'ua' ] as $requiredField ) {
			if ( !array_key_exists( $requiredField, $payload ) ) {
				return 'payload-field';
			}
		}

		if ( (int)$payload['v'] !== 1 ) {
			return 'version';
		}
		if ( !is_string( $payload['aud'] )
			|| !hash_equals( $this->audience, $payload['aud'] )
		) {
			return 'audience';
		}
		if ( !$this->isIntegerValue( $payload['iat'] )
			|| !$this->isIntegerValue( $payload['exp'] )
		) {
			return 'timestamp-type';
		}

		$now ??= time();
		$issuedAt = (int)$payload['iat'];
		$expiresAt = (int)$payload['exp'];

		if ( $issuedAt > $now + $this->clockSkew ) {
			return 'issued-in-future';
		}
		if ( $expiresAt < $issuedAt ) {
			return 'expiry-before-issue';
		}
		if ( $expiresAt < $now ) {
			return 'expired';
		}
		if ( $expiresAt > $now + $this->maxLifetime ) {
			return 'lifetime';
		}
		if ( !is_string( $payload['ua'] )
			|| !hash_equals( hash( 'sha256', $userAgent ), $payload['ua'] )
		) {
			return 'user-agent';
		}

		return null;
	}

	/**
	 * @param mixed $value
	 */
	private function isIntegerValue( $value ): bool {
		return is_int( $value )
			|| ( is_string( $value ) && preg_match( '/^-?[0-9]+$/D', $value ) === 1 );
	}

	private function base64UrlDecode( string $value ): ?string {
		if ( $value === '' || preg_match( '/^[A-Za-z0-9_-]+$/D', $value ) !== 1 ) {
			return null;
		}

		$padding = strlen( $value ) % 4;
		if ( $padding > 0 ) {
			$value .= str_repeat( '=', 4 - $padding );
		}

		$decoded = base64_decode( strtr( $value, '-_', '+/' ), true );
		return is_string( $decoded ) ? $decoded : null;
	}
}
