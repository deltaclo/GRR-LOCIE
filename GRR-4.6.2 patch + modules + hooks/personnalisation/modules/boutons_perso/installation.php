<?php

require_once __DIR__.'/lib/Config.php';
require_once __DIR__.'/lib/Repository.php';

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

        self::setDefault('boutons_perso_enabled', '1');
        self::setDefault('bperso_enabled', '1');
        self::setDefault('bperso_display_name', 'Boutons perso');
        self::setDefault('bperso_show_title', '1');
        self::setDefault('bperso_acc_menu', '0');
        self::setDefault('bperso_panel_bg', '#f6f8fb');
        self::setDefault('bperso_panel_border', '#d8dee6');
        BoutonsPersoRepository::ensureTables();
    }

    private static function setDefault($name, $value)
    {
        if (Settings::get($name) === null) {
            Settings::set($name, $value);
        }
    }
}
