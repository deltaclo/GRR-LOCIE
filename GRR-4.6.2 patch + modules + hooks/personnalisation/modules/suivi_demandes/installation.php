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

        self::setDefault('suivi_demandes_enabled', '1');
        self::setDefault('suivi_demandes_display_name', 'Suivi des demandes');
        self::setDefault('suivi_demandes_account_enabled', '1');
        self::setDefault('suivi_demandes_resa_form', '1');
        self::setDefault('suivi_demandes_resa_detail', '1');
        self::setDefault('suivi_demandes_notif', '1');
        self::setDefault('suivi_demandes_create_right', 'all');
        self::setDefault('suivi_demandes_close_right', 'creator_manager_admin');
        self::setDefault('suivi_demandes_n_created', '1');
        self::setDefault('suivi_demandes_n_comment', '1');
        self::setDefault('suivi_demandes_n_status', '1');
        self::setDefault('suivi_demandes_n_follower', '1');
        self::setDefault('suivi_demandes_n_resource', '1');
        self::setDefault('suivi_demandes_n_attachment', '1');
        self::setDefault('suivi_demandes_prio_basse', 'Basse');
        self::setDefault('suivi_demandes_prio_normale', 'Normale');
        self::setDefault('suivi_demandes_prio_haute', 'Haute');
        self::setDefault('suivi_demandes_prio_basse_on', '1');
        self::setDefault('suivi_demandes_prio_normale_on', '1');
        self::setDefault('suivi_demandes_prio_haute_on', '1');
        self::setDefault('suivi_demandes_status_ouverte', 'Ouverte');
        self::setDefault('suivi_demandes_status_en_cours', 'En cours');
        self::setDefault('suivi_demandes_status_cloturee', 'Cloturee');
        self::setDefault('suivi_demandes_nopen_col', '#5bc0de');
        self::setDefault('suivi_demandes_nprog_col', '#f0ad4e');
        self::setDefault('suivi_demandes_cats_on', '1');
        self::setDefault('suivi_demandes_cats', 'General');
        self::setDefault('suivi_demandes_attach_on', '1');
        self::setDefault('suivi_demandes_attach_mb', '5');
        self::setDefault('suivi_demandes_attach_ext', "pdf\ntxt\ncsv\njpg\njpeg\npng\ngif\nodt\nods\ndoc\ndocx\nxls\nxlsx\nzip");
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
        grr_sql_command("CREATE TABLE IF NOT EXISTS `".TABLE_PREFIX."_suivi_demande` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `titre` varchar(190) NOT NULL,
            `description` text,
            `statut` varchar(30) NOT NULL DEFAULT 'ouverte',
            `priorite` varchar(30) NOT NULL DEFAULT 'normale',
            `categorie` varchar(60) NOT NULL DEFAULT '',
            `createur` varchar(190) NOT NULL,
            `created_at` int(11) NOT NULL,
            `updated_at` int(11) NOT NULL,
            `closed_at` int(11) DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `createur` (`createur`),
            KEY `statut` (`statut`),
            KEY `priorite` (`priorite`),
            KEY `categorie` (`categorie`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");

        grr_sql_command("CREATE TABLE IF NOT EXISTS `".TABLE_PREFIX."_suivi_demande_ressource` (
            `demande_id` int(11) NOT NULL,
            `room_id` int(11) NOT NULL,
            PRIMARY KEY (`demande_id`, `room_id`),
            KEY `room_id` (`room_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");

        grr_sql_command("CREATE TABLE IF NOT EXISTS `".TABLE_PREFIX."_suivi_demande_suiveur` (
            `demande_id` int(11) NOT NULL,
            `login` varchar(190) NOT NULL,
            PRIMARY KEY (`demande_id`, `login`),
            KEY `login` (`login`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");

        grr_sql_command("CREATE TABLE IF NOT EXISTS `".TABLE_PREFIX."_suivi_demande_commentaire` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `demande_id` int(11) NOT NULL,
            `auteur` varchar(190) NOT NULL,
            `commentaire` text NOT NULL,
            `interne` tinyint(1) NOT NULL DEFAULT 0,
            `created_at` int(11) NOT NULL,
            PRIMARY KEY (`id`),
            KEY `demande_id` (`demande_id`),
            KEY `auteur` (`auteur`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");

        grr_sql_command("CREATE TABLE IF NOT EXISTS `".TABLE_PREFIX."_suivi_demande_historique` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `demande_id` int(11) NOT NULL,
            `auteur` varchar(190) NOT NULL,
            `action` varchar(50) NOT NULL,
            `details` text,
            `created_at` int(11) NOT NULL,
            PRIMARY KEY (`id`),
            KEY `demande_id` (`demande_id`),
            KEY `auteur` (`auteur`),
            KEY `action` (`action`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");

        grr_sql_command("CREATE TABLE IF NOT EXISTS `".TABLE_PREFIX."_suivi_demande_reservation` (
            `demande_id` int(11) NOT NULL,
            `entry_id` int(11) NOT NULL,
            `room_id` int(11) NOT NULL,
            `created_at` int(11) NOT NULL,
            PRIMARY KEY (`demande_id`, `entry_id`),
            KEY `entry_id` (`entry_id`),
            KEY `room_id` (`room_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");

        grr_sql_command("CREATE TABLE IF NOT EXISTS `".TABLE_PREFIX."_suivi_demande_room_config` (
            `room_id` int(11) NOT NULL,
            `enabled` tinyint(1) NOT NULL DEFAULT 1,
            `updated_at` int(11) NOT NULL,
            PRIMARY KEY (`room_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");

        grr_sql_command("CREATE TABLE IF NOT EXISTS `".TABLE_PREFIX."_suivi_demande_user_config` (
            `login` varchar(190) NOT NULL,
            `enabled` tinyint(1) NOT NULL DEFAULT 1,
            `updated_at` int(11) NOT NULL,
            PRIMARY KEY (`login`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");

        grr_sql_command("CREATE TABLE IF NOT EXISTS `".TABLE_PREFIX."_suivi_demande_fichier` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `demande_id` int(11) NOT NULL,
            `commentaire_id` int(11) NOT NULL DEFAULT 0,
            `original_name` varchar(190) NOT NULL,
            `stored_name` varchar(80) NOT NULL,
            `mime_type` varchar(120) NOT NULL DEFAULT '',
            `taille` int(11) NOT NULL DEFAULT 0,
            `uploader` varchar(190) NOT NULL,
            `created_at` int(11) NOT NULL,
            PRIMARY KEY (`id`),
            KEY `demande_id` (`demande_id`),
            KEY `commentaire_id` (`commentaire_id`),
            KEY `uploader` (`uploader`),
            UNIQUE KEY `stored_name` (`stored_name`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
    }
}
