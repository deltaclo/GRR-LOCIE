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
            `form_columns` tinyint(1) NOT NULL DEFAULT 1,
            `allow_user_edit` tinyint(1) NOT NULL DEFAULT 0,
            `confirmation_email_enabled` tinyint(1) NOT NULL DEFAULT 0,
            `result_list_template` text NULL,
            `result_detail_template` text NULL,
            `result_columns` text NULL,
            `notification_subject_template` text NULL,
            `notification_body_template` text NULL,
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
            `page_titre` varchar(190) NOT NULL DEFAULT '',
            `visibility_champ_id` int(11) NOT NULL DEFAULT 0,
            `visibility_operateur` varchar(30) NOT NULL DEFAULT '',
            `visibility_valeur` text NULL,
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
            `token_id` int(11) NOT NULL DEFAULT 0,
            `submitter_login` varchar(190) NOT NULL DEFAULT '',
            `submitter_name` varchar(190) NOT NULL DEFAULT '',
            `submitter_email` varchar(190) NOT NULL DEFAULT '',
            `source` varchar(30) NOT NULL DEFAULT 'grr',
            `ip_hash` varchar(64) NOT NULL DEFAULT '',
            `created_at` int(11) NOT NULL DEFAULT 0,
            `updated_at` int(11) NOT NULL DEFAULT 0,
            `updated_by` varchar(190) NOT NULL DEFAULT '',
            PRIMARY KEY (`id`),
            KEY `formulaire_id` (`formulaire_id`),
            KEY `token_id` (`token_id`),
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
            `condition_champ_id` int(11) NOT NULL DEFAULT 0,
            `condition_operateur` varchar(30) NOT NULL DEFAULT '',
            `condition_valeur` text NULL,
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
            `token_public` varchar(120) NOT NULL DEFAULT '',
            `expires_at` int(11) NOT NULL DEFAULT 0,
            `max_responses` int(11) NOT NULL DEFAULT 0,
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

        self::ensureColumns();
    }

    private static function ensureColumns()
    {
        self::ensureColumn(self::TABLE_FORM, 'result_list_template', 'text NULL');
        self::ensureColumn(self::TABLE_FORM, 'result_detail_template', 'text NULL');
        self::ensureColumn(self::TABLE_FORM, 'result_columns', 'text NULL');
        self::ensureColumn(self::TABLE_FORM, 'form_columns', 'tinyint(1) NOT NULL DEFAULT 1');
        self::ensureColumn(self::TABLE_FORM, 'allow_user_edit', 'tinyint(1) NOT NULL DEFAULT 0');
        self::ensureColumn(self::TABLE_FORM, 'confirmation_email_enabled', 'tinyint(1) NOT NULL DEFAULT 0');
        self::ensureColumn(self::TABLE_FORM, 'notification_subject_template', 'text NULL');
        self::ensureColumn(self::TABLE_FORM, 'notification_body_template', 'text NULL');
        self::ensureColumn(self::TABLE_FIELD, 'page_titre', "varchar(190) NOT NULL DEFAULT ''");
        self::ensureColumn(self::TABLE_FIELD, 'visibility_champ_id', 'int(11) NOT NULL DEFAULT 0');
        self::ensureColumn(self::TABLE_FIELD, 'visibility_operateur', "varchar(30) NOT NULL DEFAULT ''");
        self::ensureColumn(self::TABLE_FIELD, 'visibility_valeur', 'text NULL');
        self::ensureColumn(self::TABLE_RESPONSE, 'token_id', 'int(11) NOT NULL DEFAULT 0');
        self::ensureColumn(self::TABLE_RESPONSE, 'updated_at', 'int(11) NOT NULL DEFAULT 0');
        self::ensureColumn(self::TABLE_RESPONSE, 'updated_by', "varchar(190) NOT NULL DEFAULT ''");
        self::ensureColumn(self::TABLE_NOTIFICATION, 'condition_champ_id', 'int(11) NOT NULL DEFAULT 0');
        self::ensureColumn(self::TABLE_NOTIFICATION, 'condition_operateur', "varchar(30) NOT NULL DEFAULT ''");
        self::ensureColumn(self::TABLE_NOTIFICATION, 'condition_valeur', 'text NULL');
        self::ensureColumn(self::TABLE_TOKEN, 'token_public', "varchar(120) NOT NULL DEFAULT ''");
        self::ensureColumn(self::TABLE_TOKEN, 'expires_at', 'int(11) NOT NULL DEFAULT 0');
        self::ensureColumn(self::TABLE_TOKEN, 'max_responses', 'int(11) NOT NULL DEFAULT 0');
    }

    private static function ensureColumn($suffix, $column, $definition)
    {
        if (self::columnExists($suffix, $column)) {
            return;
        }

        grr_sql_command(
            "ALTER TABLE `".self::table($suffix)."` ADD `".str_replace('`', '', (string) $column)."` ".$definition
        );
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
            "SELECT id, formulaire_id, token_id, submitter_login, submitter_name, submitter_email, source, ip_hash, created_at, updated_at, updated_by
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
            "SELECT id, formulaire_id, token_id, submitter_login, submitter_name, submitter_email, source, ip_hash, created_at, updated_at, updated_by
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

    public static function responseStats($formId)
    {
        self::ensureTables();

        $formId = (int) $formId;
        $stats = array(
            'total' => self::countFilteredResponses($formId, array()),
            'fields' => array(),
        );
        if ($formId <= 0) {
            return $stats;
        }

        $fields = self::fields($formId, false);
        $responses = self::allResponsesWithValues($formId, array());
        foreach ($fields as $field) {
            $type = isset($field['type_champ']) ? (string) $field['type_champ'] : '';
            if (!in_array($type, array('select', 'radio', 'checkboxes'), true)) {
                continue;
            }

            $fieldId = (int) (isset($field['id']) ? $field['id'] : 0);
            $counts = array();
            foreach (self::fieldOptionsArray($field) as $option) {
                $counts[$option] = 0;
            }

            foreach ($responses as $response) {
                $values = isset($response['values']) && is_array($response['values']) ? $response['values'] : array();
                $stored = isset($values[$fieldId]) ? (string) $values[$fieldId] : '';
                $parts = $type === 'checkboxes' ? self::multiValueParts($stored) : array($stored);
                foreach ($parts as $part) {
                    $part = trim((string) $part);
                    if ($part === '') {
                        continue;
                    }
                    if (!isset($counts[$part])) {
                        $counts[$part] = 0;
                    }
                    $counts[$part]++;
                }
            }

            $stats['fields'][] = array(
                'id' => $fieldId,
                'libelle' => isset($field['libelle']) ? (string) $field['libelle'] : '',
                'type_champ' => $type,
                'counts' => $counts,
            );
        }

        return $stats;
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

    public static function history($formId, $rowLimit = 50, $filters = array())
    {
        self::ensureTables();

        $formId = (int) $formId;
        if ($formId <= 0) {
            return array();
        }

        $rowLimit = max(1, min(200, (int) $rowLimit));
        $filters = is_array($filters) ? $filters : array();
        $where = "formulaire_id = ?";
        $types = "i";
        $params = array($formId);
        $action = isset($filters['action']) ? trim((string) $filters['action']) : '';
        if ($action !== '') {
            $where .= " AND action = ?";
            $types .= "s";
            $params[] = self::limit($action, 60);
        }
        $q = isset($filters['q']) ? trim((string) $filters['q']) : '';
        if ($q !== '') {
            $where .= " AND (auteur LIKE ? OR details LIKE ?)";
            $types .= "ss";
            $like = '%'.$q.'%';
            $params[] = $like;
            $params[] = $like;
        }

        return self::rows(
            "SELECT id, formulaire_id, reponse_id, auteur, action, details, created_at
            FROM ".self::table(self::TABLE_HISTORY)."
            WHERE ".$where."
            ORDER BY created_at DESC, id DESC
            LIMIT ".$rowLimit,
            $types,
            $params
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
            'condition_champ_id' => (int) (isset($values['condition_champ_id']) ? $values['condition_champ_id'] : 0),
            'condition_operateur' => self::normalizeConditionOperator(isset($values['condition_operateur']) ? $values['condition_operateur'] : ''),
            'condition_valeur' => self::limit(isset($values['condition_valeur']) ? $values['condition_valeur'] : '', 190),
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
        } elseif (self::activeNotificationEmailExists(
            $values['formulaire_id'],
            $values['email'],
            $values['id'],
            $values['condition_champ_id'],
            $values['condition_operateur'],
            $values['condition_valeur']
        )) {
            $errors[] = 'Cette adresse email est deja active pour ce formulaire.';
        }

        if ($values['condition_champ_id'] > 0) {
            $field = self::field($values['condition_champ_id']);
            if (!$field || (int) $field['formulaire_id'] !== (int) $values['formulaire_id']) {
                $errors[] = 'Le champ de condition est invalide.';
            } elseif (!in_array((string) $field['type_champ'], array('select', 'radio', 'checkboxes'), true)) {
                $errors[] = 'Les notifications conditionnelles utilisent uniquement les listes, choix uniques et cases cochees.';
            }

            if ($values['condition_operateur'] === '') {
                $errors[] = 'L operateur de condition est obligatoire.';
            }
            if (!in_array($values['condition_operateur'], array('empty', 'not_empty'), true) && $values['condition_valeur'] === '') {
                $errors[] = 'La valeur de condition est obligatoire.';
            }
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
            (formulaire_id, email, nom, condition_champ_id, condition_operateur, condition_valeur, actif, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            "ississii",
            array(
                (int) $formId,
                $values['email'],
                $values['nom'],
                (int) $values['condition_champ_id'],
                $values['condition_operateur'],
                $values['condition_valeur'],
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
            "SELECT id, formulaire_id, email, nom, condition_champ_id, condition_operateur, condition_valeur, actif, created_at
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
            "SELECT id, formulaire_id, email, nom, condition_champ_id, condition_operateur, condition_valeur, actif, created_at
            FROM ".self::table(self::TABLE_NOTIFICATION)."
            WHERE formulaire_id = ?".$whereActive."
            ORDER BY actif DESC, nom, email, id",
            "i",
            array($formId)
        );
    }

    public static function notificationConditionOperators()
    {
        return array(
            'equals' => 'Est egal a',
            'not_equals' => 'Est different de',
            'contains' => 'Contient',
            'not_contains' => 'Ne contient pas',
            'empty' => 'Est vide',
            'not_empty' => 'N est pas vide',
        );
    }

    public static function conditionalFields($formId)
    {
        $fields = self::fields($formId, false);
        $conditionalFields = array();
        foreach ($fields as $field) {
            $type = isset($field['type_champ']) ? (string) $field['type_champ'] : '';
            if (in_array($type, array('select', 'radio', 'checkboxes'), true)) {
                $conditionalFields[] = $field;
            }
        }

        return $conditionalFields;
    }

    public static function notificationMatchesValues($recipient, $values)
    {
        $fieldId = (int) (isset($recipient['condition_champ_id']) ? $recipient['condition_champ_id'] : 0);
        if ($fieldId <= 0) {
            return true;
        }

        $operator = self::normalizeConditionOperator(isset($recipient['condition_operateur']) ? $recipient['condition_operateur'] : '');
        $expected = trim((string) (isset($recipient['condition_valeur']) ? $recipient['condition_valeur'] : ''));
        $actual = isset($values[$fieldId]) ? trim((string) $values[$fieldId]) : '';

        if ($operator === 'empty') {
            return $actual === '';
        }
        if ($operator === 'not_empty') {
            return $actual !== '';
        }
        if ($operator === 'not_equals') {
            return $actual !== $expected;
        }
        if ($operator === 'contains') {
            return in_array($expected, self::multiValueParts($actual), true) || strpos($actual, $expected) !== false;
        }
        if ($operator === 'not_contains') {
            return !in_array($expected, self::multiValueParts($actual), true) && strpos($actual, $expected) === false;
        }

        return $actual === $expected;
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
            "SELECT f.id, f.titre, f.description, f.form_columns, f.allow_user_edit, f.confirmation_email_enabled, f.result_list_template, f.result_detail_template, f.result_columns,
                f.notification_subject_template, f.notification_body_template, f.statut, f.created_by, f.created_at, f.updated_at,
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

    public static function duplicateForm($formId, $createdBy)
    {
        self::ensureTables();

        $formId = (int) $formId;
        $source = self::form($formId);
        if (!$source) {
            return 0;
        }

        $values = self::normalizeFormValues($source);
        $values['titre'] = self::limit('Copie - '.$values['titre'], 190);
        $values['statut'] = 'brouillon';
        $newFormId = self::createForm($values, $createdBy);
        if ($newFormId <= 0) {
            return 0;
        }

        $fieldMap = array();
        foreach (self::fields($formId, true) as $field) {
            $oldId = (int) (isset($field['id']) ? $field['id'] : 0);
            $newField = self::normalizeFieldValues($field);
            $newField['visibility_champ_id'] = 0;
            $createdFieldId = self::createField($newFormId, $newField, $createdBy);
            if ($oldId > 0 && $createdFieldId > 0) {
                $fieldMap[$oldId] = $createdFieldId;
            }
        }
        if (trim((string) $values['result_columns']) !== '') {
            $values['result_columns'] = self::remapIdListText($values['result_columns'], $fieldMap);
            self::updateForm($newFormId, $values, $createdBy);
        }

        foreach (self::fields($formId, true) as $field) {
            $oldId = (int) (isset($field['id']) ? $field['id'] : 0);
            $visibilityId = (int) (isset($field['visibility_champ_id']) ? $field['visibility_champ_id'] : 0);
            if ($oldId <= 0 || $visibilityId <= 0 || !isset($fieldMap[$oldId]) || !isset($fieldMap[$visibilityId])) {
                continue;
            }

            $newField = self::normalizeFieldValues($field);
            $newField['visibility_champ_id'] = $fieldMap[$visibilityId];
            self::updateField($fieldMap[$oldId], $newField, $createdBy);
        }

        foreach (self::notificationRecipients($formId, true) as $recipient) {
            $values = self::normalizeNotificationValues($recipient);
            $values['id'] = 0;
            $conditionFieldId = (int) $values['condition_champ_id'];
            $values['condition_champ_id'] = isset($fieldMap[$conditionFieldId]) ? $fieldMap[$conditionFieldId] : 0;
            self::createNotificationRecipient($newFormId, $values, $createdBy);
        }

        foreach (self::formManagers($formId) as $manager) {
            $managerLogin = isset($manager['login']) ? (string) $manager['login'] : '';
            if ($managerLogin !== '') {
                self::addFormManager($newFormId, $managerLogin, $createdBy);
            }
        }

        self::recordHistory($newFormId, 0, $createdBy, 'duplication_formulaire', 'Source #'.$formId);

        return $newFormId;
    }

    public static function exportFormDefinition($formId)
    {
        self::ensureTables();

        $formId = (int) $formId;
        $form = self::form($formId);
        if (!$form) {
            return array();
        }

        return array(
            'version' => 1,
            'exported_at' => time(),
            'form' => self::normalizeFormValues($form),
            'fields' => self::fields($formId, true),
            'notifications' => self::notificationRecipients($formId, true),
            'managers' => self::formManagers($formId),
        );
    }

    public static function importFormDefinition($filePath, $createdBy)
    {
        self::ensureTables();

        if (!is_file($filePath)) {
            return array('form_id' => 0, 'errors' => array('Le fichier JSON est introuvable.'));
        }

        $payload = json_decode((string) file_get_contents($filePath), true);
        if (!is_array($payload) || !isset($payload['form']) || !is_array($payload['form'])) {
            return array('form_id' => 0, 'errors' => array('Le fichier JSON ne contient pas de formulaire valide.'));
        }

        $formValues = self::normalizeFormValues($payload['form']);
        $formValues['statut'] = 'brouillon';
        $newFormId = self::createForm($formValues, $createdBy);
        if ($newFormId <= 0) {
            return array('form_id' => 0, 'errors' => array('Le formulaire importe n a pas pu etre cree.'));
        }

        $fieldMap = array();
        $errors = array();
        foreach ((array) (isset($payload['fields']) ? $payload['fields'] : array()) as $field) {
            $oldId = (int) (isset($field['id']) ? $field['id'] : 0);
            $values = self::normalizeFieldValues($field);
            $values['visibility_champ_id'] = 0;
            $newFieldId = self::createField($newFormId, $values, $createdBy);
            if ($newFieldId > 0) {
                $fieldMap[$oldId] = $newFieldId;
            } else {
                $errors[] = 'Un champ n a pas pu etre importe.';
            }
        }
        if (trim((string) $formValues['result_columns']) !== '') {
            $formValues['result_columns'] = self::remapIdListText($formValues['result_columns'], $fieldMap);
            self::updateForm($newFormId, $formValues, $createdBy);
        }

        foreach ((array) (isset($payload['fields']) ? $payload['fields'] : array()) as $field) {
            $oldId = (int) (isset($field['id']) ? $field['id'] : 0);
            $visibilityId = (int) (isset($field['visibility_champ_id']) ? $field['visibility_champ_id'] : 0);
            if ($oldId <= 0 || $visibilityId <= 0 || !isset($fieldMap[$oldId]) || !isset($fieldMap[$visibilityId])) {
                continue;
            }

            $values = self::normalizeFieldValues($field);
            $values['visibility_champ_id'] = $fieldMap[$visibilityId];
            self::updateField($fieldMap[$oldId], $values, $createdBy);
        }

        foreach ((array) (isset($payload['notifications']) ? $payload['notifications'] : array()) as $recipient) {
            $values = self::normalizeNotificationValues($recipient);
            $values['id'] = 0;
            $conditionFieldId = (int) $values['condition_champ_id'];
            $values['condition_champ_id'] = isset($fieldMap[$conditionFieldId]) ? $fieldMap[$conditionFieldId] : 0;
            if (count(self::validateNotificationValues(array_merge($values, array('formulaire_id' => $newFormId)))) === 0) {
                self::createNotificationRecipient($newFormId, $values, $createdBy);
            }
        }

        foreach ((array) (isset($payload['managers']) ? $payload['managers'] : array()) as $manager) {
            $managerLogin = isset($manager['login']) ? (string) $manager['login'] : '';
            if ($managerLogin !== '' && self::userByLogin($managerLogin)) {
                self::addFormManager($newFormId, $managerLogin, $createdBy);
            }
        }

        self::recordHistory($newFormId, 0, $createdBy, 'import_formulaire_json', 'Import JSON');

        return array('form_id' => $newFormId, 'errors' => $errors);
    }

    public static function statusOptions()
    {
        return array(
            'brouillon' => 'Brouillon',
            'publie' => 'Publie',
            'archive' => 'Archive',
        );
    }

    public static function normalizeFormColumns($value)
    {
        $columns = (int) $value;
        if ($columns < 1) {
            return 1;
        }
        if ($columns > 4) {
            return 4;
        }

        return $columns;
    }

    public static function normalizeFormValues($values)
    {
        if (!is_array($values)) {
            $values = array();
        }

        return array(
            'titre' => self::limit(isset($values['titre']) ? $values['titre'] : '', 190),
            'description' => trim((string) (isset($values['description']) ? $values['description'] : '')),
            'form_columns' => self::normalizeFormColumns(isset($values['form_columns']) ? $values['form_columns'] : 1),
            'allow_user_edit' => !empty($values['allow_user_edit']) ? 1 : 0,
            'confirmation_email_enabled' => !empty($values['confirmation_email_enabled']) ? 1 : 0,
            'result_list_template' => trim((string) (isset($values['result_list_template']) ? $values['result_list_template'] : '')),
            'result_detail_template' => trim((string) (isset($values['result_detail_template']) ? $values['result_detail_template'] : '')),
            'result_columns' => self::normalizeIdListText(isset($values['result_columns']) ? $values['result_columns'] : ''),
            'notification_subject_template' => trim((string) (isset($values['notification_subject_template']) ? $values['notification_subject_template'] : '')),
            'notification_body_template' => trim((string) (isset($values['notification_body_template']) ? $values['notification_body_template'] : '')),
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
            (titre, description, form_columns, allow_user_edit, confirmation_email_enabled, result_list_template, result_detail_template, result_columns, notification_subject_template, notification_body_template, statut, created_by, created_at, updated_at, published_at, archived_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            "ssiiisssssssiiii",
            array(
                $values['titre'],
                $values['description'],
                $values['form_columns'],
                $values['allow_user_edit'],
                $values['confirmation_email_enabled'],
                $values['result_list_template'],
                $values['result_detail_template'],
                $values['result_columns'],
                $values['notification_subject_template'],
                $values['notification_body_template'],
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
            SET titre = ?, description = ?, form_columns = ?, allow_user_edit = ?, confirmation_email_enabled = ?, result_list_template = ?, result_detail_template = ?, result_columns = ?, notification_subject_template = ?, notification_body_template = ?, statut = ?, updated_at = ?, published_at = ?, archived_at = ?
            WHERE id = ?",
            "ssiiissssssiiii",
            array(
                $values['titre'],
                $values['description'],
                $values['form_columns'],
                $values['allow_user_edit'],
                $values['confirmation_email_enabled'],
                $values['result_list_template'],
                $values['result_detail_template'],
                $values['result_columns'],
                $values['notification_subject_template'],
                $values['notification_body_template'],
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

    public static function deleteForm($formId)
    {
        self::ensureTables();

        $formId = (int) $formId;
        if ($formId <= 0 || !self::form($formId)) {
            return false;
        }

        $valueDelete = grr_sql_command(
            "DELETE v FROM ".self::table(self::TABLE_VALUE)." v
            INNER JOIN ".self::table(self::TABLE_RESPONSE)." r ON r.id = v.reponse_id
            WHERE r.formulaire_id = ?",
            "i",
            array($formId)
        );
        if ($valueDelete === false || $valueDelete < 0) {
            return false;
        }

        foreach (array(
            self::TABLE_RESPONSE,
            self::TABLE_FIELD,
            self::TABLE_MANAGER,
            self::TABLE_NOTIFICATION,
            self::TABLE_TOKEN,
            self::TABLE_HISTORY,
        ) as $table) {
            if (!self::deleteFormRows($table, $formId)) {
                return false;
            }
        }

        $formDelete = grr_sql_command(
            "DELETE FROM ".self::table(self::TABLE_FORM)." WHERE id = ?",
            "i",
            array($formId)
        );
        if ($formDelete === false || $formDelete < 0) {
            return false;
        }

        self::deleteFormUploadDirectory($formId);

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
            "SELECT f.id, f.titre, f.description, f.form_columns, f.allow_user_edit, f.confirmation_email_enabled, f.result_list_template, f.result_detail_template, f.result_columns,
                f.notification_subject_template, f.notification_body_template, f.statut, f.created_by, f.created_at, f.updated_at,
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
            "SELECT f.id, f.titre, f.description, f.form_columns, f.allow_user_edit, f.confirmation_email_enabled, f.result_list_template, f.result_detail_template, f.result_columns,
                f.notification_subject_template, f.notification_body_template, f.statut, f.created_by, f.created_at, f.updated_at,
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
            'radio' => 'Choix unique (case a cocher)',
            'checkboxes' => 'Choix multiples',
            'file' => 'Piece jointe',
            'signature' => 'Signature electronique',
            'image' => 'Image',
            'separator' => 'Separateur',
            'empty' => 'Vide',
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
        if ($type === 'empty') {
            $label = '';
        }

        $rawOptions = isset($values['options']) ? $values['options'] : '';
        $options = self::normalizeOptionsText($rawOptions);
        if ($type === 'image') {
            $options = self::normalizeImageDisplaySize(
                isset($values['image_display_size']) ? $values['image_display_size'] : $rawOptions
            );
        } elseif ($type === 'separator' || $type === 'empty') {
            $options = (string) self::normalizeFormColumns(
                isset($values['separator_columns']) ? $values['separator_columns'] : $rawOptions
            );
        }
        $help = trim((string) (isset($values['aide']) ? $values['aide'] : ''));
        $defaultValue = trim((string) (isset($values['valeur_defaut']) ? $values['valeur_defaut'] : ''));
        $required = self::flag(isset($values['obligatoire']) ? $values['obligatoire'] : 0);
        if ($type === 'signature') {
            $options = '';
            $defaultValue = '';
        }
        if ($type === 'empty') {
            $help = '';
            $defaultValue = '';
            $required = 0;
        }

        return array(
            'id' => (int) (isset($values['id']) ? $values['id'] : (isset($values['champ_id']) ? $values['champ_id'] : 0)),
            'formulaire_id' => (int) (isset($values['formulaire_id']) ? $values['formulaire_id'] : (isset($values['form_id']) ? $values['form_id'] : 0)),
            'type_champ' => $type,
            'libelle' => $label,
            'aide' => $help,
            'options' => $options,
            'valeur_defaut' => $defaultValue,
            'page_titre' => self::limit(isset($values['page_titre']) ? $values['page_titre'] : '', 190),
            'visibility_champ_id' => (int) (isset($values['visibility_champ_id']) ? $values['visibility_champ_id'] : 0),
            'visibility_operateur' => self::normalizeConditionOperator(isset($values['visibility_operateur']) ? $values['visibility_operateur'] : ''),
            'visibility_valeur' => self::limit(isset($values['visibility_valeur']) ? $values['visibility_valeur'] : '', 190),
            'obligatoire' => $required,
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

        if ($values['libelle'] === '' && !in_array($values['type_champ'], array('separator', 'empty'), true)) {
            $errors[] = 'Le libelle du champ est obligatoire.';
        }

        if (!isset(self::fieldTypeOptions()[$values['type_champ']])) {
            $errors[] = 'Le type de champ est invalide.';
        }

        if (self::fieldNeedsOptions($values['type_champ']) && self::normalizeOptionsArray($values['options']) === array()) {
            $errors[] = 'Ce type de champ necessite au moins une option.';
        }
        if ($values['type_champ'] === 'image') {
            if ($values['valeur_defaut'] === '') {
                $errors[] = 'Le champ image necessite une URL dans la valeur par defaut.';
            } elseif (!self::validImageSource($values['valeur_defaut'])) {
                $errors[] = 'L URL de l image est invalide.';
            }
        }
        if ($values['visibility_champ_id'] > 0) {
            $conditionField = self::field($values['visibility_champ_id']);
            if (!$conditionField || (int) $conditionField['formulaire_id'] !== (int) $values['formulaire_id']) {
                $errors[] = 'Le champ de condition d affichage est invalide.';
            } elseif ((int) (isset($values['id']) ? $values['id'] : 0) > 0
                && (int) $values['visibility_champ_id'] === (int) $values['id']) {
                $errors[] = 'Un champ ne peut pas dependre de lui-meme.';
            }
            if ($values['visibility_operateur'] === '') {
                $errors[] = 'L operateur de condition d affichage est obligatoire.';
            }
            if (!in_array($values['visibility_operateur'], array('empty', 'not_empty'), true) && $values['visibility_valeur'] === '') {
                $errors[] = 'La valeur de condition d affichage est obligatoire.';
            }
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
            (formulaire_id, type_champ, libelle, aide, options, valeur_defaut, page_titre, visibility_champ_id, visibility_operateur, visibility_valeur, obligatoire, ordre, actif)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            "issssssissiii",
            array(
                (int) $formId,
                $values['type_champ'],
                $values['libelle'],
                $values['aide'],
                $values['options'],
                $values['valeur_defaut'],
                $values['page_titre'],
                (int) $values['visibility_champ_id'],
                $values['visibility_operateur'],
                $values['visibility_valeur'],
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
            SET type_champ = ?, libelle = ?, aide = ?, options = ?, valeur_defaut = ?, page_titre = ?, visibility_champ_id = ?, visibility_operateur = ?, visibility_valeur = ?, obligatoire = ?, ordre = ?, actif = ?
            WHERE id = ?",
            "ssssssissiiii",
            array(
                $values['type_champ'],
                $values['libelle'],
                $values['aide'],
                $values['options'],
                $values['valeur_defaut'],
                $values['page_titre'],
                (int) $values['visibility_champ_id'],
                $values['visibility_operateur'],
                $values['visibility_valeur'],
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

    public static function updateFieldOrder($formId, $fieldIds, $updatedBy)
    {
        self::ensureTables();

        $formId = (int) $formId;
        if ($formId <= 0 || !is_array($fieldIds)) {
            return false;
        }

        $order = 10;
        $updated = 0;
        foreach ($fieldIds as $fieldId) {
            $fieldId = (int) $fieldId;
            if ($fieldId <= 0) {
                continue;
            }

            $field = self::field($fieldId);
            if (!$field || (int) $field['formulaire_id'] !== $formId) {
                continue;
            }

            $result = grr_sql_command(
                "UPDATE ".self::table(self::TABLE_FIELD)." SET ordre = ? WHERE id = ?",
                "ii",
                array($order, $fieldId)
            );
            if ($result !== false && $result >= 0) {
                $updated++;
                $order += 10;
            }
        }

        if ($updated > 0) {
            self::touchForm($formId);
            self::recordHistory($formId, 0, $updatedBy, 'ordre_champs', $updated.' champ(s) reordonnes');
        }

        return $updated > 0;
    }

    public static function field($fieldId)
    {
        self::ensureTables();

        $fieldId = (int) $fieldId;
        if ($fieldId <= 0) {
            return array();
        }

        $rows = self::rows(
            "SELECT id, formulaire_id, type_champ, libelle, aide, options, valeur_defaut, page_titre,
                visibility_champ_id, visibility_operateur, visibility_valeur, obligatoire, ordre, actif
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
            "SELECT id, formulaire_id, type_champ, libelle, aide, options, valeur_defaut, page_titre,
                visibility_champ_id, visibility_operateur, visibility_valeur, obligatoire, ordre, actif
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

    public static function imageDisplaySize($field)
    {
        return self::normalizeImageDisplaySize(isset($field['options']) ? $field['options'] : '');
    }

    public static function separatorColumns($field)
    {
        return self::layoutColumns($field);
    }

    public static function layoutColumns($field)
    {
        return self::normalizeFormColumns(isset($field['options']) ? $field['options'] : 1);
    }

    public static function fieldStoresResponse($field)
    {
        $type = is_array($field) && isset($field['type_champ']) ? (string) $field['type_champ'] : (string) $field;
        $type = self::normalizeFieldType($type);

        return !in_array($type, array('separator', 'image', 'empty'), true);
    }

    public static function resultFieldsForForm($form, $fields)
    {
        if (!is_array($fields)) {
            return array();
        }

        $selectedText = isset($form['result_columns']) ? trim((string) $form['result_columns']) : '';
        if ($selectedText === '') {
            return self::responseFields($fields);
        }

        $selected = array();
        foreach (preg_split('/[,;\s]+/', $selectedText) as $part) {
            $id = (int) $part;
            if ($id > 0) {
                $selected[$id] = true;
            }
        }
        if (count($selected) === 0) {
            return self::responseFields($fields);
        }

        $filtered = array();
        foreach ($fields as $field) {
            if (!self::fieldStoresResponse($field)) {
                continue;
            }
            $fieldId = (int) (isset($field['id']) ? $field['id'] : 0);
            if (isset($selected[$fieldId])) {
                $filtered[] = $field;
            }
        }

        return $filtered;
    }

    public static function responseFields($fields)
    {
        if (!is_array($fields)) {
            return array();
        }

        $filtered = array();
        foreach ($fields as $field) {
            if (self::fieldStoresResponse($field)) {
                $filtered[] = $field;
            }
        }

        return $filtered;
    }

    public static function normalizeResponseValues($fields, $source, $files = array())
    {
        if (!is_array($fields)) {
            $fields = array();
        }
        if (!is_array($source)) {
            $source = array();
        }
        if (!is_array($files)) {
            $files = array();
        }

        $values = array();
        foreach ($fields as $field) {
            $type = isset($field['type_champ']) ? (string) $field['type_champ'] : 'text';
            if (!self::fieldStoresResponse($field)) {
                continue;
            }

            $fieldId = (int) (isset($field['id']) ? $field['id'] : 0);
            if ($fieldId <= 0) {
                continue;
            }

            $name = 'field_'.$fieldId;
            if ($type === 'file') {
                $values[$fieldId] = isset($files[$name]) && is_array($files[$name]) ? $files[$name] : array();
                continue;
            }
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
            if (!self::fieldStoresResponse($field)) {
                continue;
            }

            $fieldId = (int) (isset($field['id']) ? $field['id'] : 0);
            if ($fieldId <= 0) {
                continue;
            }
            if (!self::fieldVisibleForValues($field, $values)) {
                continue;
            }

            $label = isset($field['libelle']) && trim((string) $field['libelle']) !== ''
                ? trim((string) $field['libelle'])
                : 'Champ '.$fieldId;
            $required = isset($field['obligatoire']) && (int) $field['obligatoire'] === 1;
            $value = isset($values[$fieldId]) ? $values[$fieldId] : ($type === 'checkboxes' ? array() : '');
            $fieldErrors = array();

            if ($type === 'file') {
                $hasFile = self::uploadedFilePresent($value);
                if ($required && !$hasFile) {
                    $fieldErrors[] = 'Le champ "'.$label.'" est obligatoire.';
                }
                if ($hasFile) {
                    $fileError = self::validateUploadedFile($value);
                    if ($fileError !== '') {
                        $fieldErrors[] = 'Le champ "'.$label.'" : '.$fileError;
                    }
                }
            } elseif ($type === 'checkboxes') {
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
            } elseif ($type === 'signature') {
                $value = trim((string) $value);
                if ($required && $value === '') {
                    $fieldErrors[] = 'Le champ "'.$label.'" est obligatoire.';
                } elseif ($value !== '' && !self::signatureValueValid($value)) {
                    $fieldErrors[] = 'Le champ "'.$label.'" contient une signature invalide.';
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

    public static function fieldVisibleForValues($field, $values)
    {
        $conditionFieldId = (int) (isset($field['visibility_champ_id']) ? $field['visibility_champ_id'] : 0);
        if ($conditionFieldId <= 0) {
            return true;
        }

        $operator = self::normalizeConditionOperator(isset($field['visibility_operateur']) ? $field['visibility_operateur'] : '');
        $expected = trim((string) (isset($field['visibility_valeur']) ? $field['visibility_valeur'] : ''));
        $actualValue = isset($values[$conditionFieldId]) ? $values[$conditionFieldId] : '';
        if (is_array($actualValue) && isset($actualValue['name'])) {
            $actualValue = isset($actualValue['name']) ? (string) $actualValue['name'] : '';
        } elseif (is_array($actualValue)) {
            $actualValue = implode("\n", $actualValue);
        }
        $actual = trim((string) $actualValue);

        if ($operator === 'empty') {
            return $actual === '';
        }
        if ($operator === 'not_empty') {
            return $actual !== '';
        }
        if ($operator === 'not_equals') {
            return $actual !== $expected;
        }
        if ($operator === 'contains') {
            return in_array($expected, self::multiValueParts($actual), true) || strpos($actual, $expected) !== false;
        }
        if ($operator === 'not_contains') {
            return !in_array($expected, self::multiValueParts($actual), true) && strpos($actual, $expected) === false;
        }

        return $actual === $expected;
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
        if ((int) $meta['token_id'] > 0 && !self::tokenAcceptsResponse((int) $meta['token_id'])) {
            return 0;
        }
        $now = time();
        $insert = grr_sql_command(
            "INSERT INTO ".self::table(self::TABLE_RESPONSE)."
            (formulaire_id, token_id, submitter_login, submitter_name, submitter_email, source, ip_hash, created_at, updated_at, updated_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            "iisssssiis",
            array(
                $formId,
                (int) $meta['token_id'],
                $meta['submitter_login'],
                $meta['submitter_name'],
                $meta['submitter_email'],
                $meta['source'],
                $meta['ip_hash'],
                $now,
                $now,
                $meta['submitter_login'],
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
            if (!self::fieldStoresResponse($field)) {
                continue;
            }
            if (!self::fieldVisibleForValues($field, $values)) {
                continue;
            }

            $fieldId = (int) (isset($field['id']) ? $field['id'] : 0);
            if ($fieldId <= 0) {
                continue;
            }

            if ($type === 'file') {
                $fileValue = isset($values[$fieldId]) ? $values[$fieldId] : array();
                $value = self::storeUploadedResponseFile($formId, $fieldId, $fileValue);
                if (self::uploadedFilePresent($fileValue) && $value === '') {
                    self::deleteResponse($responseId);
                    return 0;
                }
                if ($value === '' && isset($field['obligatoire']) && (int) $field['obligatoire'] === 1) {
                    self::deleteResponse($responseId);
                    return 0;
                }
            } else {
                $value = self::responseValueForStorage($type, isset($values[$fieldId]) ? $values[$fieldId] : '');
            }
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

    public static function updateResponse($responseId, $fields, $values, $updatedBy)
    {
        self::ensureTables();

        $responseId = (int) $responseId;
        $response = self::responseWithValues($responseId);
        if (!$response) {
            return false;
        }

        if (!is_array($fields)) {
            $fields = array();
        }
        $values = is_array($values) ? $values : array();

        $mergedValues = isset($response['values']) && is_array($response['values']) ? $response['values'] : array();
        foreach ($values as $fieldId => $value) {
            $mergedValues[(int) $fieldId] = $value;
        }

        if (count(self::validateResponseValues($fields, $mergedValues)) > 0) {
            return false;
        }

        foreach ($fields as $field) {
            $type = isset($field['type_champ']) ? (string) $field['type_champ'] : 'text';
            if (!self::fieldStoresResponse($field)) {
                continue;
            }

            $fieldId = (int) (isset($field['id']) ? $field['id'] : 0);
            if ($fieldId <= 0) {
                continue;
            }

            if (!self::fieldVisibleForValues($field, $mergedValues)) {
                $storedValue = '';
            } elseif ($type === 'file') {
                $currentValue = isset($response['values'][$fieldId]) ? (string) $response['values'][$fieldId] : '';
                if (self::uploadedFilePresent(isset($values[$fieldId]) ? $values[$fieldId] : array())) {
                    $storedValue = self::storeUploadedResponseFile((int) $response['formulaire_id'], $fieldId, $values[$fieldId]);
                    if ($storedValue === '') {
                        return false;
                    }
                } else {
                    $storedValue = $currentValue;
                }
            } else {
                $storedValue = self::responseValueForStorage($type, isset($mergedValues[$fieldId]) ? $mergedValues[$fieldId] : '');
            }

            grr_sql_command(
                "REPLACE INTO ".self::table(self::TABLE_VALUE)."
                (reponse_id, champ_id, valeur)
                VALUES (?, ?, ?)",
                "iis",
                array($responseId, $fieldId, $storedValue)
            );
        }

        grr_sql_command(
            "UPDATE ".self::table(self::TABLE_RESPONSE)."
            SET updated_at = ?, updated_by = ?
            WHERE id = ?",
            "isi",
            array(time(), self::limit($updatedBy, 190), $responseId)
        );

        self::recordHistory((int) $response['formulaire_id'], $responseId, $updatedBy, 'modification_reponse', 'Reponse modifiee');

        return true;
    }

    public static function createToken($formId, $type, $createdBy, $options = array())
    {
        self::ensureTables();

        $formId = (int) $formId;
        $type = self::normalizeTokenType($type);
        if ($formId <= 0 || !self::form($formId)) {
            return '';
        }

        $options = self::normalizeTokenOptions($options);
        for ($i = 0; $i < 5; $i++) {
            $token = self::randomToken();
            $hash = self::tokenHash($token);
            $insert = grr_sql_command(
                "INSERT INTO ".self::table(self::TABLE_TOKEN)."
                (formulaire_id, type_token, token_hash, token_public, expires_at, max_responses, actif, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 1, ?)",
                "isssiii",
                array($formId, $type, $hash, $token, (int) $options['expires_at'], (int) $options['max_responses'], time())
            );

            if ($insert !== false && $insert >= 0) {
                $details = 'Jeton cree';
                if ((int) $options['expires_at'] > 0) {
                    $details .= ' - expiration '.date('d/m/Y H:i', (int) $options['expires_at']);
                }
                if ((int) $options['max_responses'] > 0) {
                    $details .= ' - limite '.(int) $options['max_responses'].' reponse(s)';
                }
                self::recordHistory($formId, 0, $createdBy, 'creation_jeton_'.$type, $details);
                return $token;
            }
        }

        return '';
    }

    public static function activeTokenCount($formId, $type)
    {
        self::ensureTables();

        return (int) grr_sql_query1(
            "SELECT COUNT(*) FROM ".self::table(self::TABLE_TOKEN)." t
            WHERE t.formulaire_id = ? AND t.type_token = ? AND t.actif = 1
                AND (t.expires_at = 0 OR t.expires_at >= ?)
                AND (t.max_responses = 0 OR (
                    SELECT COUNT(*) FROM ".self::table(self::TABLE_RESPONSE)." r WHERE r.token_id = t.id
                ) < t.max_responses)",
            "isi",
            array((int) $formId, self::normalizeTokenType($type), time())
        );
    }

    public static function tokens($formId, $includeInactive = true)
    {
        self::ensureTables();

        $formId = (int) $formId;
        if ($formId <= 0) {
            return array();
        }

        $whereActive = $includeInactive ? '' : ' AND t.actif = 1';

        return self::rows(
            "SELECT t.id, t.formulaire_id, t.type_token, t.token_hash, t.token_public, t.expires_at, t.max_responses, t.actif, t.created_at,
                (SELECT COUNT(*) FROM ".self::table(self::TABLE_RESPONSE)." r WHERE r.token_id = t.id) AS response_count
            FROM ".self::table(self::TABLE_TOKEN)." t
            WHERE t.formulaire_id = ?".$whereActive."
            ORDER BY t.created_at DESC, t.id DESC",
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

    public static function deleteToken($tokenId, $updatedBy)
    {
        self::ensureTables();

        $tokenId = (int) $tokenId;
        if ($tokenId <= 0) {
            return false;
        }

        $rows = self::rows(
            "SELECT id, formulaire_id, type_token
            FROM ".self::table(self::TABLE_TOKEN)."
            WHERE id = ?",
            "i",
            array($tokenId)
        );
        if (!isset($rows[0])) {
            return false;
        }

        $delete = grr_sql_command(
            "DELETE FROM ".self::table(self::TABLE_TOKEN)." WHERE id = ?",
            "i",
            array($tokenId)
        );
        if ($delete === false || $delete < 0) {
            return false;
        }

        self::recordHistory(
            (int) $rows[0]['formulaire_id'],
            0,
            $updatedBy,
            'suppression_jeton',
            isset($rows[0]['type_token']) ? $rows[0]['type_token'] : ''
        );

        return true;
    }

    public static function formByToken($token, $type, $allowResponseLimitReached = false)
    {
        self::ensureTables();

        $token = trim((string) $token);
        if ($token === '') {
            return array();
        }

        $rows = self::rows(
            "SELECT f.id, f.titre, f.description, f.form_columns, f.allow_user_edit, f.confirmation_email_enabled, f.result_list_template, f.result_detail_template, f.result_columns,
                f.notification_subject_template, f.notification_body_template, f.statut, f.created_by, f.created_at, f.updated_at,
                f.published_at, f.archived_at, t.id AS token_id, t.expires_at, t.max_responses,
                (SELECT COUNT(*) FROM ".self::table(self::TABLE_FIELD)." c WHERE c.formulaire_id = f.id AND c.actif = 1) AS field_count,
                (SELECT COUNT(*) FROM ".self::table(self::TABLE_RESPONSE)." r WHERE r.formulaire_id = f.id) AS response_count
            FROM ".self::table(self::TABLE_TOKEN)." t
            INNER JOIN ".self::table(self::TABLE_FORM)." f ON f.id = t.formulaire_id
            WHERE t.type_token = ? AND t.token_hash = ? AND t.actif = 1",
            "ss",
            array(self::normalizeTokenType($type), self::tokenHash($token))
        );

        if (!isset($rows[0])) {
            return array();
        }

        $row = $rows[0];
        if ((int) (isset($row['expires_at']) ? $row['expires_at'] : 0) > 0
            && (int) $row['expires_at'] < time()) {
            return array();
        }
        if (self::normalizeTokenType($type) === 'formulaire'
            && !$allowResponseLimitReached
            && (int) (isset($row['max_responses']) ? $row['max_responses'] : 0) > 0
            && self::tokenUsageCount((int) $row['token_id']) >= (int) $row['max_responses']) {
            return array();
        }

        return $row;
    }

    public static function tokenUsageCount($tokenId)
    {
        self::ensureTables();

        return (int) grr_sql_query1(
            "SELECT COUNT(*) FROM ".self::table(self::TABLE_RESPONSE)." WHERE token_id = ?",
            "i",
            array((int) $tokenId)
        );
    }

    private static function tokenAcceptsResponse($tokenId)
    {
        $rows = self::rows(
            "SELECT id, type_token, actif, expires_at, max_responses
            FROM ".self::table(self::TABLE_TOKEN)."
            WHERE id = ?",
            "i",
            array((int) $tokenId)
        );
        if (!isset($rows[0])) {
            return false;
        }
        $token = $rows[0];
        if ((int) $token['actif'] !== 1 || (string) $token['type_token'] !== 'formulaire') {
            return false;
        }
        if ((int) $token['expires_at'] > 0 && (int) $token['expires_at'] < time()) {
            return false;
        }
        if ((int) $token['max_responses'] > 0 && self::tokenUsageCount((int) $tokenId) >= (int) $token['max_responses']) {
            return false;
        }

        return true;
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

    public static function columnExists($suffix, $column)
    {
        $tableName = self::table($suffix);
        $column = trim((string) $column);
        if ($tableName === TABLE_PREFIX.'_' || $column === '') {
            return false;
        }

        $count = grr_sql_query1(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
            "ss",
            array($tableName, $column)
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
        if ($type === 'upload' || $type === 'attachment') {
            $type = 'file';
        }
        $options = self::fieldTypeOptions();

        return isset($options[$type]) ? $type : 'text';
    }

    private static function normalizeConditionOperator($operator)
    {
        $operator = strtolower(trim((string) $operator));
        $options = self::notificationConditionOperators();

        return isset($options[$operator]) ? $operator : '';
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

    private static function normalizeImageDisplaySize($value)
    {
        $value = strtolower(trim((string) $value));
        $value = str_replace(' ', '', $value);
        if ($value === '' || $value === 'auto') {
            return '';
        }

        $presets = array(
            'small' => '240px',
            'medium' => '480px',
            'large' => '720px',
            'full' => '100%',
        );
        if (isset($presets[$value])) {
            return $presets[$value];
        }

        if (preg_match('/^[0-9]{2,4}$/', $value)) {
            $pixels = max(50, min(2000, (int) $value));
            return $pixels.'px';
        }

        if (preg_match('/^([0-9]{1,4})(px|%)$/', $value, $matches)) {
            $number = (int) $matches[1];
            if ($matches[2] === 'px') {
                return max(50, min(2000, $number)).'px';
            }

            return max(10, min(100, $number)).'%';
        }

        return '';
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

    private static function normalizeTokenOptions($options)
    {
        if (!is_array($options)) {
            $options = array();
        }

        return array(
            'expires_at' => self::normalizeTokenExpiry(isset($options['expires_at']) ? $options['expires_at'] : ''),
            'max_responses' => max(0, (int) (isset($options['max_responses']) ? $options['max_responses'] : 0)),
        );
    }

    private static function normalizeTokenExpiry($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return 0;
        }

        if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $value)) {
            $value .= ' 23:59:59';
        } elseif (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}$/', $value)) {
            $value = str_replace('T', ' ', $value).':00';
        }

        $timestamp = strtotime($value);
        return $timestamp !== false ? (int) $timestamp : 0;
    }

    private static function normalizeIdListText($value)
    {
        $ids = array();
        $parts = is_array($value) ? $value : preg_split('/[,;\s]+/', (string) $value);
        foreach ((array) $parts as $part) {
            $id = (int) $part;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        return implode(',', array_values($ids));
    }

    private static function remapIdListText($value, $map)
    {
        $ids = array();
        foreach (preg_split('/[,;\s]+/', (string) $value) as $part) {
            $oldId = (int) $part;
            if ($oldId > 0 && isset($map[$oldId])) {
                $ids[(int) $map[$oldId]] = (int) $map[$oldId];
            }
        }

        return implode(',', array_values($ids));
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
            'token_id' => max(0, (int) (isset($meta['token_id']) ? $meta['token_id'] : 0)),
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
        if ($type === 'signature') {
            $value = self::normalizeResponseScalar($value);
            return self::signatureValueValid($value) ? $value : '';
        }

        return self::normalizeResponseScalar($value);
    }

    public static function signatureValueValid($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return false;
        }
        if (strlen($value) > 65535 || !preg_match('/^data:image\/png;base64,[A-Za-z0-9+\/=]+$/', $value)) {
            return false;
        }

        $payload = substr($value, strlen('data:image/png;base64,'));
        $decoded = base64_decode($payload, true);
        if ($decoded === false || strlen($decoded) < 50) {
            return false;
        }

        return substr($decoded, 0, 8) === "\x89PNG\r\n\x1a\n";
    }

    private static function uploadedFilePresent($file)
    {
        if (!is_array($file)) {
            return trim((string) $file) !== '';
        }

        return is_array($file)
            && isset($file['error'])
            && (int) $file['error'] !== UPLOAD_ERR_NO_FILE
            && isset($file['name'])
            && trim((string) $file['name']) !== '';
    }

    private static function validateUploadedFile($file)
    {
        if (!self::uploadedFilePresent($file)) {
            return '';
        }
        if (!is_array($file)) {
            return '';
        }

        if ((int) $file['error'] !== UPLOAD_ERR_OK) {
            return 'le fichier n a pas ete recu correctement.';
        }

        $size = isset($file['size']) ? (int) $file['size'] : 0;
        if ($size <= 0) {
            return 'le fichier est vide.';
        }
        if ($size > 10 * 1024 * 1024) {
            return 'le fichier depasse 10 Mo.';
        }

        $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::allowedUploadExtensions(), true)) {
            return 'extension non autorisee.';
        }

        return '';
    }

    private static function storeUploadedResponseFile($formId, $fieldId, $file)
    {
        if (!self::uploadedFilePresent($file)) {
            return '';
        }
        if (self::validateUploadedFile($file) !== '') {
            return '';
        }

        $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        $directory = dirname(__DIR__).'/uploads/form_'.(int) $formId;
        if (!is_dir($directory)) {
            @mkdir($directory, 0755, true);
        }
        if (!is_dir($directory) || !is_writable($directory)) {
            return '';
        }

        $base = 'field_'.(int) $fieldId.'_'.date('YmdHis').'_'.self::randomToken();
        $filename = substr($base, 0, 80).'.'.$extension;
        $target = $directory.'/'.$filename;
        $tmp = isset($file['tmp_name']) ? (string) $file['tmp_name'] : '';
        $moved = $tmp !== '' && is_uploaded_file($tmp)
            ? move_uploaded_file($tmp, $target)
            : ($tmp !== '' && is_file($tmp) ? copy($tmp, $target) : false);

        if (!$moved) {
            return '';
        }

        return 'uploads/form_'.(int) $formId.'/'.$filename;
    }

    private static function allowedUploadExtensions()
    {
        return array('jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'txt', 'csv', 'doc', 'docx', 'xls', 'xlsx', 'odt', 'ods');
    }

    private static function multiValueParts($value)
    {
        $tokens = preg_split('/[\r\n]+/', (string) $value);
        $parts = array();
        foreach ($tokens as $token) {
            $token = trim((string) $token);
            if ($token !== '') {
                $parts[$token] = $token;
            }
        }

        return array_values($parts);
    }

    private static function validDateValue($value)
    {
        if (!preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/', (string) $value, $matches)) {
            return false;
        }

        return checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1]);
    }

    private static function validImageSource($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return false;
        }

        if (preg_match('/^https?:\/\//i', $value)) {
            return filter_var($value, FILTER_VALIDATE_URL) !== false;
        }

        return preg_match('/^[a-zA-Z0-9_\/.\-]+$/', $value) === 1;
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

    private static function activeNotificationEmailExists($formId, $email, $excludeId = 0, $conditionFieldId = 0, $conditionOperator = '', $conditionValue = '')
    {
        $formId = (int) $formId;
        $email = self::limit($email, 190);
        $excludeId = (int) $excludeId;
        $conditionFieldId = (int) $conditionFieldId;
        $conditionOperator = self::normalizeConditionOperator($conditionOperator);
        $conditionValue = self::limit($conditionValue, 190);
        if ($formId <= 0 || $email === '') {
            return false;
        }

        $sql = "SELECT COUNT(*) FROM ".self::table(self::TABLE_NOTIFICATION)."
            WHERE formulaire_id = ? AND email = ? AND condition_champ_id = ? AND condition_operateur = ? AND condition_valeur = ? AND actif = 1";
        $types = "isiss";
        $params = array($formId, $email, $conditionFieldId, $conditionOperator, $conditionValue);
        if ($excludeId > 0) {
            $sql .= " AND id <> ?";
            $types .= "i";
            $params[] = $excludeId;
        }

        return (int) grr_sql_query1($sql, $types, $params) > 0;
    }

    private static function deleteFormRows($table, $formId)
    {
        $delete = grr_sql_command(
            "DELETE FROM ".self::table($table)." WHERE formulaire_id = ?",
            "i",
            array((int) $formId)
        );

        return !($delete === false || $delete < 0);
    }

    private static function deleteFormUploadDirectory($formId)
    {
        $base = dirname(__DIR__).'/uploads';
        $directory = $base.'/form_'.(int) $formId;
        $baseReal = realpath($base);
        $directoryReal = realpath($directory);
        if (!$baseReal || !$directoryReal || !is_dir($directoryReal)) {
            return true;
        }

        $basePrefix = rtrim($baseReal, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        if (strpos($directoryReal, $basePrefix) !== 0) {
            return false;
        }

        return self::deleteDirectoryTree($directoryReal);
    }

    private static function deleteDirectoryTree($directory)
    {
        if (!is_dir($directory) || is_link($directory)) {
            return true;
        }

        $items = scandir($directory);
        if (!is_array($items)) {
            return false;
        }

        $ok = true;
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $directory.DIRECTORY_SEPARATOR.$item;
            if (is_dir($path) && !is_link($path)) {
                $ok = self::deleteDirectoryTree($path) && $ok;
            } elseif (is_file($path) || is_link($path)) {
                $ok = @unlink($path) && $ok;
            }
        }

        return @rmdir($directory) && $ok;
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
