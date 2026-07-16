<?php

class StockChimiqueRepository
{
    const TABLE_ROLE = 'stock_chimique_role';
    const TABLE_JOURNAL = 'stock_chimique_journal';
    const TABLE_SUPPLIER = 'stock_chimique_fournisseur';
    const TABLE_LOCATION = 'stock_chimique_emplacement';
    const TABLE_PRODUCT = 'stock_chimique_produit';
    const TABLE_CONTAINER = 'stock_chimique_contenant';
    const TABLE_MOVEMENT = 'stock_chimique_mouvement';
    const TABLE_DOCUMENT = 'stock_chimique_document';
    const TABLE_INVENTORY = 'stock_chimique_inventaire';
    const TABLE_INVENTORY_LINE = 'stock_chimique_inventaire_ligne';
    const TABLE_NOTIFICATION_LOG = 'stock_chimique_notification_log';
    const TABLE_IMPORT_LOG = 'stock_chimique_import_log';

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
            "CREATE TABLE IF NOT EXISTS `".self::table(self::TABLE_SUPPLIER)."` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `nom` varchar(190) NOT NULL,
                `adresse` text NULL,
                `contact` varchar(190) NOT NULL DEFAULT '',
                `telephone` varchar(50) NOT NULL DEFAULT '',
                `email` varchar(190) NOT NULL DEFAULT '',
                `site_web` varchar(255) NOT NULL DEFAULT '',
                `notes` text NULL,
                `actif` tinyint(1) NOT NULL DEFAULT 1,
                `created_by` varchar(190) NOT NULL DEFAULT '',
                `created_at` int(11) NOT NULL DEFAULT 0,
                `updated_by` varchar(190) NOT NULL DEFAULT '',
                `updated_at` int(11) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                KEY `nom` (`nom`),
                KEY `actif` (`actif`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `".self::table(self::TABLE_LOCATION)."` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `parent_id` int(11) NOT NULL DEFAULT 0,
                `code` varchar(100) NOT NULL,
                `nom` varchar(190) NOT NULL,
                `type_emplacement` varchar(30) NOT NULL DEFAULT 'autre',
                `responsable` varchar(190) NOT NULL DEFAULT '',
                `description` text NULL,
                `actif` tinyint(1) NOT NULL DEFAULT 1,
                `created_by` varchar(190) NOT NULL DEFAULT '',
                `created_at` int(11) NOT NULL DEFAULT 0,
                `updated_by` varchar(190) NOT NULL DEFAULT '',
                `updated_at` int(11) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                UNIQUE KEY `code` (`code`),
                KEY `parent_id` (`parent_id`),
                KEY `type_emplacement` (`type_emplacement`),
                KEY `actif` (`actif`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `".self::table(self::TABLE_PRODUCT)."` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `reference_interne` varchar(100) DEFAULT NULL,
                `nom_commercial` varchar(190) NOT NULL,
                `fournisseur_id` int(11) NOT NULL DEFAULT 0,
                `reference_fournisseur` varchar(100) NOT NULL DEFAULT '',
                `fabricant` varchar(190) NOT NULL DEFAULT '',
                `numero_cas` varchar(190) NOT NULL DEFAULT '',
                `numero_ce` varchar(50) NOT NULL DEFAULT '',
                `ufi` varchar(50) NOT NULL DEFAULT '',
                `etat_physique` varchar(30) NOT NULL DEFAULT 'non_renseigne',
                `unite_stock` varchar(10) NOT NULL,
                `categorie` varchar(100) NOT NULL DEFAULT '',
                `pictogrammes_clp` varchar(255) NOT NULL DEFAULT '',
                `mentions_h` text NULL,
                `conseils_p` text NULL,
                `statut_cmr` varchar(20) NOT NULL DEFAULT 'non_renseigne',
                `conditions_stockage` text NULL,
                `seuil_minimal` decimal(15,4) NOT NULL DEFAULT 0.0000,
                `description` text NULL,
                `notes` text NULL,
                `actif` tinyint(1) NOT NULL DEFAULT 1,
                `created_by` varchar(190) NOT NULL DEFAULT '',
                `created_at` int(11) NOT NULL DEFAULT 0,
                `updated_by` varchar(190) NOT NULL DEFAULT '',
                `updated_at` int(11) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                UNIQUE KEY `reference_interne` (`reference_interne`),
                KEY `nom_commercial` (`nom_commercial`),
                KEY `fournisseur_id` (`fournisseur_id`),
                KEY `fabricant` (`fabricant`),
                KEY `numero_cas` (`numero_cas`),
                KEY `categorie` (`categorie`),
                KEY `statut_cmr` (`statut_cmr`),
                KEY `actif` (`actif`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `".self::table(self::TABLE_CONTAINER)."` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `produit_id` int(11) NOT NULL,
                `fournisseur_id` int(11) NOT NULL DEFAULT 0,
                `emplacement_id` int(11) NOT NULL,
                `code_interne` varchar(100) NOT NULL,
                `numero_lot` varchar(100) NOT NULL DEFAULT '',
                `conditionnement` varchar(190) NOT NULL DEFAULT '',
                `quantite_courante` decimal(15,4) NOT NULL DEFAULT 0.0000,
                `unite` varchar(10) NOT NULL,
                `date_reception` date DEFAULT NULL,
                `date_ouverture` date DEFAULT NULL,
                `date_peremption` date DEFAULT NULL,
                `statut` varchar(20) NOT NULL DEFAULT 'en_stock',
                `notes` text NULL,
                `created_by` varchar(190) NOT NULL DEFAULT '',
                `created_at` int(11) NOT NULL DEFAULT 0,
                `updated_by` varchar(190) NOT NULL DEFAULT '',
                `updated_at` int(11) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                UNIQUE KEY `code_interne` (`code_interne`),
                KEY `produit_id` (`produit_id`),
                KEY `fournisseur_id` (`fournisseur_id`),
                KEY `emplacement_id` (`emplacement_id`),
                KEY `numero_lot` (`numero_lot`),
                KEY `date_peremption` (`date_peremption`),
                KEY `statut` (`statut`),
                KEY `produit_statut` (`produit_id`, `statut`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `".self::table(self::TABLE_MOVEMENT)."` (
                `id` bigint(20) NOT NULL AUTO_INCREMENT,
                `contenant_id` int(11) NOT NULL,
                `type_mouvement` varchar(30) NOT NULL,
                `quantite` decimal(15,4) NOT NULL DEFAULT 0.0000,
                `quantite_avant` decimal(15,4) NOT NULL DEFAULT 0.0000,
                `quantite_apres` decimal(15,4) NOT NULL DEFAULT 0.0000,
                `unite` varchar(10) NOT NULL,
                `emplacement_source_id` int(11) NOT NULL DEFAULT 0,
                `emplacement_destination_id` int(11) NOT NULL DEFAULT 0,
                `mouvement_source_id` bigint(20) NOT NULL DEFAULT 0,
                `motif` text NULL,
                `date_effective` int(11) NOT NULL DEFAULT 0,
                `request_token` char(64) NOT NULL,
                `created_by` varchar(190) NOT NULL DEFAULT '',
                `created_at` int(11) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                UNIQUE KEY `request_token` (`request_token`),
                KEY `contenant_id` (`contenant_id`),
                KEY `type_mouvement` (`type_mouvement`),
                KEY `emplacement_source_id` (`emplacement_source_id`),
                KEY `emplacement_destination_id` (`emplacement_destination_id`),
                KEY `mouvement_source_id` (`mouvement_source_id`),
                KEY `date_effective` (`date_effective`),
                KEY `created_by` (`created_by`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `".self::table(self::TABLE_DOCUMENT)."` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `produit_id` int(11) NOT NULL,
                `type_document` varchar(30) NOT NULL DEFAULT 'autre',
                `langue` varchar(10) NOT NULL DEFAULT 'fr',
                `emetteur` varchar(190) NOT NULL DEFAULT '',
                `date_revision` date DEFAULT NULL,
                `numero_version` varchar(100) NOT NULL DEFAULT '',
                `est_courant` tinyint(1) NOT NULL DEFAULT 0,
                `description` text NULL,
                `original_name` varchar(255) NOT NULL,
                `stored_name` char(64) NOT NULL,
                `mime_type` varchar(190) NOT NULL DEFAULT 'application/octet-stream',
                `taille` int(11) NOT NULL DEFAULT 0,
                `sha256` char(64) NOT NULL,
                `actif` tinyint(1) NOT NULL DEFAULT 1,
                `uploaded_by` varchar(190) NOT NULL DEFAULT '',
                `created_at` int(11) NOT NULL DEFAULT 0,
                `fds_validated_by` varchar(190) NOT NULL DEFAULT '',
                `fds_validated_at` int(11) NOT NULL DEFAULT 0,
                `archived_by` varchar(190) NOT NULL DEFAULT '',
                `archived_at` int(11) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                UNIQUE KEY `stored_name` (`stored_name`),
                KEY `produit_id` (`produit_id`),
                KEY `type_document` (`type_document`),
                KEY `date_revision` (`date_revision`),
                KEY `fds_courante` (`produit_id`, `type_document`, `langue`, `est_courant`),
                KEY `sha256` (`sha256`),
                KEY `actif` (`actif`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `".self::table(self::TABLE_INVENTORY)."` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `libelle` varchar(190) NOT NULL,
                `emplacement_id` int(11) NOT NULL DEFAULT 0,
                `statut` varchar(20) NOT NULL DEFAULT 'ouvert',
                `opened_by` varchar(190) NOT NULL DEFAULT '',
                `opened_at` int(11) NOT NULL DEFAULT 0,
                `completed_by` varchar(190) NOT NULL DEFAULT '',
                `completed_at` int(11) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                KEY `emplacement_id` (`emplacement_id`),
                KEY `statut` (`statut`),
                KEY `opened_at` (`opened_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `".self::table(self::TABLE_INVENTORY_LINE)."` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `inventaire_id` int(11) NOT NULL,
                `contenant_id` int(11) NOT NULL,
                `quantite_attendue` decimal(15,4) NOT NULL DEFAULT 0.0000,
                `quantite_comptee` decimal(15,4) DEFAULT NULL,
                `ecart` decimal(15,4) DEFAULT NULL,
                `dernier_mouvement_id` bigint(20) NOT NULL DEFAULT 0,
                `statut` varchar(20) NOT NULL DEFAULT 'a_compter',
                `commentaire` text NULL,
                `updated_by` varchar(190) NOT NULL DEFAULT '',
                `updated_at` int(11) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                UNIQUE KEY `inventaire_contenant` (`inventaire_id`, `contenant_id`),
                KEY `inventaire_id` (`inventaire_id`),
                KEY `contenant_id` (`contenant_id`),
                KEY `statut` (`statut`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `".self::table(self::TABLE_NOTIFICATION_LOG)."` (
                `id` bigint(20) NOT NULL AUTO_INCREMENT,
                `alert_key` char(64) NOT NULL,
                `login` varchar(190) NOT NULL,
                `type_notification` varchar(50) NOT NULL,
                `objet_id` int(11) NOT NULL DEFAULT 0,
                `sent_at` int(11) NOT NULL DEFAULT 0,
                `status` varchar(20) NOT NULL DEFAULT 'sent',
                `message` text NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `alert_login` (`alert_key`, `login`),
                KEY `type_notification` (`type_notification`),
                KEY `objet_id` (`objet_id`),
                KEY `sent_at` (`sent_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
            "CREATE TABLE IF NOT EXISTS `".self::table(self::TABLE_IMPORT_LOG)."` (
                `id` bigint(20) NOT NULL AUTO_INCREMENT,
                `package_hash` char(64) NOT NULL,
                `package_name` varchar(190) NOT NULL DEFAULT '',
                `source_row` int(11) NOT NULL,
                `product_id` int(11) NOT NULL DEFAULT 0,
                `container_id` int(11) NOT NULL DEFAULT 0,
                `document_id` int(11) NOT NULL DEFAULT 0,
                `status` varchar(20) NOT NULL DEFAULT 'success',
                `message` text NULL,
                `created_by` varchar(190) NOT NULL DEFAULT '',
                `created_at` int(11) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                UNIQUE KEY `package_row` (`package_hash`, `source_row`),
                KEY `package_name` (`package_name`),
                KEY `status` (`status`),
                KEY `created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8",
        );

        foreach ($commands as $sql) {
            $result = grr_sql_command($sql);
            if ($result === false || $result < 0) {
                return false;
            }
        }

        if (self::columnLength(self::TABLE_PRODUCT, 'numero_cas') < 190) {
            $casUpgrade = grr_sql_command(
                "ALTER TABLE `".self::table(self::TABLE_PRODUCT)."` MODIFY `numero_cas` varchar(190) NOT NULL DEFAULT ''"
            );
            if ($casUpgrade === false || $casUpgrade < 0) {
                return false;
            }
        }
        if (!self::columnExists(self::TABLE_DOCUMENT, 'fds_validated_by')) {
            $validationAuthor = grr_sql_command(
                "ALTER TABLE `".self::table(self::TABLE_DOCUMENT)."`
                ADD `fds_validated_by` varchar(190) NOT NULL DEFAULT '' AFTER `created_at`"
            );
            if ($validationAuthor === false || $validationAuthor < 0) {
                return false;
            }
        }
        if (!self::columnExists(self::TABLE_DOCUMENT, 'fds_validated_at')) {
            $validationDate = grr_sql_command(
                "ALTER TABLE `".self::table(self::TABLE_DOCUMENT)."`
                ADD `fds_validated_at` int(11) NOT NULL DEFAULT 0 AFTER `fds_validated_by`"
            );
            if ($validationDate === false || $validationDate < 0) {
                return false;
            }
        }

        return true;
    }

    public static function expectedTables()
    {
        return array(
            self::TABLE_ROLE => 'Rôles',
            self::TABLE_JOURNAL => 'Journal',
            self::TABLE_SUPPLIER => 'Fournisseurs',
            self::TABLE_LOCATION => 'Emplacements',
            self::TABLE_PRODUCT => 'Produits',
            self::TABLE_CONTAINER => 'Contenants',
            self::TABLE_MOVEMENT => 'Mouvements',
            self::TABLE_DOCUMENT => 'Documents',
            self::TABLE_INVENTORY => 'Inventaires',
            self::TABLE_INVENTORY_LINE => 'Lignes d inventaire',
            self::TABLE_NOTIFICATION_LOG => 'Notifications',
            self::TABLE_IMPORT_LOG => 'Journal des imports',
        );
    }

    public static function diagnostics()
    {
        $rows = array();
        foreach (self::expectedTables() as $suffix => $label) {
            $rows[] = array(
                'label' => $label,
                'table' => self::table($suffix),
                'exists' => self::tableExists($suffix),
                'engine' => self::tableEngine($suffix),
            );
        }
        $rows[] = array(
            'label' => 'Stock non négatif',
            'table' => '',
            'exists' => self::countNegativeStocks() === 0,
            'engine' => '',
        );
        $rows[] = array(
            'label' => 'Cohérence dernier mouvement',
            'table' => '',
            'exists' => self::countStockMismatches() === 0,
            'engine' => '',
        );
        $rows[] = array(
            'label' => 'Une FDS courante par langue',
            'table' => '',
            'exists' => self::countDuplicateCurrentSds() === 0,
            'engine' => '',
        );

        return $rows;
    }

    public static function roles()
    {
        return array(
            'lecteur' => 'Lecteur',
            'operateur' => 'Opérateur',
            'gestionnaire' => 'Gestionnaire',
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
            self::log('role_modifie', 'role', 0, $login.' : '.($role === '' ? 'retiré' : $role), $updatedBy);
        }
        return $ok;
    }

    public static function supplier($id)
    {
        return self::one(
            "SELECT * FROM ".self::table(self::TABLE_SUPPLIER)." WHERE id = ?",
            "i",
            array((int) $id)
        );
    }

    public static function suppliers($includeArchived = false)
    {
        self::ensureTables();
        return self::rows(
            "SELECT * FROM ".self::table(self::TABLE_SUPPLIER)
            .($includeArchived ? "" : " WHERE actif = 1").
            " ORDER BY actif DESC, nom"
        );
    }

    public static function saveSupplier($source, $login)
    {
        $id = isset($source['id']) ? (int) $source['id'] : 0;
        $name = self::limit(isset($source['nom']) ? $source['nom'] : '', 190);
        if ($name === '') {
            return array('ok' => false, 'error' => 'Le nom du fournisseur est obligatoire.');
        }
        $email = self::limit(isset($source['email']) ? $source['email'] : '', 190);
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return array('ok' => false, 'error' => 'Adresse électronique invalide.');
        }

        $values = array(
            $name,
            trim((string) (isset($source['adresse']) ? $source['adresse'] : '')),
            self::limit(isset($source['contact']) ? $source['contact'] : '', 190),
            self::limit(isset($source['telephone']) ? $source['telephone'] : '', 50),
            $email,
            self::limit(isset($source['site_web']) ? $source['site_web'] : '', 255),
            trim((string) (isset($source['notes']) ? $source['notes'] : '')),
        );
        $login = self::limit($login, 190);
        $now = time();

        if ($id > 0) {
            $values[] = $login;
            $values[] = $now;
            $values[] = $id;
            $ok = self::commandOk(grr_sql_command(
                "UPDATE ".self::table(self::TABLE_SUPPLIER)."
                SET nom = ?, adresse = ?, contact = ?, telephone = ?, email = ?, site_web = ?, notes = ?,
                    updated_by = ?, updated_at = ?
                WHERE id = ? AND actif = 1",
                "ssssssssii",
                $values
            ));
        } else {
            $values[] = $login;
            $values[] = $now;
            $values[] = $login;
            $values[] = $now;
            $ok = self::commandOk(grr_sql_command(
                "INSERT INTO ".self::table(self::TABLE_SUPPLIER)."
                    (nom, adresse, contact, telephone, email, site_web, notes, created_by, created_at, updated_by, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                "ssssssssisi",
                $values
            ));
            $id = $ok ? (int) grr_sql_insert_id() : 0;
        }

        if ($ok) {
            self::log('fournisseur_enregistre', 'fournisseur', $id, $name, $login);
        }
        return array('ok' => $ok, 'id' => $id, 'error' => $ok ? '' : 'Enregistrement du fournisseur impossible.');
    }

    public static function archiveSupplier($id, $login)
    {
        $id = (int) $id;
        $used = (int) grr_sql_query1(
            "SELECT
                (SELECT COUNT(*) FROM ".self::table(self::TABLE_PRODUCT)." WHERE fournisseur_id = ? AND actif = 1)
                + (SELECT COUNT(*) FROM ".self::table(self::TABLE_CONTAINER)." WHERE fournisseur_id = ? AND statut = 'en_stock')",
            "ii",
            array($id, $id)
        );
        if ($id <= 0 || $used > 0) {
            return false;
        }

        $ok = self::commandOk(grr_sql_command(
            "UPDATE ".self::table(self::TABLE_SUPPLIER)." SET actif = 0, updated_by = ?, updated_at = ? WHERE id = ?",
            "sii",
            array(self::limit($login, 190), time(), $id)
        ));
        if ($ok) {
            self::log('fournisseur_archive', 'fournisseur', $id, '', $login);
        }
        return $ok;
    }

    public static function locationTypes()
    {
        return array(
            'site' => 'Site',
            'batiment' => 'Bâtiment',
            'local' => 'Local',
            'armoire' => 'Armoire',
            'refrigerateur' => 'Réfrigérateur',
            'etagere' => 'Étagère',
            'autre' => 'Autre',
        );
    }

    public static function location($id)
    {
        return self::one(
            "SELECT * FROM ".self::table(self::TABLE_LOCATION)." WHERE id = ?",
            "i",
            array((int) $id)
        );
    }

    public static function locations($includeArchived = false)
    {
        self::ensureTables();
        $locations = self::rows(
            "SELECT * FROM ".self::table(self::TABLE_LOCATION)
            .($includeArchived ? "" : " WHERE actif = 1").
            " ORDER BY actif DESC, nom"
        );
        $map = array();
        foreach ($locations as $location) {
            $map[(int) $location['id']] = $location;
        }
        foreach ($locations as $index => $location) {
            $locations[$index]['chemin'] = self::locationPath($location, $map);
        }
        usort($locations, function ($a, $b) {
            return strcmp((string) $a['chemin'], (string) $b['chemin']);
        });
        return $locations;
    }

    public static function saveLocation($source, $login)
    {
        $id = isset($source['id']) ? (int) $source['id'] : 0;
        $parentId = isset($source['parent_id']) ? (int) $source['parent_id'] : 0;
        $code = self::limit(isset($source['code']) ? $source['code'] : '', 100);
        $name = self::limit(isset($source['nom']) ? $source['nom'] : '', 190);
        $type = self::limit(isset($source['type_emplacement']) ? $source['type_emplacement'] : 'autre', 30);
        if ($code === '' || $name === '') {
            return array('ok' => false, 'error' => 'Le code et le nom de l emplacement sont obligatoires.');
        }
        $locationTypes = self::locationTypes();
        if (!isset($locationTypes[$type])) {
            return array('ok' => false, 'error' => 'Type d emplacement invalide.');
        }
        if ($id > 0 && ($parentId === $id || self::locationWouldCycle($id, $parentId))) {
            return array('ok' => false, 'error' => 'La hiérarchie créerait une boucle.');
        }
        if ($parentId > 0) {
            $parent = self::location($parentId);
            if (!$parent || (int) $parent['actif'] !== 1) {
                return array('ok' => false, 'error' => 'Emplacement parent invalide.');
            }
        }

        $login = self::limit($login, 190);
        $now = time();
        $values = array(
            $parentId,
            $code,
            $name,
            $type,
            self::limit(isset($source['responsable']) ? $source['responsable'] : '', 190),
            trim((string) (isset($source['description']) ? $source['description'] : '')),
        );
        if ($id > 0) {
            $values[] = $login;
            $values[] = $now;
            $values[] = $id;
            $ok = self::commandOk(grr_sql_command(
                "UPDATE ".self::table(self::TABLE_LOCATION)."
                SET parent_id = ?, code = ?, nom = ?, type_emplacement = ?, responsable = ?, description = ?,
                    updated_by = ?, updated_at = ?
                WHERE id = ? AND actif = 1",
                "issssssii",
                $values
            ));
        } else {
            $values[] = $login;
            $values[] = $now;
            $values[] = $login;
            $values[] = $now;
            $ok = self::commandOk(grr_sql_command(
                "INSERT INTO ".self::table(self::TABLE_LOCATION)."
                    (parent_id, code, nom, type_emplacement, responsable, description, created_by, created_at, updated_by, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                "issssssisi",
                $values
            ));
            $id = $ok ? (int) grr_sql_insert_id() : 0;
        }

        if ($ok) {
            self::log('emplacement_enregistre', 'emplacement', $id, $code.' - '.$name, $login);
        }
        return array('ok' => $ok, 'id' => $id, 'error' => $ok ? '' : 'Enregistrement de l emplacement impossible.');
    }

    public static function archiveLocation($id, $login)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return false;
        }
        $db = $GLOBALS['db_c'];
        $db->begin_transaction();
        try {
            $location = self::one(
                "SELECT id FROM ".self::table(self::TABLE_LOCATION)." WHERE id = ? AND actif = 1 FOR UPDATE",
                "i",
                array($id)
            );
            if (!$location) {
                throw new RuntimeException('Emplacement introuvable.');
            }
            $used = (int) grr_sql_query1(
                "SELECT
                    (SELECT COUNT(*) FROM ".self::table(self::TABLE_LOCATION)." WHERE parent_id = ? AND actif = 1)
                    + (SELECT COUNT(*) FROM ".self::table(self::TABLE_CONTAINER)." WHERE emplacement_id = ? AND statut = 'en_stock')",
                "ii",
                array($id, $id)
            );
            if ($used > 0) {
                throw new RuntimeException('Emplacement utilisé.');
            }
            $update = grr_sql_command(
                "UPDATE ".self::table(self::TABLE_LOCATION)." SET actif = 0, updated_by = ?, updated_at = ? WHERE id = ?",
                "sii",
                array(self::limit($login, 190), time(), $id)
            );
            if (!self::commandOk($update) || !self::log('emplacement_archive', 'emplacement', $id, '', $login)) {
                throw new RuntimeException('Archivage impossible.');
            }
            $db->commit();
            return true;
        } catch (Throwable $exception) {
            $db->rollback();
            return false;
        }
    }

    public static function productUnits()
    {
        return array('mg' => 'mg', 'g' => 'g', 'kg' => 'kg', 'ml' => 'mL', 'l' => 'L', 'unite' => 'Unité');
    }

    public static function product($id)
    {
        return self::one(
            "SELECT p.*, f.nom AS fournisseur_nom,
                (SELECT COALESCE(SUM(c.quantite_courante), 0)
                 FROM ".self::table(self::TABLE_CONTAINER)." c
                 WHERE c.produit_id = p.id AND c.statut = 'en_stock') AS stock_total,
                (SELECT COUNT(*)
                 FROM ".self::table(self::TABLE_CONTAINER)." c
                 WHERE c.produit_id = p.id AND c.statut = 'en_stock') AS contenants_actifs
            FROM ".self::table(self::TABLE_PRODUCT)." p
            LEFT JOIN ".self::table(self::TABLE_SUPPLIER)." f ON f.id = p.fournisseur_id
            WHERE p.id = ?",
            "i",
            array((int) $id)
        );
    }

    public static function products($includeArchived = false, $limit = 500)
    {
        self::ensureTables();
        $limit = self::normalizeLimit($limit, 500, 2000);
        return self::rows(
            "SELECT p.*, f.nom AS fournisseur_nom,
                (SELECT COALESCE(SUM(c.quantite_courante), 0)
                 FROM ".self::table(self::TABLE_CONTAINER)." c
                 WHERE c.produit_id = p.id AND c.statut = 'en_stock') AS stock_total,
                (SELECT COUNT(*)
                 FROM ".self::table(self::TABLE_CONTAINER)." c
                 WHERE c.produit_id = p.id AND c.statut = 'en_stock') AS contenants_actifs,
                (SELECT MAX(d.date_revision)
                 FROM ".self::table(self::TABLE_DOCUMENT)." d
                 WHERE d.produit_id = p.id
                   AND d.type_document = 'fds'
                   AND d.est_courant = 1
                   AND d.actif = 1) AS fds_revision,
                (SELECT d.id
                 FROM ".self::table(self::TABLE_DOCUMENT)." d
                 WHERE d.produit_id = p.id
                   AND d.type_document = 'fds'
                   AND d.est_courant = 1
                   AND d.actif = 1
                 ORDER BY d.date_revision DESC, d.id DESC LIMIT 1) AS fds_document_id,
                (SELECT d.fds_validated_at
                 FROM ".self::table(self::TABLE_DOCUMENT)." d
                 WHERE d.produit_id = p.id
                   AND d.type_document = 'fds'
                   AND d.est_courant = 1
                   AND d.actif = 1
                 ORDER BY d.date_revision DESC, d.id DESC LIMIT 1) AS fds_validated_at
            FROM ".self::table(self::TABLE_PRODUCT)." p
            LEFT JOIN ".self::table(self::TABLE_SUPPLIER)." f ON f.id = p.fournisseur_id
            ".($includeArchived ? "" : "WHERE p.actif = 1")."
            ORDER BY p.actif DESC, p.nom_commercial
            LIMIT ".$limit
        );
    }

    public static function saveProduct($source, $login)
    {
        $id = isset($source['id']) ? (int) $source['id'] : 0;
        $name = self::limit(isset($source['nom_commercial']) ? $source['nom_commercial'] : '', 190);
        $unit = self::limit(isset($source['unite_stock']) ? strtolower($source['unite_stock']) : '', 10);
        $units = self::productUnits();
        if ($name === '' || !isset($units[$unit])) {
            return array('ok' => false, 'error' => 'Le nom et une unité valide sont obligatoires.');
        }
        if ($id > 0) {
            $existing = self::product($id);
            if (!$existing || (int) $existing['actif'] !== 1) {
                return array('ok' => false, 'error' => 'Produit introuvable ou archivé.');
            }
            $movementCount = (int) grr_sql_query1(
                "SELECT COUNT(*) FROM ".self::table(self::TABLE_MOVEMENT)." m
                JOIN ".self::table(self::TABLE_CONTAINER)." c ON c.id = m.contenant_id
                WHERE c.produit_id = ?",
                "i",
                array($id)
            );
            if ($movementCount > 0 && (string) $existing['unite_stock'] !== $unit) {
                return array('ok' => false, 'error' => 'L unité ne peut plus être modifiée après le premier mouvement.');
            }
        }

        $reference = self::limit(isset($source['reference_interne']) ? $source['reference_interne'] : '', 100);
        $supplierId = max(0, (int) (isset($source['fournisseur_id']) ? $source['fournisseur_id'] : 0));
        if ($supplierId > 0) {
            $supplier = self::supplier($supplierId);
            if (!$supplier || (int) $supplier['actif'] !== 1) {
                return array('ok' => false, 'error' => 'Fournisseur principal invalide.');
            }
        }
        $threshold = self::normalizeQuantity(isset($source['seuil_minimal']) ? $source['seuil_minimal'] : '0', true);
        if ($threshold === false) {
            return array('ok' => false, 'error' => 'Seuil minimal invalide.');
        }
        $state = self::limit(isset($source['etat_physique']) ? $source['etat_physique'] : 'non_renseigne', 30);
        if (!in_array($state, array('non_renseigne', 'solide', 'liquide', 'gaz', 'autre'), true)) {
            $state = 'non_renseigne';
        }
        $cmr = self::limit(isset($source['statut_cmr']) ? $source['statut_cmr'] : 'non_renseigne', 20);
        if (!in_array($cmr, array('non_renseigne', 'non', 'oui'), true)) {
            $cmr = 'non_renseigne';
        }
        $login = self::limit($login, 190);
        $now = time();
        $values = array(
            $reference === '' ? null : $reference,
            $name,
            $supplierId,
            self::limit(isset($source['reference_fournisseur']) ? $source['reference_fournisseur'] : '', 100),
            self::limit(isset($source['fabricant']) ? $source['fabricant'] : '', 190),
            self::limit(isset($source['numero_cas']) ? $source['numero_cas'] : '', 190),
            self::limit(isset($source['numero_ce']) ? $source['numero_ce'] : '', 50),
            self::limit(isset($source['ufi']) ? $source['ufi'] : '', 50),
            $state,
            $unit,
            self::limit(isset($source['categorie']) ? $source['categorie'] : '', 100),
            self::limit(isset($source['pictogrammes_clp']) ? $source['pictogrammes_clp'] : '', 255),
            trim((string) (isset($source['mentions_h']) ? $source['mentions_h'] : '')),
            trim((string) (isset($source['conseils_p']) ? $source['conseils_p'] : '')),
            $cmr,
            trim((string) (isset($source['conditions_stockage']) ? $source['conditions_stockage'] : '')),
            $threshold,
            trim((string) (isset($source['description']) ? $source['description'] : '')),
            trim((string) (isset($source['notes']) ? $source['notes'] : '')),
        );
        if ($id > 0) {
            $values[] = $login;
            $values[] = $now;
            $values[] = $id;
            $db = $GLOBALS['db_c'];
            $db->begin_transaction();
            try {
                $lockedProduct = self::one(
                    "SELECT unite_stock FROM ".self::table(self::TABLE_PRODUCT)." WHERE id = ? AND actif = 1 FOR UPDATE",
                    "i",
                    array($id)
                );
                if (!$lockedProduct) {
                    throw new RuntimeException('Produit introuvable.');
                }
                $currentMovementCount = (int) grr_sql_query1(
                    "SELECT COUNT(*) FROM ".self::table(self::TABLE_MOVEMENT)." m
                    JOIN ".self::table(self::TABLE_CONTAINER)." c ON c.id = m.contenant_id
                    WHERE c.produit_id = ?",
                    "i",
                    array($id)
                );
                if ($currentMovementCount > 0 && (string) $lockedProduct['unite_stock'] !== $unit) {
                    throw new RuntimeException('L unité ne peut plus être modifiée après le premier mouvement.');
                }
                $update = grr_sql_command(
                    "UPDATE ".self::table(self::TABLE_PRODUCT)."
                    SET reference_interne = ?, nom_commercial = ?, fournisseur_id = ?, reference_fournisseur = ?,
                        fabricant = ?, numero_cas = ?, numero_ce = ?, ufi = ?, etat_physique = ?, unite_stock = ?,
                        categorie = ?, pictogrammes_clp = ?, mentions_h = ?, conseils_p = ?, statut_cmr = ?,
                        conditions_stockage = ?, seuil_minimal = ?, description = ?, notes = ?,
                        updated_by = ?, updated_at = ?
                    WHERE id = ? AND actif = 1",
                    "ssisssssssssssssdsssii",
                    $values
                );
                if (!self::commandOk($update) || !self::log('produit_enregistre', 'produit', $id, $name, $login)) {
                    throw new RuntimeException('Enregistrement du produit impossible.');
                }
                $db->commit();
                return array('ok' => true, 'id' => $id, 'error' => '');
            } catch (Throwable $exception) {
                $db->rollback();
                return array('ok' => false, 'id' => $id, 'error' => $exception->getMessage());
            }
        } else {
            $values[] = $login;
            $values[] = $now;
            $values[] = $login;
            $values[] = $now;
            $ok = self::commandOk(grr_sql_command(
                "INSERT INTO ".self::table(self::TABLE_PRODUCT)."
                    (reference_interne, nom_commercial, fournisseur_id, reference_fournisseur, fabricant,
                    numero_cas, numero_ce, ufi, etat_physique, unite_stock, categorie, pictogrammes_clp,
                    mentions_h, conseils_p, statut_cmr, conditions_stockage, seuil_minimal, description,
                    notes, created_by, created_at, updated_by, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                "ssisssssssssssssdsssisi",
                $values
            ));
            $id = $ok ? (int) grr_sql_insert_id() : 0;
        }
        if ($ok) {
            self::log('produit_enregistre', 'produit', $id, $name, $login);
        }
        return array('ok' => $ok, 'id' => $id, 'error' => $ok ? '' : 'Enregistrement du produit impossible. Vérifiez notamment les références uniques.');
    }

    public static function archiveProduct($id, $login)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return false;
        }
        $db = $GLOBALS['db_c'];
        $db->begin_transaction();
        try {
            $product = self::one(
                "SELECT id FROM ".self::table(self::TABLE_PRODUCT)." WHERE id = ? AND actif = 1 FOR UPDATE",
                "i",
                array($id)
            );
            if (!$product) {
                throw new RuntimeException('Produit introuvable.');
            }
            $activeContainers = (int) grr_sql_query1(
                "SELECT COUNT(*) FROM ".self::table(self::TABLE_CONTAINER)."
                WHERE produit_id = ? AND statut = 'en_stock'",
                "i",
                array($id)
            );
            if ($activeContainers > 0) {
                throw new RuntimeException('Contenants actifs présents.');
            }
            $update = grr_sql_command(
                "UPDATE ".self::table(self::TABLE_PRODUCT)." SET actif = 0, updated_by = ?, updated_at = ? WHERE id = ?",
                "sii",
                array(self::limit($login, 190), time(), $id)
            );
            if (!self::commandOk($update) || !self::log('produit_archive', 'produit', $id, '', $login)) {
                throw new RuntimeException('Archivage impossible.');
            }
            $db->commit();
            return true;
        } catch (Throwable $exception) {
            $db->rollback();
            return false;
        }
    }

    public static function container($id, $forUpdate = false)
    {
        return self::one(
            "SELECT c.*, p.nom_commercial, p.reference_interne, p.unite_stock,
                e.code AS emplacement_code, e.nom AS emplacement_nom, f.nom AS fournisseur_nom
            FROM ".self::table(self::TABLE_CONTAINER)." c
            JOIN ".self::table(self::TABLE_PRODUCT)." p ON p.id = c.produit_id
            JOIN ".self::table(self::TABLE_LOCATION)." e ON e.id = c.emplacement_id
            LEFT JOIN ".self::table(self::TABLE_SUPPLIER)." f ON f.id = c.fournisseur_id
            WHERE c.id = ?".($forUpdate ? " FOR UPDATE" : ""),
            "i",
            array((int) $id)
        );
    }

    public static function containers($productId = 0, $includeClosed = false, $limit = 1000)
    {
        self::ensureTables();
        $conditions = array();
        $types = '';
        $params = array();
        if ((int) $productId > 0) {
            $conditions[] = 'c.produit_id = ?';
            $types .= 'i';
            $params[] = (int) $productId;
        }
        if (!$includeClosed) {
            $conditions[] = "c.statut = 'en_stock'";
        }
        $limit = self::normalizeLimit($limit, 1000, 5000);
        return self::rows(
            "SELECT c.*, p.nom_commercial, p.reference_interne,
                e.code AS emplacement_code, e.nom AS emplacement_nom, f.nom AS fournisseur_nom
            FROM ".self::table(self::TABLE_CONTAINER)." c
            JOIN ".self::table(self::TABLE_PRODUCT)." p ON p.id = c.produit_id
            JOIN ".self::table(self::TABLE_LOCATION)." e ON e.id = c.emplacement_id
            LEFT JOIN ".self::table(self::TABLE_SUPPLIER)." f ON f.id = c.fournisseur_id
            ".(count($conditions) ? 'WHERE '.implode(' AND ', $conditions) : '')."
            ORDER BY c.statut = 'en_stock' DESC, c.date_peremption IS NULL, c.date_peremption, p.nom_commercial, c.code_interne
            LIMIT ".$limit,
            $types === '' ? null : $types,
            count($params) ? $params : null
        );
    }

    public static function createContainer($source, $login)
    {
        self::ensureTables();
        $productId = (int) (isset($source['produit_id']) ? $source['produit_id'] : 0);
        $locationId = (int) (isset($source['emplacement_id']) ? $source['emplacement_id'] : 0);
        $product = self::product($productId);
        $location = self::location($locationId);
        $supplierId = max(0, (int) (isset($source['fournisseur_id']) ? $source['fournisseur_id'] : 0));
        if ($supplierId > 0) {
            $supplier = self::supplier($supplierId);
            if (!$supplier || (int) $supplier['actif'] !== 1) {
                return array('ok' => false, 'error' => 'Fournisseur du contenant invalide.');
            }
        }
        $quantity = self::normalizeQuantity(isset($source['quantite']) ? $source['quantite'] : '', false);
        $code = self::limit(isset($source['code_interne']) ? $source['code_interne'] : '', 100);
        if ($code === '') {
            $code = 'SC-'.date('Ymd-His').'-'.substr(self::randomHex(4), 0, 8);
        }
        if (!$product || (int) $product['actif'] !== 1 || !$location || (int) $location['actif'] !== 1 || $quantity === false || $quantity <= 0) {
            return array('ok' => false, 'error' => 'Produit, emplacement ou quantité invalide.');
        }
        $dates = self::validateContainerDates($source);
        if (!$dates['ok']) {
            return $dates;
        }
        $login = self::limit($login, 190);
        $now = time();
        $requestToken = self::validRequestToken(isset($source['request_token']) ? $source['request_token'] : '')
            ? (string) $source['request_token']
            : self::randomHex(32);

        $db = $GLOBALS['db_c'];
        $db->begin_transaction();
        try {
            $lockedProduct = self::one(
                "SELECT id, unite_stock FROM ".self::table(self::TABLE_PRODUCT)." WHERE id = ? AND actif = 1 FOR UPDATE",
                "i",
                array($productId)
            );
            if (!$lockedProduct) {
                throw new RuntimeException('Le produit n est plus disponible.');
            }
            $insert = grr_sql_command(
                "INSERT INTO ".self::table(self::TABLE_CONTAINER)."
                    (produit_id, fournisseur_id, emplacement_id, code_interne, numero_lot, conditionnement,
                    quantite_courante, unite, date_reception, date_ouverture, date_peremption, statut, notes,
                    created_by, created_at, updated_by, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'en_stock', ?, ?, ?, ?, ?)",
                "iiisssdssssssisi",
                array(
                    $productId,
                    $supplierId,
                    $locationId,
                    $code,
                    self::limit(isset($source['numero_lot']) ? $source['numero_lot'] : '', 100),
                    self::limit(isset($source['conditionnement']) ? $source['conditionnement'] : '', 190),
                    $quantity,
                    (string) $lockedProduct['unite_stock'],
                    $dates['date_reception'],
                    $dates['date_ouverture'],
                    $dates['date_peremption'],
                    trim((string) (isset($source['notes']) ? $source['notes'] : '')),
                    $login,
                    $now,
                    $login,
                    $now,
                )
            );
            if (!self::commandOk($insert)) {
                throw new RuntimeException('Création du contenant impossible.');
            }
            $containerId = (int) grr_sql_insert_id();
            $movementId = self::insertMovement(
                $containerId,
                'entree',
                $quantity,
                0,
                $quantity,
                (string) $lockedProduct['unite_stock'],
                0,
                $locationId,
                0,
                'Réception initiale',
                self::effectiveTimestamp(isset($source['date_reception']) ? $source['date_reception'] : ''),
                $requestToken,
                $login
            );
            if ($movementId <= 0) {
                throw new RuntimeException('Mouvement d entrée impossible.');
            }
            if (!self::log('contenant_recu', 'contenant', $containerId, $code, $login)) {
                throw new RuntimeException('Journalisation impossible.');
            }
            $db->commit();
            return array('ok' => true, 'id' => $containerId, 'error' => '');
        } catch (Throwable $exception) {
            $db->rollback();
            return array('ok' => false, 'error' => $exception->getMessage());
        }
    }

    public static function movementTypes()
    {
        return array(
            'consommation' => 'Consommation',
            'transfert' => 'Transfert',
            'correction_plus' => 'Correction positive',
            'correction_moins' => 'Correction négative',
            'elimination' => 'Élimination',
            'retour_fournisseur' => 'Retour fournisseur',
        );
    }

    public static function archiveContainer($id, $login)
    {
        $id = (int) $id;
        $container = self::container($id);
        if (
            !$container
            || !in_array((string) $container['statut'], array('vide', 'elimine', 'retourne'), true)
        ) {
            return false;
        }
        $ok = self::commandOk(grr_sql_command(
            "UPDATE ".self::table(self::TABLE_CONTAINER)."
            SET statut = 'archive', updated_by = ?, updated_at = ? WHERE id = ?",
            "sii",
            array(self::limit($login, 190), time(), $id)
        ));
        if ($ok) {
            self::log('contenant_archive', 'contenant', $id, (string) $container['code_interne'], $login);
        }
        return $ok;
    }

    public static function createMovement($source, $login)
    {
        self::ensureTables();
        $containerId = (int) (isset($source['contenant_id']) ? $source['contenant_id'] : 0);
        $type = self::limit(isset($source['type_mouvement']) ? $source['type_mouvement'] : '', 30);
        $movementTypes = self::movementTypes();
        if ($containerId <= 0 || !isset($movementTypes[$type])) {
            return array('ok' => false, 'error' => 'Mouvement invalide.');
        }
        $quantity = self::normalizeQuantity(isset($source['quantite']) ? $source['quantite'] : '', false);
        $destinationId = (int) (isset($source['emplacement_destination_id']) ? $source['emplacement_destination_id'] : 0);
        $motif = trim((string) (isset($source['motif']) ? $source['motif'] : ''));
        if (in_array($type, array('correction_plus', 'correction_moins', 'elimination', 'retour_fournisseur'), true) && $motif === '') {
            return array('ok' => false, 'error' => 'Un motif est obligatoire pour ce mouvement.');
        }
        if ($type !== 'transfert' && in_array($type, array('consommation', 'correction_plus', 'correction_moins'), true) && ($quantity === false || $quantity <= 0)) {
            return array('ok' => false, 'error' => 'La quantité doit être strictement positive.');
        }
        $requestToken = self::validRequestToken(isset($source['request_token']) ? $source['request_token'] : '')
            ? (string) $source['request_token']
            : self::randomHex(32);
        $effectiveDate = isset($source['date_effective']) ? trim((string) $source['date_effective']) : '';
        if ($effectiveDate !== '' && self::nullableDate($effectiveDate) === null) {
            return array('ok' => false, 'error' => 'Date effective invalide.');
        }
        $login = self::limit($login, 190);
        $db = $GLOBALS['db_c'];
        $db->begin_transaction();

        try {
            $container = self::container($containerId, true);
            if (!$container || (string) $container['statut'] !== 'en_stock') {
                throw new RuntimeException('Le contenant n est plus disponible.');
            }
            $before = (float) $container['quantite_courante'];
            $after = $before;
            $movementQuantity = $quantity === false ? 0 : (float) $quantity;
            $sourceLocation = (int) $container['emplacement_id'];
            $destination = 0;
            $newStatus = 'en_stock';

            if ($type === 'transfert') {
                $destinationLocation = self::one(
                    "SELECT * FROM ".self::table(self::TABLE_LOCATION)." WHERE id = ? AND actif = 1 FOR UPDATE",
                    "i",
                    array($destinationId)
                );
                if (!$destinationLocation || (int) $destinationLocation['actif'] !== 1 || $destinationId === $sourceLocation) {
                    throw new RuntimeException('Emplacement de destination invalide.');
                }
                $movementQuantity = $before;
                $destination = $destinationId;
            } elseif ($type === 'consommation' || $type === 'correction_moins') {
                if ($movementQuantity > $before) {
                    throw new RuntimeException('Le stock négatif est interdit.');
                }
                $after = round($before - $movementQuantity, 4);
                $newStatus = $after <= 0 ? 'vide' : 'en_stock';
            } elseif ($type === 'correction_plus') {
                $after = round($before + $movementQuantity, 4);
            } elseif ($type === 'elimination' || $type === 'retour_fournisseur') {
                $movementQuantity = $before;
                $after = 0;
                $newStatus = $type === 'elimination' ? 'elimine' : 'retourne';
            }

            $movementId = self::insertMovement(
                $containerId,
                $type,
                $movementQuantity,
                $before,
                $after,
                (string) $container['unite'],
                $sourceLocation,
                $destination,
                max(0, (int) (isset($source['mouvement_source_id']) ? $source['mouvement_source_id'] : 0)),
                $motif,
                self::effectiveTimestamp($effectiveDate),
                $requestToken,
                $login
            );
            if ($movementId <= 0) {
                throw new RuntimeException('Le mouvement existe déjà ou n a pas pu être enregistré.');
            }
            $newLocation = $type === 'transfert' ? $destinationId : $sourceLocation;
            $update = grr_sql_command(
                "UPDATE ".self::table(self::TABLE_CONTAINER)."
                SET quantite_courante = ?, emplacement_id = ?, statut = ?, updated_by = ?, updated_at = ?
                WHERE id = ?",
                "dissii",
                array($after, $newLocation, $newStatus, $login, time(), $containerId)
            );
            if (!self::commandOk($update)) {
                throw new RuntimeException('Mise à jour du contenant impossible.');
            }
            if (!self::log('mouvement_'.$type, 'mouvement', $movementId, $container['code_interne'], $login)) {
                throw new RuntimeException('Journalisation impossible.');
            }
            $db->commit();
            return array('ok' => true, 'id' => $movementId, 'error' => '');
        } catch (Throwable $exception) {
            $db->rollback();
            return array('ok' => false, 'error' => $exception->getMessage());
        }
    }

    public static function evacuateProduct($source, $login)
    {
        self::ensureTables();
        $productId = (int) (isset($source['produit_id']) ? $source['produit_id'] : 0);
        $reason = trim((string) (isset($source['motif']) ? $source['motif'] : ''));
        if ($productId <= 0 || $reason === '') {
            return array('ok' => false, 'count' => 0, 'error' => 'Le produit et le motif d évacuation sont obligatoires.');
        }
        $effectiveDate = isset($source['date_effective']) ? trim((string) $source['date_effective']) : '';
        if ($effectiveDate !== '' && self::nullableDate($effectiveDate) === null) {
            return array('ok' => false, 'count' => 0, 'error' => 'Date effective invalide.');
        }
        $requestToken = self::validRequestToken(isset($source['request_token']) ? $source['request_token'] : '')
            ? (string) $source['request_token']
            : self::randomHex(32);
        $login = self::limit($login, 190);
        $effectiveAt = self::effectiveTimestamp($effectiveDate);
        $reason = 'Évacuation vers les déchets chimiques : '.$reason;

        $db = $GLOBALS['db_c'];
        $db->begin_transaction();
        try {
            $product = self::one(
                "SELECT id, nom_commercial FROM ".self::table(self::TABLE_PRODUCT)."
                WHERE id = ? AND actif = 1 FOR UPDATE",
                "i",
                array($productId)
            );
            if (!$product) {
                throw new RuntimeException('Produit introuvable ou archivé.');
            }
            $containers = self::rows(
                "SELECT id, code_interne, quantite_courante, unite, emplacement_id
                FROM ".self::table(self::TABLE_CONTAINER)."
                WHERE produit_id = ? AND statut = 'en_stock'
                ORDER BY id FOR UPDATE",
                "i",
                array($productId)
            );
            if (count($containers) === 0) {
                throw new RuntimeException('Aucun contenant en stock à évacuer.');
            }
            foreach ($containers as $container) {
                $containerId = (int) $container['id'];
                $before = (float) $container['quantite_courante'];
                $movementId = self::insertMovement(
                    $containerId,
                    'elimination',
                    $before,
                    $before,
                    0,
                    (string) $container['unite'],
                    (int) $container['emplacement_id'],
                    0,
                    0,
                    $reason,
                    $effectiveAt,
                    hash('sha256', $requestToken.':'.$containerId),
                    $login
                );
                if ($movementId <= 0) {
                    throw new RuntimeException('Mouvement d évacuation impossible pour '.$container['code_interne'].'.');
                }
                $update = grr_sql_command(
                    "UPDATE ".self::table(self::TABLE_CONTAINER)."
                    SET quantite_courante = 0, statut = 'elimine', updated_by = ?, updated_at = ?
                    WHERE id = ? AND statut = 'en_stock'",
                    "sii",
                    array($login, time(), $containerId)
                );
                if (!self::commandOk($update)) {
                    throw new RuntimeException('Mise à jour impossible pour '.$container['code_interne'].'.');
                }
                if (!self::log('mouvement_elimination', 'mouvement', $movementId, (string) $container['code_interne'], $login)) {
                    throw new RuntimeException('Journalisation impossible.');
                }
            }
            if (!self::log('produit_evacue', 'produit', $productId, count($containers).' contenant(s) - '.$reason, $login)) {
                throw new RuntimeException('Journalisation du produit impossible.');
            }
            $db->commit();
            return array('ok' => true, 'count' => count($containers), 'error' => '');
        } catch (Throwable $exception) {
            $db->rollback();
            return array('ok' => false, 'count' => 0, 'error' => $exception->getMessage());
        }
    }

    public static function movements($containerId = 0, $limit = 500)
    {
        self::ensureTables();
        $limit = self::normalizeLimit($limit, 500, 5000);
        return self::rows(
            "SELECT m.*, c.code_interne, p.nom_commercial,
                es.code AS source_code, ed.code AS destination_code
            FROM ".self::table(self::TABLE_MOVEMENT)." m
            JOIN ".self::table(self::TABLE_CONTAINER)." c ON c.id = m.contenant_id
            JOIN ".self::table(self::TABLE_PRODUCT)." p ON p.id = c.produit_id
            LEFT JOIN ".self::table(self::TABLE_LOCATION)." es ON es.id = m.emplacement_source_id
            LEFT JOIN ".self::table(self::TABLE_LOCATION)." ed ON ed.id = m.emplacement_destination_id
            ".((int) $containerId > 0 ? "WHERE m.contenant_id = ?" : "")."
            ORDER BY m.date_effective DESC, m.id DESC
            LIMIT ".$limit,
            (int) $containerId > 0 ? "i" : null,
            (int) $containerId > 0 ? array((int) $containerId) : null
        );
    }

    public static function documentTypes()
    {
        return array(
            'fds' => 'FDS',
            'certificat_analyse' => 'Certificat d analyse',
            'fiche_technique' => 'Fiche technique',
            'mode_operatoire' => 'Mode opératoire',
            'notice' => 'Notice',
            'etiquette' => 'Étiquette',
            'autre' => 'Autre',
        );
    }

    public static function documentsForProduct($productId, $includeArchived = true)
    {
        return self::rows(
            "SELECT * FROM ".self::table(self::TABLE_DOCUMENT)."
            WHERE produit_id = ?".($includeArchived ? "" : " AND actif = 1")."
            ORDER BY actif DESC, est_courant DESC, date_revision DESC, created_at DESC",
            "i",
            array((int) $productId)
        );
    }

    public static function document($id)
    {
        return self::one(
            "SELECT d.*, p.nom_commercial, p.actif AS produit_actif
            FROM ".self::table(self::TABLE_DOCUMENT)." d
            JOIN ".self::table(self::TABLE_PRODUCT)." p ON p.id = d.produit_id
            WHERE d.id = ?",
            "i",
            array((int) $id)
        );
    }

    public static function addDocument($values, $login)
    {
        self::ensureTables();
        $productId = (int) (isset($values['produit_id']) ? $values['produit_id'] : 0);
        $type = self::limit(isset($values['type_document']) ? $values['type_document'] : '', 30);
        $language = self::limit(isset($values['langue']) ? strtolower($values['langue']) : 'fr', 10);
        $product = self::product($productId);
        $documentTypes = self::documentTypes();
        if (!$product || !isset($documentTypes[$type])) {
            return false;
        }
        $revision = self::nullableDate(isset($values['date_revision']) ? $values['date_revision'] : '');
        if ($type === 'fds' && $revision === null) {
            return false;
        }
        $storedName = self::limit(isset($values['stored_name']) ? $values['stored_name'] : '', 64);
        $sha256 = self::limit(isset($values['sha256']) ? $values['sha256'] : '', 64);
        if (!preg_match('/^[a-f0-9]{64}$/', $storedName) || !preg_match('/^[a-f0-9]{64}$/', $sha256)) {
            return false;
        }
        $isCurrent = $type === 'fds' && !empty($values['est_courant']) ? 1 : 0;
        $login = self::limit($login, 190);
        $db = $GLOBALS['db_c'];
        $db->begin_transaction();
        try {
            $lockedProduct = self::one(
                "SELECT id FROM ".self::table(self::TABLE_PRODUCT)." WHERE id = ? FOR UPDATE",
                "i",
                array($productId)
            );
            if (!$lockedProduct) {
                throw new RuntimeException('Produit introuvable.');
            }
            if ($isCurrent) {
                $archive = grr_sql_command(
                    "UPDATE ".self::table(self::TABLE_DOCUMENT)."
                    SET est_courant = 0, archived_by = ?, archived_at = ?
                    WHERE produit_id = ? AND type_document = 'fds' AND langue = ? AND est_courant = 1",
                    "siis",
                    array($login, time(), $productId, $language)
                );
                if (!self::commandOk($archive)) {
                    throw new RuntimeException('Archivage de l ancienne FDS impossible.');
                }
            }
            $insert = grr_sql_command(
                "INSERT INTO ".self::table(self::TABLE_DOCUMENT)."
                    (produit_id, type_document, langue, emetteur, date_revision, numero_version,
                    est_courant, description, original_name, stored_name, mime_type, taille, sha256,
                    actif, uploaded_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?)",
                "isssssissssissi",
                array(
                    $productId,
                    $type,
                    $language,
                    self::limit(isset($values['emetteur']) ? $values['emetteur'] : '', 190),
                    $revision,
                    self::limit(isset($values['numero_version']) ? $values['numero_version'] : '', 100),
                    $isCurrent,
                    trim((string) (isset($values['description']) ? $values['description'] : '')),
                    self::limit(isset($values['original_name']) ? $values['original_name'] : '', 255),
                    $storedName,
                    self::limit(isset($values['mime_type']) ? $values['mime_type'] : 'application/octet-stream', 190),
                    (int) (isset($values['taille']) ? $values['taille'] : 0),
                    $sha256,
                    $login,
                    time(),
                )
            );
            if (!self::commandOk($insert)) {
                throw new RuntimeException('Enregistrement du document impossible.');
            }
            $id = (int) grr_sql_insert_id();
            if (!self::log('document_ajoute', 'document', $id, (string) $values['original_name'], $login)) {
                throw new RuntimeException('Journalisation impossible.');
            }
            $db->commit();
            return $id;
        } catch (Throwable $exception) {
            $db->rollback();
            return false;
        }
    }

    public static function updateDocumentInfo($values, $login)
    {
        self::ensureTables();
        $id = (int) (isset($values['document_id']) ? $values['document_id'] : 0);
        if ($id <= 0) {
            return array('ok' => false, 'error' => 'Document introuvable.');
        }

        $types = self::documentTypes();
        $type = self::limit(isset($values['type_document']) ? $values['type_document'] : '', 30);
        if (!isset($types[$type])) {
            return array('ok' => false, 'error' => 'Type de document invalide.');
        }

        $language = self::limit(strtolower(trim((string) (isset($values['langue']) ? $values['langue'] : 'fr'))), 10);
        if ($language === '') {
            return array('ok' => false, 'error' => 'Langue invalide.');
        }

        $revisionSource = isset($values['date_revision']) ? trim((string) $values['date_revision']) : '';
        $revision = self::nullableDate($revisionSource);
        if ($revisionSource !== '' && $revision === null) {
            return array('ok' => false, 'error' => 'Date de révision invalide.');
        }
        if ($type === 'fds' && $revision === null) {
            return array('ok' => false, 'error' => 'La date de révision est obligatoire pour une FDS.');
        }

        $originalName = basename(str_replace('\\', '/', (string) (isset($values['original_name']) ? $values['original_name'] : '')));
        $originalName = preg_replace('/[\x00-\x1F\x7F]/u', '', $originalName);
        $originalName = self::limit(trim((string) $originalName), 255);
        if ($originalName === '') {
            return array('ok' => false, 'error' => 'Nom du document invalide.');
        }

        $login = self::limit($login, 190);
        $db = $GLOBALS['db_c'];
        $db->begin_transaction();
        try {
            $document = self::one(
                "SELECT * FROM ".self::table(self::TABLE_DOCUMENT)." WHERE id = ? FOR UPDATE",
                "i",
                array($id)
            );
            if (!$document) {
                throw new RuntimeException('Document introuvable.');
            }

            if ($type === 'fds') {
                $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
                $mime = strtolower((string) $document['mime_type']);
                if ($extension !== 'pdf' && $mime !== 'application/pdf') {
                    throw new RuntimeException('Une FDS doit rester associée à un fichier PDF.');
                }
            }

            $isCurrent = $type === 'fds' && !empty($values['est_courant']) && (int) $document['actif'] === 1 ? 1 : 0;
            if ($isCurrent) {
                $archive = grr_sql_command(
                    "UPDATE ".self::table(self::TABLE_DOCUMENT)."
                    SET est_courant = 0, archived_by = ?, archived_at = ?
                    WHERE produit_id = ? AND id <> ? AND type_document = 'fds' AND langue = ? AND est_courant = 1",
                    "siiis",
                    array($login, time(), (int) $document['produit_id'], $id, $language)
                );
                if (!self::commandOk($archive)) {
                    throw new RuntimeException('Mise à jour des versions courantes impossible.');
                }
            }

            $validatedBy = $type === 'fds' ? (string) $document['fds_validated_by'] : '';
            $validatedAt = $type === 'fds' ? (int) $document['fds_validated_at'] : 0;
            $update = grr_sql_command(
                "UPDATE ".self::table(self::TABLE_DOCUMENT)."
                SET type_document = ?, langue = ?, emetteur = ?, date_revision = ?, numero_version = ?,
                    est_courant = ?, description = ?, original_name = ?, fds_validated_by = ?, fds_validated_at = ?
                WHERE id = ?",
                "sssssisssii",
                array(
                    $type,
                    $language,
                    self::limit(isset($values['emetteur']) ? $values['emetteur'] : '', 190),
                    $revision,
                    self::limit(isset($values['numero_version']) ? $values['numero_version'] : '', 100),
                    $isCurrent,
                    trim((string) (isset($values['description']) ? $values['description'] : '')),
                    $originalName,
                    self::limit($validatedBy, 190),
                    $validatedAt,
                    $id,
                )
            );
            if (!self::commandOk($update)) {
                throw new RuntimeException('Mise à jour du document impossible.');
            }
            if (!self::log('document_modifie', 'document', $id, $originalName, $login)) {
                throw new RuntimeException('Journalisation impossible.');
            }
            $db->commit();
            return array('ok' => true, 'error' => '', 'product_id' => (int) $document['produit_id']);
        } catch (Throwable $exception) {
            $db->rollback();
            return array('ok' => false, 'error' => $exception->getMessage());
        }
    }

    public static function archiveDocument($id, $login)
    {
        $id = (int) $id;
        $ok = self::commandOk(grr_sql_command(
            "UPDATE ".self::table(self::TABLE_DOCUMENT)."
            SET actif = 0, est_courant = 0, archived_by = ?, archived_at = ?
            WHERE id = ? AND actif = 1",
            "sii",
            array(self::limit($login, 190), time(), $id)
        ));
        if ($ok) {
            self::log('document_archive', 'document', $id, '', $login);
        }
        return $ok;
    }

    public static function validateFdsAlert($documentId, $login)
    {
        $documentId = (int) $documentId;
        if ($documentId <= 0) {
            return false;
        }
        $login = self::limit($login, 190);
        $db = $GLOBALS['db_c'];
        $db->begin_transaction();
        try {
            $document = self::one(
                "SELECT id, produit_id FROM ".self::table(self::TABLE_DOCUMENT)."
                WHERE id = ? AND type_document = 'fds' AND actif = 1 AND est_courant = 1
                FOR UPDATE",
                "i",
                array($documentId)
            );
            if (!$document) {
                throw new RuntimeException('FDS courante introuvable.');
            }
            $validatedAt = time();
            $update = grr_sql_command(
                "UPDATE ".self::table(self::TABLE_DOCUMENT)."
                SET fds_validated_by = ?, fds_validated_at = ?
                WHERE id = ? AND type_document = 'fds' AND actif = 1 AND est_courant = 1",
                "sii",
                array($login, $validatedAt, $documentId)
            );
            if (!self::commandOk($update)) {
                throw new RuntimeException('Validation de la FDS impossible.');
            }
            if (!self::log('fds_validee', 'document', $documentId, 'Produit #'.(int) $document['produit_id'], $login)) {
                throw new RuntimeException('Journalisation impossible.');
            }
            $db->commit();
            return true;
        } catch (Throwable $exception) {
            $db->rollback();
            return false;
        }
    }

    public static function validateFdsAlerts($documentIds, $login)
    {
        $ids = array();
        foreach ((array) $documentIds as $documentId) {
            $documentId = (int) $documentId;
            if ($documentId > 0) {
                $ids[$documentId] = $documentId;
            }
        }
        $ids = array_values($ids);
        if (count($ids) === 0) {
            return array('ok' => false, 'count' => 0, 'error' => 'Aucune FDS à valider.');
        }

        $login = self::limit($login, 190);
        $idList = implode(',', $ids);
        $db = $GLOBALS['db_c'];
        $db->begin_transaction();
        try {
            $documents = self::rows(
                "SELECT id, produit_id FROM ".self::table(self::TABLE_DOCUMENT)."
                WHERE id IN (".$idList.")
                  AND type_document = 'fds' AND actif = 1 AND est_courant = 1
                FOR UPDATE"
            );
            if (count($documents) !== count($ids)) {
                throw new RuntimeException('Une ou plusieurs FDS ne sont plus courantes.');
            }
            $validatedAt = time();
            $update = grr_sql_command(
                "UPDATE ".self::table(self::TABLE_DOCUMENT)."
                SET fds_validated_by = ?, fds_validated_at = ?
                WHERE id IN (".$idList.")
                  AND type_document = 'fds' AND actif = 1 AND est_courant = 1",
                "si",
                array($login, $validatedAt)
            );
            if (!self::commandOk($update)) {
                throw new RuntimeException('Validation groupée des FDS impossible.');
            }
            foreach ($documents as $document) {
                if (!self::log(
                    'fds_validee',
                    'document',
                    (int) $document['id'],
                    'Validation groupée - produit #'.(int) $document['produit_id'],
                    $login
                )) {
                    throw new RuntimeException('Journalisation impossible.');
                }
            }
            $db->commit();
            return array('ok' => true, 'count' => count($documents), 'error' => '');
        } catch (Throwable $exception) {
            $db->rollback();
            return array('ok' => false, 'count' => 0, 'error' => $exception->getMessage());
        }
    }

    public static function documentStorageDir()
    {
        return dirname(__DIR__).'/storage/documents';
    }

    public static function ensureDocumentStorage()
    {
        $directory = self::documentStorageDir();
        if (!is_dir($directory) && !@mkdir($directory, 0750, true)) {
            return false;
        }
        return is_writable($directory);
    }

    public static function documentPath($storedName)
    {
        $storedName = trim((string) $storedName);
        return preg_match('/^[a-f0-9]{64}$/', $storedName)
            ? self::documentStorageDir().'/'.$storedName
            : '';
    }

    public static function dashboardCounts()
    {
        $alerts = self::alertCounts();
        return array(
            'products' => (int) grr_sql_query1("SELECT COUNT(*) FROM ".self::table(self::TABLE_PRODUCT)." WHERE actif = 1"),
            'containers' => (int) grr_sql_query1("SELECT COUNT(*) FROM ".self::table(self::TABLE_CONTAINER)." WHERE statut = 'en_stock'"),
            'movements' => (int) grr_sql_query1("SELECT COUNT(*) FROM ".self::table(self::TABLE_MOVEMENT)),
            'inventories' => (int) grr_sql_query1("SELECT COUNT(*) FROM ".self::table(self::TABLE_INVENTORY)." WHERE statut = 'ouvert'"),
            'alerts' => array_sum($alerts),
        );
    }

    public static function alertCounts()
    {
        $alerts = self::alerts(1000);
        $counts = array('stock_faible' => 0, 'peremption_proche' => 0, 'perime' => 0, 'fds_manquante' => 0, 'fds_a_verifier' => 0);
        foreach ($alerts as $alert) {
            if (isset($counts[$alert['type']])) {
                $counts[$alert['type']]++;
            }
        }
        return $counts;
    }

    public static function alerts($limit = 200)
    {
        self::ensureTables();
        if (!StockChimiqueConfig::alertsEnabled()) {
            return array();
        }
        $limit = self::normalizeLimit($limit, 200, 2000);
        $alerts = array();
        if (StockChimiqueConfig::stockAlertsEnabled() || StockChimiqueConfig::fdsAlertsEnabled()) {
            $products = self::products(false, 2000);
            $fdsThreshold = date('Y-m-d', strtotime('-'.StockChimiqueConfig::fdsMonths().' months'));
            foreach ($products as $product) {
                if ((int) $product['contenants_actifs'] <= 0) {
                    continue;
                }
                if (
                    StockChimiqueConfig::stockAlertsEnabled()
                    && (float) $product['seuil_minimal'] > 0
                    && (float) $product['stock_total'] < (float) $product['seuil_minimal']
                ) {
                    $alerts[] = self::alertRow('stock_faible', (int) $product['id'], $product['nom_commercial'], 'Stock '.$product['stock_total'].' '.$product['unite_stock'].' / seuil '.$product['seuil_minimal'], '');
                }
                if (StockChimiqueConfig::fdsAlertsEnabled()) {
                    if (empty($product['fds_revision'])) {
                        $alerts[] = self::alertRow('fds_manquante', (int) $product['id'], $product['nom_commercial'], 'Aucune FDS courante', '');
                    } else {
                        $validationDate = (int) $product['fds_validated_at'] > 0
                            ? date('Y-m-d', (int) $product['fds_validated_at'])
                            : '';
                        $controlDate = $validationDate > (string) $product['fds_revision']
                            ? $validationDate
                            : (string) $product['fds_revision'];
                        if ($controlDate < $fdsThreshold) {
                            $detail = 'FDS du '.$product['fds_revision'].' à vérifier';
                            if ($validationDate !== '') {
                                $detail .= ' ; dernière validation interne le '.$validationDate;
                            }
                            $alerts[] = self::alertRow(
                                'fds_a_verifier',
                                (int) $product['id'],
                                $product['nom_commercial'],
                                $detail,
                                $controlDate,
                                0,
                                (int) $product['fds_document_id']
                            );
                        }
                    }
                }
            }
        }
        if (StockChimiqueConfig::expiryAlertsEnabled()) {
            $today = date('Y-m-d');
            $until = date('Y-m-d', strtotime('+'.StockChimiqueConfig::expiryDays().' days'));
            $containers = self::rows(
                "SELECT c.id, c.code_interne, c.date_peremption, p.id AS produit_id, p.nom_commercial
                FROM ".self::table(self::TABLE_CONTAINER)." c
                JOIN ".self::table(self::TABLE_PRODUCT)." p ON p.id = c.produit_id
                WHERE c.statut = 'en_stock' AND c.date_peremption IS NOT NULL AND c.date_peremption <= ?
                ORDER BY c.date_peremption
                LIMIT ".$limit,
                "s",
                array($until)
            );
            foreach ($containers as $container) {
                $type = (string) $container['date_peremption'] < $today ? 'perime' : 'peremption_proche';
                $alerts[] = self::alertRow(
                    $type,
                    (int) $container['produit_id'],
                    $container['nom_commercial'].' - '.$container['code_interne'],
                    'Péremption : '.$container['date_peremption'],
                    (string) $container['date_peremption'],
                    (int) $container['id']
                );
            }
        }
        usort($alerts, function ($a, $b) {
            $priority = array('perime' => 0, 'stock_faible' => 1, 'fds_manquante' => 2, 'peremption_proche' => 3, 'fds_a_verifier' => 4);
            $pa = isset($priority[$a['type']]) ? $priority[$a['type']] : 9;
            $pb = isset($priority[$b['type']]) ? $priority[$b['type']] : 9;
            return $pa === $pb ? strcmp($a['label'], $b['label']) : ($pa < $pb ? -1 : 1);
        });
        return array_slice($alerts, 0, $limit);
    }

    public static function inventories($limit = 100)
    {
        return self::rows(
            "SELECT i.*, e.code AS emplacement_code, e.nom AS emplacement_nom,
                (SELECT COUNT(*) FROM ".self::table(self::TABLE_INVENTORY_LINE)." l WHERE l.inventaire_id = i.id) AS lignes,
                (SELECT COUNT(*) FROM ".self::table(self::TABLE_INVENTORY_LINE)." l WHERE l.inventaire_id = i.id AND l.quantite_comptee IS NOT NULL) AS comptees,
                (SELECT COUNT(*) FROM ".self::table(self::TABLE_INVENTORY_LINE)." l WHERE l.inventaire_id = i.id AND l.statut = 'conflit') AS conflits
            FROM ".self::table(self::TABLE_INVENTORY)." i
            LEFT JOIN ".self::table(self::TABLE_LOCATION)." e ON e.id = i.emplacement_id
            ORDER BY i.opened_at DESC
            LIMIT ".self::normalizeLimit($limit, 100, 500)
        );
    }

    public static function inventory($id)
    {
        return self::one(
            "SELECT i.*, e.code AS emplacement_code, e.nom AS emplacement_nom
            FROM ".self::table(self::TABLE_INVENTORY)." i
            LEFT JOIN ".self::table(self::TABLE_LOCATION)." e ON e.id = i.emplacement_id
            WHERE i.id = ?",
            "i",
            array((int) $id)
        );
    }

    public static function inventoryLines($inventoryId)
    {
        return self::rows(
            "SELECT l.*, c.code_interne, c.unite, c.quantite_courante, c.statut AS contenant_statut,
                p.nom_commercial, e.code AS emplacement_code
            FROM ".self::table(self::TABLE_INVENTORY_LINE)." l
            JOIN ".self::table(self::TABLE_CONTAINER)." c ON c.id = l.contenant_id
            JOIN ".self::table(self::TABLE_PRODUCT)." p ON p.id = c.produit_id
            JOIN ".self::table(self::TABLE_LOCATION)." e ON e.id = c.emplacement_id
            WHERE l.inventaire_id = ?
            ORDER BY p.nom_commercial, c.code_interne",
            "i",
            array((int) $inventoryId)
        );
    }

    public static function createInventory($label, $locationId, $login)
    {
        self::ensureTables();
        $label = self::limit($label, 190);
        $locationId = max(0, (int) $locationId);
        if ($label === '') {
            return 0;
        }
        if ($locationId > 0) {
            $location = self::location($locationId);
            if (!$location || (int) $location['actif'] !== 1) {
                return 0;
            }
        }
        $login = self::limit($login, 190);
        $db = $GLOBALS['db_c'];
        $db->begin_transaction();
        try {
            $insert = grr_sql_command(
                "INSERT INTO ".self::table(self::TABLE_INVENTORY)."
                    (libelle, emplacement_id, statut, opened_by, opened_at)
                VALUES (?, ?, 'ouvert', ?, ?)",
                "sisi",
                array($label, $locationId, $login, time())
            );
            if (!self::commandOk($insert)) {
                throw new RuntimeException('Création de l inventaire impossible.');
            }
            $inventoryId = (int) grr_sql_insert_id();
            $containers = self::rows(
                "SELECT c.id, c.quantite_courante,
                    COALESCE((SELECT MAX(m.id) FROM ".self::table(self::TABLE_MOVEMENT)." m WHERE m.contenant_id = c.id), 0) AS dernier_mouvement
                FROM ".self::table(self::TABLE_CONTAINER)." c
                WHERE c.statut = 'en_stock'".($locationId > 0 ? " AND c.emplacement_id = ?" : ""),
                $locationId > 0 ? "i" : null,
                $locationId > 0 ? array($locationId) : null
            );
            foreach ($containers as $container) {
                $line = grr_sql_command(
                    "INSERT INTO ".self::table(self::TABLE_INVENTORY_LINE)."
                        (inventaire_id, contenant_id, quantite_attendue, dernier_mouvement_id)
                    VALUES (?, ?, ?, ?)",
                    "iidi",
                    array($inventoryId, (int) $container['id'], (float) $container['quantite_courante'], (int) $container['dernier_mouvement'])
                );
                if (!self::commandOk($line)) {
                    throw new RuntimeException('Création des lignes d inventaire impossible.');
                }
            }
            if (!self::log('inventaire_ouvert', 'inventaire', $inventoryId, $label, $login)) {
                throw new RuntimeException('Journalisation de l inventaire impossible.');
            }
            $db->commit();
            return $inventoryId;
        } catch (Throwable $exception) {
            $db->rollback();
            return 0;
        }
    }

    public static function saveInventoryCounts($inventoryId, $counts, $comments, $login)
    {
        $inventory = self::inventory($inventoryId);
        if (!$inventory || (string) $inventory['statut'] !== 'ouvert' || !is_array($counts)) {
            return false;
        }
        $login = self::limit($login, 190);
        foreach ($counts as $lineId => $value) {
            $lineId = (int) $lineId;
            $value = trim((string) $value);
            if ($lineId <= 0 || $value === '') {
                continue;
            }
            $quantity = self::normalizeQuantity($value, true);
            if ($quantity === false) {
                return false;
            }
            $lineState = self::one(
                "SELECT l.statut, l.contenant_id, c.quantite_courante, c.statut AS contenant_statut,
                    COALESCE((SELECT MAX(m.id) FROM ".self::table(self::TABLE_MOVEMENT)." m WHERE m.contenant_id = l.contenant_id), 0) AS dernier_mouvement
                FROM ".self::table(self::TABLE_INVENTORY_LINE)." l
                JOIN ".self::table(self::TABLE_CONTAINER)." c ON c.id = l.contenant_id
                WHERE l.id = ? AND l.inventaire_id = ?",
                "ii",
                array($lineId, (int) $inventoryId)
            );
            if (!$lineState) {
                return false;
            }
            if ((string) $lineState['statut'] === 'conflit') {
                $refresh = grr_sql_command(
                    "UPDATE ".self::table(self::TABLE_INVENTORY_LINE)."
                    SET quantite_attendue = ?, dernier_mouvement_id = ?, statut = 'a_compter'
                    WHERE id = ? AND inventaire_id = ?",
                    "diii",
                    array(
                        (float) $lineState['quantite_courante'],
                        (int) $lineState['dernier_mouvement'],
                        $lineId,
                        (int) $inventoryId,
                    )
                );
                if (!self::commandOk($refresh)) {
                    return false;
                }
            }
            $comment = is_array($comments) && isset($comments[$lineId]) ? trim((string) $comments[$lineId]) : '';
            $lineStatus = (string) $lineState['contenant_statut'] === 'en_stock' ? 'compte' : 'hors_stock';
            $ok = grr_sql_command(
                "UPDATE ".self::table(self::TABLE_INVENTORY_LINE)."
                SET quantite_comptee = ?, ecart = ? - quantite_attendue, commentaire = ?,
                    statut = ?, updated_by = ?, updated_at = ?
                WHERE id = ? AND inventaire_id = ?",
                "ddsssiii",
                array($quantity, $quantity, $comment, $lineStatus, $login, time(), $lineId, (int) $inventoryId)
            );
            if (!self::commandOk($ok)) {
                return false;
            }
        }
        self::log('inventaire_saisi', 'inventaire', (int) $inventoryId, '', $login);
        return true;
    }

    public static function finalizeInventory($inventoryId, $login)
    {
        $inventory = self::inventory($inventoryId);
        if (!$inventory || (string) $inventory['statut'] !== 'ouvert') {
            return array('ok' => false, 'error' => 'Inventaire introuvable ou déjà terminé.');
        }
        $lines = self::inventoryLines($inventoryId);
        foreach ($lines as $line) {
            if ($line['quantite_comptee'] === null || $line['quantite_comptee'] === '') {
                return array('ok' => false, 'error' => 'Tous les contenants doivent être comptés.');
            }
        }
        $login = self::limit($login, 190);
        $db = $GLOBALS['db_c'];
        $db->begin_transaction();
        try {
            $locked = array();
            $conflicts = array();
            foreach ($lines as $line) {
                if ((string) $line['contenant_statut'] !== 'en_stock') {
                    if ((float) $line['quantite_comptee'] === 0.0) {
                        $locked[(int) $line['id']] = null;
                        continue;
                    }
                    $conflicts[] = (int) $line['id'];
                    continue;
                }
                $container = self::container((int) $line['contenant_id'], true);
                if (!$container || (string) $container['statut'] !== 'en_stock') {
                    $conflicts[] = (int) $line['id'];
                    continue;
                }
                $lastMovement = (int) grr_sql_query1(
                    "SELECT COALESCE(MAX(id), 0) FROM ".self::table(self::TABLE_MOVEMENT)." WHERE contenant_id = ?",
                    "i",
                    array((int) $container['id'])
                );
                if ($lastMovement !== (int) $line['dernier_mouvement_id']) {
                    $conflicts[] = (int) $line['id'];
                }
                $locked[(int) $line['id']] = $container;
            }
            if (count($conflicts) > 0) {
                foreach ($conflicts as $lineId) {
                    $conflictUpdate = grr_sql_command(
                        "UPDATE ".self::table(self::TABLE_INVENTORY_LINE)." SET statut = 'conflit', updated_by = ?, updated_at = ? WHERE id = ?",
                        "sii",
                        array($login, time(), $lineId)
                    );
                    if (!self::commandOk($conflictUpdate)) {
                        throw new RuntimeException('Enregistrement du conflit impossible.');
                    }
                }
                $db->commit();
                return array('ok' => false, 'error' => 'Des mouvements ont eu lieu depuis l ouverture. Les lignes en conflit doivent être vérifiées.');
            }

            foreach ($lines as $line) {
                $lineId = (int) $line['id'];
                $container = $locked[$lineId];
                if ($container === null) {
                    $closedUpdate = grr_sql_command(
                        "UPDATE ".self::table(self::TABLE_INVENTORY_LINE)."
                        SET ecart = 0, statut = 'applique', updated_by = ?, updated_at = ? WHERE id = ?",
                        "sii",
                        array($login, time(), $lineId)
                    );
                    if (!self::commandOk($closedUpdate)) {
                        throw new RuntimeException('Mise à jour de la ligne d inventaire impossible.');
                    }
                    continue;
                }
                $before = (float) $container['quantite_courante'];
                $after = (float) $line['quantite_comptee'];
                $difference = round($after - $before, 4);
                if (abs($difference) > 0.00001) {
                    $type = $difference > 0 ? 'correction_plus' : 'correction_moins';
                    $movementId = self::insertMovement(
                        (int) $container['id'],
                        $type,
                        abs($difference),
                        $before,
                        $after,
                        (string) $container['unite'],
                        (int) $container['emplacement_id'],
                        0,
                        0,
                        'Correction inventaire #'.(int) $inventoryId,
                        time(),
                        self::randomHex(32),
                        $login
                    );
                    if ($movementId <= 0) {
                        throw new RuntimeException('Correction d inventaire impossible.');
                    }
                    $status = $after <= 0 ? 'vide' : 'en_stock';
                    $update = grr_sql_command(
                        "UPDATE ".self::table(self::TABLE_CONTAINER)."
                        SET quantite_courante = ?, statut = ?, updated_by = ?, updated_at = ? WHERE id = ?",
                        "dssii",
                        array($after, $status, $login, time(), (int) $container['id'])
                    );
                    if (!self::commandOk($update)) {
                        throw new RuntimeException('Mise à jour du contenant impossible.');
                    }
                }
                $lineUpdate = grr_sql_command(
                    "UPDATE ".self::table(self::TABLE_INVENTORY_LINE)."
                    SET ecart = ?, statut = 'applique', updated_by = ?, updated_at = ? WHERE id = ?",
                    "dsii",
                    array($difference, $login, time(), $lineId)
                );
                if (!self::commandOk($lineUpdate)) {
                    throw new RuntimeException('Mise à jour de la ligne d inventaire impossible.');
                }
            }
            $finish = grr_sql_command(
                "UPDATE ".self::table(self::TABLE_INVENTORY)."
                SET statut = 'termine', completed_by = ?, completed_at = ? WHERE id = ?",
                "sii",
                array($login, time(), (int) $inventoryId)
            );
            if (!self::commandOk($finish)) {
                throw new RuntimeException('Clôture de l inventaire impossible.');
            }
            if (!self::log('inventaire_termine', 'inventaire', (int) $inventoryId, '', $login)) {
                throw new RuntimeException('Journalisation de la clôture impossible.');
            }
            $db->commit();
            return array('ok' => true, 'error' => '');
        } catch (Throwable $exception) {
            $db->rollback();
            return array('ok' => false, 'error' => $exception->getMessage());
        }
    }

    public static function cancelInventory($inventoryId, $login)
    {
        $inventoryId = (int) $inventoryId;
        $inventory = self::inventory($inventoryId);
        if (!$inventory || (string) $inventory['statut'] !== 'ouvert') {
            return array('ok' => false, 'error' => 'Seul un inventaire ouvert peut être annulé.');
        }
        $login = self::limit($login, 190);
        $db = $GLOBALS['db_c'];
        $db->begin_transaction();
        try {
            $update = grr_sql_command(
                "UPDATE ".self::table(self::TABLE_INVENTORY)."
                SET statut = 'annule', completed_by = ?, completed_at = ?
                WHERE id = ? AND statut = 'ouvert'",
                "sii",
                array($login, time(), $inventoryId)
            );
            if (!self::commandOk($update)) {
                throw new RuntimeException('Annulation de l inventaire impossible.');
            }
            if (!self::log('inventaire_annule', 'inventaire', $inventoryId, (string) $inventory['libelle'], $login)) {
                throw new RuntimeException('Journalisation de l annulation impossible.');
            }
            $db->commit();
            return array('ok' => true, 'error' => '');
        } catch (Throwable $exception) {
            $db->rollback();
            return array('ok' => false, 'error' => $exception->getMessage());
        }
    }

    public static function notificationRecipients()
    {
        return self::rows(
            "SELECT r.login, u.email, u.nom, u.prenom
            FROM ".self::table(self::TABLE_ROLE)." r
            JOIN ".TABLE_PREFIX."_utilisateurs u ON u.login = r.login
            WHERE r.role = 'gestionnaire' AND u.etat != 'inactif' AND u.email != ''
            ORDER BY r.login"
        );
    }

    public static function notificationLogs($limit = 500)
    {
        return self::rows(
            "SELECT * FROM ".self::table(self::TABLE_NOTIFICATION_LOG)."
            ORDER BY sent_at DESC, id DESC
            LIMIT ".self::normalizeLimit($limit, 500, 2000)
        );
    }

    public static function supplierIdByName($name)
    {
        $name = self::limit($name, 190);
        if ($name === '') {
            return 0;
        }
        $id = grr_sql_query1(
            "SELECT id FROM ".self::table(self::TABLE_SUPPLIER)."
            WHERE LOWER(nom) = LOWER(?) AND actif = 1
            ORDER BY id LIMIT 1",
            "s",
            array($name)
        );
        return is_numeric($id) && (int) $id > 0 ? (int) $id : 0;
    }

    public static function locationIdByName($name)
    {
        $name = self::limit($name, 190);
        if ($name === '') {
            return 0;
        }
        $id = grr_sql_query1(
            "SELECT id FROM ".self::table(self::TABLE_LOCATION)."
            WHERE LOWER(nom) = LOWER(?) AND actif = 1
            ORDER BY id LIMIT 1",
            "s",
            array($name)
        );
        return is_numeric($id) && (int) $id > 0 ? (int) $id : 0;
    }

    public static function productIdByReference($reference)
    {
        $reference = self::limit($reference, 100);
        if ($reference === '') {
            return 0;
        }
        $id = grr_sql_query1(
            "SELECT id FROM ".self::table(self::TABLE_PRODUCT)."
            WHERE reference_interne = ?
            ORDER BY id LIMIT 1",
            "s",
            array($reference)
        );
        return is_numeric($id) && (int) $id > 0 ? (int) $id : 0;
    }

    public static function containerIdByCode($code)
    {
        $code = self::limit($code, 100);
        if ($code === '') {
            return 0;
        }
        $id = grr_sql_query1(
            "SELECT id FROM ".self::table(self::TABLE_CONTAINER)."
            WHERE code_interne = ?
            ORDER BY id LIMIT 1",
            "s",
            array($code)
        );
        return is_numeric($id) && (int) $id > 0 ? (int) $id : 0;
    }

    public static function documentIdByHash($productId, $sha256)
    {
        $id = grr_sql_query1(
            "SELECT id FROM ".self::table(self::TABLE_DOCUMENT)."
            WHERE produit_id = ? AND sha256 = ? AND actif = 1
            ORDER BY id LIMIT 1",
            "is",
            array((int) $productId, self::limit($sha256, 64))
        );
        return is_numeric($id) && (int) $id > 0 ? (int) $id : 0;
    }

    public static function importRowStatus($packageHash, $sourceRow)
    {
        $status = grr_sql_query1(
            "SELECT status FROM ".self::table(self::TABLE_IMPORT_LOG)."
            WHERE package_hash = ? AND source_row = ?",
            "si",
            array(self::limit($packageHash, 64), (int) $sourceRow)
        );
        return is_string($status) ? $status : '';
    }

    public static function logImportRow($packageHash, $packageName, $sourceRow, $productId, $containerId, $documentId, $status, $message, $login)
    {
        return self::commandOk(grr_sql_command(
            "INSERT INTO ".self::table(self::TABLE_IMPORT_LOG)."
                (package_hash, package_name, source_row, product_id, container_id, document_id,
                status, message, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE product_id = VALUES(product_id), container_id = VALUES(container_id),
                document_id = VALUES(document_id), status = VALUES(status), message = VALUES(message),
                created_by = VALUES(created_by), created_at = VALUES(created_at)",
            "ssiiiisssi",
            array(
                self::limit($packageHash, 64),
                self::limit($packageName, 190),
                (int) $sourceRow,
                (int) $productId,
                (int) $containerId,
                (int) $documentId,
                self::limit($status, 20),
                self::limit($message, 5000),
                self::limit($login, 190),
                time(),
            )
        ));
    }

    public static function importHistory($limit = 500)
    {
        return self::rows(
            "SELECT * FROM ".self::table(self::TABLE_IMPORT_LOG)."
            ORDER BY created_at DESC, id DESC
            LIMIT ".self::normalizeLimit($limit, 500, 2000)
        );
    }

    public static function notificationWasSent($alertKey, $login)
    {
        return (int) grr_sql_query1(
            "SELECT COUNT(*) FROM ".self::table(self::TABLE_NOTIFICATION_LOG)." WHERE alert_key = ? AND login = ? AND status = 'sent'",
            "ss",
            array(hash('sha256', (string) $alertKey), self::limit($login, 190))
        ) > 0;
    }

    public static function logNotification($alertKey, $login, $type, $objectId, $status, $message)
    {
        return self::commandOk(grr_sql_command(
            "INSERT INTO ".self::table(self::TABLE_NOTIFICATION_LOG)."
                (alert_key, login, type_notification, objet_id, sent_at, status, message)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE sent_at = VALUES(sent_at), status = VALUES(status), message = VALUES(message)",
            "sssiiss",
            array(
                hash('sha256', (string) $alertKey),
                self::limit($login, 190),
                self::limit($type, 50),
                (int) $objectId,
                time(),
                self::limit($status, 20),
                trim((string) $message),
            )
        ));
    }

    public static function journal($limit = 200)
    {
        return self::rows(
            "SELECT * FROM ".self::table(self::TABLE_JOURNAL)." ORDER BY created_at DESC, id DESC LIMIT "
            .self::normalizeLimit($limit, 200, 2000)
        );
    }

    public static function log($event, $objectType, $objectId, $summary, $login)
    {
        return self::commandOk(grr_sql_command(
            "INSERT INTO ".self::table(self::TABLE_JOURNAL)."
                (type_evenement, type_objet, objet_id, resume, login, created_at)
            VALUES (?, ?, ?, ?, ?, ?)",
            "ssissi",
            array(
                self::limit($event, 50),
                self::limit($objectType, 50),
                (int) $objectId,
                self::limit($summary, 5000),
                self::limit($login, 190),
                time(),
            )
        ));
    }

    public static function countRows($suffix)
    {
        return self::tableExists($suffix)
            ? max(0, (int) grr_sql_query1("SELECT COUNT(*) FROM ".self::table($suffix)))
            : 0;
    }

    private static function insertMovement($containerId, $type, $quantity, $before, $after, $unit, $sourceId, $destinationId, $sourceMovementId, $reason, $effectiveAt, $requestToken, $login)
    {
        $insert = grr_sql_command(
            "INSERT INTO ".self::table(self::TABLE_MOVEMENT)."
                (contenant_id, type_mouvement, quantite, quantite_avant, quantite_apres, unite,
                emplacement_source_id, emplacement_destination_id, mouvement_source_id, motif,
                date_effective, request_token, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            "isdddsiiisissi",
            array(
                (int) $containerId,
                self::limit($type, 30),
                (float) $quantity,
                (float) $before,
                (float) $after,
                self::limit($unit, 10),
                (int) $sourceId,
                (int) $destinationId,
                (int) $sourceMovementId,
                trim((string) $reason),
                (int) $effectiveAt,
                self::limit($requestToken, 64),
                self::limit($login, 190),
                time(),
            )
        );
        return self::commandOk($insert) ? (int) grr_sql_insert_id() : 0;
    }

    private static function alertRow($type, $productId, $label, $detail, $date = '', $containerId = 0, $documentId = 0)
    {
        return array(
            'type' => $type,
            'produit_id' => (int) $productId,
            'contenant_id' => (int) $containerId,
            'document_id' => (int) $documentId,
            'label' => (string) $label,
            'detail' => (string) $detail,
            'date' => (string) $date,
            'alert_key' => $type.':'.(int) $productId.':'.(int) $containerId.':'.(string) $date,
        );
    }

    private static function validateContainerDates($source)
    {
        $receipt = self::nullableDate(isset($source['date_reception']) ? $source['date_reception'] : '');
        $opening = self::nullableDate(isset($source['date_ouverture']) ? $source['date_ouverture'] : '');
        $expiry = self::nullableDate(isset($source['date_peremption']) ? $source['date_peremption'] : '');
        if (
            (isset($source['date_reception']) && trim((string) $source['date_reception']) !== '' && $receipt === null)
            || (isset($source['date_ouverture']) && trim((string) $source['date_ouverture']) !== '' && $opening === null)
            || (isset($source['date_peremption']) && trim((string) $source['date_peremption']) !== '' && $expiry === null)
        ) {
            return array('ok' => false, 'error' => 'Date invalide.');
        }
        if ($receipt !== null && $opening !== null && $opening < $receipt) {
            return array('ok' => false, 'error' => 'La date d ouverture ne peut pas précéder la réception.');
        }
        if ($receipt !== null && $expiry !== null && $expiry < $receipt) {
            return array('ok' => false, 'error' => 'La péremption ne peut pas précéder la réception.');
        }
        return array('ok' => true, 'date_reception' => $receipt, 'date_ouverture' => $opening, 'date_peremption' => $expiry, 'error' => '');
    }

    private static function locationWouldCycle($locationId, $parentId)
    {
        $locationId = (int) $locationId;
        $parentId = (int) $parentId;
        $seen = array();
        for ($depth = 0; $parentId > 0 && $depth < 20; $depth++) {
            if ($parentId === $locationId || isset($seen[$parentId])) {
                return true;
            }
            $seen[$parentId] = true;
            $parent = self::location($parentId);
            $parentId = $parent ? (int) $parent['parent_id'] : 0;
        }
        return $parentId > 0;
    }

    private static function locationPath($location, $map)
    {
        $parts = array((string) $location['nom']);
        $parentId = (int) $location['parent_id'];
        $seen = array((int) $location['id'] => true);
        for ($depth = 0; $parentId > 0 && $depth < 20; $depth++) {
            if (isset($seen[$parentId]) || !isset($map[$parentId])) {
                break;
            }
            $seen[$parentId] = true;
            array_unshift($parts, (string) $map[$parentId]['nom']);
            $parentId = (int) $map[$parentId]['parent_id'];
        }
        return implode(' / ', $parts);
    }

    private static function effectiveTimestamp($date)
    {
        $date = trim((string) $date);
        if ($date === '') {
            return time();
        }
        $value = strtotime($date.' 12:00:00');
        return $value === false ? time() : (int) $value;
    }

    private static function nullableDate($date)
    {
        $date = trim((string) $date);
        if ($date === '') {
            return null;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return null;
        }
        $parts = array_map('intval', explode('-', $date));
        return checkdate($parts[1], $parts[2], $parts[0]) ? $date : null;
    }

    private static function normalizeQuantity($value, $allowZero)
    {
        $value = str_replace(',', '.', trim((string) $value));
        if ($value === '' || !preg_match('/^\d{1,11}(?:\.\d{1,4})?$/', $value)) {
            return false;
        }
        $number = round((float) $value, 4);
        if ($number < 0 || (!$allowZero && $number <= 0)) {
            return false;
        }
        return $number;
    }

    private static function validRequestToken($token)
    {
        return preg_match('/^[a-f0-9]{64}$/', trim((string) $token)) === 1;
    }

    private static function randomHex($bytes)
    {
        try {
            return bin2hex(random_bytes((int) $bytes));
        } catch (Throwable $exception) {
            return substr(hash('sha256', uniqid('', true).mt_rand()), 0, ((int) $bytes) * 2);
        }
    }

    private static function countNegativeStocks()
    {
        return self::tableExists(self::TABLE_CONTAINER)
            ? (int) grr_sql_query1("SELECT COUNT(*) FROM ".self::table(self::TABLE_CONTAINER)." WHERE quantite_courante < 0")
            : 0;
    }

    private static function countStockMismatches()
    {
        if (!self::tableExists(self::TABLE_CONTAINER) || !self::tableExists(self::TABLE_MOVEMENT)) {
            return 0;
        }
        return (int) grr_sql_query1(
            "SELECT COUNT(*)
            FROM ".self::table(self::TABLE_CONTAINER)." c
            LEFT JOIN ".self::table(self::TABLE_MOVEMENT)." m ON m.id = (
                SELECT m2.id FROM ".self::table(self::TABLE_MOVEMENT)." m2
                WHERE m2.contenant_id = c.id ORDER BY m2.id DESC LIMIT 1
            )
            WHERE m.id IS NULL OR c.quantite_courante <> m.quantite_apres"
        );
    }

    private static function countDuplicateCurrentSds()
    {
        if (!self::tableExists(self::TABLE_DOCUMENT)) {
            return 0;
        }
        $rows = self::rows(
            "SELECT produit_id, langue FROM ".self::table(self::TABLE_DOCUMENT)."
            WHERE actif = 1 AND type_document = 'fds' AND est_courant = 1
            GROUP BY produit_id, langue HAVING COUNT(*) > 1"
        );
        return count($rows);
    }

    private static function table($suffix)
    {
        return TABLE_PREFIX.'_'.$suffix;
    }

    private static function tableExists($suffix)
    {
        $table = self::table($suffix);
        return (int) grr_sql_query1(
            "SELECT COUNT(*) FROM information_schema.tables
            WHERE table_schema = DATABASE() AND table_name = ?",
            "s",
            array($table)
        ) > 0;
    }

    private static function tableEngine($suffix)
    {
        $table = self::table($suffix);
        $engine = grr_sql_query1(
            "SELECT engine FROM information_schema.tables
            WHERE table_schema = DATABASE() AND table_name = ?",
            "s",
            array($table)
        );
        return is_string($engine) ? $engine : '';
    }

    private static function columnLength($suffix, $column)
    {
        $length = grr_sql_query1(
            "SELECT COALESCE(character_maximum_length, 0)
            FROM information_schema.columns
            WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?",
            "ss",
            array(self::table($suffix), self::limit($column, 64))
        );
        return is_numeric($length) ? (int) $length : 0;
    }

    private static function columnExists($suffix, $column)
    {
        return (int) grr_sql_query1(
            "SELECT COUNT(*) FROM information_schema.columns
            WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?",
            "ss",
            array(self::table($suffix), self::limit($column, 64))
        ) > 0;
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

    private static function commandOk($result)
    {
        return !($result === false || $result < 0);
    }

    private static function normalizeLimit($limit, $default, $max)
    {
        $limit = (int) $limit;
        return $limit > 0 && $limit <= $max ? $limit : $default;
    }

    private static function limit($value, $length)
    {
        return substr(trim((string) $value), 0, (int) $length);
    }
}
