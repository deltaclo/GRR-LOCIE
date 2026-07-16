<?php

class Module
{
    public static function Installation($iter, $module_versionBDD)
    {
        $nom = SecuChaine::ProtectDataSql($iter);
        $version = (int) $module_versionBDD;

        $exists = grr_sql_query1("SELECT COUNT(*) FROM ".TABLE_PREFIX."_modulesext WHERE nom = '".$nom."'");
        if ($exists > 0) {
            grr_sql_command("UPDATE ".TABLE_PREFIX."_modulesext SET actif = '1', version = '".$version."' WHERE nom = '".$nom."'");
        } else {
            grr_sql_command("INSERT INTO ".TABLE_PREFIX."_modulesext (nom, actif, version) VALUES ('".$nom."', '1', '".$version."')");
        }

        if (Settings::get('ics_ressources_secret') == '') {
            Settings::set('ics_ressources_secret', bin2hex(random_bytes(32)));
        }

        self::setDefault('ics_ressources_enabled', '1');
        self::setDefault('ics_ressources_privacy', 'busy');
        self::setDefault('ics_ressources_past_days', '30');
        self::setDefault('ics_ressources_future_days', '365');
        self::setDefault('ics_ressources_include_moderated', '0');
        self::setDefault('ics_ressources_include_option', '0');
        self::setDefault('ics_ressources_include_inactive_rooms', '0');
        self::setDefault('ics_ressources_disabled_rooms', '');
    }

    private static function setDefault($name, $value)
    {
        if (Settings::get($name) === null) {
            Settings::set($name, $value);
        }
    }
}
