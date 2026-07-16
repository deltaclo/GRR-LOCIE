<?php

class GrrMediaWikiAuthConfig
{
    const MODULE = 'mediawiki_auth';

    public static function isTestEnvironment()
    {
        $scriptName = isset($_SERVER['SCRIPT_NAME'])
            ? str_replace('\\', '/', (string) $_SERVER['SCRIPT_NAME'])
            : '';

        return strpos($scriptName, '/test/grr/') !== false;
    }

    public static function defaultAllowedPath()
    {
        return self::isTestEnvironment() ? '/test/mediawiki/' : '/mediawiki/';
    }

    public static function defaultCookieName()
    {
        return self::isTestEnvironment()
            ? 'GRRMediaWikiAccessTest'
            : 'GRRMediaWikiAccess';
    }

    public static function defaultAudience()
    {
        return self::isTestEnvironment()
            ? 'mediawiki-test'
            : 'mediawiki-production';
    }

    public static function get($name, $default = '')
    {
        $value = Settings::get('mediawiki_auth_'.$name);
        return ($value === null || $value === '') ? $default : (string) $value;
    }

    public static function set($name, $value)
    {
        return Settings::set('mediawiki_auth_'.$name, (string) $value);
    }

    public static function isEnabled()
    {
        return self::isModuleActive() && self::get('enabled', '1') === '1';
    }

    public static function isModuleActive()
    {
        $count = grr_sql_query1(
            "SELECT COUNT(*) FROM ".TABLE_PREFIX."_modulesext
            WHERE nom = 'mediawiki_auth' AND actif = 1"
        );

        return (int) $count > 0;
    }

    public static function allowedPath()
    {
        $default = self::defaultAllowedPath();

        return self::normalizePathSetting(
            self::get('allowed_path', $default),
            $default
        );
    }

    public static function cookiePath()
    {
        return self::normalizePathSetting(
            self::get('cookie_path', self::allowedPath()),
            self::allowedPath()
        );
    }

    public static function cookieName()
    {
        $default = self::defaultCookieName();
        $name = self::get('cookie_name', $default);
        if (!preg_match('/^[A-Za-z0-9_-]{1,64}$/', $name)) {
            return $default;
        }

        return $name;
    }

    public static function audience()
    {
        $default = self::defaultAudience();
        $audience = self::get('audience', $default);
        if (!preg_match('/^[A-Za-z0-9._-]{1,64}$/', $audience)) {
            return $default;
        }

        return $audience;
    }

    public static function ttl()
    {
        return min(600, max(30, (int) self::get('ttl', '120')));
    }

    public static function secret()
    {
        $secret = self::get('secret', '');
        if (strlen($secret) < 64) {
            $secret = self::regenerateSecret();
        }

        return $secret;
    }

    public static function regenerateSecret()
    {
        try {
            $secret = bin2hex(random_bytes(32));
        } catch (Throwable $exception) {
            $secret = hash('sha256', uniqid('', true).mt_rand());
        }
        self::set('secret', $secret);

        return $secret;
    }

    public static function normalizePathSetting($path, $default)
    {
        $path = trim((string) $path);
        if ($path === '' || $path[0] !== '/' || strpos($path, '//') === 0) {
            return $default;
        }
        $decodedPath = rawurldecode($path);
        if (preg_match('/[\x00-\x1F\x7F]/', $decodedPath)
            || strpos($decodedPath, "\\") !== false
            || strpos($decodedPath, '?') !== false
            || strpos($decodedPath, '#') !== false
            || in_array('..', explode('/', $decodedPath), true)
        ) {
            return $default;
        }
        if (substr($path, -1) !== '/') {
            $path .= '/';
        }

        return $path;
    }

    public static function isValidCookieName($name)
    {
        return is_string($name) && preg_match('/^[A-Za-z0-9_-]{1,64}$/', $name);
    }

    public static function isValidAudience($audience)
    {
        return is_string($audience) && preg_match('/^[A-Za-z0-9._-]{1,64}$/', $audience);
    }

    public static function isAllowedDeploymentPath($path)
    {
        return (string) $path === self::defaultAllowedPath();
    }

    public static function hasEnvironmentMismatch()
    {
        return self::allowedPath() !== self::defaultAllowedPath()
            || self::cookiePath() !== self::defaultAllowedPath()
            || self::cookieName() !== self::defaultCookieName()
            || self::audience() !== self::defaultAudience();
    }

    public static function isSecureRequest()
    {
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
                && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
    }

    public static function modulePath()
    {
        $scriptName = isset($_SERVER['SCRIPT_NAME'])
            ? str_replace('\\', '/', (string) $_SERVER['SCRIPT_NAME'])
            : '';
        $needle = '/personnalisation/modules/'.self::MODULE;
        $position = strpos($scriptName, $needle);

        if ($position === false) {
            return $needle;
        }

        return substr($scriptName, 0, $position).$needle;
    }

    public static function authorizePath()
    {
        return rtrim(self::modulePath(), '/').'/authorize.php';
    }

    public static function grrBasePath()
    {
        $modulePath = self::modulePath();
        $needle = '/personnalisation/modules/'.self::MODULE;
        $position = strpos($modulePath, $needle);

        if ($position === false) {
            return '';
        }

        return rtrim(substr($modulePath, 0, $position), '/');
    }

    public static function loginPath($callbackPath)
    {
        return self::grrBasePath().'/app.php?p=login&url='.rawurlencode($callbackPath);
    }
}
