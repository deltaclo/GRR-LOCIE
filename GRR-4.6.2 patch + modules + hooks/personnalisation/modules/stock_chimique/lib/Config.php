<?php

class StockChimiqueConfig
{
    const MODULE = 'stock_chimique';
    const DEFAULT_DOCUMENT_EXTENSIONS = 'pdf,txt,csv,jpg,jpeg,png,odt,ods,doc,docx,xls,xlsx';
    const MIN_DOCUMENT_MAX_MB = 1;
    const MAX_DOCUMENT_MAX_MB = 50;

    public static function get($name, $default = '')
    {
        $value = Settings::get(self::storageName($name));
        return $value === null ? $default : $value;
    }

    public static function set($name, $value)
    {
        return Settings::set(self::storageName($name), (string) $value);
    }

    private static function storageName($name)
    {
        $map = array(
            'enabled' => 'schim_enabled',
            'display_name' => 'schim_display_name',
            'alerts_enabled' => 'schim_alerts_enabled',
            'alert_stock_enabled' => 'schim_alert_stock',
            'alert_expiry_enabled' => 'schim_alert_expiry',
            'alert_fds_enabled' => 'schim_alert_fds',
            'expiry_days' => 'schim_expiry_days',
            'fds_months' => 'schim_fds_months',
            'documents_enabled' => 'schim_docs_enabled',
            'document_max_mb' => 'schim_docs_mb',
            'document_extensions' => 'schim_docs_ext',
            'notifications_enabled' => 'schim_notif_enabled',
            'notification_token' => 'schim_notif_token',
        );

        return isset($map[$name]) ? $map[$name] : 'schim_'.$name;
    }

    public static function isEnabled()
    {
        return self::get('enabled', '1') === '1';
    }

    public static function displayName()
    {
        $name = trim((string) self::get('display_name', 'Stock chimique'));
        return $name === '' ? 'Stock chimique' : $name;
    }

    public static function expiryDays()
    {
        return self::boundedInt(self::get('expiry_days', '90'), 1, 730, 90);
    }

    public static function alertsEnabled()
    {
        return self::get('alerts_enabled', '1') === '1';
    }

    public static function stockAlertsEnabled()
    {
        return self::alertsEnabled() && self::get('alert_stock_enabled', '1') === '1';
    }

    public static function expiryAlertsEnabled()
    {
        return self::alertsEnabled() && self::get('alert_expiry_enabled', '1') === '1';
    }

    public static function fdsAlertsEnabled()
    {
        return self::alertsEnabled() && self::get('alert_fds_enabled', '1') === '1';
    }

    public static function fdsMonths()
    {
        return self::boundedInt(self::get('fds_months', '36'), 1, 120, 36);
    }

    public static function documentsEnabled()
    {
        return self::get('documents_enabled', '1') === '1';
    }

    public static function documentMaxMb()
    {
        return self::boundedInt(
            self::get('document_max_mb', '10'),
            self::MIN_DOCUMENT_MAX_MB,
            self::MAX_DOCUMENT_MAX_MB,
            10
        );
    }

    public static function documentMaxBytes()
    {
        return self::documentMaxMb() * 1024 * 1024;
    }

    public static function documentExtensions()
    {
        $extensions = self::extensionsFromText(
            self::get('document_extensions', self::DEFAULT_DOCUMENT_EXTENSIONS)
        );
        return count($extensions) > 0
            ? $extensions
            : self::extensionsFromText(self::DEFAULT_DOCUMENT_EXTENSIONS);
    }

    public static function documentExtensionsText()
    {
        return implode(', ', self::documentExtensions());
    }

    public static function extensionsFromText($text)
    {
        $forbidden = array_fill_keys(array(
            'php', 'php3', 'php4', 'php5', 'phtml', 'phar', 'cgi', 'pl', 'py',
            'js', 'html', 'htm', 'svg', 'sh', 'bash', 'bat', 'cmd', 'com',
            'exe', 'msi', 'jar', 'htaccess', 'ini',
        ), true);
        $clean = array();
        foreach (preg_split('/[\s,;]+/', strtolower((string) $text)) as $token) {
            $extension = ltrim(trim((string) $token), '.');
            if (
                $extension !== ''
                && preg_match('/^[a-z0-9]{1,10}$/', $extension)
                && !isset($forbidden[$extension])
            ) {
                $clean[$extension] = $extension;
            }
        }

        return array_values($clean);
    }

    public static function notificationToken()
    {
        return trim((string) self::get('notification_token', ''));
    }

    public static function notificationsEnabled()
    {
        return self::get('notifications_enabled', '1') === '1';
    }

    public static function setNotificationToken($token)
    {
        $token = trim((string) $token);
        if ($token !== '' && !preg_match('/^[a-f0-9]{64}$/', $token)) {
            return false;
        }

        return self::set('notification_token', $token);
    }

    public static function notificationTokenIsValid($token)
    {
        $expected = self::notificationToken();
        $token = trim((string) $token);
        return $expected !== '' && $token !== '' && hash_equals($expected, $token);
    }

    private static function boundedInt($value, $min, $max, $default)
    {
        $value = (int) $value;
        return $value >= $min && $value <= $max ? $value : $default;
    }
}
