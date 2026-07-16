<?php

namespace MediaWiki\Extension\GrrAccessGate;

use MediaWiki\Context\RequestContext;
use MediaWiki\MediaWikiServices;

/**
 * Contrôle les points d'entrée qui ne disposent pas de hook global.
 */
class EntryPointGate {
	public static function enforce( string $format ): void {
		$config = MediaWikiServices::getInstance()->getMainConfig();
		$gate = new AccessGate( $config );
		if ( !$gate->isEnabled() ) {
			return;
		}

		header( 'Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0' );
		header( 'Pragma: no-cache' );
		header( 'Vary: Cookie' );

		$configurationError = $gate->getConfigurationError();
		if ( $configurationError !== null ) {
			wfDebugLog(
				'GrrAccessGate',
				'Point d’entrée mal configuré ; motif=' . $configurationError
			);
			self::deny( $format, 503, 'configuration_error' );
		}

		$request = RequestContext::getMain()->getRequest();
		$verificationError = $gate->getVerificationError( $request );
		if ( $verificationError !== null ) {
			wfDebugLog(
				'GrrAccessGate',
				'Point d’entrée refusé ; motif=' . $verificationError
			);
			self::deny( $format, 403, 'grr_session_required' );
		}
	}

	private static function deny( string $format, int $status, string $code ): void {
		header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
		header( 'Pragma: no-cache' );
		http_response_code( $status );

		if ( $format === 'rest' ) {
			header( 'Content-Type: application/json; charset=UTF-8' );
			echo json_encode(
				[
					'error' => $code,
					'message' => 'Une session GRR valide est requise.',
				],
				JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
			);
		} else {
			header( 'Content-Type: text/plain; charset=UTF-8' );
			echo 'Accès refusé : une session GRR valide est requise.';
		}

		exit;
	}
}
