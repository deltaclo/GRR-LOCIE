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

        self::setDefault('stagiaire_enabled', '1');
        self::setDefault('stagiaire_display_name', 'Stagiaire');
        self::setDefault('stagiaire_form_on', '1');
        self::setDefault('stagiaire_detail_on', '1');
        self::setDefault('stagiaire_mail_on', '1');
        self::createTables();
    }

    private static function setDefault($name, $value)
    {
        if (Settings::get($name) === null) {
            Settings::set($name, $value);
        }
    }

    private static function createTables()
    {
        grr_sql_command("CREATE TABLE IF NOT EXISTS `".TABLE_PREFIX."_stagiaire_user` (
            `login` varchar(190) NOT NULL,
            `created_by` varchar(190) NOT NULL DEFAULT '',
            `created_at` int(11) NOT NULL,
            PRIMARY KEY (`login`),
            KEY `created_by` (`created_by`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");

        grr_sql_command("CREATE TABLE IF NOT EXISTS `".TABLE_PREFIX."_stagiaire_reservation` (
            `entry_id` int(11) NOT NULL,
            `nom` varchar(100) NOT NULL,
            `prenom` varchar(100) NOT NULL,
            `email` varchar(190) NOT NULL,
            `encadrant` varchar(190) NOT NULL,
            `created_by` varchar(190) NOT NULL DEFAULT '',
            `created_at` int(11) NOT NULL,
            `updated_at` int(11) NOT NULL,
            `mail_creation_sent` tinyint(1) NOT NULL DEFAULT 0,
            `mail_moderation_sent` tinyint(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (`entry_id`),
            KEY `email` (`email`),
            KEY `created_by` (`created_by`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
    }
}
