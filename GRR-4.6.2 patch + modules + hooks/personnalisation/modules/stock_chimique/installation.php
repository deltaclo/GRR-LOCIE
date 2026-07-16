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
            grr_sql_command("UPDATE ".TABLE_PREFIX."_modulesext SET actif = '1' WHERE nom = '".$nom."'");
        } else {
            grr_sql_command("INSERT INTO ".TABLE_PREFIX."_modulesext (nom, actif, version) VALUES ('".$nom."', '1', '0')");
        }

        self::setDefault('schim_enabled', '1');
        self::setDefault('schim_display_name', 'Stock chimique');
        self::setDefault('schim_alerts_enabled', '1');
        self::setDefault('schim_alert_stock', '1');
        self::setDefault('schim_alert_expiry', '1');
        self::setDefault('schim_alert_fds', '1');
        self::setDefault('schim_expiry_days', '90');
        self::setDefault('schim_fds_months', '36');
        self::setDefault('schim_docs_enabled', '1');
        self::setDefault('schim_docs_mb', '10');
        self::setDefault('schim_docs_ext', 'pdf,txt,csv,jpg,jpeg,png,odt,ods,doc,docx,xls,xlsx');
        self::setDefault('schim_notif_enabled', '1');

        if (!StockChimiqueRepository::ensureTables() || !StockChimiqueRepository::ensureDocumentStorage()) {
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
