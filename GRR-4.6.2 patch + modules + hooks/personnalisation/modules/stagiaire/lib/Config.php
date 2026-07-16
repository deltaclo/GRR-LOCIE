<?php

class StagiaireConfig
{
    const MODULE = 'stagiaire';

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
            'enabled' => 'stagiaire_enabled',
            'display_name' => 'stagiaire_display_name',
            'form_enabled' => 'stagiaire_form_on',
            'detail_enabled' => 'stagiaire_detail_on',
            'mail_enabled' => 'stagiaire_mail_on',
        );

        return isset($names[$name]) ? $names[$name] : 'stagiaire_'.$name;
    }

    public static function isEnabled()
    {
        return self::get('enabled', '1') === '1';
    }

    public static function displayName()
    {
        return self::get('display_name', 'Stagiaire');
    }

    public static function formEnabled()
    {
        return self::get('form_enabled', '1') === '1';
    }

    public static function detailEnabled()
    {
        return self::get('detail_enabled', '1') === '1';
    }

    public static function mailEnabled()
    {
        return self::get('mail_enabled', '1') === '1';
    }
}
