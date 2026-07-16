<?php

class GestionMaterielConfig
{
    const MODULE = 'gestion_materiel';
    const DEFAULT_DOCUMENT_MAX_MB = 10;
    const MIN_DOCUMENT_MAX_MB = 1;
    const MAX_DOCUMENT_MAX_MB = 50;
    const DEFAULT_DOCUMENT_EXTENSIONS = 'pdf,txt,csv,jpg,jpeg,png,odt,ods,doc,docx,xls,xlsx,zip';

    public static function get($name, $default = '')
    {
        $value = Settings::get(self::storageName($name));

        return ($value === null || $value === '') ? $default : $value;
    }

    public static function set($name, $value)
    {
        $storageName = self::storageName($name);
        if (strlen($storageName) > 32) {
            return false;
        }

        $safeName = SecuChaine::ProtectDataSql($storageName);
        $safeValue = SecuChaine::ProtectDataSql((string) $value);
        $exists = grr_sql_query1("SELECT COUNT(*) FROM ".TABLE_PREFIX."_setting WHERE NAME = '".$safeName."'");

        if ((int) $exists > 0) {
            $result = grr_sql_command("UPDATE ".TABLE_PREFIX."_setting SET VALUE = '".$safeValue."' WHERE NAME = '".$safeName."'");
        } else {
            $result = grr_sql_command("INSERT INTO ".TABLE_PREFIX."_setting SET NAME = '".$safeName."', VALUE = '".$safeValue."'");
        }

        if ($result === false || $result < 0) {
            return false;
        }

        Settings::load();
        return true;
    }

    private static function storageName($name)
    {
        $names = array(
            'enabled' => 'gmateriel_enabled',
            'display_name' => 'gmateriel_display_name',
            'upcoming_days' => 'gmateriel_upcoming_days',
            'manager_logins' => 'gmateriel_managers',
            'notification_token' => 'gmateriel_notif_token',
            'alert_overdue_color' => 'gmateriel_alert_late_col',
            'alert_upcoming_color' => 'gmateriel_alert_soon_col',
            'dashboard_tiles' => 'gmateriel_dash_tiles',
            'documents_enabled' => 'gmateriel_docs_on',
            'document_max_mb' => 'gmateriel_docs_mb',
            'document_extensions' => 'gmateriel_docs_ext',
        );

        return isset($names[$name]) ? $names[$name] : 'gmateriel_'.$name;
    }

    public static function isEnabled()
    {
        return self::get('enabled', '1') === '1';
    }

    public static function displayName()
    {
        return self::get('display_name', 'Gestion materiel');
    }

    public static function upcomingDays()
    {
        return self::normalizeDays(self::get('upcoming_days', '30'));
    }

    public static function setUpcomingDays($days)
    {
        return self::set('upcoming_days', (string) self::normalizeDays($days));
    }

    public static function documentsEnabled()
    {
        return self::get('documents_enabled', '1') === '1';
    }

    public static function documentMaxMb()
    {
        $megabytes = (int) self::get('document_max_mb', (string) self::DEFAULT_DOCUMENT_MAX_MB);
        if ($megabytes < self::MIN_DOCUMENT_MAX_MB || $megabytes > self::MAX_DOCUMENT_MAX_MB) {
            return self::DEFAULT_DOCUMENT_MAX_MB;
        }

        return $megabytes;
    }

    public static function documentMaxBytes()
    {
        return self::documentMaxMb() * 1024 * 1024;
    }

    public static function documentExtensions()
    {
        $extensions = self::documentExtensionsFromText(
            self::get('document_extensions', self::DEFAULT_DOCUMENT_EXTENSIONS)
        );

        return count($extensions) > 0
            ? $extensions
            : self::documentExtensionsFromText(self::DEFAULT_DOCUMENT_EXTENSIONS);
    }

    public static function documentExtensionsText()
    {
        return implode(', ', self::documentExtensions());
    }

    public static function setDocumentExtensions($extensions)
    {
        if (!is_array($extensions)) {
            $extensions = self::documentExtensionsFromText($extensions);
        }

        $clean = self::documentExtensionsFromText(implode(',', $extensions));
        if (count($clean) === 0) {
            return false;
        }

        return self::set('document_extensions', implode(',', $clean));
    }

