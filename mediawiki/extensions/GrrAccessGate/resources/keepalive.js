( function () {
	'use strict';

	var refreshUrl = mw.config.get( 'wgGrrAccessGateRefreshUrl' );
	var intervalSeconds = mw.config.get( 'wgGrrAccessGateRefreshInterval', 60 );
	var refreshInProgress = false;
	var lastRefresh = 0;

	if ( !refreshUrl || typeof window.fetch !== 'function' ) {
		return;
	}

	function refreshProof() {
		var now = Date.now();

		if ( refreshInProgress || now - lastRefresh < 15000 ) {
			return;
		}

		refreshInProgress = true;
		lastRefresh = now;

		window.fetch( refreshUrl, {
			method: 'GET',
			credentials: 'same-origin',
			cache: 'no-store'
		} ).catch( function () {
			// La prochaine requête protégée appliquera le contrôle normal.
		} ).finally( function () {
			refreshInProgress = false;
		} );
	}

	window.setTimeout( refreshProof, 1000 );
	window.setInterval( refreshProof, Math.max( 15, intervalSeconds ) * 1000 );
	window.addEventListener( 'focus', refreshProof );
}() );
