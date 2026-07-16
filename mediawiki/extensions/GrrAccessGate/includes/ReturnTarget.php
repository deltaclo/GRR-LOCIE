<?php

namespace MediaWiki\Extension\GrrAccessGate;

/**
 * Construit une URL de retour relative et limitée à l'instance MediaWiki.
 */
class ReturnTarget {
	public static function fromRequestUrl( string $requestUrl, string $wikiPath ): string {
		if ( !self::isValidWikiPath( $wikiPath )
			|| preg_match( '/[\x00-\x1F\x7F\\\\]/', $requestUrl )
		) {
			return $wikiPath;
		}

		$path = parse_url( $requestUrl, PHP_URL_PATH );
		$query = parse_url( $requestUrl, PHP_URL_QUERY );
		if ( !is_string( $path ) || $path === '' ) {
			return $wikiPath;
		}

		$decodedPath = rawurldecode( $path );
		if ( preg_match( '/[\x00-\x1F\x7F\\\\]/', $decodedPath )
			|| in_array( '..', explode( '/', $decodedPath ), true )
		) {
			return $wikiPath;
		}

		$wikiPathWithoutSlash = rtrim( $wikiPath, '/' );
		if ( $path !== $wikiPathWithoutSlash && !str_starts_with( $path, $wikiPath ) ) {
			return $wikiPath;
		}

		return $path . ( is_string( $query ) && $query !== '' ? '?' . $query : '' );
	}

	public static function isValidWikiPath( string $wikiPath ): bool {
		if ( $wikiPath === ''
			|| $wikiPath[0] !== '/'
			|| str_starts_with( $wikiPath, '//' )
			|| !str_ends_with( $wikiPath, '/' )
		) {
			return false;
		}

		$decodedPath = rawurldecode( $wikiPath );
		return !preg_match( '/[\x00-\x1F\x7F\\\\?#]/', $decodedPath )
			&& !in_array( '..', explode( '/', $decodedPath ), true );
	}
}