    public static function documentExtensionsFromText($text)
    {
        $tokens = preg_split('/[\s,;]+/', strtolower((string) $text));
        $extensions = array();
        $forbidden = self::forbiddenDocumentExtensions();

        foreach ($tokens as $token) {
            $extension = ltrim(trim((string) $token), '.');
            if (
                $extension !== ''
                && preg_match('/^[a-z0-9]{1,10}$/', $extension)
                && !isset($forbidden[$extension])
            ) {
                $extensions[$extension] = $extension;
            }
        }

        return array_values($extensions);
    }

    public static function invalidDocumentExtensionsFromText($text)
    {
        $tokens = preg_split('/[\s,;]+/', strtolower((string) $text));
        $invalid = array();
        $forbidden = self::forbiddenDocumentExtensions();

        foreach ($tokens as $token) {
            $extension = ltrim(trim((string) $token), '.');
            if ($extension === '') {
                continue;
            }
            if (!preg_match('/^[a-z0-9]{1,10}$/', $extension) || isset($forbidden[$extension])) {
                $invalid[$extension] = $extension;
            }
        }

        return array_values($invalid);
    }

    private static function forbiddenDocumentExtensions()
    {
        $extensions = array(
            'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar',
            'cgi', 'pl', 'py', 'sh', 'bash', 'exe', 'bat', 'cmd', 'com', 'msi',
            'js', 'html', 'htm', 'svg',
        );

        return array_fill_keys($extensions, true);
    }

    public static function normalizeDays($days)
    {
        $days = (int) $days;
        if ($days < 1 || $days > 365) {
            return 30;
        }

        return $days;
    }

    public static function notificationToken()
    {
        return trim((string) self::get('notification_token', ''));
    }

    public static function setNotificationToken($token)
    {
        $token = trim((string) $token);
        if ($token !== '' && !preg_match('/^[a-zA-Z0-9]{32,128}$/', $token)) {
            return false;
        }

        return self::set('notification_token', $token);
    }

    public static function hasNotificationToken()
    {
        return self::notificationToken() !== '';
    }

    public static function generateNotificationToken()
    {
        if (function_exists('random_bytes')) {
            try {
                return bin2hex(random_bytes(24));
            } catch (Exception $exception) {
                // Fallback below.
            }
        }

        return substr(sha1(uniqid('', true).mt_rand()).sha1((string) microtime(true)), 0, 48);
    }

    public static function notificationTokenIsValid($token)
    {
        $expected = self::notificationToken();
        $token = trim((string) $token);
        if ($expected === '' || $token === '') {
            return false;
        }

        if (function_exists('hash_equals')) {
            return hash_equals($expected, $token);
        }

        return $expected === $token;
    }

    public static function managerLogins()
    {
        $raw = str_replace(array("\r\n", "\r", "\n"), ',', self::get('manager_logins', ''));
        $parts = explode(',', $raw);
        $logins = array();

        foreach ($parts as $login) {
            $login = trim((string) $login);
            if ($login !== '' && strlen($login) <= 190) {
                $logins[$login] = $login;
            }
        }

        return array_values($logins);
    }

    public static function setManagerLogins($logins)
    {
        if (!is_array($logins)) {
            $logins = array($logins);
        }

        $clean = array();
        foreach ($logins as $login) {
            $login = trim((string) $login);
            if ($login !== '' && strlen($login) <= 190) {
                $clean[$login] = $login;
            }
        }

        return self::set('manager_logins', implode(',', array_values($clean)));
    }

    public static function isManager($login)
    {
        $login = self::normalizeLogin($login);
        if ($login === '') {
            return false;
        }

        foreach (self::managerLogins() as $managerLogin) {
            if (self::normalizeLogin($managerLogin) === $login) {
                return true;
            }
        }

        return false;
    }

    private static function normalizeLogin($login)
    {
        $login = trim((string) $login);
        if ($login === '') {
            return '';
        }

        return function_exists('mb_strtolower')
            ? mb_strtolower($login, 'UTF-8')
            : strtolower($login);
    }

    public static function dashboardTileDefinitions()
    {
        return array(
            'items' => array('label' => 'Materiels', 'color' => '#17a2b8'),
            'maintenance_overdue' => array('label' => 'Maintenances en retard', 'color' => '#dc3545'),
            'etalonnage_overdue' => array('label' => 'Etalonnages en retard', 'color' => '#dc3545'),
            'assigned_users' => array('label' => 'Utilisateurs assignes', 'color' => '#17a2b8'),
            'maintenance_upcoming' => array('label' => 'Maintenances a venir', 'color' => '#ffc107'),
            'etalonnage_upcoming' => array('label' => 'Etalonnages a venir', 'color' => '#ffc107'),
            'deadlines_total' => array('label' => 'Echeances a signaler', 'color' => '#17a2b8'),
            'actions' => array('label' => 'Actions journalisees', 'color' => '#17a2b8'),
        );
    }

