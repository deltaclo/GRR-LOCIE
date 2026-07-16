<?php

class InformatiqueMaterielRepository
{
    const TABLE_ROLE = 'informatique_materiel_role';
    const TABLE_JOURNAL = 'informatique_materiel_journal';
    const TABLE_PERSON = 'informatique_materiel_personne';
    const TABLE_CATEGORY = 'informatique_materiel_categorie';
    const TABLE_SEQUENCE = 'informatique_materiel_sequence';
    const TABLE_ITEM = 'informatique_materiel_item';
    const TABLE_LOAN = 'informatique_materiel_pret';
    const TABLE_LOAN_CONFLICT = 'informatique_materiel_pret_conflit';
    const TABLE_DOCUMENT = 'informatique_materiel_document';
    const TABLE_IMPORT_LOG = 'informatique_materiel_import_log';

    public static function ensureTables()
    {
        $commands = array(
            "CREATE TABLE IF NOT EXISTS `".self::table(self::TABLE_ROLE)."` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `login` varchar(190) NOT NULL,
                `role` varchar(20) NOT NULL,
                `created_by` varchar(190) NOT NULL DEFAULT '',
                `created_at` int(11) NOT NULL DEFAULT 0,
                `updated_by` varchar(190) NOT NULL DEFAULT '',
                `updated_at` int(11) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                UNIQUE KEY `login` (`login`),
                KEY `role` (`role`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8",

            "CREATE TABLE IF NOT EXISTS `".self::table(self::TABLE_JOURNAL)."` (
                `id` bigint(20) NOT NULL AUTO_INCREMENT,
                `type_evenement` varchar(50) NOT NULL,
                `type_objet` varchar(50) NOT NULL DEFAULT '',
                `objet_id` int(11) NOT NULL DEFAULT 0,
                `resume` text NULL,
                `login` varchar(190) NOT NULL DEFAULT '',
                `created_at` int(11) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                KEY `type_evenement` (`type_evenement`),
                KEY `objet` (`type_objet`, `objet_id`),
                KEY `login` (`login`),
                KEY `created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8",

            "CREATE TABLE IF NOT EXISTS `".self::table(self::TABLE_PERSON)."` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `legacy_id` int(11) NOT NULL DEFAULT 0,
                `identifiant_legacy` varchar(100) DEFAULT NULL,
                `prenom` varchar(100) NOT NULL,
                `nom` varchar(100) NOT NULL,
                `cadre_usage` varchar(100) NOT NULL DEFAULT '',
                `date_depart` date DEFAULT NULL,
                `login_grr` varchar(190) NOT NULL DEFAULT '',
                `email` varchar(190) NOT NULL DEFAULT '',
                `notes` text NULL,
                `actif` tinyint(1) NOT NULL DEFAULT 1,
                `created_by` varchar(190) NOT NULL DEFAULT '',
                `created_at` int(11) NOT NULL DEFAULT 0,
                `updated_by` varchar(190) NOT NULL DEFAULT '',
                `updated_at` int(11) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                UNIQUE KEY `identifiant_legacy` (`identifiant_legacy`),
                KEY `legacy_id` (`legacy_id`),
                KEY `nom_prenom` (`nom`, `prenom`),
                KEY `cadre_usage` (`cadre_usage`),
                KEY `date_depart` (`date_depart`),
                KEY `login_grr` (`login_grr`),
                KEY `email` (`email`),
                KEY `actif` (`actif`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8",

            "CREATE TABLE IF NOT EXISTS `".self::table(self::TABLE_CATEGORY)."` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `prefixe` varchar(20) NOT NULL,
                `designation` varchar(190) NOT NULL,
                `description` text NULL,
                `actif` tinyint(1) NOT NULL DEFAULT 1,
                `created_by` varchar(190) NOT NULL DEFAULT '',
                `created_at` int(11) NOT NULL DEFAULT 0,
                `updated_by` varchar(190) NOT NULL DEFAULT '',
                `updated_at` int(11) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                KEY `prefixe` (`prefixe`),
                KEY `designation` (`designation`),
                KEY `actif` (`actif`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8",

            "CREATE TABLE IF NOT EXISTS `".self::table(self::TABLE_SEQUENCE)."` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `prefixe` varchar(20) NOT NULL,
                `dernier_numero` int(11) NOT NULL DEFAULT 0,
                `updated_by` varchar(190) NOT NULL DEFAULT '',
                `updated_at` int(11) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                UNIQUE KEY `prefixe` (`prefixe`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8",

            "CREATE TABLE IF NOT EXISTS `".self::table(self::TABLE_ITEM)."` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `identifiant` varchar(100) NOT NULL,
                `identifiant_legacy` varchar(100) DEFAULT NULL,
                `categorie_id` int(11) NOT NULL DEFAULT 0,
                `designation` varchar(190) NOT NULL,
                `precision_materiel` varchar(190) NOT NULL DEFAULT '',
                `mac` varchar(100) NOT NULL DEFAULT '',
                `marque` varchar(100) NOT NULL DEFAULT '',
                `numero_serie` varchar(190) NOT NULL DEFAULT '',
                `code_barre_usmb` varchar(100) DEFAULT NULL,
                `os` varchar(100) NOT NULL DEFAULT '',
                `annee` varchar(20) NOT NULL DEFAULT '',
                `commentaire` text NULL,
                `localisation_stockage` varchar(190) NOT NULL DEFAULT '',
                `statut` varchar(30) NOT NULL DEFAULT 'actif',
                `pret_multiple` tinyint(1) NOT NULL DEFAULT 0,
                `notes` text NULL,
                `actif` tinyint(1) NOT NULL DEFAULT 1,
                `created_by` varchar(190) NOT NULL DEFAULT '',
                `created_at` int(11) NOT NULL DEFAULT 0,
                `updated_by` varchar(190) NOT NULL DEFAULT '',
                `updated_at` int(11) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                UNIQUE KEY `identifiant` (`identifiant`),
                UNIQUE KEY `identifiant_legacy` (`identifiant_legacy`),
                UNIQUE KEY `code_barre_usmb` (`code_barre_usmb`),
                KEY `categorie_id` (`categorie_id`),
                KEY `designation` (`designation`),
                KEY `marque` (`marque`),
                KEY `numero_serie` (`numero_serie`),
                KEY `mac` (`mac`),
                KEY `statut` (`statut`),
                KEY `pret_multiple` (`pret_multiple`),
                KEY `actif` (`actif`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8",

            "CREATE TABLE IF NOT EXISTS `".self::table(self::TABLE_LOAN)."` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `personne_id` int(11) NOT NULL,
                `item_id` int(11) NOT NULL,
                `localisation` varchar(190) NOT NULL DEFAULT '',
                `date_debut` date NOT NULL,
                `date_fin_prevue` date DEFAULT NULL,
                `date_fin_effective` date DEFAULT NULL,
                `commentaire` text NULL,
                `statut` varchar(20) NOT NULL DEFAULT 'ouvert',
                `source_import_id` bigint(20) NOT NULL DEFAULT 0,
                `created_by` varchar(190) NOT NULL DEFAULT '',
                `created_at` int(11) NOT NULL DEFAULT 0,
                `updated_by` varchar(190) NOT NULL DEFAULT '',
                `updated_at` int(11) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                KEY `personne_id` (`personne_id`),
                KEY `item_id` (`item_id`),
                KEY `statut` (`statut`),
                KEY `date_debut` (`date_debut`),
                KEY `date_fin_prevue` (`date_fin_prevue`),
                KEY `date_fin_effective` (`date_fin_effective`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8",

            "CREATE TABLE IF NOT EXISTS `".self::table(self::TABLE_DOCUMENT)."` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `item_id` int(11) NOT NULL,
                `type_document` varchar(50) NOT NULL DEFAULT 'autre',
                `description` text NULL,
                `original_name` varchar(255) NOT NULL DEFAULT '',
                `stored_name` char(64) NOT NULL,
                `mime_type` varchar(190) NOT NULL DEFAULT 'application/octet-stream',
                `taille` int(11) NOT NULL DEFAULT 0,
                `sha256` char(64) NOT NULL DEFAULT '',
                `actif` tinyint(1) NOT NULL DEFAULT 1,
                `uploaded_by` varchar(190) NOT NULL DEFAULT '',
                `created_at` int(11) NOT NULL DEFAULT 0,
                `archived_by` varchar(190) NOT NULL DEFAULT '',
                `archived_at` int(11) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                UNIQUE KEY `stored_name` (`stored_name`),
                KEY `item_id` (`item_id`),
                KEY `type_document` (`type_document`),
                KEY `sha256` (`sha256`),
                KEY `actif` (`actif`),
                KEY `created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8",

            "CREATE TABLE IF NOT EXISTS `".self::table(self::TABLE_LOAN_CONFLICT)."` (
                `id` bigint(20) NOT NULL AUTO_INCREMENT,
                `package_hash` char(64) NOT NULL DEFAULT '',
                `package_name` varchar(190) NOT NULL DEFAULT '',
                `source_table` varchar(50) NOT NULL DEFAULT 'loans',
                `source_row` int(11) NOT NULL DEFAULT 0,
                `motif` varchar(100) NOT NULL DEFAULT '',
                `statut` varchar(20) NOT NULL DEFAULT 'en_attente',
                `personne_id` int(11) NOT NULL DEFAULT 0,
                `item_id` int(11) NOT NULL DEFAULT 0,
                `pret_existant_id` int(11) NOT NULL DEFAULT 0,
                `localisation` varchar(190) NOT NULL DEFAULT '',
                `date_debut` date DEFAULT NULL,
                `date_fin_prevue` date DEFAULT NULL,
                `date_fin_effective` date DEFAULT NULL,
                `commentaire` text NULL,
                `resume_source` text NULL,
                `decision` text NULL,
                `created_by` varchar(190) NOT NULL DEFAULT '',
                `created_at` int(11) NOT NULL DEFAULT 0,
                `resolved_by` varchar(190) NOT NULL DEFAULT '',
                `resolved_at` int(11) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                UNIQUE KEY `package_source_row` (`package_hash`, `source_table`, `source_row`),
                KEY `statut` (`statut`),
                KEY `item_id` (`item_id`),
                KEY `personne_id` (`personne_id`),
                KEY `pret_existant_id` (`pret_existant_id`),
                KEY `created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8",

            "CREATE TABLE IF NOT EXISTS `".self::table(self::TABLE_IMPORT_LOG)."` (
                `id` bigint(20) NOT NULL AUTO_INCREMENT,
                `package_hash` char(64) NOT NULL,
                `package_name` varchar(190) NOT NULL DEFAULT '',
                `source_table` varchar(50) NOT NULL DEFAULT '',
                `source_row` int(11) NOT NULL,
                `personne_id` int(11) NOT NULL DEFAULT 0,
                `item_id` int(11) NOT NULL DEFAULT 0,
                `pret_id` int(11) NOT NULL DEFAULT 0,
                `status` varchar(20) NOT NULL DEFAULT 'success',
                `message` text NULL,
                `created_by` varchar(190) NOT NULL DEFAULT '',
                `created_at` int(11) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                UNIQUE KEY `package_source_row` (`package_hash`, `source_table`, `source_row`),
                KEY `package_name` (`package_name`),
                KEY `source_table` (`source_table`),
                KEY `status` (`status`),
                KEY `created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
        );

        foreach ($commands as $command) {
            if (!self::commandOk(grr_sql_command($command))) {
                return false;
            }
        }

        if (!self::ensurePersonEmailColumn()) {
            return false;
        }

        if (!self::ensureItemLoanModeColumn()) {
            return false;
        }

        return true;
    }

    public static function expectedTables()
    {
        return array(
            self::TABLE_ROLE => 'Roles utilisateurs',
            self::TABLE_JOURNAL => 'Journal fonctionnel',
            self::TABLE_PERSON => 'Personnes',
            self::TABLE_CATEGORY => 'Categories materiel',
            self::TABLE_SEQUENCE => 'Sequences identifiants',
            self::TABLE_ITEM => 'Materiels',
            self::TABLE_LOAN => 'Prets',
            self::TABLE_LOAN_CONFLICT => 'Conflits de prets',
            self::TABLE_DOCUMENT => 'Documents materiel',
            self::TABLE_IMPORT_LOG => 'Journal des imports',
        );
    }

    public static function diagnostics()
    {
        $diagnostics = array();
        foreach (self::expectedTables() as $table => $label) {
            $exists = self::tableExists($table);
            $diagnostics[] = array(
                'label' => $label,
                'table' => self::table($table),
                'exists' => $exists,
                'engine' => $exists ? self::tableEngine($table) : '',
            );
        }

        return $diagnostics;
    }

    public static function roles()
    {
        return array(
            'lecteur' => 'Lecteur',
            'operateur' => 'Operateur',
            'gestionnaire' => 'Gestionnaire',
        );
    }

    public static function itemStatuses()
    {
        return array(
            'actif' => 'Actif',
            'stocke' => 'Stocke',
            'en_pret' => 'En pret',
            'pret_multiple' => 'Pret multiple',
            'maintenance' => 'Maintenance',
            'a_reformer' => 'A reformer',
            'archive' => 'Archive',
        );
    }

    public static function loanStatuses()
    {
        return array(
            'ouvert' => 'Ouvert',
            'clos' => 'Clos',
            'annule' => 'Annule',
        );
    }

    public static function roleForLogin($login)
    {
        $login = self::limit($login, 190);
        if ($login === '' || !self::tableExists(self::TABLE_ROLE)) {
            return '';
        }

        $role = grr_sql_query1(
            "SELECT role FROM ".self::table(self::TABLE_ROLE)." WHERE LOWER(login) = LOWER(?)",
            "s",
            array($login)
        );
        $roles = self::roles();

        return is_string($role) && isset($roles[$role]) ? $role : '';
    }

    public static function assignedRoles()
    {
        self::ensureTables();
        return self::rows(
            "SELECT r.login, r.role, u.nom, u.prenom, u.email
            FROM ".self::table(self::TABLE_ROLE)." r
            LEFT JOIN ".TABLE_PREFIX."_utilisateurs u ON u.login = r.login
            ORDER BY r.role, u.nom, u.prenom, r.login"
        );
    }

    public static function activeUsers()
    {
        return self::rows(
            "SELECT login, nom, prenom, email
            FROM ".TABLE_PREFIX."_utilisateurs
            WHERE etat != 'inactif'
            ORDER BY nom, prenom, login"
        );
    }

    public static function grrUsersForPerson($prenom, $nom, $selectedLogin = '')
    {
        $prenom = self::limit($prenom, 100);
        $nom = self::limit($nom, 100);
        $selectedLogin = self::limit($selectedLogin, 190);
        if ($prenom === '' && $nom === '' && $selectedLogin === '') {
            return array();
        }

        $conditions = array();
        $types = '';
        $params = array();
        $nameConditions = array();
        foreach (array($prenom, $nom) as $term) {
            if ($term === '') {
                continue;
            }
            $like = '%'.$term.'%';
            $nameConditions[] = '(nom LIKE ? OR prenom LIKE ? OR login LIKE ?)';
            $types .= 'sss';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if (count($nameConditions) > 0) {
            $conditions[] = '('.implode(' OR ', $nameConditions).')';
        }
        if ($selectedLogin !== '') {
            $conditions[] = 'login = ?';
            $types .= 's';
            $params[] = $selectedLogin;
        }

        $where = array();
        foreach ($conditions as $condition) {
            $where[] = '('.$condition.' AND etat != \'inactif\')';
        }

        return self::rows(
            "SELECT login, nom, prenom, email
            FROM ".TABLE_PREFIX."_utilisateurs
            WHERE ".implode(' OR ', $where)."
            ORDER BY nom, prenom, login
            LIMIT 25",
            $types,
            $params
        );
    }

    public static function setUserRole($login, $role, $updatedBy)
    {
        self::ensureTables();
        $login = self::limit($login, 190);
        $role = self::limit($role, 20);
        $updatedBy = self::limit($updatedBy, 190);
        $roles = self::roles();
        if ($login === '' || ($role !== '' && !isset($roles[$role]))) {
            return false;
        }

        if ($role !== '') {
            $activeUser = (int) grr_sql_query1(
                "SELECT COUNT(*) FROM ".TABLE_PREFIX."_utilisateurs WHERE login = ? AND etat != 'inactif'",
                "s",
                array($login)
            );
            if ($activeUser !== 1) {
                return false;
            }
        }

        if ($role === '') {
            $ok = self::commandOk(grr_sql_command(
                "DELETE FROM ".self::table(self::TABLE_ROLE)." WHERE login = ?",
                "s",
                array($login)
            ));
        } else {
            $now = time();
            $ok = self::commandOk(grr_sql_command(
                "INSERT INTO ".self::table(self::TABLE_ROLE)."
                    (login, role, created_by, created_at, updated_by, updated_at)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE role = VALUES(role), updated_by = VALUES(updated_by), updated_at = VALUES(updated_at)",
                "sssisi",
                array($login, $role, $updatedBy, $now, $updatedBy, $now)
            ));
        }

        if ($ok) {
            self::log('role_modifie', 'role', 0, $login.' : '.($role === '' ? 'retire' : $role), $updatedBy);
        }

        return $ok;
    }

    public static function categories($includeArchived = false)
    {
        self::ensureTables();
        $where = $includeArchived ? '' : ' WHERE c.actif = 1';
        return self::rows(
            "SELECT c.*, COALESCE(s.dernier_numero, 0) AS dernier_numero
            FROM ".self::table(self::TABLE_CATEGORY)." c
            LEFT JOIN ".self::table(self::TABLE_SEQUENCE)." s ON s.prefixe = c.prefixe"
            .$where."
            ORDER BY c.actif DESC, c.prefixe, c.designation"
        );
    }

    public static function category($id)
    {
        self::ensureTables();
        return self::one(
            "SELECT c.*, COALESCE(s.dernier_numero, 0) AS dernier_numero
            FROM ".self::table(self::TABLE_CATEGORY)." c
            LEFT JOIN ".self::table(self::TABLE_SEQUENCE)." s ON s.prefixe = c.prefixe
            WHERE c.id = ?",
            "i",
            array((int) $id)
        );
    }

    public static function emptyCategoryValues()
    {
        return array(
            'id' => 0,
            'prefixe' => '',
            'designation' => '',
            'description' => '',
        );
    }

    public static function normalizeCategoryValues($source)
    {
        return array(
            'id' => isset($source['id']) ? (int) $source['id'] : 0,
            'prefixe' => self::limit(isset($source['prefixe']) ? $source['prefixe'] : '', 20),
            'designation' => self::limit(isset($source['designation']) ? $source['designation'] : '', 190),
            'description' => self::limit(isset($source['description']) ? $source['description'] : '', 2000),
        );
    }

    public static function validateCategoryValues($values)
    {
        $errors = array();
        if ($values['prefixe'] === '' || preg_match('/^[A-Za-z0-9_-]{1,20}$/', $values['prefixe']) !== 1) {
            $errors[] = 'Le prefixe est obligatoire et ne doit contenir que lettres, chiffres, tirets ou underscores.';
        }
        if ($values['designation'] === '') {
            $errors[] = 'La designation est obligatoire.';
        }

        return $errors;
    }

    public static function saveCategory($source, $login)
    {
        self::ensureTables();
        $values = self::normalizeCategoryValues($source);
        $errors = self::validateCategoryValues($values);
        if (count($errors) > 0) {
            return array('ok' => false, 'errors' => $errors);
        }

        $now = time();
        if ((int) $values['id'] > 0) {
            $ok = self::commandOk(grr_sql_command(
                "UPDATE ".self::table(self::TABLE_CATEGORY)."
                SET prefixe = ?, designation = ?, description = ?, updated_by = ?, updated_at = ?
                WHERE id = ?",
                "ssssii",
                array($values['prefixe'], $values['designation'], $values['description'], self::limit($login, 190), $now, (int) $values['id'])
            ));
            $id = (int) $values['id'];
        } else {
            $ok = self::commandOk(grr_sql_command(
                "INSERT INTO ".self::table(self::TABLE_CATEGORY)."
                    (prefixe, designation, description, created_by, created_at, updated_by, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)",
                "ssssisi",
                array($values['prefixe'], $values['designation'], $values['description'], self::limit($login, 190), $now, self::limit($login, 190), $now)
            ));
            $id = $ok ? self::lastInsertId() : 0;
        }

        if ($ok) {
            self::ensureSequence($values['prefixe'], $login);
            self::log('categorie_enregistree', 'categorie', $id, $values['prefixe'].' - '.$values['designation'], $login);
            return array('ok' => true, 'id' => $id, 'errors' => array());
        }

        return array('ok' => false, 'errors' => array('Enregistrement de la categorie impossible.'));
    }

    public static function archiveCategory($id, $login)
    {
        self::ensureTables();
        $id = (int) $id;
        if ($id <= 0) {
            return false;
        }

        $ok = self::commandOk(grr_sql_command(
            "UPDATE ".self::table(self::TABLE_CATEGORY)." SET actif = 0, updated_by = ?, updated_at = ? WHERE id = ?",
            "sii",
            array(self::limit($login, 190), time(), $id)
        ));
        if ($ok) {
            self::log('categorie_archivee', 'categorie', $id, '', $login);
        }

        return $ok;
    }

    public static function deleteCategory($id, $login)
    {
        self::ensureTables();
        $id = (int) $id;
        if ($id <= 0) {
            return array('ok' => false, 'errors' => array('Categorie introuvable.'));
        }
        if ((int) grr_sql_query1(
            "SELECT COUNT(*) FROM ".self::table(self::TABLE_ITEM)." WHERE categorie_id = ?",
            "i",
            array($id)
        ) > 0) {
            return array('ok' => false, 'errors' => array('Suppression impossible : des materiels utilisent cette categorie.'));
        }

        $ok = self::commandOk(grr_sql_command(
            "DELETE FROM ".self::table(self::TABLE_CATEGORY)." WHERE id = ?",
            "i",
            array($id)
        ));
        if ($ok) {
            self::log('categorie_supprimee', 'categorie', $id, '', $login);
            return array('ok' => true, 'errors' => array());
        }

        return array('ok' => false, 'errors' => array('Suppression de la categorie impossible.'));
    }

    public static function people($includeArchived = false)
    {
        self::ensureTables();
        $where = $includeArchived ? '' : ' WHERE pe.actif = 1';
        return self::rows(
            "SELECT pe.*,
                (
                    SELECT COUNT(*)
                    FROM ".self::table(self::TABLE_LOAN)." p
                    WHERE p.personne_id = pe.id AND p.statut = 'ouvert'
                ) AS open_loan_count
            FROM ".self::table(self::TABLE_PERSON)." pe"
            .$where."
            ORDER BY pe.actif DESC, pe.nom, pe.prenom, pe.id"
        );
    }

    public static function peopleForAssociations($includeAssociated = false)
    {
        self::ensureTables();
        $where = "WHERE actif = 1";
        if (!$includeAssociated) {
            $where .= " AND login_grr = ''";
        }

        return self::rows(
            "SELECT *
            FROM ".self::table(self::TABLE_PERSON)."
            ".$where."
            ORDER BY CASE WHEN login_grr = '' THEN 0 ELSE 1 END, nom, prenom, id"
        );
    }

    public static function person($id)
    {
        self::ensureTables();
        return self::one(
            "SELECT pe.*,
                (
                    SELECT COUNT(*)
                    FROM ".self::table(self::TABLE_LOAN)." p
                    WHERE p.personne_id = pe.id AND p.statut = 'ouvert'
                ) AS open_loan_count
            FROM ".self::table(self::TABLE_PERSON)." pe
            WHERE pe.id = ?",
            "i",
            array((int) $id)
        );
    }

    public static function emptyPersonValues()
    {
        return array(
            'id' => 0,
            'legacy_id' => 0,
            'identifiant_legacy' => '',
            'prenom' => '',
            'nom' => '',
            'cadre_usage' => '',
            'date_depart' => '',
            'login_grr' => '',
            'email' => '',
            'notes' => '',
        );
    }

    public static function normalizePersonValues($source)
    {
        $date = self::nullableDate(isset($source['date_depart']) ? $source['date_depart'] : '');
        return array(
            'id' => isset($source['id']) ? (int) $source['id'] : 0,
            'legacy_id' => isset($source['legacy_id']) ? max(0, (int) $source['legacy_id']) : 0,
            'identifiant_legacy' => self::limit(isset($source['identifiant_legacy']) ? $source['identifiant_legacy'] : '', 100),
            'prenom' => self::limit(isset($source['prenom']) ? $source['prenom'] : '', 100),
            'nom' => self::limit(isset($source['nom']) ? $source['nom'] : '', 100),
            'cadre_usage' => self::limit(isset($source['cadre_usage']) ? $source['cadre_usage'] : '', 100),
            'date_depart' => $date,
            'login_grr' => self::limit(isset($source['login_grr']) ? $source['login_grr'] : '', 190),
            'email' => self::limit(isset($source['email']) ? $source['email'] : '', 190),
            'notes' => self::limit(isset($source['notes']) ? $source['notes'] : '', 2000),
        );
    }

    public static function validatePersonValues($values)
    {
        $errors = array();
        if ($values['prenom'] === '') {
            $errors[] = 'Le prenom est obligatoire.';
        }
        if ($values['nom'] === '') {
            $errors[] = 'Le nom est obligatoire.';
        }
        if (isset($values['date_depart']) && $values['date_depart'] === false) {
            $errors[] = 'La date de depart doit etre au format AAAA-MM-JJ.';
        }
        if ($values['identifiant_legacy'] !== '' && !self::legacyPersonIdentifierAvailable($values['identifiant_legacy'], (int) $values['id'])) {
            $errors[] = 'Cet identifiant personnel est deja utilise.';
        }
        if ($values['login_grr'] !== '' && !self::activeLoginExists($values['login_grr'])) {
            $errors[] = 'Le compte associe est introuvable dans GRR ou LDAP.';
        }
        if ($values['email'] !== '' && filter_var($values['email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = 'L email doit etre valide.';
        }

        return $errors;
    }

    public static function savePerson($source, $login)
    {
        self::ensureTables();
        $values = self::normalizePersonValues($source);
        $errors = self::validatePersonValues($values);
        if (count($errors) > 0) {
            return array('ok' => false, 'errors' => $errors);
        }

        $now = time();
        $legacyIdentifier = $values['identifiant_legacy'] === '' ? null : $values['identifiant_legacy'];
        $dateDepart = $values['date_depart'] === null ? null : $values['date_depart'];
        if ((int) $values['id'] > 0) {
            $ok = self::commandOk(grr_sql_command(
                "UPDATE ".self::table(self::TABLE_PERSON)."
                SET legacy_id = ?, identifiant_legacy = ?, prenom = ?, nom = ?, cadre_usage = ?,
                    date_depart = ?, login_grr = ?, email = ?, notes = ?, updated_by = ?, updated_at = ?
                WHERE id = ?",
                "isssssssssii",
                array(
                    (int) $values['legacy_id'],
                    $legacyIdentifier,
                    $values['prenom'],
                    $values['nom'],
                    $values['cadre_usage'],
                    $dateDepart,
                    $values['login_grr'],
                    $values['email'],
                    $values['notes'],
                    self::limit($login, 190),
                    $now,
                    (int) $values['id'],
                )
            ));
            $id = (int) $values['id'];
        } else {
            $ok = self::commandOk(grr_sql_command(
                "INSERT INTO ".self::table(self::TABLE_PERSON)."
                    (legacy_id, identifiant_legacy, prenom, nom, cadre_usage, date_depart, login_grr, email, notes,
                     created_by, created_at, updated_by, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                "isssssssssisi",
                array(
                    (int) $values['legacy_id'],
                    $legacyIdentifier,
                    $values['prenom'],
                    $values['nom'],
                    $values['cadre_usage'],
                    $dateDepart,
                    $values['login_grr'],
                    $values['email'],
                    $values['notes'],
                    self::limit($login, 190),
                    $now,
                    self::limit($login, 190),
                    $now,
                )
            ));
            $id = $ok ? self::lastInsertId() : 0;
        }

        if ($ok) {
            self::log('personne_enregistree', 'personne', $id, $values['prenom'].' '.$values['nom'], $login);
            return array('ok' => true, 'id' => $id, 'errors' => array());
        }

        return array('ok' => false, 'errors' => array('Enregistrement de la personne impossible.'));
    }

    public static function archivePerson($id, $login)
    {
        self::ensureTables();
        $id = (int) $id;
        if ($id <= 0) {
            return false;
        }

        $ok = self::commandOk(grr_sql_command(
            "UPDATE ".self::table(self::TABLE_PERSON)." SET actif = 0, updated_by = ?, updated_at = ? WHERE id = ?",
            "sii",
            array(self::limit($login, 190), time(), $id)
        ));
        if ($ok) {
            self::log('personne_archivee', 'personne', $id, '', $login);
        }

        return $ok;
    }

    public static function deletePerson($id, $login)
    {
        self::ensureTables();
        $id = (int) $id;
        if ($id <= 0) {
            return array('ok' => false, 'errors' => array('Personne introuvable.'));
        }
        if ((int) grr_sql_query1(
            "SELECT COUNT(*) FROM ".self::table(self::TABLE_LOAN)." WHERE personne_id = ?",
            "i",
            array($id)
        ) > 0) {
            return array('ok' => false, 'errors' => array('Suppression impossible : des prets sont lies a cette personne.'));
        }
        if (self::tableExists(self::TABLE_LOAN_CONFLICT) && (int) grr_sql_query1(
            "SELECT COUNT(*) FROM ".self::table(self::TABLE_LOAN_CONFLICT)." WHERE personne_id = ?",
            "i",
            array($id)
        ) > 0) {
            return array('ok' => false, 'errors' => array('Suppression impossible : des conflits sont lies a cette personne.'));
        }

        $ok = self::commandOk(grr_sql_command(
            "DELETE FROM ".self::table(self::TABLE_PERSON)." WHERE id = ?",
            "i",
            array($id)
        ));
        if ($ok) {
            self::log('personne_supprimee', 'personne', $id, '', $login);
            return array('ok' => true, 'errors' => array());
        }

        return array('ok' => false, 'errors' => array('Suppression de la personne impossible.'));
    }

    public static function savePersonAssociations($source, $login)
    {
        self::ensureTables();
        $login = self::limit($login, 190);
        $loginValues = isset($source['login_grr']) && is_array($source['login_grr']) ? $source['login_grr'] : array();
        $scope = isset($source['association_scope']) ? (string) $source['association_scope'] : 'selected';
        $ids = array();

        if (preg_match('/^one:([0-9]+)$/', $scope, $matches) === 1) {
            $ids[] = (int) $matches[1];
        } elseif ($scope === 'all') {
            foreach (array_keys($loginValues) as $id) {
                $ids[] = (int) $id;
            }
        } else {
            $selected = isset($source['selected_people']) && is_array($source['selected_people'])
                ? $source['selected_people']
                : array();
            foreach ($selected as $id) {
                $ids[] = (int) $id;
            }
        }

        $cleanIds = array();
        foreach ($ids as $id) {
            if ($id > 0) {
                $cleanIds[$id] = $id;
            }
        }
        $ids = array_values($cleanIds);
        if (count($ids) === 0) {
            return array('ok' => false, 'updated' => 0, 'errors' => array('Aucune personne selectionnee.'));
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $people = self::rows(
            "SELECT id, prenom, nom, email, login_grr
            FROM ".self::table(self::TABLE_PERSON)."
            WHERE actif = 1 AND id IN (".$placeholders.")",
            str_repeat('i', count($ids)),
            $ids
        );
        $peopleById = array();
        foreach ($people as $person) {
            $peopleById[(int) $person['id']] = $person;
        }

        $changes = array();
        $errors = array();
        foreach ($ids as $id) {
            if (!isset($peopleById[$id])) {
                $errors[] = 'Personne #'.$id.' introuvable ou inactive.';
                continue;
            }
            $newLogin = self::limit(isset($loginValues[$id]) ? $loginValues[$id] : '', 190);
            if ($newLogin !== '' && !self::activeLoginExists($newLogin)) {
                $label = trim((string) $peopleById[$id]['prenom'].' '.(string) $peopleById[$id]['nom']);
                $errors[] = 'Association impossible pour '.$label.' : compte GRR ou LDAP introuvable.';
                continue;
            }
            $oldLogin = isset($peopleById[$id]['login_grr']) ? (string) $peopleById[$id]['login_grr'] : '';
            $oldEmail = isset($peopleById[$id]['email']) ? (string) $peopleById[$id]['email'] : '';
            $newEmail = $oldEmail;
            if ($oldEmail === '' && $newLogin !== '') {
                $ldapEmail = self::emailForDirectoryLogin($newLogin);
                if ($ldapEmail !== '') {
                    $newEmail = $ldapEmail;
                }
            }
            if ($newLogin !== $oldLogin || $newEmail !== $oldEmail) {
                $changes[$id] = array(
                    'old' => $oldLogin,
                    'new' => $newLogin,
                    'old_email' => $oldEmail,
                    'new_email' => $newEmail,
                    'person' => $peopleById[$id],
                );
            }
        }

        if (count($errors) > 0) {
            return array('ok' => false, 'updated' => 0, 'errors' => $errors);
        }

        $updated = 0;
        $now = time();
        foreach ($changes as $id => $change) {
            $ok = self::commandOk(grr_sql_command(
                "UPDATE ".self::table(self::TABLE_PERSON)."
                SET login_grr = ?, email = ?, updated_by = ?, updated_at = ?
                WHERE id = ?",
                "sssii",
                array($change['new'], $change['new_email'], $login, $now, (int) $id)
            ));
            if ($ok) {
                $updated++;
                $detail = $change['old'].' => '.$change['new'];
                if ($change['old_email'] === '' && $change['new_email'] !== '') {
                    $detail .= ' ; email '.$change['new_email'];
                }
                self::log('personne_login_associe', 'personne', (int) $id, $detail, $login);
            }
        }

        return array('ok' => true, 'updated' => $updated, 'errors' => array());
    }

    public static function createPersonFromLdap($source, $login)
    {
        self::ensureTables();
        $ldapLogin = self::limit(isset($source['ldap_login']) ? $source['ldap_login'] : '', 190);
        if ($ldapLogin === '') {
            return array('ok' => false, 'errors' => array('Selectionnez un compte LDAP.'));
        }
        if (!class_exists('InformatiqueMaterielLdapDirectory')) {
            return array('ok' => false, 'errors' => array('Recherche LDAP indisponible.'));
        }

        $existing = self::peopleForLogin($ldapLogin);
        if (count($existing) > 0) {
            return array('ok' => false, 'errors' => array('Une personne est deja associee a ce login.'));
        }

        $suggestion = InformatiqueMaterielLdapDirectory::suggestionForLogin($ldapLogin);
        if (!$suggestion || !isset($suggestion['login'])) {
            return array('ok' => false, 'errors' => array('Compte LDAP introuvable.'));
        }

        $prenom = self::limit(isset($suggestion['prenom']) ? $suggestion['prenom'] : '', 100);
        $nom = self::limit(isset($suggestion['nom']) ? $suggestion['nom'] : '', 100);
        if ($prenom === '') {
            $prenom = $ldapLogin;
        }
        if ($nom === '') {
            $nom = $ldapLogin;
        }

        return self::savePerson(
            array(
                'id' => 0,
                'legacy_id' => 0,
                'identifiant_legacy' => '',
                'prenom' => $prenom,
                'nom' => $nom,
                'cadre_usage' => '',
                'date_depart' => '',
                'login_grr' => $ldapLogin,
                'email' => isset($suggestion['email']) ? $suggestion['email'] : '',
                'notes' => 'Cree depuis LDAP',
            ),
            $login
        );
    }

    public static function items($includeArchived = false, $filters = array())
    {
        self::ensureTables();
        $conditions = array();
        $types = '';
        $params = array();

        if (!$includeArchived) {
            $conditions[] = 'i.actif = 1';
        }

        $query = self::limit(isset($filters['q']) ? $filters['q'] : '', 100);
        if ($query !== '') {
            $like = '%'.$query.'%';
            $conditions[] = "(i.identifiant LIKE ? OR i.designation LIKE ? OR i.precision_materiel LIKE ?
                OR i.marque LIKE ? OR i.numero_serie LIKE ? OR i.code_barre_usmb LIKE ?)";
            $types .= 'ssssss';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $categoryId = isset($filters['categorie_id']) ? (int) $filters['categorie_id'] : 0;
        if ($categoryId > 0) {
            $conditions[] = 'i.categorie_id = ?';
            $types .= 'i';
            $params[] = $categoryId;
        }

        $status = self::limit(isset($filters['statut']) ? $filters['statut'] : '', 30);
        $statuses = self::itemStatuses();
        if ($status !== '' && isset($statuses[$status])) {
            $conditions[] = 'i.statut = ?';
            $types .= 's';
            $params[] = $status;
        }

        $where = count($conditions) > 0 ? ' WHERE '.implode(' AND ', $conditions) : '';
        return self::rows(
            "SELECT i.*, c.prefixe AS categorie_prefixe, c.designation AS categorie_designation,
                (
                    SELECT COUNT(*)
                    FROM ".self::table(self::TABLE_LOAN)." p
                    WHERE p.item_id = i.id AND p.statut = 'ouvert'
                ) AS open_loan_count
            FROM ".self::table(self::TABLE_ITEM)." i
            LEFT JOIN ".self::table(self::TABLE_CATEGORY)." c ON c.id = i.categorie_id"
            .$where."
            ORDER BY i.actif DESC, i.identifiant, i.designation, i.id",
            $types === '' ? null : $types,
            count($params) === 0 ? null : $params
        );
    }

    public static function item($id)
    {
        self::ensureTables();
        return self::one(
            "SELECT i.*, c.prefixe AS categorie_prefixe, c.designation AS categorie_designation,
                (
                    SELECT COUNT(*)
                    FROM ".self::table(self::TABLE_LOAN)." p
                    WHERE p.item_id = i.id AND p.statut = 'ouvert'
                ) AS open_loan_count
            FROM ".self::table(self::TABLE_ITEM)." i
            LEFT JOIN ".self::table(self::TABLE_CATEGORY)." c ON c.id = i.categorie_id
            WHERE i.id = ?",
            "i",
            array((int) $id)
        );
    }

    public static function emptyItemValues()
    {
        return array(
            'id' => 0,
            'identifiant' => '',
            'identifiant_legacy' => '',
            'categorie_id' => 0,
            'designation' => '',
            'precision_materiel' => '',
            'mac' => '',
            'marque' => '',
            'numero_serie' => '',
            'code_barre_usmb' => '',
            'os' => '',
            'annee' => '',
            'commentaire' => '',
            'localisation_stockage' => '',
            'statut' => 'actif',
            'pret_multiple' => 0,
            'notes' => '',
            'actif' => 1,
        );
    }

    public static function normalizeItemValues($source)
    {
        return array(
            'id' => isset($source['id']) ? (int) $source['id'] : 0,
            'identifiant' => self::limit(isset($source['identifiant']) ? $source['identifiant'] : '', 100),
            'identifiant_legacy' => self::limit(isset($source['identifiant_legacy']) ? $source['identifiant_legacy'] : '', 100),
            'categorie_id' => isset($source['categorie_id']) ? (int) $source['categorie_id'] : 0,
            'designation' => self::limit(isset($source['designation']) ? $source['designation'] : '', 190),
            'precision_materiel' => self::limit(isset($source['precision_materiel']) ? $source['precision_materiel'] : '', 190),
            'mac' => self::limit(isset($source['mac']) ? $source['mac'] : '', 100),
            'marque' => self::limit(isset($source['marque']) ? $source['marque'] : '', 100),
            'numero_serie' => self::limit(isset($source['numero_serie']) ? $source['numero_serie'] : '', 190),
            'code_barre_usmb' => self::limit(isset($source['code_barre_usmb']) ? $source['code_barre_usmb'] : '', 100),
            'os' => self::limit(isset($source['os']) ? $source['os'] : '', 100),
            'annee' => self::limit(isset($source['annee']) ? $source['annee'] : '', 20),
            'commentaire' => self::limit(isset($source['commentaire']) ? $source['commentaire'] : '', 4000),
            'localisation_stockage' => self::limit(isset($source['localisation_stockage']) ? $source['localisation_stockage'] : '', 190),
            'statut' => self::limit(isset($source['statut']) ? $source['statut'] : 'actif', 30),
            'pret_multiple' => self::normalizeBool(isset($source['pret_multiple']) ? $source['pret_multiple'] : 0),
            'notes' => self::limit(isset($source['notes']) ? $source['notes'] : '', 2000),
        );
    }

    public static function validateItemValues($values)
    {
        $errors = array();
        if ((int) $values['categorie_id'] <= 0 || !self::activeCategoryExists((int) $values['categorie_id'])) {
            $errors[] = 'La categorie du materiel est obligatoire.';
        }
        if ($values['designation'] === '') {
            $errors[] = 'La designation du materiel est obligatoire.';
        }
        if ((int) $values['id'] > 0 && $values['identifiant'] === '') {
            $errors[] = 'L identifiant est obligatoire pour modifier un materiel existant.';
        }
        if ($values['identifiant'] !== '' && !self::itemIdentifierAvailable($values['identifiant'], (int) $values['id'])) {
            $errors[] = 'Cet identifiant materiel est deja utilise.';
        }
        if ($values['identifiant_legacy'] !== '' && !self::legacyItemIdentifierAvailable($values['identifiant_legacy'], (int) $values['id'])) {
            $errors[] = 'Cet identifiant historique est deja utilise.';
        }
        if ($values['code_barre_usmb'] !== '' && !self::itemBarcodeAvailable($values['code_barre_usmb'], (int) $values['id'])) {
            $errors[] = 'Ce code-barres USMB est deja utilise.';
        }
        if ($values['annee'] !== '' && preg_match('/^\d{4}$/', $values['annee']) !== 1) {
            $errors[] = 'L annee doit etre au format AAAA.';
        }
        $statuses = self::itemStatuses();
        if (!isset($statuses[$values['statut']])) {
            $errors[] = 'Le statut du materiel est invalide.';
        }
        $openLoanCount = (int) $values['id'] > 0 ? self::openLoanCountForItem((int) $values['id']) : 0;
        if ((int) $values['id'] > 0 && $openLoanCount > 0 && !in_array($values['statut'], array('en_pret', 'pret_multiple'), true)) {
            $errors[] = 'Un materiel avec un pret ouvert doit conserver un statut de pret.';
        }
        if ((int) $values['pret_multiple'] !== 1 && $openLoanCount > 1) {
            $errors[] = 'Impossible de desactiver le pret multiple tant que plusieurs prets sont ouverts.';
        }
        if ($values['statut'] === 'pret_multiple' && ((int) $values['pret_multiple'] !== 1 || $openLoanCount < 2)) {
            $errors[] = 'Le statut pret multiple est reserve aux materiels generiques avec plusieurs prets ouverts.';
        }

        return $errors;
    }

    public static function saveItem($source, $login)
    {
        self::ensureTables();
        $values = self::normalizeItemValues($source);
        if ((int) $values['id'] > 0) {
            $openLoanCount = self::openLoanCountForItem((int) $values['id']);
            if ($openLoanCount > 0) {
                $values['statut'] = self::loanDrivenItemStatus($openLoanCount, (int) $values['pret_multiple'] === 1);
            }
        }
        $errors = self::validateItemValues($values);
        if (count($errors) > 0) {
            return array('ok' => false, 'errors' => $errors);
        }

        if ((int) $values['id'] <= 0 && $values['identifiant'] === '') {
            $values['identifiant'] = self::generateItemIdentifier((int) $values['categorie_id'], $login);
            if ($values['identifiant'] === '') {
                return array('ok' => false, 'errors' => array('Generation de l identifiant materiel impossible.'));
            }
            if (!self::itemIdentifierAvailable($values['identifiant'], 0)) {
                return array('ok' => false, 'errors' => array('L identifiant genere est deja utilise.'));
            }
        }

        $now = time();
        $login = self::limit($login, 190);
        $legacyIdentifier = $values['identifiant_legacy'] === '' ? null : $values['identifiant_legacy'];
        $barcode = $values['code_barre_usmb'] === '' ? null : $values['code_barre_usmb'];

        if ((int) $values['id'] > 0) {
            $ok = self::commandOk(grr_sql_command(
                "UPDATE ".self::table(self::TABLE_ITEM)."
                SET identifiant = ?, identifiant_legacy = ?, categorie_id = ?, designation = ?,
                    precision_materiel = ?, mac = ?, marque = ?, numero_serie = ?,
                    code_barre_usmb = ?, os = ?, annee = ?, commentaire = ?,
                    localisation_stockage = ?, statut = ?, pret_multiple = ?, notes = ?, updated_by = ?, updated_at = ?
                WHERE id = ?",
                "ssi".str_repeat("s", 11)."issii",
                array(
                    $values['identifiant'],
                    $legacyIdentifier,
                    (int) $values['categorie_id'],
                    $values['designation'],
                    $values['precision_materiel'],
                    $values['mac'],
                    $values['marque'],
                    $values['numero_serie'],
                    $barcode,
                    $values['os'],
                    $values['annee'],
                    $values['commentaire'],
                    $values['localisation_stockage'],
                    $values['statut'],
                    (int) $values['pret_multiple'],
                    $values['notes'],
                    $login,
                    $now,
                    (int) $values['id'],
                )
            ));
            $id = (int) $values['id'];
        } else {
            $ok = self::commandOk(grr_sql_command(
                "INSERT INTO ".self::table(self::TABLE_ITEM)."
                    (identifiant, identifiant_legacy, categorie_id, designation, precision_materiel,
                     mac, marque, numero_serie, code_barre_usmb, os, annee, commentaire,
                     localisation_stockage, statut, pret_multiple, notes, created_by, created_at, updated_by, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                "ssi".str_repeat("s", 11)."issisi",
                array(
                    $values['identifiant'],
                    $legacyIdentifier,
                    (int) $values['categorie_id'],
                    $values['designation'],
                    $values['precision_materiel'],
                    $values['mac'],
                    $values['marque'],
                    $values['numero_serie'],
                    $barcode,
                    $values['os'],
                    $values['annee'],
                    $values['commentaire'],
                    $values['localisation_stockage'],
                    $values['statut'],
                    (int) $values['pret_multiple'],
                    $values['notes'],
                    $login,
                    $now,
                    $login,
                    $now,
                )
            ));
            $id = $ok ? self::lastInsertId() : 0;
        }

        if ($ok) {
            self::log('materiel_enregistre', 'materiel', $id, $values['identifiant'].' - '.$values['designation'], $login);
            return array('ok' => true, 'id' => $id, 'errors' => array());
        }

        return array('ok' => false, 'errors' => array('Enregistrement du materiel impossible.'));
    }

    public static function archiveItem($id, $login)
    {
        self::ensureTables();
        $id = (int) $id;
        if ($id <= 0) {
            return false;
        }
        if (self::openLoanForItem($id, 0, false)) {
            return false;
        }

        $ok = self::commandOk(grr_sql_command(
            "UPDATE ".self::table(self::TABLE_ITEM)." SET actif = 0, statut = 'archive', updated_by = ?, updated_at = ? WHERE id = ?",
            "sii",
            array(self::limit($login, 190), time(), $id)
        ));
        if ($ok) {
            self::log('materiel_archive', 'materiel', $id, '', $login);
        }

        return $ok;
    }

    public static function deleteItem($id, $login)
    {
        self::ensureTables();
        $id = (int) $id;
        if ($id <= 0) {
            return array('ok' => false, 'errors' => array('Materiel introuvable.'));
        }
        if ((int) grr_sql_query1(
            "SELECT COUNT(*) FROM ".self::table(self::TABLE_LOAN)." WHERE item_id = ?",
            "i",
            array($id)
        ) > 0) {
            return array('ok' => false, 'errors' => array('Suppression impossible : des prets sont lies a ce materiel.'));
        }
        if (self::tableExists(self::TABLE_DOCUMENT) && (int) grr_sql_query1(
            "SELECT COUNT(*) FROM ".self::table(self::TABLE_DOCUMENT)." WHERE item_id = ?",
            "i",
            array($id)
        ) > 0) {
            return array('ok' => false, 'errors' => array('Suppression impossible : des documents sont lies a ce materiel.'));
        }
        if (self::tableExists(self::TABLE_LOAN_CONFLICT) && (int) grr_sql_query1(
            "SELECT COUNT(*) FROM ".self::table(self::TABLE_LOAN_CONFLICT)." WHERE item_id = ?",
            "i",
            array($id)
        ) > 0) {
            return array('ok' => false, 'errors' => array('Suppression impossible : des conflits sont lies a ce materiel.'));
        }

        $ok = self::commandOk(grr_sql_command(
            "DELETE FROM ".self::table(self::TABLE_ITEM)." WHERE id = ?",
            "i",
            array($id)
        ));
        if ($ok) {
            self::log('materiel_supprime', 'materiel', $id, '', $login);
            return array('ok' => true, 'errors' => array());
        }

        return array('ok' => false, 'errors' => array('Suppression du materiel impossible.'));
    }

    public static function itemDiagnostics()
    {
        self::ensureTables();
        if (!self::tableExists(self::TABLE_ITEM)) {
            return array(
                'sans_categorie' => 0,
                'mac_dupliquees' => 0,
                'numeros_serie_dupliques' => 0,
                'codes_barres_dupliques' => 0,
            );
        }

        return array(
            'sans_categorie' => (int) grr_sql_query1(
                "SELECT COUNT(*)
                FROM ".self::table(self::TABLE_ITEM)." i
                LEFT JOIN ".self::table(self::TABLE_CATEGORY)." c ON c.id = i.categorie_id
                WHERE i.actif = 1 AND c.id IS NULL"
            ),
            'mac_dupliquees' => self::countDuplicateGroups(self::TABLE_ITEM, 'mac'),
            'numeros_serie_dupliques' => self::countDuplicateGroups(self::TABLE_ITEM, 'numero_serie'),
            'codes_barres_dupliques' => self::countDuplicateGroups(self::TABLE_ITEM, 'code_barre_usmb'),
        );
    }

    public static function loans($includeClosed = false, $filters = array())
    {
        self::ensureTables();
        $conditions = array();
        $types = '';
        $params = array();

        if (!$includeClosed) {
            $conditions[] = "p.statut = 'ouvert'";
        }

        $query = self::limit(isset($filters['q']) ? $filters['q'] : '', 100);
        if ($query !== '') {
            $like = '%'.$query.'%';
            $conditions[] = "(p.localisation LIKE ? OR i.identifiant LIKE ? OR i.designation LIKE ?
                OR pe.nom LIKE ? OR pe.prenom LIKE ?)";
            $types .= 'sssss';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $status = self::limit(isset($filters['statut']) ? $filters['statut'] : '', 20);
        $statuses = self::loanStatuses();
        if ($status !== '' && isset($statuses[$status])) {
            $conditions[] = 'p.statut = ?';
            $types .= 's';
            $params[] = $status;
        }

        $itemId = isset($filters['item_id']) ? (int) $filters['item_id'] : 0;
        if ($itemId > 0) {
            $conditions[] = 'p.item_id = ?';
            $types .= 'i';
            $params[] = $itemId;
        }

        $personId = isset($filters['personne_id']) ? (int) $filters['personne_id'] : 0;
        if ($personId > 0) {
            $conditions[] = 'p.personne_id = ?';
            $types .= 'i';
            $params[] = $personId;
        }

        $where = count($conditions) > 0 ? ' WHERE '.implode(' AND ', $conditions) : '';
        return self::rows(
            self::loanSelectSql()
            .$where."
            ORDER BY CASE WHEN p.statut = 'ouvert' THEN 0 ELSE 1 END,
                p.date_fin_prevue IS NULL, p.date_fin_prevue, p.date_debut DESC, p.id DESC",
            $types === '' ? null : $types,
            count($params) === 0 ? null : $params
        );
    }

    public static function loan($id)
    {
        self::ensureTables();
        return self::one(
            self::loanSelectSql()." WHERE p.id = ?",
            "i",
            array((int) $id)
        );
    }

    public static function loansForItem($itemId)
    {
        return self::loans(true, array('item_id' => (int) $itemId));
    }

    public static function openLoansForItem($itemId)
    {
        return self::loans(false, array('item_id' => (int) $itemId));
    }

    public static function loansForPerson($personId)
    {
        return self::loans(true, array('personne_id' => (int) $personId));
    }

    public static function openLoansForPerson($personId)
    {
        return self::loans(false, array('personne_id' => (int) $personId));
    }

    public static function peopleForLogin($login)
    {
        self::ensureTables();
        $login = self::limit($login, 190);
        if ($login === '') {
            return array();
        }

        return self::rows(
            "SELECT *
            FROM ".self::table(self::TABLE_PERSON)."
            WHERE LOWER(login_grr) = LOWER(?)
            ORDER BY actif DESC, nom, prenom, id",
            "s",
            array($login)
        );
    }

    public static function loansForLogin($login, $includeClosed = true)
    {
        self::ensureTables();
        $login = self::limit($login, 190);
        if ($login === '') {
            return array();
        }

        $status = $includeClosed ? '' : " AND p.statut = 'ouvert'";
        return self::rows(
            self::loanSelectSql()."
            WHERE LOWER(pe.login_grr) = LOWER(?)".$status."
            ORDER BY CASE WHEN p.statut = 'ouvert' THEN 0 ELSE 1 END,
                p.date_fin_prevue IS NULL, p.date_fin_prevue, p.date_debut DESC, p.id DESC",
            "s",
            array($login)
        );
    }

    public static function loginHasOpenEquipment($login)
    {
        $login = self::limit($login, 190);
        if ($login === '' || !self::tableExists(self::TABLE_LOAN) || !self::tableExists(self::TABLE_PERSON)) {
            return false;
        }

        $count = grr_sql_query1(
            "SELECT COUNT(*)
            FROM ".self::table(self::TABLE_LOAN)." p
            JOIN ".self::table(self::TABLE_PERSON)." pe ON pe.id = p.personne_id
            WHERE LOWER(pe.login_grr) = LOWER(?)
              AND pe.actif = 1
              AND p.statut = 'ouvert'",
            "s",
            array($login)
        );

        return (int) $count > 0;
    }

    public static function emptyLoanValues()
    {
        return array(
            'id' => 0,
            'personne_id' => 0,
            'item_id' => 0,
            'localisation' => '',
            'date_debut' => date('Y-m-d'),
            'date_fin_prevue' => '',
            'date_fin_effective' => '',
            'commentaire' => '',
            'statut' => 'ouvert',
        );
    }

    public static function normalizeLoanValues($source)
    {
        return array(
            'id' => isset($source['id']) ? (int) $source['id'] : 0,
            'personne_id' => isset($source['personne_id']) ? (int) $source['personne_id'] : 0,
            'item_id' => isset($source['item_id']) ? (int) $source['item_id'] : 0,
            'localisation' => self::limit(isset($source['localisation']) ? $source['localisation'] : '', 190),
            'date_debut' => self::nullableDate(isset($source['date_debut']) ? $source['date_debut'] : ''),
            'date_fin_prevue' => self::nullableDate(isset($source['date_fin_prevue']) ? $source['date_fin_prevue'] : ''),
            'date_fin_effective' => self::nullableDate(isset($source['date_fin_effective']) ? $source['date_fin_effective'] : ''),
            'commentaire' => self::limit(isset($source['commentaire']) ? $source['commentaire'] : '', 2000),
        );
    }

    private static function normalizeTransferValues($source)
    {
        return array(
            'pret_id' => isset($source['pret_id']) ? (int) $source['pret_id'] : (isset($source['id']) ? (int) $source['id'] : 0),
            'personne_id' => isset($source['personne_id']) ? (int) $source['personne_id'] : 0,
            'date_transfert' => self::nullableDate(isset($source['date_transfert']) ? $source['date_transfert'] : ''),
            'commentaire' => self::limit(isset($source['commentaire']) ? $source['commentaire'] : '', 2000),
        );
    }

    public static function createLoan($source, $login)
    {
        self::ensureTables();
        $values = self::normalizeLoanValues($source);
        $errors = self::validateLoanCreationValues($values);
        if (count($errors) > 0) {
            return array('ok' => false, 'errors' => $errors);
        }

        $login = self::limit($login, 190);
        $now = time();
        $dateFinPrevue = $values['date_fin_prevue'] === null ? null : $values['date_fin_prevue'];
        $transaction = self::beginTransaction();

        try {
            $item = self::itemForUpdate((int) $values['item_id']);
            $itemErrors = self::loanableItemErrors($item);
            if (count($itemErrors) > 0) {
                self::rollbackTransaction($transaction);
                return array('ok' => false, 'errors' => $itemErrors);
            }

            if (!self::isMultipleLoanItem($item) && self::openLoanForItem((int) $values['item_id'], 0, true)) {
                self::rollbackTransaction($transaction);
                return array('ok' => false, 'errors' => array('Ce materiel a deja un pret ouvert.'));
            }

            $ok = self::commandOk(grr_sql_command(
                "INSERT INTO ".self::table(self::TABLE_LOAN)."
                    (personne_id, item_id, localisation, date_debut, date_fin_prevue,
                     date_fin_effective, commentaire, statut, source_import_id,
                     created_by, created_at, updated_by, updated_at)
                VALUES (?, ?, ?, ?, ?, NULL, ?, 'ouvert', 0, ?, ?, ?, ?)",
                "iisssssisi",
                array(
                    (int) $values['personne_id'],
                    (int) $values['item_id'],
                    $values['localisation'],
                    $values['date_debut'],
                    $dateFinPrevue,
                    $values['commentaire'],
                    $login,
                    $now,
                    $login,
                    $now,
                )
            ));
            $id = $ok ? self::lastInsertId() : 0;

            if ($ok) {
                $ok = self::syncItemLoanStatus((int) $values['item_id'], $login, $now);
            }

            if (!$ok) {
                self::rollbackTransaction($transaction);
                return array('ok' => false, 'errors' => array('Creation du pret impossible.'));
            }

            self::log('pret_cree', 'pret', $id, 'Materiel '.$item['identifiant'], $login);
            self::commitTransaction($transaction);

            return array('ok' => true, 'id' => $id, 'errors' => array());
        } catch (Throwable $exception) {
            self::rollbackTransaction($transaction);
            return array('ok' => false, 'errors' => array('Creation du pret impossible.'));
        }
    }

    public static function closeLoan($source, $login)
    {
        self::ensureTables();
        $values = self::normalizeLoanValues($source);
        $errors = self::validateLoanCloseValues($values);
        if (count($errors) > 0) {
            return array('ok' => false, 'errors' => $errors);
        }

        $login = self::limit($login, 190);
        $now = time();
        $transaction = self::beginTransaction();

        try {
            $loan = self::loanForUpdate((int) $values['id']);
            if (!$loan) {
                self::rollbackTransaction($transaction);
                return array('ok' => false, 'errors' => array('Pret introuvable.'));
            }
            if ((string) $loan['statut'] !== 'ouvert') {
                self::rollbackTransaction($transaction);
                return array('ok' => false, 'errors' => array('Seul un pret ouvert peut etre restitue.'));
            }
            if ($values['date_fin_effective'] < $loan['date_debut']) {
                self::rollbackTransaction($transaction);
                return array('ok' => false, 'errors' => array('La date de retour ne peut pas preceder la date de debut.'));
            }

            $comment = self::mergeComment($loan['commentaire'], $values['commentaire'], 'Retour');
            $ok = self::commandOk(grr_sql_command(
                "UPDATE ".self::table(self::TABLE_LOAN)."
                SET date_fin_effective = ?, commentaire = ?, statut = 'clos',
                    updated_by = ?, updated_at = ?
                WHERE id = ? AND statut = 'ouvert'",
                "sssii",
                array($values['date_fin_effective'], $comment, $login, $now, (int) $values['id'])
            ));

            if ($ok) {
                $ok = self::syncItemLoanStatus((int) $loan['item_id'], $login, $now);
            }

            if (!$ok) {
                self::rollbackTransaction($transaction);
                return array('ok' => false, 'errors' => array('Restitution du pret impossible.'));
            }

            self::log('pret_restitue', 'pret', (int) $loan['id'], 'Materiel '.$loan['item_identifiant'], $login);
            self::commitTransaction($transaction);

            return array('ok' => true, 'id' => (int) $loan['id'], 'errors' => array());
        } catch (Throwable $exception) {
            self::rollbackTransaction($transaction);
            return array('ok' => false, 'errors' => array('Restitution du pret impossible.'));
        }
    }

    public static function cancelLoan($source, $login)
    {
        self::ensureTables();
        $id = isset($source['id']) ? (int) $source['id'] : 0;
        $commentaire = self::limit(isset($source['commentaire']) ? $source['commentaire'] : '', 2000);
        if ($id <= 0) {
            return array('ok' => false, 'errors' => array('Pret introuvable.'));
        }

        $login = self::limit($login, 190);
        $now = time();
        $transaction = self::beginTransaction();

        try {
            $loan = self::loanForUpdate($id);
            if (!$loan) {
                self::rollbackTransaction($transaction);
                return array('ok' => false, 'errors' => array('Pret introuvable.'));
            }
            if ((string) $loan['statut'] === 'annule') {
                self::rollbackTransaction($transaction);
                return array('ok' => false, 'errors' => array('Ce pret est deja annule.'));
            }

            $wasOpen = (string) $loan['statut'] === 'ouvert';
            $comment = self::mergeComment($loan['commentaire'], $commentaire, 'Annulation');
            $ok = self::commandOk(grr_sql_command(
                "UPDATE ".self::table(self::TABLE_LOAN)."
                SET commentaire = ?, statut = 'annule', updated_by = ?, updated_at = ?
                WHERE id = ?",
                "ssii",
                array($comment, $login, $now, $id)
            ));

            if ($ok && $wasOpen) {
                $ok = self::syncItemLoanStatus((int) $loan['item_id'], $login, $now);
            }

            if (!$ok) {
                self::rollbackTransaction($transaction);
                return array('ok' => false, 'errors' => array('Annulation du pret impossible.'));
            }

            self::log('pret_annule', 'pret', (int) $loan['id'], 'Materiel '.$loan['item_identifiant'], $login);
            self::commitTransaction($transaction);

            return array('ok' => true, 'id' => (int) $loan['id'], 'errors' => array());
        } catch (Throwable $exception) {
            self::rollbackTransaction($transaction);
            return array('ok' => false, 'errors' => array('Annulation du pret impossible.'));
        }
    }

    public static function deleteLoan($id, $login)
    {
        self::ensureTables();
        $id = (int) $id;
        if ($id <= 0) {
            return array('ok' => false, 'errors' => array('Pret introuvable.'));
        }
        if (self::tableExists(self::TABLE_LOAN_CONFLICT) && (int) grr_sql_query1(
            "SELECT COUNT(*) FROM ".self::table(self::TABLE_LOAN_CONFLICT)." WHERE pret_existant_id = ?",
            "i",
            array($id)
        ) > 0) {
            return array('ok' => false, 'errors' => array('Suppression impossible : ce pret est reference par un conflit.'));
        }

        $login = self::limit($login, 190);
        $now = time();
        $transaction = self::beginTransaction();

        try {
            $loan = self::loanForUpdate($id);
            if (!$loan) {
                self::rollbackTransaction($transaction);
                return array('ok' => false, 'errors' => array('Pret introuvable.'));
            }
            $wasOpen = (string) $loan['statut'] === 'ouvert';
            $itemId = (int) $loan['item_id'];
            $ok = self::commandOk(grr_sql_command(
                "DELETE FROM ".self::table(self::TABLE_LOAN)." WHERE id = ?",
                "i",
                array($id)
            ));

            if ($ok && $wasOpen) {
                $ok = self::syncItemLoanStatus($itemId, $login, $now);
            }

            if (!$ok) {
                self::rollbackTransaction($transaction);
                return array('ok' => false, 'errors' => array('Suppression du pret impossible.'));
            }

            self::log('pret_supprime', 'pret', $id, 'Materiel '.$loan['item_identifiant'], $login);
            self::commitTransaction($transaction);
            return array('ok' => true, 'errors' => array());
        } catch (Throwable $exception) {
            self::rollbackTransaction($transaction);
            return array('ok' => false, 'errors' => array('Suppression du pret impossible.'));
        }
    }

    public static function transferLoan($source, $login)
    {
        self::ensureTables();
        $values = self::normalizeTransferValues($source);
        $errors = self::validateTransferBaseValues($values);
        if ((int) $values['pret_id'] <= 0) {
            $errors[] = 'Pret a transferer introuvable.';
        }
        if (count($errors) > 0) {
            return array('ok' => false, 'errors' => $errors);
        }

        $login = self::limit($login, 190);
        $now = time();
        $transaction = self::beginTransaction();

        try {
            $loan = self::loanForUpdate((int) $values['pret_id']);
            $targetPerson = self::personForUpdate((int) $values['personne_id']);
            $errors = self::validateTransferLoan($loan, $targetPerson, $values);
            if (count($errors) > 0) {
                self::rollbackTransaction($transaction);
                return array('ok' => false, 'errors' => $errors);
            }

            $newLoanId = self::transferLockedLoan($loan, $targetPerson, $values, $login, $now);
            if ($newLoanId <= 0) {
                self::rollbackTransaction($transaction);
                return array('ok' => false, 'errors' => array('Transfert du pret impossible.'));
            }

            self::commitTransaction($transaction);
            return array('ok' => true, 'id' => $newLoanId, 'count' => 1, 'errors' => array());
        } catch (Throwable $exception) {
            self::rollbackTransaction($transaction);
            return array('ok' => false, 'errors' => array('Transfert du pret impossible.'));
        }
    }

    public static function transferPersonOpenLoans($source, $login)
    {
        self::ensureTables();
        $values = self::normalizeTransferValues($source);
        $sourcePersonId = isset($source['source_personne_id']) ? (int) $source['source_personne_id'] : 0;
        $errors = self::validateTransferBaseValues($values);
        if ($sourcePersonId <= 0) {
            $errors[] = 'Personne source introuvable.';
        }
        if ($sourcePersonId > 0 && (int) $values['personne_id'] === $sourcePersonId) {
            $errors[] = 'La personne destinataire doit etre differente de la personne source.';
        }
        if (count($errors) > 0) {
            return array('ok' => false, 'errors' => $errors);
        }

        $login = self::limit($login, 190);
        $now = time();
        $transaction = self::beginTransaction();

        try {
            $sourcePerson = self::personForUpdate($sourcePersonId);
            $targetPerson = self::personForUpdate((int) $values['personne_id']);
            if (!$sourcePerson || !isset($sourcePerson['id'])) {
                self::rollbackTransaction($transaction);
                return array('ok' => false, 'errors' => array('Personne source introuvable.'));
            }
            if (!$targetPerson || !isset($targetPerson['id']) || (int) $targetPerson['actif'] !== 1) {
                self::rollbackTransaction($transaction);
                return array('ok' => false, 'errors' => array('La personne destinataire est introuvable ou inactive.'));
            }

            $loans = self::openLoansForPersonForUpdate($sourcePersonId);
            if (count($loans) === 0) {
                self::rollbackTransaction($transaction);
                return array('ok' => false, 'errors' => array('Aucun pret ouvert a transferer pour cette personne.'));
            }

            foreach ($loans as $loan) {
                $loanErrors = self::validateTransferLoan($loan, $targetPerson, $values);
                if (count($loanErrors) > 0) {
                    self::rollbackTransaction($transaction);
                    return array('ok' => false, 'errors' => $loanErrors);
                }
            }

            $newLoanIds = array();
            foreach ($loans as $loan) {
                $newLoanId = self::transferLockedLoan($loan, $targetPerson, $values, $login, $now);
                if ($newLoanId <= 0) {
                    self::rollbackTransaction($transaction);
                    return array('ok' => false, 'errors' => array('Transfert de tous les prets impossible.'));
                }
                $newLoanIds[] = $newLoanId;
            }

            self::log(
                'prets_personne_transferes',
                'personne',
                $sourcePersonId,
                count($newLoanIds).' pret(s) vers '.self::personSummary($targetPerson),
                $login
            );
            self::commitTransaction($transaction);

            return array('ok' => true, 'ids' => $newLoanIds, 'count' => count($newLoanIds), 'errors' => array());
        } catch (Throwable $exception) {
            self::rollbackTransaction($transaction);
            return array('ok' => false, 'errors' => array('Transfert de tous les prets impossible.'));
        }
    }

    public static function alignPersonOpenLoansToDeparture($source, $login)
    {
        self::ensureTables();
        $personId = isset($source['personne_id']) ? (int) $source['personne_id'] : 0;
        $commentaire = self::limit(isset($source['commentaire']) ? $source['commentaire'] : '', 2000);
        $errors = array();

        if ($personId <= 0) {
            $errors[] = 'Personne introuvable.';
        }
        if (trim($commentaire) === '') {
            $errors[] = 'Le commentaire est obligatoire.';
        }
        if (count($errors) > 0) {
            return array('ok' => false, 'errors' => $errors);
        }

        $login = self::limit($login, 190);
        $now = time();
        $transaction = self::beginTransaction();

        try {
            $person = self::personForUpdate($personId);
            if (!$person || !isset($person['id'])) {
                self::rollbackTransaction($transaction);
                return array('ok' => false, 'errors' => array('Personne introuvable.'));
            }

            $dateDepart = self::nullableDate(isset($person['date_depart']) ? $person['date_depart'] : '');
            if ($dateDepart === null || $dateDepart === false) {
                self::rollbackTransaction($transaction);
                return array('ok' => false, 'errors' => array('La personne n a pas de date de depart exploitable.'));
            }

            $loans = self::openLoansForPersonForUpdate($personId);
            if (count($loans) === 0) {
                self::rollbackTransaction($transaction);
                return array('ok' => false, 'errors' => array('Aucun pret ouvert a mettre a jour pour cette personne.'));
            }

            foreach ($loans as $loan) {
                if ((string) $dateDepart < (string) $loan['date_debut']) {
                    self::rollbackTransaction($transaction);
                    return array(
                        'ok' => false,
                        'errors' => array('La date de depart ne peut pas preceder la date de debut du pret #'.(int) $loan['id'].'.')
                    );
                }
            }

            $count = 0;
            foreach ($loans as $loan) {
                $comment = self::mergeComment(
                    $loan['commentaire'],
                    'Fin prevue '.$loan['date_fin_prevue'].' -> '.$dateDepart.' - '.$commentaire,
                    'Date depart'
                );
                $ok = self::commandOk(grr_sql_command(
                    "UPDATE ".self::table(self::TABLE_LOAN)."
                    SET date_fin_prevue = ?, commentaire = ?, updated_by = ?, updated_at = ?
                    WHERE id = ? AND statut = 'ouvert'",
                    "sssii",
                    array($dateDepart, $comment, $login, $now, (int) $loan['id'])
                ));

                if (!$ok) {
                    self::rollbackTransaction($transaction);
                    return array('ok' => false, 'errors' => array('Mise a jour des fins de prets impossible.'));
                }
                $count++;
            }

            self::log(
                'prets_personne_date_depart',
                'personne',
                $personId,
                $count.' pret(s) alignes sur la date de depart '.$dateDepart,
                $login
            );
            self::commitTransaction($transaction);

            return array('ok' => true, 'id' => $personId, 'count' => $count, 'errors' => array());
        } catch (Throwable $exception) {
            self::rollbackTransaction($transaction);
            return array('ok' => false, 'errors' => array('Mise a jour des fins de prets impossible.'));
        }
    }

    public static function extendLoanDueDateFromAlert($source, $login)
    {
        self::ensureTables();
        $id = isset($source['pret_id']) ? (int) $source['pret_id'] : 0;
        $newDate = self::nullableDate(isset($source['new_date']) ? $source['new_date'] : '');
        $commentaire = self::limit(isset($source['commentaire']) ? $source['commentaire'] : '', 2000);
        $errors = array();

        if ($id <= 0) {
            $errors[] = 'Pret introuvable.';
        }
        if ($newDate === null || $newDate === false) {
            $errors[] = 'La nouvelle date de retour doit etre au format AAAA-MM-JJ.';
        } elseif ($newDate < date('Y-m-d')) {
            $errors[] = 'La nouvelle date de retour ne peut pas etre passee.';
        }
        if (trim($commentaire) === '') {
            $errors[] = 'Le commentaire de prolongation est obligatoire.';
        }
        if (count($errors) > 0) {
            return array('ok' => false, 'errors' => $errors);
        }

        $login = self::limit($login, 190);
        $now = time();
        $transaction = self::beginTransaction();

        try {
            $loan = self::loanForUpdate($id);
            if (!$loan) {
                self::rollbackTransaction($transaction);
                return array('ok' => false, 'errors' => array('Pret introuvable.'));
            }
            if ((string) $loan['statut'] !== 'ouvert') {
                self::rollbackTransaction($transaction);
                return array('ok' => false, 'errors' => array('Seul un pret ouvert peut etre prolonge.'));
            }
            if ((string) $newDate < (string) $loan['date_debut']) {
                self::rollbackTransaction($transaction);
                return array('ok' => false, 'errors' => array('La nouvelle date de retour ne peut pas preceder la date de debut.'));
            }
            if ((string) $loan['date_fin_prevue'] !== '' && (string) $newDate <= (string) $loan['date_fin_prevue']) {
                self::rollbackTransaction($transaction);
                return array('ok' => false, 'errors' => array('La nouvelle date de retour doit etre posterieure a la date actuelle.'));
            }

            $comment = self::mergeComment($loan['commentaire'], $commentaire, 'Prolongation retour');
            $ok = self::commandOk(grr_sql_command(
                "UPDATE ".self::table(self::TABLE_LOAN)."
                SET date_fin_prevue = ?, commentaire = ?, updated_by = ?, updated_at = ?
                WHERE id = ? AND statut = 'ouvert'",
                "sssii",
                array($newDate, $comment, $login, $now, $id)
            ));

            if (!$ok) {
                self::rollbackTransaction($transaction);
                return array('ok' => false, 'errors' => array('Prolongation du pret impossible.'));
            }

            self::log(
                'alerte_pret_prolongee',
                'pret',
                (int) $loan['id'],
                'Date retour prevue '.$loan['date_fin_prevue'].' -> '.$newDate.' ; '.$commentaire,
                $login
            );
            self::commitTransaction($transaction);

            return array('ok' => true, 'id' => (int) $loan['id'], 'errors' => array());
        } catch (Throwable $exception) {
            self::rollbackTransaction($transaction);
            return array('ok' => false, 'errors' => array('Prolongation du pret impossible.'));
        }
    }

    public static function extendPersonDepartureFromAlert($source, $login)
    {
        self::ensureTables();
        $id = isset($source['personne_id']) ? (int) $source['personne_id'] : 0;
        $newDate = self::nullableDate(isset($source['new_date']) ? $source['new_date'] : '');
        $commentaire = self::limit(isset($source['commentaire']) ? $source['commentaire'] : '', 2000);
        $errors = array();

        if ($id <= 0) {
            $errors[] = 'Personne introuvable.';
        }
        if ($newDate === null || $newDate === false) {
            $errors[] = 'La nouvelle date de depart doit etre au format AAAA-MM-JJ.';
        } elseif ($newDate < date('Y-m-d')) {
            $errors[] = 'La nouvelle date de depart ne peut pas etre passee.';
        }
        if (trim($commentaire) === '') {
            $errors[] = 'Le commentaire de prolongation est obligatoire.';
        }
        if (count($errors) > 0) {
            return array('ok' => false, 'errors' => $errors);
        }

        $login = self::limit($login, 190);
        $now = time();
        $transaction = self::beginTransaction();

        try {
            $person = self::one(
                "SELECT * FROM ".self::table(self::TABLE_PERSON)." WHERE id = ? FOR UPDATE",
                "i",
                array($id)
            );
            if (!$person) {
                self::rollbackTransaction($transaction);
                return array('ok' => false, 'errors' => array('Personne introuvable.'));
            }
            if ((string) $person['date_depart'] !== '' && (string) $newDate <= (string) $person['date_depart']) {
                self::rollbackTransaction($transaction);
                return array('ok' => false, 'errors' => array('La nouvelle date de depart doit etre posterieure a la date actuelle.'));
            }

            $notes = self::mergeComment($person['notes'], $commentaire, 'Prolongation depart');
            $ok = self::commandOk(grr_sql_command(
                "UPDATE ".self::table(self::TABLE_PERSON)."
                SET date_depart = ?, notes = ?, updated_by = ?, updated_at = ?
                WHERE id = ?",
                "sssii",
                array($newDate, $notes, $login, $now, $id)
            ));

            if (!$ok) {
                self::rollbackTransaction($transaction);
                return array('ok' => false, 'errors' => array('Prolongation de la date de depart impossible.'));
            }

            self::log(
                'alerte_depart_prolongee',
                'personne',
                (int) $person['id'],
                'Date depart '.$person['date_depart'].' -> '.$newDate.' ; '.$commentaire,
                $login
            );
            self::commitTransaction($transaction);

            return array('ok' => true, 'id' => (int) $person['id'], 'errors' => array());
        } catch (Throwable $exception) {
            self::rollbackTransaction($transaction);
            return array('ok' => false, 'errors' => array('Prolongation de la date de depart impossible.'));
        }
    }

    public static function loanDiagnostics()
    {
        self::ensureTables();
        if (!self::tableExists(self::TABLE_LOAN)) {
            return array(
                'prets_ouverts_multiples' => 0,
                'prets_sans_personne' => 0,
                'prets_sans_materiel' => 0,
                'prets_en_retard' => 0,
                'personnes_parties_avec_pret' => 0,
            );
        }

        return array(
            'prets_ouverts_multiples' => (int) grr_sql_query1(
                "SELECT COUNT(*) FROM (
                    SELECT p.item_id
                    FROM ".self::table(self::TABLE_LOAN)." p
                    LEFT JOIN ".self::table(self::TABLE_ITEM)." i ON i.id = p.item_id
                    WHERE p.statut = 'ouvert'
                      AND COALESCE(i.pret_multiple, 0) = 0
                    GROUP BY p.item_id
                    HAVING COUNT(*) > 1
                ) duplicates"
            ),
            'prets_sans_personne' => (int) grr_sql_query1(
                "SELECT COUNT(*)
                FROM ".self::table(self::TABLE_LOAN)." p
                LEFT JOIN ".self::table(self::TABLE_PERSON)." pe ON pe.id = p.personne_id
                WHERE pe.id IS NULL"
            ),
            'prets_sans_materiel' => (int) grr_sql_query1(
                "SELECT COUNT(*)
                FROM ".self::table(self::TABLE_LOAN)." p
                LEFT JOIN ".self::table(self::TABLE_ITEM)." i ON i.id = p.item_id
                WHERE i.id IS NULL"
            ),
            'prets_en_retard' => self::countLateLoans(),
            'personnes_parties_avec_pret' => self::countDepartedPeopleWithOpenLoans(),
        );
    }

    public static function alertCounts()
    {
        $counts = array(
            'prets_en_retard' => 0,
            'personnes_parties_avec_pret' => 0,
            'materiels_sans_identifiant' => 0,
            'materiels_sans_categorie' => 0,
            'codes_barres_dupliques' => 0,
            'prets_ouverts_multiples' => 0,
            'total' => 0,
        );

        self::ensureTables();
        if (!InformatiqueMaterielConfig::alertsEnabled()) {
            return $counts;
        }

        $loanDiagnostics = self::loanDiagnostics();
        $itemDiagnostics = self::itemDiagnostics();
        $counts['prets_en_retard'] = (int) $loanDiagnostics['prets_en_retard'];
        $counts['personnes_parties_avec_pret'] = (int) $loanDiagnostics['personnes_parties_avec_pret'];
        $counts['prets_ouverts_multiples'] = (int) $loanDiagnostics['prets_ouverts_multiples'];
        $counts['materiels_sans_identifiant'] = self::countItemsMissingIdentifier();
        $counts['materiels_sans_categorie'] = (int) $itemDiagnostics['sans_categorie'];
        $counts['codes_barres_dupliques'] = self::countActiveDuplicateBarcodes();

        foreach ($counts as $key => $value) {
            if ($key !== 'total') {
                $counts['total'] += (int) $value;
            }
        }

        return $counts;
    }

    public static function alerts($limit = 200)
    {
        self::ensureTables();
        if (!InformatiqueMaterielConfig::alertsEnabled()) {
            return array();
        }

        $limit = max(1, min(500, (int) $limit));
        $alerts = array();

        if (self::tableExists(self::TABLE_LOAN)) {
            $lateLoans = self::rows(
                self::loanSelectSql()."
                WHERE p.statut = 'ouvert'
                  AND p.date_fin_prevue IS NOT NULL
                  AND p.date_fin_prevue < CURRENT_DATE()
                ORDER BY p.date_fin_prevue, p.id
                LIMIT ".$limit
            );
            foreach ($lateLoans as $loan) {
                $alerts[] = self::alertRow(
                    'pret_en_retard',
                    'danger',
                    self::loanAlertLabel($loan),
                    'Retour prevu le '.$loan['date_fin_prevue'],
                    $loan['date_fin_prevue'],
                    (int) $loan['id'],
                    (int) $loan['item_id'],
                    (int) $loan['personne_id'],
                    '',
                    '#'.(int) $loan['id'],
                    isset($loan['commentaire']) ? (string) $loan['commentaire'] : ''
                );
            }
        }

        if (self::tableExists(self::TABLE_LOAN) && self::tableExists(self::TABLE_PERSON)) {
            $departedLoans = self::rows(
                self::loanSelectSql()."
                WHERE p.statut = 'ouvert'
                  AND pe.date_depart IS NOT NULL
                  AND pe.date_depart < CURRENT_DATE()
                ORDER BY pe.date_depart, p.id
                LIMIT ".$limit
            );
            foreach ($departedLoans as $loan) {
                $alerts[] = self::alertRow(
                    'personne_partie',
                    'danger',
                    self::loanAlertLabel($loan),
                    'Personne partie le '.$loan['personne_date_depart'].' avec un pret ouvert',
                    $loan['personne_date_depart'],
                    (int) $loan['id'],
                    (int) $loan['item_id'],
                    (int) $loan['personne_id'],
                    '',
                    '#'.(int) $loan['id'],
                    isset($loan['commentaire']) ? (string) $loan['commentaire'] : ''
                );
            }
        }

        if (self::tableExists(self::TABLE_ITEM)) {
            $itemsWithoutIdentifier = self::rows(
                "SELECT i.id, i.identifiant, i.designation, i.marque, i.numero_serie
                FROM ".self::table(self::TABLE_ITEM)." i
                WHERE i.actif = 1 AND (i.identifiant IS NULL OR i.identifiant = '')
                ORDER BY i.designation, i.id
                LIMIT ".$limit
            );
            foreach ($itemsWithoutIdentifier as $item) {
                $alerts[] = self::alertRow(
                    'materiel_sans_identifiant',
                    'warning',
                    self::itemAlertLabel($item),
                    'Materiel actif sans identifiant courant',
                    '',
                    0,
                    (int) $item['id'],
                    0,
                    ''
                );
            }

            $itemsWithoutCategory = self::rows(
                "SELECT i.id, i.identifiant, i.designation, i.marque, i.numero_serie
                FROM ".self::table(self::TABLE_ITEM)." i
                LEFT JOIN ".self::table(self::TABLE_CATEGORY)." c ON c.id = i.categorie_id
                WHERE i.actif = 1 AND c.id IS NULL
                ORDER BY i.identifiant, i.designation, i.id
                LIMIT ".$limit
            );
            foreach ($itemsWithoutCategory as $item) {
                $alerts[] = self::alertRow(
                    'materiel_sans_categorie',
                    'warning',
                    self::itemAlertLabel($item),
                    'Materiel actif sans categorie valide',
                    '',
                    0,
                    (int) $item['id'],
                    0,
                    ''
                );
            }

            $duplicateBarcodes = self::rows(
                "SELECT i.code_barre_usmb, COUNT(*) AS doublons, MIN(i.id) AS item_id,
                    GROUP_CONCAT(COALESCE(NULLIF(i.identifiant, ''), i.designation) ORDER BY i.identifiant SEPARATOR ', ') AS elements
                FROM ".self::table(self::TABLE_ITEM)." i
                WHERE i.actif = 1 AND i.code_barre_usmb IS NOT NULL AND i.code_barre_usmb <> ''
                GROUP BY i.code_barre_usmb
                HAVING COUNT(*) > 1
                ORDER BY i.code_barre_usmb
                LIMIT ".$limit
            );
            foreach ($duplicateBarcodes as $barcode) {
                $alerts[] = self::alertRow(
                    'code_barre_duplique',
                    'warning',
                    'Code-barres '.$barcode['code_barre_usmb'],
                    (int) $barcode['doublons'].' materiels actifs : '.$barcode['elements'],
                    '',
                    0,
                    (int) $barcode['item_id'],
                    0,
                    $barcode['code_barre_usmb']
                );
            }
        }

        if (self::tableExists(self::TABLE_LOAN)) {
            $multipleLoans = self::rows(
                "SELECT p.item_id, COUNT(*) AS doublons, MIN(p.id) AS loan_id,
                    GROUP_CONCAT(p.id ORDER BY p.id SEPARATOR ', ') AS loan_ids,
                    GROUP_CONCAT(CONCAT('#', p.id, ': ', COALESCE(NULLIF(TRIM(p.commentaire), ''), 'sans commentaire')) ORDER BY p.id SEPARATOR ' | ') AS loan_comments,
                    i.identifiant, i.designation
                FROM ".self::table(self::TABLE_LOAN)." p
                LEFT JOIN ".self::table(self::TABLE_ITEM)." i ON i.id = p.item_id
                WHERE p.statut = 'ouvert'
                  AND COALESCE(i.pret_multiple, 0) = 0
                GROUP BY p.item_id, i.identifiant, i.designation
                HAVING COUNT(*) > 1
                ORDER BY i.identifiant, p.item_id
                LIMIT ".$limit
            );
            foreach ($multipleLoans as $loan) {
                $alerts[] = self::alertRow(
                    'prets_ouverts_multiples',
                    'danger',
                    self::itemAlertLabel($loan),
                    (int) $loan['doublons'].' prets ouverts pour le meme materiel non generique',
                    '',
                    (int) $loan['loan_id'],
                    (int) $loan['item_id'],
                    0,
                    '',
                    isset($loan['loan_ids']) ? '#'.str_replace(', ', ', #', (string) $loan['loan_ids']) : '#'.(int) $loan['loan_id'],
                    isset($loan['loan_comments']) ? (string) $loan['loan_comments'] : ''
                );
            }
        }

        usort($alerts, function ($a, $b) {
            return self::sortAlerts($a, $b);
        });
        return array_slice($alerts, 0, $limit);
    }

    public static function importLogs($limit = 50)
    {
        self::ensureTables();
        if (!self::tableExists(self::TABLE_IMPORT_LOG)) {
            return array();
        }

        return self::rows(
            "SELECT *
            FROM ".self::table(self::TABLE_IMPORT_LOG)."
            ORDER BY id DESC
            LIMIT ".max(1, min(200, (int) $limit))
        );
    }

    public static function importLineStatus($packageHash, $sourceTable, $sourceRow)
    {
        self::ensureTables();
        if (!self::tableExists(self::TABLE_IMPORT_LOG)) {
            return array();
        }

        return self::one(
            "SELECT status, message, personne_id, item_id, pret_id
            FROM ".self::table(self::TABLE_IMPORT_LOG)."
            WHERE package_hash = ? AND source_table = ? AND source_row = ?",
            "ssi",
            array(self::limit($packageHash, 64), self::limit($sourceTable, 50), (int) $sourceRow)
        );
    }

    public static function logImportLine($packageHash, $packageName, $sourceTable, $sourceRow, $status, $message, $ids, $login)
    {
        self::ensureTables();
        if (!self::tableExists(self::TABLE_IMPORT_LOG)) {
            return false;
        }

        return self::commandOk(grr_sql_command(
            "INSERT INTO ".self::table(self::TABLE_IMPORT_LOG)."
                (package_hash, package_name, source_table, source_row, personne_id, item_id, pret_id,
                 status, message, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            "sssiiiisssi",
            array(
                self::limit($packageHash, 64),
                self::limit($packageName, 190),
                self::limit($sourceTable, 50),
                (int) $sourceRow,
                isset($ids['personne_id']) ? (int) $ids['personne_id'] : 0,
                isset($ids['item_id']) ? (int) $ids['item_id'] : 0,
                isset($ids['pret_id']) ? (int) $ids['pret_id'] : 0,
                self::limit($status, 20),
                self::limit($message, 2000),
                self::limit($login, 190),
                time(),
            )
        ));
    }

    public static function categoryIdByDesignation($designation)
    {
        $designation = self::limit($designation, 190);
        if ($designation === '' || !self::tableExists(self::TABLE_CATEGORY)) {
            return 0;
        }

        return (int) grr_sql_query1(
            "SELECT id FROM ".self::table(self::TABLE_CATEGORY)."
            WHERE LOWER(designation) = LOWER(?) AND actif = 1
            ORDER BY id
            LIMIT 1",
            "s",
            array($designation)
        );
    }

    public static function personIdByLegacyIdentifier($identifier)
    {
        $identifier = self::limit($identifier, 100);
        if ($identifier === '' || !self::tableExists(self::TABLE_PERSON)) {
            return 0;
        }

        return (int) grr_sql_query1(
            "SELECT id FROM ".self::table(self::TABLE_PERSON)."
            WHERE identifiant_legacy = ?
            ORDER BY id
            LIMIT 1",
            "s",
            array($identifier)
        );
    }

    public static function itemIdByIdentifier($identifier)
    {
        $identifier = self::limit($identifier, 100);
        if ($identifier === '' || !self::tableExists(self::TABLE_ITEM)) {
            return 0;
        }

        return (int) grr_sql_query1(
            "SELECT id FROM ".self::table(self::TABLE_ITEM)."
            WHERE identifiant = ? OR identifiant_legacy = ?
            ORDER BY id
            LIMIT 1",
            "ss",
            array($identifier, $identifier)
        );
    }

    public static function importLoanRecord($source, $login, $context = array())
    {
        self::ensureTables();
        $values = self::normalizeLoanValues($source);
        $errors = self::validateImportedLoanValues($values);
        if (count($errors) > 0) {
            return array('ok' => false, 'errors' => $errors);
        }

        if ($values['date_fin_effective'] === null) {
            $conflict = self::loanImportConflict($values);
            if ($conflict !== null) {
                return self::storeImportedLoanConflict($source, $values, $conflict, $context, $login);
            }

            return self::createLoan($source, $login);
        }

        $login = self::limit($login, 190);
        $now = time();
        $dateFinPrevue = $values['date_fin_prevue'] === null ? null : $values['date_fin_prevue'];
        $transaction = self::beginTransaction();

        try {
            $ok = self::commandOk(grr_sql_command(
                "INSERT INTO ".self::table(self::TABLE_LOAN)."
                    (personne_id, item_id, localisation, date_debut, date_fin_prevue,
                     date_fin_effective, commentaire, statut, source_import_id,
                     created_by, created_at, updated_by, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'clos', 0, ?, ?, ?, ?)",
                "iissssssisi",
                array(
                    (int) $values['personne_id'],
                    (int) $values['item_id'],
                    $values['localisation'],
                    $values['date_debut'],
                    $dateFinPrevue,
                    $values['date_fin_effective'],
                    $values['commentaire'],
                    $login,
                    $now,
                    $login,
                    $now,
                )
            ));
            $id = $ok ? self::lastInsertId() : 0;

            if (!$ok) {
                self::rollbackTransaction($transaction);
                return array('ok' => false, 'errors' => array('Import du pret clos impossible.'));
            }

            self::log('pret_importe', 'pret', $id, 'Pret historique clos', $login);
            self::commitTransaction($transaction);

            return array('ok' => true, 'id' => $id, 'errors' => array());
        } catch (Throwable $exception) {
            self::rollbackTransaction($transaction);
            return array('ok' => false, 'errors' => array('Import du pret clos impossible.'));
        }
    }

    public static function loanConflicts($status = 'en_attente', $limit = 100)
    {
        self::ensureTables();
        if (!self::tableExists(self::TABLE_LOAN_CONFLICT)) {
            return array();
        }

        $limit = max(1, min(500, (int) $limit));
        $conditions = array();
        $types = '';
        $params = array();
        $status = self::limit($status, 20);
        if ($status !== '') {
            $conditions[] = 'c.statut = ?';
            $types .= 's';
            $params[] = $status;
        }
        $where = count($conditions) > 0 ? ' WHERE '.implode(' AND ', $conditions) : '';
        $queryTypes = $types === '' ? null : $types;
        $queryParams = $types === '' ? null : $params;

        return self::rows(
            "SELECT c.*,
                pe.prenom AS personne_prenom, pe.nom AS personne_nom, pe.identifiant_legacy AS personne_identifiant,
                i.identifiant AS item_identifiant, i.designation AS item_designation,
                ep.prenom AS existant_personne_prenom, ep.nom AS existant_personne_nom,
                p.date_debut AS existant_date_debut, p.date_fin_prevue AS existant_date_fin_prevue
            FROM ".self::table(self::TABLE_LOAN_CONFLICT)." c
            LEFT JOIN ".self::table(self::TABLE_PERSON)." pe ON pe.id = c.personne_id
            LEFT JOIN ".self::table(self::TABLE_ITEM)." i ON i.id = c.item_id
            LEFT JOIN ".self::table(self::TABLE_LOAN)." p ON p.id = c.pret_existant_id
            LEFT JOIN ".self::table(self::TABLE_PERSON)." ep ON ep.id = p.personne_id"
            .$where."
            ORDER BY c.created_at DESC, c.id DESC
            LIMIT ".$limit,
            $queryTypes,
            $queryParams
        );
    }

    public static function countPendingLoanConflicts()
    {
        if (!self::tableExists(self::TABLE_LOAN_CONFLICT)) {
            return 0;
        }

        return (int) grr_sql_query1(
            "SELECT COUNT(*) FROM ".self::table(self::TABLE_LOAN_CONFLICT)." WHERE statut = 'en_attente'"
        );
    }

    public static function deleteLoanConflict($id, $login)
    {
        self::ensureTables();
        $id = (int) $id;
        if ($id <= 0 || !self::tableExists(self::TABLE_LOAN_CONFLICT)) {
            return array('ok' => false, 'errors' => array('Conflit introuvable.'));
        }

        $ok = self::commandOk(grr_sql_command(
            "DELETE FROM ".self::table(self::TABLE_LOAN_CONFLICT)." WHERE id = ?",
            "i",
            array($id)
        ));
        if ($ok) {
            self::log('conflit_pret_supprime', 'pret_conflit', $id, '', $login);
            return array('ok' => true, 'errors' => array());
        }

        return array('ok' => false, 'errors' => array('Suppression du conflit impossible.'));
    }

    public static function resolveLoanConflict($source, $login)
    {
        self::ensureTables();
        $id = isset($source['conflict_id']) ? (int) $source['conflict_id'] : 0;
        $action = isset($source['resolution_action']) ? (string) $source['resolution_action'] : '';
        $decision = self::limit(isset($source['decision']) ? $source['decision'] : '', 2000);
        $confirmation = trim((string) (isset($source['confirmation']) ? $source['confirmation'] : ''));
        $allowed = array('ignore', 'create', 'replace');
        $errors = array();

        if ($id <= 0) {
            $errors[] = 'Conflit introuvable.';
        }
        if (!in_array($action, $allowed, true)) {
            $errors[] = 'Decision de resolution invalide.';
        }
        if (trim($decision) === '') {
            $errors[] = 'La justification de resolution est obligatoire.';
        }
        if ($action === 'replace' && $confirmation !== 'REMPLACER') {
            $errors[] = 'La confirmation REMPLACER est obligatoire.';
        }
        if (count($errors) > 0) {
            return array('ok' => false, 'errors' => $errors);
        }

        $login = self::limit($login, 190);
        $now = time();
        $transaction = self::beginTransaction();

        try {
            $conflict = self::loanConflictForUpdate($id);
            if (!$conflict) {
                self::rollbackTransaction($transaction);
                return array('ok' => false, 'errors' => array('Conflit introuvable.'));
            }
            if ((string) $conflict['statut'] !== 'en_attente') {
                self::rollbackTransaction($transaction);
                return array('ok' => false, 'errors' => array('Ce conflit est deja resolu.'));
            }

            if ($action === 'ignore') {
                $ok = self::markLoanConflictResolved($id, 'ignore', $decision, $login, $now);
                if (!$ok) {
                    self::rollbackTransaction($transaction);
                    return array('ok' => false, 'errors' => array('Resolution du conflit impossible.'));
                }

                self::log('conflit_pret_ignore', 'pret_conflit', $id, $decision, $login);
                self::commitTransaction($transaction);
                return array('ok' => true, 'id' => $id, 'errors' => array());
            }

            if ($action === 'replace') {
                if ((int) $conflict['pret_existant_id'] <= 0) {
                    self::rollbackTransaction($transaction);
                    return array('ok' => false, 'errors' => array('Aucun pret existant a remplacer.'));
                }

                $existingLoan = self::loanForUpdate((int) $conflict['pret_existant_id']);
                if (!$existingLoan || (string) $existingLoan['statut'] !== 'ouvert') {
                    self::rollbackTransaction($transaction);
                    return array('ok' => false, 'errors' => array('Le pret existant n est plus ouvert.'));
                }

                $existingComment = self::mergeComment($existingLoan['commentaire'], $decision, 'Resolution conflit import');
                $ok = self::commandOk(grr_sql_command(
                    "UPDATE ".self::table(self::TABLE_LOAN)."
                    SET commentaire = ?, statut = 'annule', updated_by = ?, updated_at = ?
                    WHERE id = ? AND statut = 'ouvert'",
                    "ssii",
                    array($existingComment, $login, $now, (int) $existingLoan['id'])
                ));
                if (!$ok) {
                    self::rollbackTransaction($transaction);
                    return array('ok' => false, 'errors' => array('Annulation du pret existant impossible.'));
                }
            }

            $conflictItem = self::itemForUpdate((int) $conflict['item_id']);
            if (!self::isMultipleLoanItem($conflictItem)) {
                $openLoan = self::openLoanForItemRow((int) $conflict['item_id'], 0, true);
                if ($openLoan) {
                    self::rollbackTransaction($transaction);
                    return array('ok' => false, 'errors' => array('Le materiel possede encore un pret ouvert.'));
                }
            }

            $createdLoanId = self::createOpenLoanFromConflict($conflict, $decision, $login, $now);
            if ($createdLoanId <= 0) {
                self::rollbackTransaction($transaction);
                return array('ok' => false, 'errors' => array('Creation du nouveau pret impossible.'));
            }

            $status = $action === 'replace' ? 'remplace' : 'importe';
            $resolvedDecision = $decision.' | nouveau pret #'.$createdLoanId;
            $ok = self::markLoanConflictResolved($id, $status, $resolvedDecision, $login, $now);
            if (!$ok) {
                self::rollbackTransaction($transaction);
                return array('ok' => false, 'errors' => array('Resolution du conflit impossible.'));
            }

            self::log(
                $action === 'replace' ? 'conflit_pret_remplace' : 'conflit_pret_importe',
                'pret_conflit',
                $id,
                $resolvedDecision,
                $login
            );
            self::commitTransaction($transaction);

            return array('ok' => true, 'id' => $id, 'pret_id' => $createdLoanId, 'errors' => array());
        } catch (Throwable $exception) {
            self::rollbackTransaction($transaction);
            return array('ok' => false, 'errors' => array('Resolution du conflit impossible.'));
        }
    }

    private static function loanImportConflict($values)
    {
        $itemId = isset($values['item_id']) ? (int) $values['item_id'] : 0;
        if ($itemId <= 0) {
            return null;
        }

        $item = self::item($itemId);
        if (self::isMultipleLoanItem($item)) {
            return null;
        }

        $openLoan = self::openLoanForItemRow($itemId, 0, false);
        if ($openLoan) {
            return array(
                'motif' => 'pret_ouvert_doublon_materiel',
                'pret_existant_id' => (int) $openLoan['id'],
                'message' => 'Materiel deja associe a un pret ouvert.',
            );
        }

        if ($item && isset($item['statut']) && in_array((string) $item['statut'], array('en_pret', 'pret_multiple'), true)) {
            return array(
                'motif' => 'materiel_statut_en_pret',
                'pret_existant_id' => 0,
                'message' => 'Materiel marque en pret sans pret ouvert retrouve.',
            );
        }

        return null;
    }

    private static function loanConflictForUpdate($id)
    {
        if ((int) $id <= 0 || !self::tableExists(self::TABLE_LOAN_CONFLICT)) {
            return array();
        }

        return self::one(
            "SELECT * FROM ".self::table(self::TABLE_LOAN_CONFLICT)." WHERE id = ? FOR UPDATE",
            "i",
            array((int) $id)
        );
    }

    private static function markLoanConflictResolved($id, $status, $decision, $login, $now)
    {
        return self::commandOk(grr_sql_command(
            "UPDATE ".self::table(self::TABLE_LOAN_CONFLICT)."
            SET statut = ?, decision = ?, resolved_by = ?, resolved_at = ?
            WHERE id = ? AND statut = 'en_attente'",
            "sssii",
            array(self::limit($status, 20), self::limit($decision, 4000), self::limit($login, 190), (int) $now, (int) $id)
        ));
    }

    private static function createOpenLoanFromConflict($conflict, $decision, $login, $now)
    {
        $values = array(
            'personne_id' => isset($conflict['personne_id']) ? (int) $conflict['personne_id'] : 0,
            'item_id' => isset($conflict['item_id']) ? (int) $conflict['item_id'] : 0,
            'localisation' => isset($conflict['localisation']) ? (string) $conflict['localisation'] : '',
            'date_debut' => isset($conflict['date_debut']) ? (string) $conflict['date_debut'] : '',
            'date_fin_prevue' => isset($conflict['date_fin_prevue']) ? (string) $conflict['date_fin_prevue'] : '',
            'commentaire' => isset($conflict['commentaire']) ? (string) $conflict['commentaire'] : '',
        );
        $errors = self::validateLoanConflictCreationValues($values);
        if (count($errors) > 0) {
            return 0;
        }

        $item = self::itemForUpdate((int) $values['item_id']);
        $itemErrors = self::loanConflictItemErrors($item);
        if (count($itemErrors) > 0) {
            return 0;
        }

        $dateFinPrevue = trim((string) $values['date_fin_prevue']) === '' ? null : $values['date_fin_prevue'];
        $comment = self::mergeComment($values['commentaire'], $decision, 'Resolution conflit');
        $ok = self::commandOk(grr_sql_command(
            "INSERT INTO ".self::table(self::TABLE_LOAN)."
                (personne_id, item_id, localisation, date_debut, date_fin_prevue,
                 date_fin_effective, commentaire, statut, source_import_id,
                 created_by, created_at, updated_by, updated_at)
            VALUES (?, ?, ?, ?, ?, NULL, ?, 'ouvert', 0, ?, ?, ?, ?)",
            "iisssssisi",
            array(
                (int) $values['personne_id'],
                (int) $values['item_id'],
                self::limit($values['localisation'], 190),
                $values['date_debut'],
                $dateFinPrevue,
                self::limit($comment, 4000),
                self::limit($login, 190),
                (int) $now,
                self::limit($login, 190),
                (int) $now,
            )
        ));
        $id = $ok ? self::lastInsertId() : 0;
        if (!$ok || $id <= 0) {
            return 0;
        }

        $ok = self::syncItemLoanStatus((int) $values['item_id'], $login, $now);
        if (!$ok) {
            return 0;
        }

        self::log('pret_cree_depuis_conflit', 'pret', $id, 'Conflit #'.(int) $conflict['id'], $login);

        return $id;
    }

    private static function validateLoanConflictCreationValues($values)
    {
        $errors = array();
        if ((int) $values['personne_id'] <= 0 || !self::activePersonExists((int) $values['personne_id'])) {
            $errors[] = 'La personne du conflit est introuvable ou inactive.';
        }
        if ((int) $values['item_id'] <= 0) {
            $errors[] = 'Le materiel du conflit est introuvable.';
        }
        if (trim((string) $values['date_debut']) === '' || self::nullableDate($values['date_debut']) === false) {
            $errors[] = 'La date de debut du conflit est invalide.';
        }
        if (trim((string) $values['date_fin_prevue']) !== '' && self::nullableDate($values['date_fin_prevue']) === false) {
            $errors[] = 'La date de fin prevue du conflit est invalide.';
        }
        if (trim((string) $values['date_fin_prevue']) !== '' && (string) $values['date_fin_prevue'] < (string) $values['date_debut']) {
            $errors[] = 'La date de fin prevue precede la date de debut.';
        }

        return $errors;
    }

    private static function loanConflictItemErrors($item)
    {
        $errors = array();
        if (!$item || !isset($item['id'])) {
            return array('Materiel introuvable.');
        }
        if ((int) $item['actif'] !== 1) {
            $errors[] = 'Un materiel archive ne peut pas etre prete.';
        }
        $status = isset($item['statut']) ? (string) $item['statut'] : '';
        if (in_array($status, array('archive', 'maintenance', 'a_reformer'), true)) {
            $errors[] = 'Le statut courant du materiel ne permet pas un nouveau pret.';
        }

        return $errors;
    }

    private static function storeImportedLoanConflict($source, $values, $conflict, $context, $login)
    {
        if (!self::tableExists(self::TABLE_LOAN_CONFLICT)) {
            return array('ok' => false, 'errors' => array('Table des conflits de prets indisponible.'));
        }

        $packageHash = self::limit(isset($context['package_hash']) ? $context['package_hash'] : '', 64);
        $packageName = self::limit(isset($context['package_name']) ? $context['package_name'] : '', 190);
        $sourceTable = self::limit(isset($context['source_table']) ? $context['source_table'] : 'loans', 50);
        $sourceRow = isset($context['source_row']) ? (int) $context['source_row'] : 0;
        $motif = self::limit(isset($source['motif_anomalie']) && (string) $source['motif_anomalie'] !== '' ? $source['motif_anomalie'] : $conflict['motif'], 100);
        $resumeSource = self::loanConflictSourceSummary($source, $conflict);
        $login = self::limit($login, 190);
        $now = time();
        $dateFinPrevue = $values['date_fin_prevue'] === null ? null : $values['date_fin_prevue'];
        $dateFinEffective = $values['date_fin_effective'] === null ? null : $values['date_fin_effective'];

        $ok = self::commandOk(grr_sql_command(
            "INSERT INTO ".self::table(self::TABLE_LOAN_CONFLICT)."
                (package_hash, package_name, source_table, source_row, motif, statut,
                 personne_id, item_id, pret_existant_id, localisation, date_debut,
                 date_fin_prevue, date_fin_effective, commentaire, resume_source,
                 created_by, created_at)
            VALUES (?, ?, ?, ?, ?, 'en_attente', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                id = LAST_INSERT_ID(id),
                motif = VALUES(motif),
                personne_id = VALUES(personne_id),
                item_id = VALUES(item_id),
                pret_existant_id = VALUES(pret_existant_id),
                localisation = VALUES(localisation),
                date_debut = VALUES(date_debut),
                date_fin_prevue = VALUES(date_fin_prevue),
                date_fin_effective = VALUES(date_fin_effective),
                commentaire = VALUES(commentaire),
                resume_source = VALUES(resume_source)",
            "sssisiiisssssssi",
            array(
                $packageHash,
                $packageName,
                $sourceTable,
                $sourceRow,
                $motif,
                (int) $values['personne_id'],
                (int) $values['item_id'],
                isset($conflict['pret_existant_id']) ? (int) $conflict['pret_existant_id'] : 0,
                $values['localisation'],
                $values['date_debut'],
                $dateFinPrevue,
                $dateFinEffective,
                $values['commentaire'],
                $resumeSource,
                $login,
                $now,
            )
        ));
        $id = $ok ? self::lastInsertId() : 0;

        if (!$ok) {
            return array('ok' => false, 'errors' => array('Enregistrement du conflit de pret impossible.'));
        }

        self::log('conflit_pret_importe', 'pret_conflit', $id, $motif.' - materiel #'.(int) $values['item_id'], $login);

        return array(
            'ok' => true,
            'id' => $id,
            'conflict' => true,
            'message' => 'Conflit de pret stocke en attente de decision : '.$motif,
            'errors' => array(),
        );
    }

    private static function loanConflictSourceSummary($source, $conflict)
    {
        $parts = array();
        foreach (array(
            'ligne_excel_source' => 'ligne_excel',
            'identifiant_personnel_source' => 'personne',
            'identifiant_materiel_source' => 'materiel',
            'identifiant_materiel_excel_source' => 'materiel_excel',
            'action_proposee' => 'action',
            'justification' => 'justification',
        ) as $key => $label) {
            if (isset($source[$key]) && trim((string) $source[$key]) !== '') {
                $parts[] = $label.'='.trim((string) $source[$key]);
            }
        }
        if (isset($conflict['message']) && trim((string) $conflict['message']) !== '') {
            $parts[] = 'conflit='.trim((string) $conflict['message']);
        }

        return self::limit(implode(' ; ', $parts), 4000);
    }

    public static function documentTypes()
    {
        return array(
            'facture' => 'Facture',
            'bon_livraison' => 'Bon de livraison',
            'notice' => 'Notice',
            'garantie' => 'Garantie',
            'intervention' => 'Intervention',
            'photo' => 'Photo',
            'autre' => 'Autre',
        );
    }

    public static function documentsForItem($itemId, $includeArchived = false, $limit = 100)
    {
        self::ensureTables();
        $itemId = (int) $itemId;
        if ($itemId <= 0 || !self::tableExists(self::TABLE_DOCUMENT)) {
            return array();
        }

        $limit = max(1, min(300, (int) $limit));
        $where = $includeArchived ? '' : ' AND d.actif = 1';

        return self::rows(
            "SELECT d.*
            FROM ".self::table(self::TABLE_DOCUMENT)." d
            JOIN ".self::table(self::TABLE_ITEM)." i ON i.id = d.item_id
            WHERE d.item_id = ?".$where."
            ORDER BY d.actif DESC, d.created_at DESC, d.id DESC
            LIMIT ".$limit,
            "i",
            array($itemId)
        );
    }

    public static function document($documentId, $includeArchived = false)
    {
        self::ensureTables();
        $documentId = (int) $documentId;
        if ($documentId <= 0 || !self::tableExists(self::TABLE_DOCUMENT)) {
            return array();
        }

        $where = $includeArchived ? '' : ' AND d.actif = 1';

        return self::one(
            "SELECT d.*, i.identifiant AS item_identifiant, i.designation AS item_designation,
                i.actif AS item_actif, i.statut AS item_statut
            FROM ".self::table(self::TABLE_DOCUMENT)." d
            JOIN ".self::table(self::TABLE_ITEM)." i ON i.id = d.item_id
            WHERE d.id = ?".$where,
            "i",
            array($documentId)
        );
    }

    public static function addDocument($itemId, $type, $description, $originalName, $storedName, $mimeType, $size, $sha256, $login)
    {
        self::ensureTables();
        $itemId = (int) $itemId;
        $item = self::item($itemId);
        $types = self::documentTypes();
        $type = self::limit($type, 50);
        $description = self::limit($description, 5000);
        $originalName = self::limit(trim((string) $originalName), 255);
        $storedName = trim((string) $storedName);
        $mimeType = self::limit(trim((string) $mimeType), 190);
        $sha256 = trim((string) $sha256);
        $login = self::limit($login, 190);
        $size = (int) $size;

        if (
            !$item
            || (int) $item['actif'] !== 1
            || (string) $item['statut'] === 'archive'
            || !isset($types[$type])
            || $originalName === ''
            || !preg_match('/^[a-f0-9]{64}$/', $storedName)
            || !preg_match('/^[a-f0-9]{64}$/', $sha256)
            || $size <= 0
        ) {
            return false;
        }
        if ($mimeType === '') {
            $mimeType = 'application/octet-stream';
        }

        $ok = self::commandOk(grr_sql_command(
            "INSERT INTO ".self::table(self::TABLE_DOCUMENT)."
                (item_id, type_document, description, original_name, stored_name, mime_type,
                 taille, sha256, uploaded_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            "isssssissi",
            array(
                $itemId,
                $type,
                $description,
                $originalName,
                $storedName,
                $mimeType,
                $size,
                $sha256,
                $login,
                time(),
            )
        ));

        if ($ok) {
            self::log('document_ajoute', 'document', self::lastInsertId(), $originalName, $login);
        }

        return $ok;
    }

    public static function archiveDocument($documentId, $login)
    {
        self::ensureTables();
        $document = self::document($documentId, false);
        if (!$document || (int) $document['item_actif'] !== 1 || (string) $document['item_statut'] === 'archive') {
            return false;
        }

        $ok = self::commandOk(grr_sql_command(
            "UPDATE ".self::table(self::TABLE_DOCUMENT)."
            SET actif = 0, archived_by = ?, archived_at = ?
            WHERE id = ? AND actif = 1",
            "sii",
            array(self::limit($login, 190), time(), (int) $document['id'])
        ));
        if ($ok) {
            self::log('document_archive', 'document', (int) $document['id'], $document['original_name'], $login);
        }

        return $ok;
    }

    public static function documentStorageDir()
    {
        return dirname(__DIR__).'/storage/documents';
    }

    public static function ensureDocumentStorage()
    {
        $directory = self::documentStorageDir();
        if (!is_dir($directory)) {
            @mkdir($directory, 0750, true);
        }

        return is_dir($directory) && is_writable($directory);
    }

    public static function documentPath($storedName)
    {
        $storedName = trim((string) $storedName);
        if (!preg_match('/^[a-f0-9]{64}$/', $storedName)) {
            return '';
        }

        return self::documentStorageDir().'/'.$storedName;
    }

    public static function dashboardCounts()
    {
        self::ensureTables();
        $alertCounts = self::alertCounts();
        return array(
            'roles' => self::countRows(self::TABLE_ROLE),
            'journal' => self::countRows(self::TABLE_JOURNAL),
            'personnes' => self::countActiveRows(self::TABLE_PERSON),
            'categories' => self::countActiveRows(self::TABLE_CATEGORY),
            'materiels' => self::countActiveRows(self::TABLE_ITEM),
            'prets_ouverts' => self::countOpenLoans(),
            'conflits_prets' => self::countPendingLoanConflicts(),
            'alertes' => (int) $alertCounts['total'],
            'documents' => self::countActiveRows(self::TABLE_DOCUMENT),
            'imports' => self::countRows(self::TABLE_IMPORT_LOG),
        );
    }

    public static function resetModuleData($includeRolesAndJournal, $login)
    {
        self::ensureTables();
        self::log(
            'remise_zero_demarre',
            'module',
            0,
            $includeRolesAndJournal ? 'avec roles et journal' : 'donnees metier',
            $login
        );

        $deletedFiles = self::deleteStoredFiles(self::documentStorageDir(), array('.htaccess'));
        $deletedFiles += self::deleteStoredFiles(dirname(__DIR__).'/storage/imports', array('.htaccess'));

        $tables = array(
            self::TABLE_DOCUMENT,
            self::TABLE_LOAN_CONFLICT,
            self::TABLE_LOAN,
            self::TABLE_ITEM,
            self::TABLE_PERSON,
            self::TABLE_CATEGORY,
            self::TABLE_SEQUENCE,
            self::TABLE_IMPORT_LOG,
        );
        if ($includeRolesAndJournal) {
            $tables[] = self::TABLE_ROLE;
            $tables[] = self::TABLE_JOURNAL;
        }

        $ok = true;
        foreach ($tables as $table) {
            if (!self::tableExists($table)) {
                continue;
            }
            $ok = self::commandOk(grr_sql_command("DELETE FROM ".self::table($table))) && $ok;
            grr_sql_command("ALTER TABLE ".self::table($table)." AUTO_INCREMENT = 1");
        }

        if ($ok && !$includeRolesAndJournal) {
            self::log('remise_zero_terminee', 'module', 0, 'fichiers supprimes : '.$deletedFiles, $login);
        }

        return array(
            'ok' => $ok,
            'deleted_files' => $deletedFiles,
        );
    }

    public static function log($type, $objectType, $objectId, $summary, $login)
    {
        if (!self::tableExists(self::TABLE_JOURNAL)) {
            return false;
        }

        return self::commandOk(grr_sql_command(
            "INSERT INTO ".self::table(self::TABLE_JOURNAL)."
                (type_evenement, type_objet, objet_id, resume, login, created_at)
            VALUES (?, ?, ?, ?, ?, ?)",
            "ssissi",
            array(
                self::limit($type, 50),
                self::limit($objectType, 50),
                (int) $objectId,
                self::limit($summary, 1000),
                self::limit($login, 190),
                time(),
            )
        ));
    }

    public static function table($suffix)
    {
        return TABLE_PREFIX.'_'.trim((string) $suffix);
    }

    private static function tableExists($suffix)
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

    private static function ensurePersonEmailColumn()
    {
        if (!self::tableExists(self::TABLE_PERSON)) {
            return false;
        }

        if (!self::columnExists(self::TABLE_PERSON, 'email')) {
            if (!self::commandOk(grr_sql_command(
                "ALTER TABLE ".self::table(self::TABLE_PERSON)." ADD COLUMN `email` varchar(190) NOT NULL DEFAULT '' AFTER `login_grr`"
            ))) {
                return false;
            }
        }

        if (!self::indexExists(self::TABLE_PERSON, 'email')) {
            if (!self::commandOk(grr_sql_command(
                "ALTER TABLE ".self::table(self::TABLE_PERSON)." ADD KEY `email` (`email`)"
            ))) {
                return false;
            }
        }

        return true;
    }

    private static function ensureItemLoanModeColumn()
    {
        if (!self::tableExists(self::TABLE_ITEM)) {
            return false;
        }

        if (!self::columnExists(self::TABLE_ITEM, 'pret_multiple')) {
            if (!self::commandOk(grr_sql_command(
                "ALTER TABLE ".self::table(self::TABLE_ITEM)." ADD COLUMN `pret_multiple` tinyint(1) NOT NULL DEFAULT 0 AFTER `statut`"
            ))) {
                return false;
            }
        }

        if (!self::indexExists(self::TABLE_ITEM, 'pret_multiple')) {
            if (!self::commandOk(grr_sql_command(
                "ALTER TABLE ".self::table(self::TABLE_ITEM)." ADD KEY `pret_multiple` (`pret_multiple`)"
            ))) {
                return false;
            }
        }

        return true;
    }

    private static function columnExists($suffix, $column)
    {
        $tableName = self::table($suffix);
        $column = preg_replace('/[^a-z0-9_]/i', '', (string) $column);
        if ($tableName === TABLE_PREFIX.'_' || $column === '') {
            return false;
        }

        $count = grr_sql_query1(
            "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
            "ss",
            array($tableName, $column)
        );

        return (int) $count > 0;
    }

    private static function indexExists($suffix, $index)
    {
        $tableName = self::table($suffix);
        $index = preg_replace('/[^a-z0-9_]/i', '', (string) $index);
        if ($tableName === TABLE_PREFIX.'_' || $index === '') {
            return false;
        }

        $count = grr_sql_query1(
            "SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?",
            "ss",
            array($tableName, $index)
        );

        return (int) $count > 0;
    }

    private static function tableEngine($suffix)
    {
        $tableName = self::table($suffix);
        $engine = grr_sql_query1(
            "SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?",
            "s",
            array($tableName)
        );

        return is_string($engine) ? $engine : '';
    }

    private static function countRows($suffix)
    {
        if (!self::tableExists($suffix)) {
            return 0;
        }

        return (int) grr_sql_query1("SELECT COUNT(*) FROM ".self::table($suffix));
    }

    private static function countActiveRows($suffix)
    {
        if (!self::tableExists($suffix)) {
            return 0;
        }

        return (int) grr_sql_query1("SELECT COUNT(*) FROM ".self::table($suffix)." WHERE actif = 1");
    }

    private static function deleteStoredFiles($directory, $keepNames)
    {
        $directory = (string) $directory;
        $moduleDir = realpath(dirname(__DIR__));
        $realDirectory = realpath($directory);
        if (!$moduleDir || !$realDirectory || strpos($realDirectory, $moduleDir) !== 0 || !is_dir($realDirectory)) {
            return 0;
        }

        $keep = array();
        foreach ($keepNames as $name) {
            $keep[(string) $name] = true;
        }

        $count = 0;
        $files = scandir($realDirectory);
        if (!is_array($files)) {
            return 0;
        }
        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || isset($keep[$file])) {
                continue;
            }
            $path = $realDirectory.DIRECTORY_SEPARATOR.$file;
            if (is_file($path) && @unlink($path)) {
                $count++;
            }
        }

        return $count;
    }

    private static function rows($sql, $types = null, $params = null)
    {
        $rows = array();
        $result = grr_sql_query($sql, $types, $params);

        if (!$result) {
            return $rows;
        }

        for ($i = 0; ($row = grr_sql_row_keyed($result, $i)); $i++) {
            $rows[] = $row;
        }

        return $rows;
    }

    private static function one($sql, $types = null, $params = null)
    {
        $rows = self::rows($sql, $types, $params);
        return isset($rows[0]) ? $rows[0] : array();
    }

    private static function ensureSequence($prefix, $login)
    {
        if (!self::tableExists(self::TABLE_SEQUENCE)) {
            return false;
        }

        return self::commandOk(grr_sql_command(
            "INSERT INTO ".self::table(self::TABLE_SEQUENCE)."
                (prefixe, dernier_numero, updated_by, updated_at)
            VALUES (?, 0, ?, ?)
            ON DUPLICATE KEY UPDATE prefixe = VALUES(prefixe)",
            "ssi",
            array(self::limit($prefix, 20), self::limit($login, 190), time())
        ));
    }

    private static function legacyPersonIdentifierAvailable($identifier, $currentId)
    {
        $identifier = self::limit($identifier, 100);
        if ($identifier === '' || !self::tableExists(self::TABLE_PERSON)) {
            return true;
        }

        $count = grr_sql_query1(
            "SELECT COUNT(*) FROM ".self::table(self::TABLE_PERSON)." WHERE identifiant_legacy = ? AND id <> ?",
            "si",
            array($identifier, (int) $currentId)
        );

        return (int) $count === 0;
    }

    private static function activeLoginExists($login)
    {
        $login = self::limit($login, 190);
        if ($login === '') {
            return true;
        }

        $count = grr_sql_query1(
            "SELECT COUNT(*) FROM ".TABLE_PREFIX."_utilisateurs WHERE login = ? AND etat != 'inactif'",
            "s",
            array($login)
        );

        if ((int) $count === 1) {
            return true;
        }

        return class_exists('InformatiqueMaterielLdapDirectory')
            && InformatiqueMaterielLdapDirectory::loginExists($login);
    }

    private static function emailForDirectoryLogin($login)
    {
        $login = self::limit($login, 190);
        if ($login === '' || !class_exists('InformatiqueMaterielLdapDirectory')) {
            return '';
        }

        $suggestion = InformatiqueMaterielLdapDirectory::suggestionForLogin($login);
        if (!$suggestion || !isset($suggestion['email'])) {
            return '';
        }

        $email = self::limit($suggestion['email'], 190);
        return $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false ? $email : '';
    }

    private static function activeCategoryExists($id)
    {
        if ((int) $id <= 0 || !self::tableExists(self::TABLE_CATEGORY)) {
            return false;
        }

        $count = grr_sql_query1(
            "SELECT COUNT(*) FROM ".self::table(self::TABLE_CATEGORY)." WHERE id = ? AND actif = 1",
            "i",
            array((int) $id)
        );

        return (int) $count === 1;
    }

    private static function itemIdentifierAvailable($identifier, $currentId)
    {
        $identifier = self::limit($identifier, 100);
        if ($identifier === '' || !self::tableExists(self::TABLE_ITEM)) {
            return true;
        }

        $count = grr_sql_query1(
            "SELECT COUNT(*) FROM ".self::table(self::TABLE_ITEM)." WHERE identifiant = ? AND id <> ?",
            "si",
            array($identifier, (int) $currentId)
        );

        return (int) $count === 0;
    }

    private static function legacyItemIdentifierAvailable($identifier, $currentId)
    {
        $identifier = self::limit($identifier, 100);
        if ($identifier === '' || !self::tableExists(self::TABLE_ITEM)) {
            return true;
        }

        $count = grr_sql_query1(
            "SELECT COUNT(*) FROM ".self::table(self::TABLE_ITEM)." WHERE identifiant_legacy = ? AND id <> ?",
            "si",
            array($identifier, (int) $currentId)
        );

        return (int) $count === 0;
    }

    private static function itemBarcodeAvailable($barcode, $currentId)
    {
        $barcode = self::limit($barcode, 100);
        if ($barcode === '' || !self::tableExists(self::TABLE_ITEM)) {
            return true;
        }

        $count = grr_sql_query1(
            "SELECT COUNT(*) FROM ".self::table(self::TABLE_ITEM)." WHERE code_barre_usmb = ? AND id <> ?",
            "si",
            array($barcode, (int) $currentId)
        );

        return (int) $count === 0;
    }

    private static function generateItemIdentifier($categoryId, $login)
    {
        $category = self::category((int) $categoryId);
        if (!$category || (int) $category['actif'] !== 1 || (string) $category['prefixe'] === '') {
            return '';
        }

        $prefix = self::limit($category['prefixe'], 20);
        self::ensureSequence($prefix, $login);

        $db = (isset($GLOBALS['db_c']) && is_object($GLOBALS['db_c'])) ? $GLOBALS['db_c'] : null;
        if ($db && method_exists($db, 'begin_transaction')) {
            try {
                $db->begin_transaction();
                $identifier = self::nextIdentifierInSequence($prefix, $login);
                if ($identifier === '') {
                    $db->rollback();
                    return '';
                }
                $db->commit();
                return $identifier;
            } catch (Throwable $exception) {
                if (method_exists($db, 'rollback')) {
                    $db->rollback();
                }
                return '';
            }
        }

        return self::nextIdentifierInSequence($prefix, $login);
    }

    private static function nextIdentifierInSequence($prefix, $login)
    {
        $current = grr_sql_query1(
            "SELECT dernier_numero FROM ".self::table(self::TABLE_SEQUENCE)." WHERE prefixe = ? FOR UPDATE",
            "s",
            array($prefix)
        );
        if ($current === false || (int) $current < 0) {
            return '';
        }

        $next = (int) $current + 1;
        $guard = 0;
        while (!self::itemIdentifierAvailable($prefix.'_'.$next, 0) && $guard < 10000) {
            $next++;
            $guard++;
        }
        if ($guard >= 10000) {
            return '';
        }

        $ok = self::commandOk(grr_sql_command(
            "UPDATE ".self::table(self::TABLE_SEQUENCE)." SET dernier_numero = ?, updated_by = ?, updated_at = ? WHERE prefixe = ?",
            "isis",
            array((int) $next, self::limit($login, 190), time(), $prefix)
        ));

        return $ok ? $prefix.'_'.$next : '';
    }

    private static function countDuplicateGroups($suffix, $column)
    {
        if (!self::tableExists($suffix)) {
            return 0;
        }

        $column = preg_replace('/[^a-z0-9_]/i', '', (string) $column);
        if ($column === '') {
            return 0;
        }

        $count = grr_sql_query1(
            "SELECT COUNT(*) FROM (
                SELECT `".$column."`
                FROM ".self::table($suffix)."
                WHERE `".$column."` IS NOT NULL AND `".$column."` <> ''
                GROUP BY `".$column."`
                HAVING COUNT(*) > 1
            ) duplicates"
        );

        return (int) $count;
    }

    private static function loanSelectSql()
    {
        return "SELECT p.*,
                pe.prenom AS personne_prenom, pe.nom AS personne_nom,
                pe.identifiant_legacy AS personne_identifiant, pe.date_depart AS personne_date_depart,
                i.identifiant AS item_identifiant, i.designation AS item_designation,
                i.statut AS item_statut, i.pret_multiple AS item_pret_multiple, i.actif AS item_actif,
                c.prefixe AS categorie_prefixe, c.designation AS categorie_designation
            FROM ".self::table(self::TABLE_LOAN)." p
            LEFT JOIN ".self::table(self::TABLE_PERSON)." pe ON pe.id = p.personne_id
            LEFT JOIN ".self::table(self::TABLE_ITEM)." i ON i.id = p.item_id
            LEFT JOIN ".self::table(self::TABLE_CATEGORY)." c ON c.id = i.categorie_id";
    }

    private static function validateTransferBaseValues($values)
    {
        $errors = array();
        if ((int) $values['personne_id'] <= 0) {
            $errors[] = 'La personne destinataire est obligatoire.';
        }
        if ($values['date_transfert'] === null || $values['date_transfert'] === false) {
            $errors[] = 'La date de transfert est obligatoire au format AAAA-MM-JJ.';
        }
        if (trim((string) $values['commentaire']) === '') {
            $errors[] = 'Le commentaire de transfert est obligatoire.';
        }

        return $errors;
    }

    private static function validateTransferLoan($loan, $targetPerson, $values)
    {
        $errors = array();
        if (!$loan || !isset($loan['id'])) {
            return array('Pret a transferer introuvable.');
        }
        if ((string) $loan['statut'] !== 'ouvert') {
            $errors[] = 'Seul un pret ouvert peut etre transfere.';
        }
        if (!$targetPerson || !isset($targetPerson['id']) || (int) $targetPerson['actif'] !== 1) {
            $errors[] = 'La personne destinataire est introuvable ou inactive.';
        } elseif ((int) $targetPerson['id'] === (int) $loan['personne_id']) {
            $errors[] = 'La personne destinataire doit etre differente de la personne actuelle.';
        }
        if ((int) $loan['item_actif'] !== 1) {
            $errors[] = 'Un materiel archive ne peut pas etre transfere.';
        }
        if (isset($loan['item_pret_multiple']) && (int) $loan['item_pret_multiple'] !== 1
            && self::openLoanForItem((int) $loan['item_id'], (int) $loan['id'], true)
        ) {
            $errors[] = 'Ce materiel possede deja un autre pret ouvert.';
        }
        if (is_string($values['date_transfert']) && (string) $values['date_transfert'] < (string) $loan['date_debut']) {
            $errors[] = 'La date de transfert ne peut pas preceder la date de debut du pret.';
        }
        if (isset($targetPerson['date_depart']) && trim((string) $targetPerson['date_depart']) !== ''
            && is_string($values['date_transfert'])
            && (string) $targetPerson['date_depart'] < (string) $values['date_transfert']
        ) {
            $errors[] = 'La date de depart de la personne destinataire precede la date de transfert.';
        }

        return $errors;
    }

    private static function validateLoanCreationValues($values)
    {
        $errors = array();
        if ((int) $values['personne_id'] <= 0 || !self::activePersonExists((int) $values['personne_id'])) {
            $errors[] = 'La personne du pret est obligatoire et doit etre active.';
        }
        if ((int) $values['item_id'] <= 0) {
            $errors[] = 'Le materiel du pret est obligatoire.';
        } else {
            $item = self::item((int) $values['item_id']);
            $itemErrors = self::loanableItemErrors($item);
            foreach ($itemErrors as $error) {
                $errors[] = $error;
            }
            if (!self::isMultipleLoanItem($item) && self::openLoanForItem((int) $values['item_id'], 0, false)) {
                $errors[] = 'Ce materiel a deja un pret ouvert.';
            }
        }
        if ($values['date_debut'] === null || $values['date_debut'] === false) {
            $errors[] = 'La date de debut est obligatoire au format AAAA-MM-JJ.';
        }
        if ($values['date_fin_prevue'] === false) {
            $errors[] = 'La date de fin prevue doit etre au format AAAA-MM-JJ.';
        }
        if (is_string($values['date_debut']) && is_string($values['date_fin_prevue'])
            && $values['date_fin_prevue'] < $values['date_debut']) {
            $errors[] = 'La date de fin prevue ne peut pas preceder la date de debut.';
        }

        return $errors;
    }

    private static function validateLoanCloseValues($values)
    {
        $errors = array();
        if ((int) $values['id'] <= 0) {
            $errors[] = 'Le pret est obligatoire.';
        }
        if ($values['date_fin_effective'] === null || $values['date_fin_effective'] === false) {
            $errors[] = 'La date de retour effective est obligatoire au format AAAA-MM-JJ.';
        }

        return $errors;
    }

    private static function validateImportedLoanValues($values)
    {
        $errors = array();
        if ((int) $values['personne_id'] <= 0 || !self::activePersonExists((int) $values['personne_id'])) {
            $errors[] = 'La personne du pret est obligatoire et doit etre active.';
        }
        if ((int) $values['item_id'] <= 0 || !self::item((int) $values['item_id'])) {
            $errors[] = 'Le materiel du pret est obligatoire.';
        }
        if ($values['date_debut'] === null || $values['date_debut'] === false) {
            $errors[] = 'La date de debut est obligatoire au format AAAA-MM-JJ.';
        }
        if ($values['date_fin_prevue'] === false) {
            $errors[] = 'La date de fin prevue doit etre au format AAAA-MM-JJ.';
        }
        if ($values['date_fin_effective'] === false) {
            $errors[] = 'La date de retour effective doit etre au format AAAA-MM-JJ.';
        }
        if (is_string($values['date_debut']) && is_string($values['date_fin_prevue'])
            && $values['date_fin_prevue'] < $values['date_debut']) {
            $errors[] = 'La date de fin prevue ne peut pas preceder la date de debut.';
        }
        if (is_string($values['date_debut']) && is_string($values['date_fin_effective'])
            && $values['date_fin_effective'] < $values['date_debut']) {
            $errors[] = 'La date de retour ne peut pas preceder la date de debut.';
        }

        return $errors;
    }

    private static function loanableItemErrors($item)
    {
        $errors = array();
        if (!$item || !isset($item['id'])) {
            return array('Materiel introuvable.');
        }
        if ((int) $item['actif'] !== 1) {
            $errors[] = 'Un materiel archive ne peut pas etre prete.';
        }

        $status = isset($item['statut']) ? (string) $item['statut'] : '';
        $multipleLoanItem = self::isMultipleLoanItem($item);
        if (in_array($status, array('archive', 'maintenance', 'a_reformer'), true)
            || (!$multipleLoanItem && in_array($status, array('en_pret', 'pret_multiple'), true))
        ) {
            $errors[] = 'Le statut courant du materiel ne permet pas un nouveau pret.';
        }

        return $errors;
    }

    private static function activePersonExists($id)
    {
        if ((int) $id <= 0 || !self::tableExists(self::TABLE_PERSON)) {
            return false;
        }

        $count = grr_sql_query1(
            "SELECT COUNT(*) FROM ".self::table(self::TABLE_PERSON)." WHERE id = ? AND actif = 1",
            "i",
            array((int) $id)
        );

        return (int) $count === 1;
    }

    private static function personForUpdate($id)
    {
        if ((int) $id <= 0 || !self::tableExists(self::TABLE_PERSON)) {
            return array();
        }

        return self::one(
            "SELECT * FROM ".self::table(self::TABLE_PERSON)." WHERE id = ? FOR UPDATE",
            "i",
            array((int) $id)
        );
    }

    private static function itemForUpdate($id)
    {
        if ((int) $id <= 0 || !self::tableExists(self::TABLE_ITEM)) {
            return array();
        }

        return self::one(
            "SELECT * FROM ".self::table(self::TABLE_ITEM)." WHERE id = ? FOR UPDATE",
            "i",
            array((int) $id)
        );
    }

    private static function loanForUpdate($id)
    {
        if ((int) $id <= 0 || !self::tableExists(self::TABLE_LOAN)) {
            return array();
        }

        return self::one(
            self::loanSelectSql()." WHERE p.id = ? FOR UPDATE",
            "i",
            array((int) $id)
        );
    }

    private static function openLoanForItem($itemId, $excludeLoanId = 0, $forUpdate = false)
    {
        $row = self::openLoanForItemRow($itemId, $excludeLoanId, $forUpdate);
        return isset($row['id']) && (int) $row['id'] > 0;
    }

    private static function openLoanForItemRow($itemId, $excludeLoanId = 0, $forUpdate = false)
    {
        if ((int) $itemId <= 0 || !self::tableExists(self::TABLE_LOAN)) {
            return array();
        }

        $sql = self::loanSelectSql()."
            WHERE p.item_id = ? AND p.statut = 'ouvert' AND p.id <> ?
            ORDER BY p.id
            LIMIT 1";
        if ($forUpdate) {
            $sql .= " FOR UPDATE";
        }

        return self::one($sql, "ii", array((int) $itemId, (int) $excludeLoanId));
    }

    private static function openLoanCountForItem($itemId, $excludeLoanId = 0)
    {
        if ((int) $itemId <= 0 || !self::tableExists(self::TABLE_LOAN)) {
            return 0;
        }

        return (int) grr_sql_query1(
            "SELECT COUNT(*) FROM ".self::table(self::TABLE_LOAN)."
            WHERE item_id = ? AND statut = 'ouvert' AND id <> ?",
            "ii",
            array((int) $itemId, (int) $excludeLoanId)
        );
    }

    private static function openLoansForPersonForUpdate($personId)
    {
        if ((int) $personId <= 0 || !self::tableExists(self::TABLE_LOAN)) {
            return array();
        }

        return self::rows(
            self::loanSelectSql()."
            WHERE p.personne_id = ? AND p.statut = 'ouvert'
            ORDER BY p.date_debut, p.id
            FOR UPDATE",
            "i",
            array((int) $personId)
        );
    }

    private static function transferLockedLoan($loan, $targetPerson, $values, $login, $now)
    {
        $loanId = (int) $loan['id'];
        $targetPersonId = (int) $targetPerson['id'];
        $dateTransfert = (string) $values['date_transfert'];
        $commentaire = trim((string) $values['commentaire']);
        $targetLabel = self::personSummary($targetPerson);
        $sourceLabel = self::loanPersonSummary($loan);

        $oldComment = self::mergeComment(
            $loan['commentaire'],
            'Vers '.$targetLabel.' - '.$commentaire,
            'Transfert'
        );

        $ok = self::commandOk(grr_sql_command(
            "UPDATE ".self::table(self::TABLE_LOAN)."
            SET date_fin_effective = ?, commentaire = ?, statut = 'clos',
                updated_by = ?, updated_at = ?
            WHERE id = ? AND statut = 'ouvert'",
            "sssii",
            array($dateTransfert, $oldComment, $login, (int) $now, $loanId)
        ));
        if (!$ok) {
            return 0;
        }

        if (isset($targetPerson['date_depart']) && trim((string) $targetPerson['date_depart']) !== '') {
            $dateFinPrevue = (string) $targetPerson['date_depart'];
        } else {
            $dateFinPrevue = trim((string) $loan['date_fin_prevue']) === '' ? null : (string) $loan['date_fin_prevue'];
            if ($dateFinPrevue !== null && $dateFinPrevue < $dateTransfert) {
                $dateFinPrevue = null;
            }
        }

        $newComment = self::mergeComment(
            '',
            'Depuis '.$sourceLabel.' - pret #'.$loanId.' - '.$commentaire,
            'Transfert'
        );

        $ok = self::commandOk(grr_sql_command(
            "INSERT INTO ".self::table(self::TABLE_LOAN)."
                (personne_id, item_id, localisation, date_debut, date_fin_prevue,
                 date_fin_effective, commentaire, statut, source_import_id,
                 created_by, created_at, updated_by, updated_at)
            VALUES (?, ?, ?, ?, ?, NULL, ?, 'ouvert', 0, ?, ?, ?, ?)",
            "iisssssisi",
            array(
                $targetPersonId,
                (int) $loan['item_id'],
                self::limit($loan['localisation'], 190),
                $dateTransfert,
                $dateFinPrevue,
                $newComment,
                $login,
                (int) $now,
                $login,
                (int) $now,
            )
        ));
        $newLoanId = $ok ? self::lastInsertId() : 0;
        if (!$ok || $newLoanId <= 0) {
            return 0;
        }

        if (!self::syncItemLoanStatus((int) $loan['item_id'], $login, $now)) {
            return 0;
        }

        self::log(
            'pret_transfere',
            'pret',
            $newLoanId,
            'Pret #'.$loanId.' de '.$sourceLabel.' vers '.$targetLabel,
            $login
        );

        return $newLoanId;
    }

    private static function isMultipleLoanItem($item)
    {
        return is_array($item)
            && isset($item['pret_multiple'])
            && (int) $item['pret_multiple'] === 1;
    }

    private static function loanDrivenItemStatus($openLoanCount, $multipleLoanItem)
    {
        $openLoanCount = (int) $openLoanCount;
        if ($openLoanCount <= 0) {
            return 'stocke';
        }

        return $multipleLoanItem && $openLoanCount > 1 ? 'pret_multiple' : 'en_pret';
    }

    private static function syncItemLoanStatus($itemId, $login, $now)
    {
        $item = self::itemForUpdate((int) $itemId);
        if (!$item || !isset($item['id'])) {
            return false;
        }

        $status = self::loanDrivenItemStatus(
            self::openLoanCountForItem((int) $itemId),
            self::isMultipleLoanItem($item)
        );

        return self::commandOk(grr_sql_command(
            "UPDATE ".self::table(self::TABLE_ITEM)."
            SET statut = ?, updated_by = ?, updated_at = ?
            WHERE id = ?",
            "ssii",
            array($status, self::limit($login, 190), (int) $now, (int) $itemId)
        ));
    }

    private static function countOpenLoans()
    {
        if (!self::tableExists(self::TABLE_LOAN)) {
            return 0;
        }

        return (int) grr_sql_query1(
            "SELECT COUNT(*) FROM ".self::table(self::TABLE_LOAN)." WHERE statut = 'ouvert'"
        );
    }

    private static function countLateLoans()
    {
        if (!self::tableExists(self::TABLE_LOAN)) {
            return 0;
        }

        return (int) grr_sql_query1(
            "SELECT COUNT(*)
            FROM ".self::table(self::TABLE_LOAN)."
            WHERE statut = 'ouvert'
              AND date_fin_prevue IS NOT NULL
              AND date_fin_prevue < CURRENT_DATE()"
        );
    }

    private static function countItemsMissingIdentifier()
    {
        if (!self::tableExists(self::TABLE_ITEM)) {
            return 0;
        }

        return (int) grr_sql_query1(
            "SELECT COUNT(*)
            FROM ".self::table(self::TABLE_ITEM)."
            WHERE actif = 1 AND (identifiant IS NULL OR identifiant = '')"
        );
    }

    private static function countActiveDuplicateBarcodes()
    {
        if (!self::tableExists(self::TABLE_ITEM)) {
            return 0;
        }

        return (int) grr_sql_query1(
            "SELECT COUNT(*) FROM (
                SELECT code_barre_usmb
                FROM ".self::table(self::TABLE_ITEM)."
                WHERE actif = 1 AND code_barre_usmb IS NOT NULL AND code_barre_usmb <> ''
                GROUP BY code_barre_usmb
                HAVING COUNT(*) > 1
            ) duplicates"
        );
    }

    private static function countDepartedPeopleWithOpenLoans()
    {
        if (!self::tableExists(self::TABLE_LOAN) || !self::tableExists(self::TABLE_PERSON)) {
            return 0;
        }

        return (int) grr_sql_query1(
            "SELECT COUNT(*)
            FROM ".self::table(self::TABLE_LOAN)." p
            JOIN ".self::table(self::TABLE_PERSON)." pe ON pe.id = p.personne_id
            WHERE p.statut = 'ouvert'
              AND pe.date_depart IS NOT NULL
              AND pe.date_depart < CURRENT_DATE()"
        );
    }

    private static function alertRow($type, $severity, $label, $detail, $referenceDate, $loanId, $itemId, $personId, $query, $loanNumber = '', $commentaire = '')
    {
        $loanId = (int) $loanId;
        $loanNumber = trim((string) $loanNumber);
        if ($loanNumber === '' && $loanId > 0) {
            $loanNumber = '#'.$loanId;
        }

        return array(
            'type' => (string) $type,
            'severity' => (string) $severity,
            'label' => (string) $label,
            'detail' => (string) $detail,
            'reference_date' => (string) $referenceDate,
            'pret_id' => $loanId,
            'pret_numero' => $loanNumber,
            'commentaire' => trim((string) $commentaire),
            'item_id' => (int) $itemId,
            'personne_id' => (int) $personId,
            'query' => (string) $query,
        );
    }

    private static function loanAlertLabel($loan)
    {
        $item = trim((string) $loan['item_identifiant'].' - '.(string) $loan['item_designation']);
        if ($item === '-') {
            $item = 'Materiel #'.(int) $loan['item_id'];
        }
        $person = trim((string) $loan['personne_prenom'].' '.(string) $loan['personne_nom']);
        if ($person === '') {
            $person = 'Personne #'.(int) $loan['personne_id'];
        }

        return $item.' / '.$person;
    }

    private static function personSummary($person)
    {
        $label = trim((string) $person['prenom'].' '.(string) $person['nom']);
        return $label !== '' ? $label : 'Personne #'.(int) $person['id'];
    }

    private static function loanPersonSummary($loan)
    {
        $label = trim((string) $loan['personne_prenom'].' '.(string) $loan['personne_nom']);
        return $label !== '' ? $label : 'Personne #'.(int) $loan['personne_id'];
    }

    private static function itemAlertLabel($item)
    {
        $identifier = isset($item['identifiant']) ? trim((string) $item['identifiant']) : '';
        $designation = isset($item['designation']) ? trim((string) $item['designation']) : '';
        $label = trim($identifier.' - '.$designation);
        if ($label === '-') {
            $label = '';
        }
        if ($label === '') {
            $label = isset($item['item_id']) ? 'Materiel #'.(int) $item['item_id'] : 'Materiel #'.(int) $item['id'];
        }

        return $label;
    }

    private static function sortAlerts($a, $b)
    {
        $priority = array(
            'pret_en_retard' => 0,
            'personne_partie' => 1,
            'prets_ouverts_multiples' => 2,
            'materiel_sans_identifiant' => 3,
            'materiel_sans_categorie' => 4,
            'code_barre_duplique' => 5,
        );
        $typeA = isset($a['type']) ? (string) $a['type'] : '';
        $typeB = isset($b['type']) ? (string) $b['type'] : '';
        $priorityA = isset($priority[$typeA]) ? (int) $priority[$typeA] : 99;
        $priorityB = isset($priority[$typeB]) ? (int) $priority[$typeB] : 99;
        if ($priorityA !== $priorityB) {
            return $priorityA < $priorityB ? -1 : 1;
        }

        $dateA = isset($a['reference_date']) ? (string) $a['reference_date'] : '';
        $dateB = isset($b['reference_date']) ? (string) $b['reference_date'] : '';
        if ($dateA !== $dateB) {
            if ($dateA === '') {
                return 1;
            }
            if ($dateB === '') {
                return -1;
            }
            return strcmp($dateA, $dateB);
        }

        return strcmp((string) $a['label'], (string) $b['label']);
    }

    private static function mergeComment($current, $addition, $label)
    {
        $current = trim((string) $current);
        $addition = trim((string) $addition);
        if ($addition === '') {
            return self::limit($current, 4000);
        }

        $line = '['.date('Y-m-d H:i').'] '.self::limit($label, 40).' : '.$addition;
        $merged = $current === '' ? $line : $current."\n".$line;

        return self::limit($merged, 4000);
    }

    private static function beginTransaction()
    {
        $db = self::database();
        if ($db && method_exists($db, 'begin_transaction')) {
            $db->begin_transaction();
            return true;
        }

        return false;
    }

    private static function commitTransaction($active)
    {
        $db = self::database();
        if ($active && $db && method_exists($db, 'commit')) {
            $db->commit();
        }
    }

    private static function rollbackTransaction($active)
    {
        $db = self::database();
        if ($active && $db && method_exists($db, 'rollback')) {
            $db->rollback();
        }
    }

    private static function database()
    {
        return (isset($GLOBALS['db_c']) && is_object($GLOBALS['db_c'])) ? $GLOBALS['db_c'] : null;
    }

    private static function nullableDate($date)
    {
        $date = trim((string) $date);
        if ($date === '') {
            return null;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1) {
            return false;
        }
        $parts = array_map('intval', explode('-', $date));

        return checkdate($parts[1], $parts[2], $parts[0]) ? $date : false;
    }

    private static function lastInsertId()
    {
        if (isset($GLOBALS['db_c']) && is_object($GLOBALS['db_c']) && isset($GLOBALS['db_c']->insert_id)) {
            return (int) $GLOBALS['db_c']->insert_id;
        }

        return 0;
    }

    private static function commandOk($result)
    {
        return !($result === false || $result < 0);
    }

    private static function limit($value, $length)
    {
        return substr(trim((string) $value), 0, (int) $length);
    }

    private static function normalizeBool($value)
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        $value = strtolower(trim((string) $value));
        return in_array($value, array('1', 'on', 'oui', 'yes', 'true', 'vrai', 'x'), true) ? 1 : 0;
    }
}
