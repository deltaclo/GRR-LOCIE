<?php

class FormulairesDynamiquesRepository
{
    const TABLE_FORM = 'formulaire_dyn_formulaire';
    const TABLE_FIELD = 'formulaire_dyn_champ';
    const TABLE_RESPONSE = 'formulaire_dyn_reponse';
    const TABLE_VALUE = 'formulaire_dyn_valeur';
    const TABLE_MANAGER = 'formulaire_dyn_gestionnaire';
    const TABLE_NOTIFICATION = 'formulaire_dyn_notification';
    const TABLE_TOKEN = 'formulaire_dyn_token';
    const TABLE_HISTORY = 'formulaire_dyn_historique';

    public static function ensureTables()
    {
        grr_sql_command("CREATE TABLE IF NOT EXISTS `".self::table(self::TABLE_FORM)."` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `titre` varchar(190) NOT NULL,
            `description` text NULL,
            `statut` varchar(30) NOT NULL DEFAULT 'brouillon',
            `created_by` varchar(190) NOT NULL DEFAULT '',
            `created_at` int(11) NOT NULL DEFAULT 0,
            `updated_at` int(11) NOT NULL DEFAULT 0,
            `published_at` int(11) DEFAULT NULL,
            `archived_at` int(11) DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `statut` (`statut`),
            KEY `created_by` (`created_by`),
            KEY `updated_at` (`updated_at`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");

        grr_sql_command("CREATE TABLE IF NOT EXISTS `".self::table(self::TABLE_FIELD)."` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `formulaire_id` int(11) NOT NULL,
            `type_champ` varchar(40) NOT NULL,
            `libelle` varchar(190) NOT NULL,
            `aide` text NULL,
            `options` text NULL,
            `valeur_defaut` text NULL,
            `obligatoire` tinyint(1) NOT NULL DEFAULT 0,
            `ordre` int(11) NOT NULL DEFAULT 0,
            `actif` tinyint(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (`id`),
            KEY `formulaire_id` (`formulaire_id`),
            KEY `ordre` (`ordre`),
            KEY `actif` (`actif`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");

        grr_sql_command("CREATE TABLE IF NOT EXISTS `".self::table(self::TABLE_RESPONSE)."` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `formulaire_id` int(11) NOT NULL,
            `submitter_login` varchar(190) NOT NULL DEFAULT '',
            `submitter_name` varchar(190) NOT NULL DEFAULT '',
            `submitter_email` varchar(190) NOT NULL DEFAULT '',
            `source` varchar(30) NOT NULL DEFAULT 'grr',
            `ip_hash` varchar(64) NOT NULL DEFAULT '',
            `created_at` int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            KEY `formulaire_id` (`formulaire_id`),
            KEY `submitter_login` (`submitter_login`),
            KEY `created_at` (`created_at`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");

        grr_sql_command("CREATE TABLE IF NOT EXISTS `".self::table(self::TABLE_VALUE)."` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `reponse_id` int(11) NOT NULL,
            `champ_id` int(11) NOT NULL,
            `valeur` longtext NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `reponse_champ` (`reponse_id`, `champ_id`),
            KEY `champ_id` (`champ_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");

        grr_sql_command("CREATE TABLE IF NOT EXISTS `".self::table(self::TABLE_MANAGER)."` (
            `formulaire_id` int(11) NOT NULL,
            `login` varchar(190) NOT NULL,
            `created_at` int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (`formulaire_id`, `login`),
            KEY `login` (`login`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");

        grr_sql_command("CREATE TABLE IF NOT EXISTS `".self::table(self::TABLE_NOTIFICATION)."` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `formulaire_id` int(11) NOT NULL,
            `email` varchar(190) NOT NULL,
            `nom` varchar(190) NOT NULL DEFAULT '',
            `actif` tinyint(1) NOT NULL DEFAULT 1,
            `created_at` int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            KEY `formulaire_id` (`formulaire_id`),
            KEY `actif` (`actif`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");

        grr_sql_command("CREATE TABLE IF NOT EXISTS `".self::table(self::TABLE_TOKEN)."` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `formulaire_id` int(11) NOT NULL,
            `type_token` varchar(30) NOT NULL,
            `token_hash` varchar(64) NOT NULL,
            `actif` tinyint(1) NOT NULL DEFAULT 1,
            `created_at` int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            UNIQUE KEY `token_hash` (`token_hash`),
            KEY `formulaire_id` (`formulaire_id`),
            KEY `type_token` (`type_token`),
            KEY `actif` (`actif`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");

        grr_sql_command("CREATE TABLE IF NOT EXISTS `".self::table(self::TABLE_HISTORY)."` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `formulaire_id` int(11) NOT NULL,
            `reponse_id` int(11) NOT NULL DEFAULT 0,
            `auteur` varchar(190) NOT NULL DEFAULT '',
            `action` varchar(60) NOT NULL,
            `details` text NULL,
            `created_at` int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            KEY `formulaire_id` (`formulaire_id`),
            KEY `reponse_id` (`reponse_id`),
            KEY `action` (`action`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
    }

    public static function diagnostics()
    {
        $tables = array(
            self::TABLE_FORM => 'Formulaires',
            self::TABLE_FIELD => 'Champs',
            self::TABLE_RESPONSE => 'Reponses',
            self::TABLE_VALUE => 'Valeurs de reponse',
            self::TABLE_MANAGER => 'Gestionnaires par formulaire',
            self::TABLE_NOTIFICATION => 'Notifications',
            self::TABLE_TOKEN => 'Jetons autonomes',
            self::TABLE_HISTORY => 'Historique',
        );
        $diagnostics = array();

        foreach ($tables as $suffix => $label) {
            $diagnostics[] = array(
                'label' => $label,
                'table' => self::table($suffix),
                'exists' => self::tableExists($suffix),
            );
        }

        return $diagnostics;
    }

    public static function countForms()
    {
        return self::countRows(self::TABLE_FORM);
    }

    public static function countFields()
    {
        return self::countRows(self::TABLE_FIELD);
    }

    public static function countResponses()
    {
        return self::countRows(self::TABLE_RESPONSE);
    }

    public static function activeUsers()
    {
        $rows = array();
        $result = grr_sql_query(
            "SELECT login, nom, prenom, email
            FROM ".TABLE_PREFIX."_utilisateurs
            WHERE etat != 'inactif'
            ORDER BY nom, prenom, login"
        );
        if (!$result) {
            return $rows;
        }

        for ($i = 0; ($row = grr_sql_row_keyed($result, $i)); $i++) {
            $login = isset($row['login']) ? trim((string) $row['login']) : '';
            if ($login === '') {
                continue;
            }

            $row['label'] = self::userLabel($row);
            $rows[] = $row;
        }

        return $rows;
    }

    public static function tableExists($suffix)
    {
        $tableName = self::table($suffix);
        if ($tableName === TABLE_PREFIX.'_') {
            return false;
        }

        $count = grr_sql_query1(
            "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?",
            "s",
            array($tableName)
        );

        return (int) $count > 0;
    }

    public static function table($suffix)
    {
        return TABLE_PREFIX.'_'.trim((string) $suffix);
    }

    private static function countRows($suffix)
    {
        if (!self::tableExists($suffix)) {
            return 0;
        }

        return (int) grr_sql_query1("SELECT COUNT(*) FROM `".self::table($suffix)."`");
    }

    private static function userLabel($user)
    {
        $parts = array();
        if (isset($user['nom']) && trim((string) $user['nom']) !== '') {
            $parts[] = trim((string) $user['nom']);
        }
        if (isset($user['prenom']) && trim((string) $user['prenom']) !== '') {
            $parts[] = trim((string) $user['prenom']);
        }

        $login = isset($user['login']) ? trim((string) $user['login']) : '';
        $label = implode(' ', $parts);
        if ($label === '') {
            $label = $login;
        } elseif ($login !== '') {
            $label .= ' ('.$login.')';
        }

        if (isset($user['email']) && trim((string) $user['email']) !== '') {
            $label .= ' - '.trim((string) $user['email']);
        }

        return $label;
    }
}
