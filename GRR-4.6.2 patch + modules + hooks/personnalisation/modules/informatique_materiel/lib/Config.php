<?php

class InformatiqueMaterielConfig
{
    const MODULE = 'informatique_materiel';
    const DEFAULT_DOCUMENT_EXTENSIONS = 'pdf,txt,csv,jpg,jpeg,png,odt,ods,doc,docx,xls,xlsx';
    const MIN_DOCUMENT_MAX_MB = 1;
    const MAX_DOCUMENT_MAX_MB = 50;
    const DEFAULT_ALERT_DANGER_COLOR = '#c9302c';
    const DEFAULT_ALERT_WARNING_COLOR = '#f0ad4e';
    const DEFAULT_CONFLICT_ALERT_COLOR = '#8a6d3b';

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
            'enabled' => 'imat_enabled',
            'display_name' => 'imat_display_name',
            'documents_enabled' => 'imat_docs_enabled',
            'document_max_mb' => 'imat_docs_mb',
            'document_extensions' => 'imat_docs_ext',
            'alerts_enabled' => 'imat_alerts_enabled',
            'depart_days' => 'imat_depart_days',
            'conflict_banner_enabled' => 'imat_conflict_banner_enabled',
            'alert_danger_color' => 'imat_alert_danger_color',
            'alert_warning_color' => 'imat_alert_warning_color',
            'conflict_alert_color' => 'imat_conflict_alert_color',
        );

        return isset($map[$name]) ? $map[$name] : 'imat_'.$name;
    }

    public static function isEnabled()
    {
        return self::get('enabled', '1') === '1';
    }

    public static function displayName()
    {
        $name = trim((string) self::get('display_name', 'Informatique materiel'));
        return $name === '' ? 'Informatique materiel' : $name;
    }

    public static function alertsEnabled()
    {
        return self::get('alerts_enabled', '1') === '1';
    }

    public static function departDays()
    {
        return self::boundedInt(self::get('depart_days', '30'), 1, 365, 30);
    }

    public static function conflictBannerEnabled()
    {
        return self::get('conflict_banner_enabled', '1') === '1';
    }

    public static function alertDangerColor()
    {
        return self::cleanColor(self::get('alert_danger_color', self::DEFAULT_ALERT_DANGER_COLOR), self::DEFAULT_ALERT_DANGER_COLOR);
    }

    public static function alertWarningColor()
    {
        return self::cleanColor(self::get('alert_warning_color', self::DEFAULT_ALERT_WARNING_COLOR), self::DEFAULT_ALERT_WARNING_COLOR);
    }

    public static function conflictAlertColor()
    {
        return self::cleanColor(self::get('conflict_alert_color', self::DEFAULT_CONFLICT_ALERT_COLOR), self::DEFAULT_CONFLICT_ALERT_COLOR);
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
        return self::documentMaxMb() * 1048576;
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

    public static function isHexColor($value)
    {
        return preg_match('/^#[0-9a-fA-F]{6}$/', (string) $value) === 1;
    }

    public static function cleanColor($value, $default)
    {
        $value = trim((string) $value);
        return self::isHexColor($value) ? strtolower($value) : $default;
    }

    private static function boundedInt($value, $min, $max, $default)
    {
        $value = (int) $value;
        return $value >= $min && $value <= $max ? $value : $default;
    }
}
