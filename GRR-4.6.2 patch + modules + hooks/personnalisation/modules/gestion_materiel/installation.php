<?php

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

        self::setDefault('gmateriel_enabled', '1');
        self::setDefault('gmateriel_display_name', 'Gestion materiel');
        self::setDefault('gmateriel_upcoming_days', '30');
        self::setDefault('gmateriel_managers', '');
        self::setDefault('gmateriel_docs_on', '1');
        self::setDefault('gmateriel_docs_mb', '10');
        self::setDefault('gmateriel_docs_ext', 'pdf,txt,csv,jpg,jpeg,png,odt,ods,doc,docx,xls,xlsx,zip');

        GestionMaterielRepository::ensureTables();
        GestionMaterielRepository::ensureDocumentStorage();
    }

    private static function setDefault($name, $value)
    {
        if (Settings::get($name) === null) {
            Settings::set($name, $value);
        }
    }
}
