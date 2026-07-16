<?php

require_once __DIR__.'/lib/Repository.php';

class Module
{
    public static function Installation($iter, $module_versionBDD)
    {
        $nom = SecuChaine::ProtectDataSql($iter);
        $version = (int) $module_versionBDD;

        $exists = grr_sql_query1("SELECT COUNT(*) FROM ".TABLE_PREFIX."_modulesext WHERE nom = '".$nom."'");
        if ((int) $exists > 0) {
            grr_sql_command("UPDATE ".TABLE_PREFIX."_modulesext SET actif = '1' WHERE nom = '".$nom."'");
        } else {
            grr_sql_command("INSERT INTO ".TABLE_PREFIX."_modulesext (nom, actif, version) VALUES ('".$nom."', '1', '0')");
        }

        self::setDefault('imat_enabled', '1');
        self::setDefault('imat_display_name', 'Informatique materiel');
        self::setDefault('imat_docs_enabled', '1');
        self::setDefault('imat_docs_mb', '10');
        self::setDefault('imat_docs_ext', 'pdf,txt,csv,jpg,jpeg,png,odt,ods,doc,docx,xls,xlsx');
        self::setDefault('imat_alerts_enabled', '1');
        self::setDefault('imat_depart_days', '30');
        self::setDefault('imat_conflict_banner_enabled', '1');
        self::setDefault('imat_alert_danger_color', '#c9302c');
        self::setDefault('imat_alert_warning_color', '#f0ad4e');
        self::setDefault('imat_conflict_alert_color', '#8a6d3b');

        if (!InformatiqueMaterielRepository::ensureTables()) {
            return false;
        }

        grr_sql_command(
            "UPDATE ".TABLE_PREFIX."_modulesext SET version = '".$version."' WHERE nom = '".$nom."'"
        );

        return true;
    }

    private static function setDefault($name, $value)
    {
        if (Settings::get($name) === null) {
            Settings::set($name, $value);
        }
    }
}
