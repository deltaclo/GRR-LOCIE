<?php

class FormulairesDynamiquesConfig
{
    const MODULE = 'formulaires_dynamiques';
    const APP_PAGE = 'formulairesdynamiques';

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
            'enabled' => 'formdyn_enabled',
            'display_name' => 'formdyn_display_name',
            'account_enabled' => 'formdyn_account_on',
            'autonomous_enabled' => 'formdyn_auto_on',
            'notifications_enabled' => 'formdyn_notif_on',
            'manager_logins' => 'formdyn_managers',
        );

        return isset($names[$name]) ? $names[$name] : 'formdyn_'.$name;
    }

    public static function isEnabled()
    {
        return self::get('enabled', '1') === '1';
    }

    public static function displayName()
    {
        return self::get('display_name', 'Formulaires dynamiques');
    }

    public static function accountEnabled()
    {
        return self::get('account_enabled', '1') === '1';
    }

    public static function autonomousEnabled()
    {
        return self::get('autonomous_enabled', '1') === '1';
    }

    public static function notificationsEnabled()
    {
        return self::get('notifications_enabled', '1') === '1';
    }

    public static function managerLogins()
    {
        $raw = str_replace(array("\r\n", "\r", "\n"), ',', self::get('manager_logins', ''));
        $tokens = preg_split('/[,;]+/', $raw);
        $logins = array();

        foreach ($tokens as $token) {
            $login = trim((string) $token);
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
        return strtolower(trim((string) $login));
    }
}
