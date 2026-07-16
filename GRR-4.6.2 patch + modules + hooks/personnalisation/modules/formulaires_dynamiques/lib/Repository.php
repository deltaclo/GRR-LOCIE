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

    public static function response($responseId)
    {
        self::ensureTables();

        $responseId = (int) $responseId;
        if ($responseId <= 0) {
            return array();
        }

        $rows = self::rows(
            "SELECT id, formulaire_id, submitter_login, submitter_name, submitter_email, source, ip_hash, created_at
            FROM ".self::table(self::TABLE_RESPONSE)."
            WHERE id = ?",
            "i",
            array($responseId)
        );

        return isset($rows[0]) ? $rows[0] : array();
    }

    public static function responses($formId, $rowLimit = 200, $filters = array(), $offset = 0)
    {
        self::ensureTables();

        $formId = (int) $formId;
        if ($formId <= 0) {
            return array();
        }

        $rowLimit = max(1, min(500, (int) $rowLimit));
        $offset = max(0, (int) $offset);
        $filters = self::normalizeResponseFilters($filters);
        $query = self::responseFilterQuery($formId, $filters);

        return self::rows(
            "SELECT id, formulaire_id, submitter_login, submitter_name, submitter_email, source, ip_hash, created_at
            FROM ".self::table(self::TABLE_RESPONSE)." r
            WHERE ".$query['where']."
            ORDER BY created_at DESC, id DESC
            LIMIT ".$rowLimit." OFFSET ".$offset,
            $query['types'],
            $query['params']
        );
    }

    public static function countFilteredResponses($formId, $filters = array())
    {
        self::ensureTables();

        $formId = (int) $formId;
        if ($formId <= 0) {
            return 0;
        }

        $query = self::responseFilterQuery($formId, self::normalizeResponseFilters($filters));

        return (int) grr_sql_query1(
            "SELECT COUNT(*) FROM ".self::table(self::TABLE_RESPONSE)." r WHERE ".$query['where'],
            $query['types'],
            $query['params']
        );
    }

    public static function normalizeResponseFilters($filters)
    {
        if (!is_array($filters)) {
            $filters = array();
        }

        $source = isset($filters['source']) ? trim((string) $filters['source']) : '';
        if (!in_array($source, array('grr', 'autonomous'), true)) {
            $source = '';
        }

        return array(
            'q' => self::limit(isset($filters['q']) ? $filters['q'] : '', 190),
            'source' => $source,
            'date_from' => self::normalizeDateFilter(isset($filters['date_from']) ? $filters['date_from'] : '', false),
            'date_to' => self::normalizeDateFilter(isset($filters['date_to']) ? $filters['date_to'] : '', true),
        );
    }

    public static function responseValues($responseId)
    {
        self::ensureTables();

        $responseId = (int) $responseId;
        if ($responseId <= 0) {
            return array();
        }

        $rows = self::rows(
            "SELECT champ_id, valeur
            FROM ".self::table(self::TABLE_VALUE)."
            WHERE reponse_id = ?
            ORDER BY champ_id",
            "i",
            array($responseId)
        );

        $values = array();
        foreach ($rows as $row) {
            $fieldId = (int) (isset($row['champ_id']) ? $row['champ_id'] : 0);
            if ($fieldId > 0) {
                $values[$fieldId] = isset($row['valeur']) ? (string) $row['valeur'] : '';
            }
        }

        return $values;
    }

    public static function responseWithValues($responseId)
    {
        $response = self::response($responseId);
        if (!$response) {
            return array();
        }

        $response['values'] = self::responseValues($responseId);

        return $response;
    }

    public static function responsesWithValues($formId, $rowLimit = 200, $filters = array(), $offset = 0)
    {
        $responses = self::responses($formId, $rowLimit, $filters, $offset);
        foreach ($responses as $key => $response) {
            $responses[$key]['values'] = self::responseValues(isset($response['id']) ? (int) $response['id'] : 0);
        }

        return $responses;
    }

    public static function allResponsesWithValues($formId, $filters = array())
    {
        $all = array();
        $offset = 0;
        $batchSize = 500;

        do {
            $batch = self::responsesWithValues($formId, $batchSize, $filters, $offset);
            foreach ($batch as $response) {
                $all[] = $response;
            }

            $count = count($batch);
            $offset += $batchSize;
        } while ($count === $batchSize);

        return $all;
    }

    public static function recordResponseNotification($formId, $responseId, $action, $details)
    {
        self::recordHistory(
            (int) $formId,
            (int) $responseId,
            'notification',
            (string) $action,
            (string) $details
        );
    }

    public static function recordExport($formId, $responseId, $author, $details)
    {
        self::recordHistory(
            (int) $formId,
            (int) $responseId,
            $author,
            'export_reponses',
            $details
        );
    }

    public static function history($formId, $rowLimit = 50)
    {
        self::ensureTables();

        $formId = (int) $formId;
        if ($formId <= 0) {
            return array();
        }

        $rowLimit = max(1, min(200, (int) $rowLimit));

        return self::rows(
            "SELECT id, formulaire_id, reponse_id, auteur, action, details, created_at
            FROM ".self::table(self::TABLE_HISTORY)."
            WHERE formulaire_id = ?
            ORDER BY created_at DESC, id DESC
            LIMIT ".$rowLimit,
            "i",
            array($formId)
        );
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

    public static function userByLogin($login)
    {
        $login = self::limit($login, 190);
        if ($login === '') {
            return array();
        }

        $rows = self::rows(
            "SELECT login, nom, prenom, email
            FROM ".TABLE_PREFIX."_utilisateurs
            WHERE login = ? AND etat != 'inactif'
            LIMIT 1",
            "s",
            array($login)
        );

        return isset($rows[0]) ? $rows[0] : array();
    }

    public static function normalizeNotificationValues($values)
    {
        if (!is_array($values)) {
            $values = array();
        }

        return array(
            'id' => (int) (isset($values['id']) ? $values['id'] : (isset($values['notification_id']) ? $values['notification_id'] : 0)),
            'formulaire_id' => (int) (isset($values['formulaire_id']) ? $values['formulaire_id'] : (isset($values['form_id']) ? $values['form_id'] : 0)),
            'email' => self::limit(isset($values['email']) ? $values['email'] : '', 190),
            'nom' => self::limit(isset($values['nom']) ? $values['nom'] : '', 190),
            'actif' => self::flag(isset($values['actif']) ? $values['actif'] : 1),
        );
    }

    public static function validateNotificationValues($values)
    {
        $values = self::normalizeNotificationValues($values);
        $errors = array();

        if ($values['formulaire_id'] <= 0 || !self::form($values['formulaire_id'])) {
            $errors[] = 'Le formulaire est introuvable.';
        }

        if ($values['email'] === '') {
            $errors[] = 'L adresse email est obligatoire.';
        } elseif (!self::validEmail($values['email'])) {
            $errors[] = 'L adresse email est invalide.';
        } elseif (self::activeNotificationEmailExists($values['formulaire_id'], $values['email'], $values['id'])) {
            $errors[] = 'Cette adresse email est deja active pour ce formulaire.';
        }

        return $errors;
    }

    public static function createNotificationRecipient($formId, $values, $createdBy)
    {
        self::ensureTables();

        $values = self::normalizeNotificationValues(array_merge((array) $values, array('formulaire_id' => (int) $formId)));
        if (count(self::validateNotificationValues($values)) > 0) {
            return 0;
        }

        $insert = grr_sql_command(
            "INSERT INTO ".self::table(self::TABLE_NOTIFICATION)."
            (formulaire_id, email, nom, actif, created_at)
            VALUES (?, ?, ?, ?, ?)",
            "issii",
            array(
                (int) $formId,
                $values['email'],
                $values['nom'],
                (int) $values['actif'],
                time(),
            )
        );
        if ($insert === false || $insert < 0) {
            return 0;
        }

        $recipientId = (int) grr_sql_insert_id();
        self::recordHistory((int) $formId, 0, $createdBy, 'creation_notification', $values['email']);

        return $recipientId;
    }

    public static function disableNotificationRecipient($recipientId, $updatedBy)
    {
        self::ensureTables();

        $recipient = self::notificationRecipient($recipientId);
        if (!$recipient) {
            return false;
        }

        $update = grr_sql_command(
            "UPDATE ".self::table(self::TABLE_NOTIFICATION)." SET actif = 0 WHERE id = ?",
            "i",
            array((int) $recipientId)
        );
        if ($update === false || $update < 0) {
            return false;
        }

        self::recordHistory(
            (int) $recipient['formulaire_id'],
            0,
            $updatedBy,
            'desactivation_notification',
            isset($recipient['email']) ? $recipient['email'] : ''
        );

        return true;
    }

    public static function notificationRecipient($recipientId)
    {
        self::ensureTables();

        $recipientId = (int) $recipientId;
        if ($recipientId <= 0) {
            return array();
        }

        $rows = self::rows(
            "SELECT id, formulaire_id, email, nom, actif, created_at
            FROM ".self::table(self::TABLE_NOTIFICATION)."
            WHERE id = ?",
            "i",
            array($recipientId)
        );

        return isset($rows[0]) ? $rows[0] : array();
    }

    public static function notificationRecipients($formId, $activeOnly = true)
    {
        self::ensureTables();

        $formId = (int) $formId;
        if ($formId <= 0) {
            return array();
        }

        $whereActive = $activeOnly ? ' AND actif = 1' : '';

        return self::rows(
            "SELECT id, formulaire_id, email, nom, actif, created_at
            FROM ".self::table(self::TABLE_NOTIFICATION)."
            WHERE formulaire_id = ?".$whereActive."
            ORDER BY actif DESC, nom, email, id",
            "i",
            array($formId)
        );
    }

    public static function formManagers($formId)
    {
        self::ensureTables();

        $formId = (int) $formId;
        if ($formId <= 0) {
            return array();
        }

        return self::rows(
            "SELECT m.formulaire_id, m.login, m.created_at, u.nom, u.prenom, u.email
            FROM ".self::table(self::TABLE_MANAGER)." m
            LEFT JOIN ".TABLE_PREFIX."_utilisateurs u ON u.login = m.login
            WHERE m.formulaire_id = ?
            ORDER BY u.nom, u.prenom, m.login",
            "i",
            array($formId)
        );
    }

    public static function addFormManager($formId, $managerLogin, $createdBy)
    {
        self::ensureTables();

        $formId = (int) $formId;
        $managerLogin = self::limit($managerLogin, 190);
        if ($formId <= 0 || $managerLogin === '' || !self::form($formId)) {
            return false;
        }

        $insert = grr_sql_command(
            "INSERT IGNORE INTO ".self::table(self::TABLE_MANAGER)."
            (formulaire_id, login, created_at)
            VALUES (?, ?, ?)",
            "isi",
            array($formId, $managerLogin, time())
        );
        if ($insert === false || $insert < 0) {
            return false;
        }

        self::recordHistory($formId, 0, $createdBy, 'ajout_gestionnaire', $managerLogin);

        return true;
    }

    public static function removeFormManager($formId, $managerLogin, $updatedBy)
    {
        self::ensureTables();

        $formId = (int) $formId;
        $managerLogin = self::limit($managerLogin, 190);
        if ($formId <= 0 || $managerLogin === '') {
            return false;
        }

        $delete = grr_sql_command(
            "DELETE FROM ".self::table(self::TABLE_MANAGER)."
            WHERE formulaire_id = ? AND login = ?",
            "is",
            array($formId, $managerLogin)
        );
        if ($delete === false || $delete < 0) {
            return false;
        }

        self::recordHistory($formId, 0, $updatedBy, 'retrait_gestionnaire', $managerLogin);

        return true;
    }

    public static function userCanManageForm($login, $formId)
    {
        self::ensureTables();

        $login = self::limit($login, 190);
        $formId = (int) $formId;
        if ($login === '' || $formId <= 0) {
            return false;
        }

        return (int) grr_sql_query1(
            "SELECT COUNT(*) FROM ".self::table(self::TABLE_MANAGER)."
            WHERE formulaire_id = ? AND login = ?",
            "is",
            array($formId, $login)
        ) > 0;
    }

    public static function userManagesAnyForm($login)
    {
        self::ensureTables();

        $login = self::limit($login, 190);
        if ($login === '') {
            return false;
        }

        return (int) grr_sql_query1(
            "SELECT COUNT(*) FROM ".self::table(self::TABLE_MANAGER)." WHERE login = ?",
            "s",
            array($login)
        ) > 0;
    }

    public static function formsForLogin($login, $includeArchived = true)
    {
        self::ensureTables();

        $login = self::limit($login, 190);
        if ($login === '') {
            return array();
        }

        $whereArchived = $includeArchived ? '' : " AND f.statut <> 'archive'";

        return self::rows(
            "SELECT f.id, f.titre, f.description, f.statut, f.created_by, f.created_at, f.updated_at,
                f.published_at, f.archived_at,
                (SELECT COUNT(*) FROM ".self::table(self::TABLE_FIELD)." c WHERE c.formulaire_id = f.id AND c.actif = 1) AS field_count,
                (SELECT COUNT(*) FROM ".self::table(self::TABLE_RESPONSE)." r WHERE r.formulaire_id = f.id) AS response_count
            FROM ".self::table(self::TABLE_FORM)." f
            INNER JOIN ".self::table(self::TABLE_MANAGER)." m ON m.formulaire_id = f.id
            WHERE m.login = ?".$whereArchived."
            ORDER BY f.updated_at DESC, f.id DESC",
            "s",
            array($login)
        );
    }

    public static function statusOptions()
    {
        return array(
            'brouillon' => 'Brouillon',
            'publie' => 'Publie',
            'archive' => 'Archive',
        );
    }

    public static function normalizeFormValues($values)
    {
        if (!is_array($values)) {
            $values = array();
        }

        return array(
            'titre' => self::limit(isset($values['titre']) ? $values['titre'] : '', 190),
            'description' => trim((string) (isset($values['description']) ? $values['description'] : '')),
            'statut' => self::normalizeStatus(isset($values['statut']) ? $values['statut'] : 'brouillon'),
        );
    }

    public static function validateFormValues($values)
    {
        $values = self::normalizeFormValues($values);
        $errors = array();

        if ($values['titre'] === '') {
            $errors[] = 'Le titre du formulaire est obligatoire.';
        }

        if (!isset(self::statusOptions()[$values['statut']])) {
            $errors[] = 'Le statut du formulaire est invalide.';
        }

        return $errors;
    }

    public static function createForm($values, $createdBy)
    {
        self::ensureTables();

        $values = self::normalizeFormValues($values);
        if (count(self::validateFormValues($values)) > 0) {
            return 0;
        }

        $now = time();
        $createdBy = self::limit($createdBy, 190);
        $publishedAt = $values['statut'] === 'publie' ? $now : null;
        $archivedAt = $values['statut'] === 'archive' ? $now : null;

        $insert = grr_sql_command(
            "INSERT INTO ".self::table(self::TABLE_FORM)."
            (titre, description, statut, created_by, created_at, updated_at, published_at, archived_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            "ssssiiii",
            array(
                $values['titre'],
                $values['description'],
                $values['statut'],
                $createdBy,
                $now,
                $now,
                $publishedAt,
                $archivedAt,
            )
        );
        if ($insert === false || $insert < 0) {
            return 0;
        }

        $formId = (int) grr_sql_insert_id();
        self::recordHistory($formId, 0, $createdBy, 'creation_formulaire', $values['titre']);

        return $formId;
    }

    public static function updateForm($id, $values, $updatedBy)
    {
        self::ensureTables();

        $id = (int) $id;
        if ($id <= 0) {
            return false;
        }

        $current = self::form($id);
        if (!$current) {
            return false;
        }

        $values = self::normalizeFormValues($values);
        if (count(self::validateFormValues($values)) > 0) {
            return false;
        }

        $now = time();
        $publishedAt = null;
        if ($values['statut'] === 'publie') {
            $publishedAt = isset($current['published_at']) && (int) $current['published_at'] > 0
                ? (int) $current['published_at']
                : $now;
        }

        $archivedAt = null;
        if ($values['statut'] === 'archive') {
            $archivedAt = isset($current['archived_at']) && (int) $current['archived_at'] > 0
                ? (int) $current['archived_at']
                : $now;
        }

        $update = grr_sql_command(
            "UPDATE ".self::table(self::TABLE_FORM)."
            SET titre = ?, description = ?, statut = ?, updated_at = ?, published_at = ?, archived_at = ?
            WHERE id = ?",
            "sssiiii",
            array(
                $values['titre'],
                $values['description'],
                $values['statut'],
                $now,
                $publishedAt,
                $archivedAt,
                $id,
            )
        );
        if ($update === false || $update < 0) {
            return false;
        }

        self::recordHistory($id, 0, $updatedBy, 'modification_formulaire', $values['titre']);

        return true;
    }

    public static function form($id)
    {
        self::ensureTables();

        $id = (int) $id;
        if ($id <= 0) {
            return array();
        }

        $rows = self::rows(
            "SELECT f.id, f.titre, f.description, f.statut, f.created_by, f.created_at, f.updated_at,
                f.published_at, f.archived_at,
                (SELECT COUNT(*) FROM ".self::table(self::TABLE_FIELD)." c WHERE c.formulaire_id = f.id AND c.actif = 1) AS field_count,
                (SELECT COUNT(*) FROM ".self::table(self::TABLE_RESPONSE)." r WHERE r.formulaire_id = f.id) AS response_count
            FROM ".self::table(self::TABLE_FORM)." f
            WHERE f.id = ?",
            "i",
            array($id)
        );

        return isset($rows[0]) ? $rows[0] : array();
    }

    public static function forms($includeArchived = true)
    {
        self::ensureTables();

        $where = $includeArchived ? '' : " WHERE f.statut <> 'archive'";

        return self::rows(
            "SELECT f.id, f.titre, f.description, f.statut, f.created_by, f.created_at, f.updated_at,
                f.published_at, f.archived_at,
                (SELECT COUNT(*) FROM ".self::table(self::TABLE_FIELD)." c WHERE c.formulaire_id = f.id AND c.actif = 1) AS field_count,
                (SELECT COUNT(*) FROM ".self::table(self::TABLE_RESPONSE)." r WHERE r.formulaire_id = f.id) AS response_count
            FROM ".self::table(self::TABLE_FORM)." f".$where."
            ORDER BY f.updated_at DESC, f.id DESC"
        );
    }

    public static function statusLabel($status)
    {
        $status = self::normalizeStatus($status);
        $options = self::statusOptions();

        return isset($options[$status]) ? $options[$status] : $status;
    }

    public static function fieldTypeOptions()
    {
        return array(
            'text' => 'Texte court',
            'textarea' => 'Texte long',
            'email' => 'Adresse e-mail',
            'number' => 'Nombre',
            'date' => 'Date',
            'select' => 'Liste deroulante',
            'radio' => 'Choix unique',
            'checkboxes' => 'Choix multiples',
            'separator' => 'Separateur',
        );
    }

    public static function fieldTypeLabel($type)
    {
        $type = self::normalizeFieldType($type);
        $options = self::fieldTypeOptions();

        return isset($options[$type]) ? $options[$type] : $type;
    }

    public static function fieldNeedsOptions($type)
    {
        return in_array(self::normalizeFieldType($type), array('select', 'radio', 'checkboxes'), true);
    }

    public static function normalizeFieldValues($values)
    {
        if (!is_array($values)) {
            $values = array();
        }

        $type = self::normalizeFieldType(isset($values['type_champ']) ? $values['type_champ'] : 'text');
        $label = self::limit(isset($values['libelle']) ? $values['libelle'] : '', 190);
        if ($type === 'separator' && $label === '') {
            $label = 'Separateur';
        }

        return array(
            'id' => (int) (isset($values['id']) ? $values['id'] : (isset($values['champ_id']) ? $values['champ_id'] : 0)),
            'formulaire_id' => (int) (isset($values['formulaire_id']) ? $values['formulaire_id'] : (isset($values['form_id']) ? $values['form_id'] : 0)),
            'type_champ' => $type,
            'libelle' => $label,
            'aide' => trim((string) (isset($values['aide']) ? $values['aide'] : '')),
            'options' => self::normalizeOptionsText(isset($values['options']) ? $values['options'] : ''),
            'valeur_defaut' => trim((string) (isset($values['valeur_defaut']) ? $values['valeur_defaut'] : '')),
            'obligatoire' => self::flag(isset($values['obligatoire']) ? $values['obligatoire'] : 0),
            'ordre' => max(0, (int) (isset($values['ordre']) ? $values['ordre'] : 0)),
            'actif' => self::flag(isset($values['actif']) ? $values['actif'] : 1),
        );
    }

    public static function validateFieldValues($values)
    {
        $values = self::normalizeFieldValues($values);
        $errors = array();

        if ($values['formulaire_id'] <= 0 || !self::form($values['formulaire_id'])) {
            $errors[] = 'Le formulaire est introuvable.';
        }

        if ($values['libelle'] === '') {
            $errors[] = 'Le libelle du champ est obligatoire.';
        }

        if (!isset(self::fieldTypeOptions()[$values['type_champ']])) {
            $errors[] = 'Le type de champ est invalide.';
        }

        if (self::fieldNeedsOptions($values['type_champ']) && self::normalizeOptionsArray($values['options']) === array()) {
            $errors[] = 'Ce type de champ necessite au moins une option.';
        }

        return $errors;
    }

    public static function createField($formId, $values, $createdBy)
    {
        self::ensureTables();

        $values = self::normalizeFieldValues(array_merge((array) $values, array('formulaire_id' => (int) $formId)));
        if (count(self::validateFieldValues($values)) > 0) {
            return 0;
        }

        if ($values['ordre'] <= 0) {
            $values['ordre'] = self::nextFieldOrder($formId);
        }

        $insert = grr_sql_command(
            "INSERT INTO ".self::table(self::TABLE_FIELD)."
            (formulaire_id, type_champ, libelle, aide, options, valeur_defaut, obligatoire, ordre, actif)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            "isssssiii",
            array(
                (int) $formId,
                $values['type_champ'],
                $values['libelle'],
                $values['aide'],
                $values['options'],
                $values['valeur_defaut'],
                (int) $values['obligatoire'],
                (int) $values['ordre'],
                (int) $values['actif'],
            )
        );
        if ($insert === false || $insert < 0) {
            return 0;
        }

        $fieldId = (int) grr_sql_insert_id();
        self::touchForm($formId);
        self::recordHistory($formId, 0, $createdBy, 'creation_champ', $values['libelle']);

        return $fieldId;
    }

    public static function updateField($fieldId, $values, $updatedBy)
    {
        self::ensureTables();

        $fieldId = (int) $fieldId;
        $current = self::field($fieldId);
        if (!$current) {
            return false;
        }

        $values = self::normalizeFieldValues(array_merge((array) $values, array(
            'id' => $fieldId,
            'formulaire_id' => (int) $current['formulaire_id'],
        )));
        if (count(self::validateFieldValues($values)) > 0) {
            return false;
        }

        $update = grr_sql_command(
            "UPDATE ".self::table(self::TABLE_FIELD)."
            SET type_champ = ?, libelle = ?, aide = ?, options = ?, valeur_defaut = ?, obligatoire = ?, ordre = ?, actif = ?
            WHERE id = ?",
            "sssssiiii",
            array(
                $values['type_champ'],
                $values['libelle'],
                $values['aide'],
                $values['options'],
                $values['valeur_defaut'],
                (int) $values['obligatoire'],
                (int) $values['ordre'],
                (int) $values['actif'],
                $fieldId,
            )
        );
        if ($update === false || $update < 0) {
            return false;
        }

        self::touchForm((int) $current['formulaire_id']);
        self::recordHistory((int) $current['formulaire_id'], 0, $updatedBy, 'modification_champ', $values['libelle']);

        return true;
    }

    public static function disableField($fieldId, $updatedBy)
    {
        self::ensureTables();

        $field = self::field($fieldId);
        if (!$field) {
            return false;
        }

        $update = grr_sql_command(
            "UPDATE ".self::table(self::TABLE_FIELD)." SET actif = 0 WHERE id = ?",
            "i",
            array((int) $fieldId)
        );
        if ($update === false || $update < 0) {
            return false;
        }

        self::touchForm((int) $field['formulaire_id']);
        self::recordHistory((int) $field['formulaire_id'], 0, $updatedBy, 'desactivation_champ', isset($field['libelle']) ? $field['libelle'] : '');

        return true;
    }

    public static function field($fieldId)
    {
        self::ensureTables();

        $fieldId = (int) $fieldId;
        if ($fieldId <= 0) {
            return array();
        }

        $rows = self::rows(
            "SELECT id, formulaire_id, type_champ, libelle, aide, options, valeur_defaut, obligatoire, ordre, actif
            FROM ".self::table(self::TABLE_FIELD)."
            WHERE id = ?",
            "i",
            array($fieldId)
        );

        return isset($rows[0]) ? $rows[0] : array();
    }

    public static function fields($formId, $includeInactive = false)
    {
        self::ensureTables();

        $formId = (int) $formId;
        if ($formId <= 0) {
            return array();
        }

        $whereActive = $includeInactive ? '' : ' AND actif = 1';

        return self::rows(
            "SELECT id, formulaire_id, type_champ, libelle, aide, options, valeur_defaut, obligatoire, ordre, actif
            FROM ".self::table(self::TABLE_FIELD)."
            WHERE formulaire_id = ?".$whereActive."
            ORDER BY ordre, id",
            "i",
            array($formId)
        );
    }

    public static function fieldOptionsArray($field)
    {
        return self::normalizeOptionsArray(isset($field['options']) ? $field['options'] : '');
    }

    public static function normalizeResponseValues($fields, $source)
    {
        if (!is_array($fields)) {
            $fields = array();
        }
        if (!is_array($source)) {
            $source = array();
        }

        $values = array();
        foreach ($fields as $field) {
            $type = isset($field['type_champ']) ? (string) $field['type_champ'] : 'text';
            if ($type === 'separator') {
                continue;
            }

            $fieldId = (int) (isset($field['id']) ? $field['id'] : 0);
            if ($fieldId <= 0) {
                continue;
            }

            $name = 'field_'.$fieldId;
            if ($type === 'checkboxes') {
                $posted = isset($source[$name]) ? $source[$name] : array();
                if (!is_array($posted)) {
                    $posted = array($posted);
                }

                $clean = array();
                foreach ($posted as $value) {
                    $value = self::normalizeResponseScalar($value);
                    if ($value !== '') {
                        $clean[$value] = $value;
                    }
                }

                $values[$fieldId] = array_values($clean);
                continue;
            }

            $values[$fieldId] = self::normalizeResponseScalar(isset($source[$name]) ? $source[$name] : '');
        }

        return $values;
    }

    public static function validateResponseValues($fields, $values)
    {
        if (!is_array($fields)) {
            $fields = array();
        }
        if (!is_array($values)) {
            $values = array();
        }

        $errors = array();
        foreach ($fields as $field) {
            $type = isset($field['type_champ']) ? (string) $field['type_champ'] : 'text';
            if ($type === 'separator') {
                continue;
            }

            $fieldId = (int) (isset($field['id']) ? $field['id'] : 0);
            if ($fieldId <= 0) {
                continue;
            }

            $label = isset($field['libelle']) && trim((string) $field['libelle']) !== ''
                ? trim((string) $field['libelle'])
                : 'Champ '.$fieldId;
            $required = isset($field['obligatoire']) && (int) $field['obligatoire'] === 1;
            $value = isset($values[$fieldId]) ? $values[$fieldId] : ($type === 'checkboxes' ? array() : '');
            $fieldErrors = array();

            if ($type === 'checkboxes') {
                $choices = is_array($value) ? $value : array();
                if ($required && count($choices) === 0) {
                    $fieldErrors[] = 'Le champ "'.$label.'" est obligatoire.';
                }

                $allowed = self::fieldOptionsArray($field);
                foreach ($choices as $choice) {
                    if (!in_array($choice, $allowed, true)) {
                        $fieldErrors[] = 'Le champ "'.$label.'" contient un choix invalide.';
                        break;
                    }
                }
            } else {
                $value = (string) $value;
                if ($required && $value === '') {
                    $fieldErrors[] = 'Le champ "'.$label.'" est obligatoire.';
                }

                if ($value !== '') {
                    if ($type === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $fieldErrors[] = 'Le champ "'.$label.'" doit contenir une adresse email valide.';
                    } elseif ($type === 'number' && !is_numeric($value)) {
                        $fieldErrors[] = 'Le champ "'.$label.'" doit contenir un nombre valide.';
                    } elseif ($type === 'date' && !self::validDateValue($value)) {
                        $fieldErrors[] = 'Le champ "'.$label.'" doit contenir une date valide.';
                    } elseif (in_array($type, array('select', 'radio'), true)
                        && !in_array($value, self::fieldOptionsArray($field), true)) {
                        $fieldErrors[] = 'Le champ "'.$label.'" contient un choix invalide.';
                    }
                }
            }

            if (count($fieldErrors) > 0) {
                $errors[$fieldId] = $fieldErrors;
            }
        }

        return $errors;
    }

    public static function createResponse($formId, $fields, $values, $meta)
    {
        self::ensureTables();

        $formId = (int) $formId;
        if ($formId <= 0 || !self::form($formId)) {
            return 0;
        }

        if (!is_array($fields)) {
            $fields = array();
        }
        $values = is_array($values) ? $values : array();
        if (count(self::validateResponseValues($fields, $values)) > 0) {
            return 0;
        }

        $meta = self::normalizeResponseMeta($meta);
        $insert = grr_sql_command(
            "INSERT INTO ".self::table(self::TABLE_RESPONSE)."
            (formulaire_id, submitter_login, submitter_name, submitter_email, source, ip_hash, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?)",
            "isssssi",
            array(
                $formId,
                $meta['submitter_login'],
                $meta['submitter_name'],
                $meta['submitter_email'],
                $meta['source'],
                $meta['ip_hash'],
                time(),
            )
        );
        if ($insert === false || $insert < 0) {
            return 0;
        }

        $responseId = (int) grr_sql_insert_id();
        if ($responseId <= 0) {
            return 0;
        }

        foreach ($fields as $field) {
            $type = isset($field['type_champ']) ? (string) $field['type_champ'] : 'text';
            if ($type === 'separator') {
                continue;
            }

            $fieldId = (int) (isset($field['id']) ? $field['id'] : 0);
            if ($fieldId <= 0) {
                continue;
            }

            $value = self::responseValueForStorage($type, isset($values[$fieldId]) ? $values[$fieldId] : '');
            $valueInsert = grr_sql_command(
                "INSERT INTO ".self::table(self::TABLE_VALUE)."
                (reponse_id, champ_id, valeur)
                VALUES (?, ?, ?)",
                "iis",
                array($responseId, $fieldId, $value)
            );

            if ($valueInsert === false || $valueInsert < 0) {
                self::deleteResponse($responseId);
                return 0;
            }
        }

        self::recordHistory($formId, $responseId, $meta['submitter_login'], 'creation_reponse', 'Reponse enregistree');

        return $responseId;
    }

    public static function createToken($formId, $type, $createdBy)
    {
        self::ensureTables();

        $formId = (int) $formId;
        $type = self::normalizeTokenType($type);
        if ($formId <= 0 || !self::form($formId)) {
            return '';
        }

        for ($i = 0; $i < 5; $i++) {
            $token = self::randomToken();
            $hash = self::tokenHash($token);
            $insert = grr_sql_command(
                "INSERT INTO ".self::table(self::TABLE_TOKEN)."
                (formulaire_id, type_token, token_hash, actif, created_at)
                VALUES (?, ?, ?, 1, ?)",
                "issi",
                array($formId, $type, $hash, time())
            );

            if ($insert !== false && $insert >= 0) {
                self::recordHistory($formId, 0, $createdBy, 'creation_jeton_'.$type, 'Jeton cree');
                return $token;
            }
        }

        return '';
    }

    public static function activeTokenCount($formId, $type)
    {
        self::ensureTables();

        return (int) grr_sql_query1(
            "SELECT COUNT(*) FROM ".self::table(self::TABLE_TOKEN)."
            WHERE formulaire_id = ? AND type_token = ? AND actif = 1",
            "is",
            array((int) $formId, self::normalizeTokenType($type))
        );
    }

    public static function tokens($formId, $includeInactive = true)
    {
        self::ensureTables();

        $formId = (int) $formId;
        if ($formId <= 0) {
            return array();
        }

        $whereActive = $includeInactive ? '' : ' AND actif = 1';

        return self::rows(
            "SELECT id, formulaire_id, type_token, token_hash, actif, created_at
            FROM ".self::table(self::TABLE_TOKEN)."
            WHERE formulaire_id = ?".$whereActive."
            ORDER BY created_at DESC, id DESC",
            "i",
            array($formId)
        );
    }

    public static function disableToken($tokenId, $updatedBy)
    {
        self::ensureTables();

        $tokenId = (int) $tokenId;
        if ($tokenId <= 0) {
            return false;
        }

        $rows = self::rows(
            "SELECT id, formulaire_id, type_token, actif
            FROM ".self::table(self::TABLE_TOKEN)."
            WHERE id = ?",
            "i",
            array($tokenId)
        );
        if (!isset($rows[0])) {
            return false;
        }

        $update = grr_sql_command(
            "UPDATE ".self::table(self::TABLE_TOKEN)." SET actif = 0 WHERE id = ?",
            "i",
            array($tokenId)
        );
        if ($update === false || $update < 0) {
            return false;
        }

        self::recordHistory(
            (int) $rows[0]['formulaire_id'],
            0,
            $updatedBy,
            'desactivation_jeton',
            isset($rows[0]['type_token']) ? $rows[0]['type_token'] : ''
        );

        return true;
    }

    public static function formByToken($token, $type)
    {
        self::ensureTables();

        $token = trim((string) $token);
        if ($token === '') {
            return array();
        }

        $rows = self::rows(
            "SELECT f.id, f.titre, f.description, f.statut, f.created_by, f.created_at, f.updated_at,
                f.published_at, f.archived_at, t.id AS token_id,
                (SELECT COUNT(*) FROM ".self::table(self::TABLE_FIELD)." c WHERE c.formulaire_id = f.id AND c.actif = 1) AS field_count,
                (SELECT COUNT(*) FROM ".self::table(self::TABLE_RESPONSE)." r WHERE r.formulaire_id = f.id) AS response_count
            FROM ".self::table(self::TABLE_TOKEN)." t
            INNER JOIN ".self::table(self::TABLE_FORM)." f ON f.id = t.formulaire_id
            WHERE t.type_token = ? AND t.token_hash = ? AND t.actif = 1",
            "ss",
            array(self::normalizeTokenType($type), self::tokenHash($token))
        );

        return isset($rows[0]) ? $rows[0] : array();
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

    private static function rows($sql, $types = null, $params = null)
    {
        $result = grr_sql_query($sql, $types, $params);
        if (!$result) {
            return array();
        }

        $rows = array();
        for ($i = 0; ($row = grr_sql_row_keyed($result, $i)); $i++) {
            $rows[] = $row;
        }

        return $rows;
    }

    private static function normalizeStatus($status)
    {
        $status = strtolower(trim((string) $status));
        $options = self::statusOptions();

        return isset($options[$status]) ? $status : 'brouillon';
    }

    private static function normalizeFieldType($type)
    {
        $type = strtolower(trim((string) $type));
        $options = self::fieldTypeOptions();

        return isset($options[$type]) ? $type : 'text';
    }

    private static function normalizeOptionsText($options)
    {
        $clean = self::normalizeOptionsArray($options);

        return implode("\n", $clean);
    }

    private static function normalizeOptionsArray($options)
    {
        $lines = preg_split('/[\r\n]+/', (string) $options);
        $clean = array();

        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line !== '' && strlen($line) <= 190) {
                $clean[$line] = $line;
            }
        }

        return array_values($clean);
    }

    private static function nextFieldOrder($formId)
    {
        $max = grr_sql_query1(
            "SELECT COALESCE(MAX(ordre), 0) FROM ".self::table(self::TABLE_FIELD)." WHERE formulaire_id = ?",
            "i",
            array((int) $formId)
        );

        return ((int) $max) + 10;
    }

    private static function touchForm($formId)
    {
        grr_sql_command(
            "UPDATE ".self::table(self::TABLE_FORM)." SET updated_at = ? WHERE id = ?",
            "ii",
            array(time(), (int) $formId)
        );
    }

    private static function flag($value)
    {
        return (string) $value === '1' || $value === 1 || $value === true ? 1 : 0;
    }

    private static function normalizeTokenType($type)
    {
        $type = strtolower(trim((string) $type));
        return in_array($type, array('formulaire', 'resultats'), true) ? $type : 'formulaire';
    }

    private static function responseFilterQuery($formId, $filters)
    {
        $clauses = array('r.formulaire_id = ?');
        $types = 'i';
        $params = array((int) $formId);

        if (isset($filters['date_from']) && (int) $filters['date_from'] > 0) {
            $clauses[] = 'r.created_at >= ?';
            $types .= 'i';
            $params[] = (int) $filters['date_from'];
        }
        if (isset($filters['date_to']) && (int) $filters['date_to'] > 0) {
            $clauses[] = 'r.created_at <= ?';
            $types .= 'i';
            $params[] = (int) $filters['date_to'];
        }
        if (isset($filters['source']) && $filters['source'] !== '') {
            $clauses[] = 'r.source = ?';
            $types .= 's';
            $params[] = (string) $filters['source'];
        }
        if (isset($filters['q']) && $filters['q'] !== '') {
            $like = '%'.$filters['q'].'%';
            $clauses[] = "(r.submitter_login LIKE ? OR r.submitter_name LIKE ? OR r.submitter_email LIKE ?
                OR EXISTS (
                    SELECT 1 FROM ".self::table(self::TABLE_VALUE)." v
                    WHERE v.reponse_id = r.id AND v.valeur LIKE ?
                ))";
            $types .= 'ssss';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        return array(
            'where' => implode(' AND ', $clauses),
            'types' => $types,
            'params' => $params,
        );
    }

    private static function normalizeDateFilter($value, $endOfDay)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return 0;
        }

        if (!preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/', $value, $matches)) {
            return 0;
        }
        if (!checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1])) {
            return 0;
        }

        $hour = $endOfDay ? 23 : 0;
        $minute = $endOfDay ? 59 : 0;
        $second = $endOfDay ? 59 : 0;

        return mktime($hour, $minute, $second, (int) $matches[2], (int) $matches[3], (int) $matches[1]);
    }

    private static function normalizeResponseScalar($value)
    {
        if (is_array($value)) {
            $value = '';
        }

        $value = str_replace(array("\r\n", "\r"), "\n", (string) $value);
        return substr(trim($value), 0, 65535);
    }

    private static function normalizeResponseMeta($meta)
    {
        if (!is_array($meta)) {
            $meta = array();
        }

        $login = self::limit(isset($meta['submitter_login']) ? $meta['submitter_login'] : '', 190);
        $name = self::limit(isset($meta['submitter_name']) ? $meta['submitter_name'] : '', 190);
        $email = self::limit(isset($meta['submitter_email']) ? $meta['submitter_email'] : '', 190);

        if ($login !== '' && ($name === '' || $email === '')) {
            $user = self::userByLogin($login);
            if ($user) {
                if ($name === '') {
                    $nameParts = array();
                    if (isset($user['prenom']) && trim((string) $user['prenom']) !== '') {
                        $nameParts[] = trim((string) $user['prenom']);
                    }
                    if (isset($user['nom']) && trim((string) $user['nom']) !== '') {
                        $nameParts[] = trim((string) $user['nom']);
                    }
                    $name = self::limit(implode(' ', $nameParts), 190);
                }
                if ($email === '' && isset($user['email'])) {
                    $email = self::limit($user['email'], 190);
                }
            }
        }

        return array(
            'submitter_login' => $login,
            'submitter_name' => $name,
            'submitter_email' => $email,
            'source' => self::limit(isset($meta['source']) ? $meta['source'] : 'grr', 30),
            'ip_hash' => self::limit(isset($meta['ip_hash']) ? $meta['ip_hash'] : '', 64),
        );
    }

    private static function responseValueForStorage($type, $value)
    {
        if ($type === 'checkboxes') {
            if (!is_array($value)) {
                return '';
            }

            $clean = array();
            foreach ($value as $item) {
                $item = self::normalizeResponseScalar($item);
                if ($item !== '') {
                    $clean[] = $item;
                }
            }

            return implode("\n", $clean);
        }

        return self::normalizeResponseScalar($value);
    }

    private static function validDateValue($value)
    {
        if (!preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/', (string) $value, $matches)) {
            return false;
        }

        return checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1]);
    }

    private static function validEmail($email)
    {
        $email = trim((string) $email);
        if ($email === '') {
            return false;
        }

        if (class_exists('SecuChaine') && method_exists('SecuChaine', 'ValideMail')) {
            return SecuChaine::ValideMail($email);
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    private static function activeNotificationEmailExists($formId, $email, $excludeId = 0)
    {
        $formId = (int) $formId;
        $email = self::limit($email, 190);
        $excludeId = (int) $excludeId;
        if ($formId <= 0 || $email === '') {
            return false;
        }

        $sql = "SELECT COUNT(*) FROM ".self::table(self::TABLE_NOTIFICATION)."
            WHERE formulaire_id = ? AND email = ? AND actif = 1";
        $types = "is";
        $params = array($formId, $email);
        if ($excludeId > 0) {
            $sql .= " AND id <> ?";
            $types .= "i";
            $params[] = $excludeId;
        }

        return (int) grr_sql_query1($sql, $types, $params) > 0;
    }

    private static function deleteResponse($responseId)
    {
        $responseId = (int) $responseId;
        if ($responseId <= 0) {
            return;
        }

        grr_sql_command(
            "DELETE FROM ".self::table(self::TABLE_VALUE)." WHERE reponse_id = ?",
            "i",
            array($responseId)
        );
        grr_sql_command(
            "DELETE FROM ".self::table(self::TABLE_RESPONSE)." WHERE id = ?",
            "i",
            array($responseId)
        );
    }

    private static function tokenHash($token)
    {
        return hash('sha256', (string) $token);
    }

    private static function randomToken()
    {
        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes(24));
        }

        return hash('sha256', uniqid((string) mt_rand(), true));
    }

    private static function recordHistory($formId, $responseId, $author, $action, $details)
    {
        $formId = (int) $formId;
        if ($formId <= 0) {
            return;
        }

        grr_sql_command(
            "INSERT INTO ".self::table(self::TABLE_HISTORY)."
            (formulaire_id, reponse_id, auteur, action, details, created_at)
            VALUES (?, ?, ?, ?, ?, ?)",
            "iisssi",
            array(
                $formId,
                (int) $responseId,
                self::limit($author, 190),
                self::limit($action, 60),
                (string) $details,
                time(),
            )
        );
    }

    private static function limit($value, $length)
    {
        return substr(trim((string) $value), 0, (int) $length);
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
