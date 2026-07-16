<?php

namespace MediaWiki\Extension\GrrAccessGate;

use MediaWiki\Cache\Hook\HTMLFileCache__useFileCacheHook;
use MediaWiki\Config\Config;
use MediaWiki\Hook\ApiBeforeMainHook;
use MediaWiki\Hook\BeforeInitializeHook;
use MediaWiki\Output\Hook\BeforePageDisplayHook;
use MediaWiki\ResourceLoader\Hook\ResourceLoaderGetConfigVarsHook;

class Hooks implements
	BeforeInitializeHook,
	ApiBeforeMainHook,
	HTMLFileCache__useFileCacheHook,
	BeforePageDisplayHook,
	ResourceLoaderGetConfigVarsHook
{
	private Config $config;
	private AccessGate $gate;

	public function __construct( Config $config ) {
		$this->config = $config;
		$this->gate = new AccessGate( $config );
	}

	/**
	 * Contrôle les pages servies par index.php.
	 */
	public function onBeforeInitialize(
		$title,
		$unused,
		$output,
		$user,
		$request,
		$mediaWikiEntryPoint
	) {
		if ( !$this->gate->isEnabled() ) {
			return;
		}

		$configurationError = $this->gate->getConfigurationError();
		if ( $configurationError !== null ) {
			$this->stopForConfigurationError( $configurationError );
		}

		$verificationError = $this->gate->getVerificationError( $request );
		if ( $verificationError === null ) {
			return;
		}

		wfDebugLog(
			'GrrAccessGate',
			'Redirection vers GRR ; motif=' . $verificationError
		);

		header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
		header( 'Pragma: no-cache' );
		header( 'Location: ' . $this->gate->buildAuthorizeUrl( $request ), true, 302 );
		exit;
	}

	/**
	 * Contrôle les appels à api.php sans renvoyer de page HTML dans le JSON.
	 */
	public function onApiBeforeMain( &$main ) {
		if ( !$this->gate->isEnabled() ) {
			return;
		}

		$main->getRequest()->response()->header(
			'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
		);
		$main->getRequest()->response()->header( 'Vary: Cookie' );

		$configurationError = $this->gate->getConfigurationError();
		if ( $configurationError !== null ) {
			wfDebugLog(
				'GrrAccessGate',
				'Configuration invalide ; motif=' . $configurationError
			);
			$main->dieWithError(
				'grraccessgate-api-config-error',
				'grraccessgate-config-error',
				null,
				503
			);
		}

		$verificationError = $this->gate->getVerificationError( $main->getRequest() );
		if ( $verificationError !== null ) {
			wfDebugLog(
				'GrrAccessGate',
				'API refusée ; motif=' . $verificationError
			);
			$main->dieWithError(
				'grraccessgate-api-access-denied',
				'grraccessgate-access-denied',
				null,
				403
			);
		}
	}

	/**
	 * Empêche le cache HTML fichier de servir une page avant le contrôle GRR.
	 */
	public function onHTMLFileCache__useFileCache( $context ) {
		if ( $this->gate->isEnabled() ) {
			return false;
		}
	}

	/**
	 * Maintient la preuve pendant une page ou une édition laissée ouverte.
	 */
	public function onBeforePageDisplay( $out, $skin ): void {
		if ( $this->gate->isEnabled()
			&& $this->gate->getConfigurationError() === null
		) {
			$out->disableClientCache();
			$out->addModules( 'ext.grrAccessGate.keepalive' );
		}
	}

	/**
	 * Expose uniquement les paramètres non sensibles nécessaires au navigateur.
	 */
	public function onResourceLoaderGetConfigVars(
		array &$vars,
		$skin,
		Config $config
	): void {
		if ( $this->gate->isEnabled() ) {
			$vars['wgGrrAccessGateRefreshUrl'] =
				(string)$this->config->get( 'GrrAccessGateRefreshUrl' );
			$vars['wgGrrAccessGateRefreshInterval'] =
				(int)$this->config->get( 'GrrAccessGateRefreshInterval' );
		}
	}

	private function stopForConfigurationError( string $configurationError ): void {
		wfDebugLog(
			'GrrAccessGate',
			'Configuration invalide ; motif=' . $configurationError
		);
		header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
		header( 'Content-Type: text/plain; charset=UTF-8' );
		http_response_code( 503 );
		exit( 'Passerelle GRR/MediaWiki mal configurée. Contactez un administrateur.' );
	}
}
