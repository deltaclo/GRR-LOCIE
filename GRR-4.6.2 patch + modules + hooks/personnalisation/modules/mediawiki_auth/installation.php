<?php

require_once __DIR__.'/lib/Config.php';

class Module
{
    public static function Installation($iter, $module_versionBDD)
    {
        $nom = SecuChaine::ProtectDataSql($iter);
        $version = (int) $module_versionBDD;

        $exists = grr_sql_query1(
            "SELECT COUNT(*) FROM ".TABLE_PREFIX."_modulesext WHERE nom = '".$nom."'"
        );
        if ($exists > 0) {
            grr_sql_command(
                "UPDATE ".TABLE_PREFIX."_modulesext
                SET actif = '1', version = '".$version."'
                WHERE nom = '".$nom."'"
            );
        } else {
            grr_sql_command(
                "INSERT INTO ".TABLE_PREFIX."_modulesext (nom, actif, version)
                VALUES ('".$nom."', '1', '".$version."')"
            );
        }

        self::setDefault('mediawiki_auth_enabled', '1');
        self::setDefault(
            'mediawiki_auth_allowed_path',
            GrrMediaWikiAuthConfig::defaultAllowedPath()
        );
        self::setDefault(
            'mediawiki_auth_cookie_path',
            GrrMediaWikiAuthConfig::defaultAllowedPath()
        );
        self::setDefault(
            'mediawiki_auth_cookie_name',
            GrrMediaWikiAuthConfig::defaultCookieName()
        );
        self::setDefault(
            'mediawiki_auth_audience',
            GrrMediaWikiAuthConfig::defaultAudience()
        );
        self::setDefault('mediawiki_auth_ttl', '120');

        if ((string) Settings::get('mediawiki_auth_secret') === '') {
            Settings::set('mediawiki_auth_secret', self::generateSecret());
        }

        return true;
    }

    private static function setDefault($name, $value)
    {
        if (Settings::get($name) === null) {
            Settings::set($name, $value);
        }
    }

    private static function generateSecret()
    {
        try {
            return bin2hex(random_bytes(32));
        } catch (Throwable $exception) {
            return hash('sha256', uniqid('', true).mt_rand());
        }
    }
}
