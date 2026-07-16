<?php

require_once __DIR__.'/../lib/Config.php';

function assertSameValue($expected, $actual, $message)
{
    if ($expected !== $actual) {
        fwrite(
            STDERR,
            "ECHEC: ".$message."\nAttendu: "
                .var_export($expected, true)
                ."\nObtenu: "
                .var_export($actual, true)
                ."\n"
        );
        exit(1);
    }
}

$_SERVER['SCRIPT_NAME'] = '/test/grr/personnalisation/modules/mediawiki_auth/admin.php';
assertSameValue(
    '/test/mediawiki/',
    GrrMediaWikiAuthConfig::defaultAllowedPath(),
    'chemin par défaut de test'
);
assertSameValue(
    'GRRMediaWikiAccessTest',
    GrrMediaWikiAuthConfig::defaultCookieName(),
    'cookie par défaut de test'
);
assertSameValue(
    'mediawiki-test',
    GrrMediaWikiAuthConfig::defaultAudience(),
    'audience par défaut de test'
);

$_SERVER['SCRIPT_NAME'] = '/grr/personnalisation/modules/mediawiki_auth/admin.php';
assertSameValue(
    '/mediawiki/',
    GrrMediaWikiAuthConfig::defaultAllowedPath(),
    'chemin par défaut de production'
);
assertSameValue(
    'GRRMediaWikiAccess',
    GrrMediaWikiAuthConfig::defaultCookieName(),
    'cookie par défaut de production'
);
assertSameValue(
    'mediawiki-production',
    GrrMediaWikiAuthConfig::defaultAudience(),
    'audience par défaut de production'
);
assertSameValue(
    true,
    GrrMediaWikiAuthConfig::isAllowedDeploymentPath('/mediawiki/'),
    'chemin de production autorisé'
);
assertSameValue(
    false,
    GrrMediaWikiAuthConfig::isAllowedDeploymentPath('/test/mediawiki/'),
    'chemin de test refusé sur la production'
);

$_SERVER['SCRIPT_NAME'] = '/test/grr/personnalisation/modules/mediawiki_auth/admin.php';
assertSameValue(
    true,
    GrrMediaWikiAuthConfig::isAllowedDeploymentPath('/test/mediawiki/'),
    'chemin de test autorisé sur la copie de test'
);
assertSameValue(
    false,
    GrrMediaWikiAuthConfig::isAllowedDeploymentPath('/mediawiki/'),
    'chemin de production refusé sur la copie de test'
);

fwrite(STDOUT, "OK - tests de configuration mediawiki_auth réussis.\n");