    public static function dashboardTileColumnOptions()
    {
        $options = array();
        for ($columns = 1; $columns <= 8; $columns++) {
            $options[$columns] = $columns.' tuile'.($columns > 1 ? 's' : '').' par ligne';
        }

        return $options;
    }

    public static function dashboardTileSizeOptions()
    {
        return array(
            'compact' => 'Compacte',
            'normal' => 'Normale',
            'large' => 'Grande',
        );
    }

    public static function dashboardTileConfig()
    {
        $raw = trim((string) self::get('dashboard_tiles', ''));
        $config = $raw === '' ? array() : json_decode($raw, true);

        return self::normalizeDashboardTileConfig(is_array($config) ? $config : array());
    }

    public static function normalizeDashboardTileConfig($config)
    {
        $definitions = self::dashboardTileDefinitions();
        $config = is_array($config) ? $config : array();
        $order = array();
        $seen = array();
        $configuredOrder = isset($config['order']) && is_array($config['order']) ? $config['order'] : array();

        foreach ($configuredOrder as $key) {
            $key = (string) $key;
            if (isset($definitions[$key]) && !isset($seen[$key])) {
                $order[] = $key;
                $seen[$key] = true;
            }
        }
        foreach ($definitions as $key => $definition) {
            if (!isset($seen[$key])) {
                $order[] = $key;
            }
        }

        $enabled = array();
        $colors = array();
        $configuredEnabled = isset($config['enabled']) && is_array($config['enabled']) ? $config['enabled'] : null;
        $configuredColors = isset($config['colors']) && is_array($config['colors']) ? $config['colors'] : array();
        foreach ($definitions as $key => $definition) {
            $enabled[$key] = $configuredEnabled === null
                ? true
                : isset($configuredEnabled[$key]) && (bool) $configuredEnabled[$key];
            $colors[$key] = self::normalizeColor(
                isset($configuredColors[$key]) ? $configuredColors[$key] : '',
                $definition['color']
            );
        }

        $columns = isset($config['columns']) ? (int) $config['columns'] : 4;
        $columnOptions = self::dashboardTileColumnOptions();
        if (!isset($columnOptions[$columns])) {
            $columns = 4;
        }

        $size = isset($config['size']) ? (string) $config['size'] : 'compact';
        $sizeOptions = self::dashboardTileSizeOptions();
        if (!isset($sizeOptions[$size])) {
            $size = 'compact';
        }

        return array(
            'order' => $order,
            'enabled' => $enabled,
            'colors' => $colors,
            'columns' => $columns,
            'size' => $size,
        );
    }

    public static function setDashboardTileConfig($config)
    {
        $json = json_encode(self::normalizeDashboardTileConfig($config));
        if ($json === false) {
            return false;
        }

        return self::set('dashboard_tiles', $json);
    }

    public static function alertLinkColorDefaults()
    {
        return array(
            'overdue' => '#dd4b39',
            'upcoming' => '#f39c12',
        );
    }

    public static function alertLinkColor($status)
    {
        $defaults = self::alertLinkColorDefaults();
        if (!isset($defaults[$status])) {
            return '#dd4b39';
        }

        $key = self::alertLinkColorKey($status);
        return self::normalizeColor(self::get($key, $defaults[$status]), $defaults[$status]);
    }

    public static function setAlertLinkColor($status, $color)
    {
        $defaults = self::alertLinkColorDefaults();
        if (!isset($defaults[$status])) {
            return false;
        }

        $color = self::normalizeColor($color, '');
        if ($color === '') {
            return false;
        }

        return self::set(self::alertLinkColorKey($status), $color);
    }

    public static function normalizeColor($color, $default)
    {
        $color = trim((string) $color);
        if (preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            return strtolower($color);
        }

        if (preg_match('/^[0-9a-fA-F]{6}$/', $color)) {
            return '#'.strtolower($color);
        }

        return $default;
    }

    private static function alertLinkColorKey($status)
    {
        return $status === 'upcoming' ? 'alert_upcoming_color' : 'alert_overdue_color';
    }
}
