<?php

class GestionMaterielRepository
{
    const TABLE_ITEM = 'gestion_materiel_item';
    const TABLE_GROUP = 'gestion_materiel_groupe';
    const TABLE_USER = 'gestion_materiel_user';
    const TABLE_ACTION = 'gestion_materiel_action';
    const TABLE_DOCUMENT = 'gestion_materiel_document';
    const TABLE_NOTIFICATION_LOG = 'gestion_materiel_notification_log';

    public static function ensureTables()
    {
        grr_sql_command("CREATE TABLE IF NOT EXISTS `".self::table(self::TABLE_ITEM)."` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `reference` varchar(100) NOT NULL DEFAULT '',
            `nom` varchar(190) NOT NULL,
            `categorie` varchar(100) NOT NULL DEFAULT '',
            `fabricant` varchar(100) NOT NULL DEFAULT '',
            `modele` varchar(100) NOT NULL DEFAULT '',
            `numero_serie` varchar(100) NOT NULL DEFAULT '',
            `numero_inventaire` varchar(100) NOT NULL DEFAULT '',
            `statut` varchar(30) NOT NULL DEFAULT 'en_service',
            `localisation` varchar(190) NOT NULL DEFAULT '',
            `groupe_id` int(11) NOT NULL DEFAULT 0,
            `date_acquisition` int(11) NOT NULL DEFAULT 0,
            `date_fin_garantie` int(11) NOT NULL DEFAULT 0,
            `maintenance_interval_jours` int(11) NOT NULL DEFAULT 0,
            `maintenance_prochaine` int(11) NOT NULL DEFAULT 0,
            `etalonnage_interval_jours` int(11) NOT NULL DEFAULT 0,
            `etalonnage_prochain` int(11) NOT NULL DEFAULT 0,
            `description` text NULL,
            `created_by` varchar(190) NOT NULL DEFAULT '',
            `created_at` int(11) NOT NULL DEFAULT 0,
            `updated_at` int(11) NOT NULL DEFAULT 0,
            `actif` tinyint(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (`id`),
            KEY `reference` (`reference`),
            KEY `statut` (`statut`),
            KEY `groupe_id` (`groupe_id`),
            KEY `maintenance_prochaine` (`maintenance_prochaine`),
            KEY `etalonnage_prochain` (`etalonnage_prochain`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");

        if (self::tableExists(self::TABLE_ITEM) && !self::columnExists(self::TABLE_ITEM, 'groupe_id')) {
            grr_sql_command("ALTER TABLE `".self::table(self::TABLE_ITEM)."` ADD `groupe_id` int(11) NOT NULL DEFAULT 0 AFTER `localisation`");
            grr_sql_command("ALTER TABLE `".self::table(self::TABLE_ITEM)."` ADD KEY `groupe_id` (`groupe_id`)");
        }

        grr_sql_command("CREATE TABLE IF NOT EXISTS `".self::table(self::TABLE_GROUP)."` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `nom` varchar(190) NOT NULL,
            `description` text NULL,
            `created_by` varchar(190) NOT NULL DEFAULT '',
            `created_at` int(11) NOT NULL DEFAULT 0,
            `updated_at` int(11) NOT NULL DEFAULT 0,
            `actif` tinyint(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (`id`),
            KEY `nom` (`nom`),
            KEY `actif` (`actif`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");

        grr_sql_command("CREATE TABLE IF NOT EXISTS `".self::table(self::TABLE_USER)."` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `item_id` int(11) NOT NULL,
            `login` varchar(190) NOT NULL,
            `notify_maintenance` tinyint(1) NOT NULL DEFAULT 1,
            `notify_etalonnage` tinyint(1) NOT NULL DEFAULT 1,
            `created_by` varchar(190) NOT NULL DEFAULT '',
            `created_at` int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            UNIQUE KEY `item_login` (`item_id`, `login`),
            KEY `item_id` (`item_id`),
            KEY `login` (`login`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");

        grr_sql_command("CREATE TABLE IF NOT EXISTS `".self::table(self::TABLE_ACTION)."` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `item_id` int(11) NOT NULL,
            `type_action` varchar(50) NOT NULL DEFAULT '',
            `date_action` int(11) NOT NULL DEFAULT 0,
            `commentaire` text NULL,
            `cout` decimal(10,2) DEFAULT NULL,
            `prochaine_maintenance` int(11) NOT NULL DEFAULT 0,
            `prochain_etalonnage` int(11) NOT NULL DEFAULT 0,
            `created_by` varchar(190) NOT NULL DEFAULT '',
            `created_at` int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            KEY `item_id` (`item_id`),
            KEY `type_action` (`type_action`),
            KEY `date_action` (`date_action`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");

        grr_sql_command("CREATE TABLE IF NOT EXISTS `".self::table(self::TABLE_DOCUMENT)."` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `item_id` int(11) NOT NULL,
            `type_document` varchar(50) NOT NULL DEFAULT 'autre',
            `description` text NULL,
            `original_name` varchar(255) NOT NULL DEFAULT '',
            `stored_name` varchar(64) NOT NULL DEFAULT '',
            `mime_type` varchar(190) NOT NULL DEFAULT 'application/octet-stream',
            `taille` int(11) NOT NULL DEFAULT 0,
            `uploaded_by` varchar(190) NOT NULL DEFAULT '',
            `created_at` int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            UNIQUE KEY `stored_name` (`stored_name`),
            KEY `item_id` (`item_id`),
            KEY `type_document` (`type_document`),
            KEY `created_at` (`created_at`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");

        grr_sql_command("CREATE TABLE IF NOT EXISTS `".self::table(self::TABLE_NOTIFICATION_LOG)."` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `item_id` int(11) NOT NULL,
            `login` varchar(190) NOT NULL DEFAULT '',
            `type_notification` varchar(50) NOT NULL DEFAULT '',
            `echeance` int(11) NOT NULL DEFAULT 0,
            `sent_at` int(11) NOT NULL DEFAULT 0,
            `status` varchar(20) NOT NULL DEFAULT 'sent',
            `message` text NULL,
            PRIMARY KEY (`id`),
            KEY `item_login` (`item_id`, `login`),
            KEY `type_notification` (`type_notification`),
            KEY `sent_at` (`sent_at`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");
    }

    public static function expectedTables()
    {
        return array(
            self::TABLE_ITEM => 'Materiels',
            self::TABLE_GROUP => 'Groupes materiel',
            self::TABLE_USER => 'Utilisateurs assignes',
            self::TABLE_ACTION => 'Actions materiel',
            self::TABLE_DOCUMENT => 'Documents materiel',
            self::TABLE_NOTIFICATION_LOG => 'Journal des notifications',
        );
    }

    public static function diagnostics()
    {
        $diagnostics = array();
        foreach (self::expectedTables() as $suffix => $label) {
            $diagnostics[] = array(
                'label' => $label,
                'table' => self::table($suffix),
                'exists' => self::tableExists($suffix),
            );
        }

        return $diagnostics;
    }

    public static function countItems()
    {
        if (!self::tableExists(self::TABLE_ITEM)) {
            return 0;
        }

        return (int) grr_sql_query1(
            "SELECT COUNT(*) FROM ".self::table(self::TABLE_ITEM)."
            WHERE actif = 1 AND statut <> 'archive'"
        );
    }

    public static function countArchivedItems()
    {
        if (!self::tableExists(self::TABLE_ITEM)) {
            return 0;
        }

        return (int) grr_sql_query1(
            "SELECT COUNT(*) FROM ".self::table(self::TABLE_ITEM)."
            WHERE statut = 'archive'"
        );
    }

    public static function countAssignedUsers()
    {
        if (!self::tableExists(self::TABLE_USER) || !self::tableExists(self::TABLE_ITEM)) {
            return 0;
        }

        return (int) grr_sql_query1(
            "SELECT COUNT(*)
            FROM ".self::table(self::TABLE_USER)." gu
            JOIN ".self::table(self::TABLE_ITEM)." i ON i.id = gu.item_id
            WHERE i.actif = 1 AND i.statut <> 'archive'"
        );
    }

    public static function countItemsForUser($login, $canViewAll)
    {
        if ($canViewAll) {
            return self::countItems();
        }

        $login = self::limit($login, 190);
        if ($login === '' || !self::tableExists(self::TABLE_ITEM) || !self::tableExists(self::TABLE_USER)) {
            return 0;
        }

        return (int) grr_sql_query1(
            "SELECT COUNT(DISTINCT i.id)
            FROM ".self::table(self::TABLE_ITEM)." i
            JOIN ".self::table(self::TABLE_USER)." gu ON gu.item_id = i.id
            WHERE i.actif = 1 AND i.statut <> 'archive' AND gu.login = ?",
            "s",
            array($login)
        );
    }

    public static function countAssignedUsersForUser($login, $canViewAll)
    {
        if ($canViewAll) {
            return self::countAssignedUsers();
        }

        $login = self::limit($login, 190);
        if ($login === '' || !self::tableExists(self::TABLE_ITEM) || !self::tableExists(self::TABLE_USER)) {
            return 0;
        }

        return (int) grr_sql_query1(
            "SELECT COUNT(DISTINCT gu2.login)
            FROM ".self::table(self::TABLE_ITEM)." i
            JOIN ".self::table(self::TABLE_USER)." gu ON gu.item_id = i.id
            JOIN ".self::table(self::TABLE_USER)." gu2 ON gu2.item_id = i.id
            WHERE i.actif = 1 AND i.statut <> 'archive' AND gu.login = ?",
            "s",
            array($login)
        );
    }

    public static function userHasAssignedItems($login)
    {
        return self::countItemsForUser($login, false) > 0;
    }

    public static function userIsAssignedToItem($login, $itemId)
    {
        $login = self::limit($login, 190);
        $itemId = (int) $itemId;
        if ($login === '' || $itemId <= 0 || !self::tableExists(self::TABLE_ITEM) || !self::tableExists(self::TABLE_USER)) {
            return false;
        }

        $count = grr_sql_query1(
            "SELECT COUNT(*)
            FROM ".self::table(self::TABLE_USER)." gu
            JOIN ".self::table(self::TABLE_ITEM)." i ON i.id = gu.item_id
            WHERE gu.login = ? AND gu.item_id = ? AND i.actif = 1 AND i.statut <> 'archive'",
            "si",
            array($login, $itemId)
        );

        return (int) $count > 0;
    }

    public static function countAssignedUsersForItem($itemId)
    {
        $itemId = (int) $itemId;
        if ($itemId <= 0 || !self::tableExists(self::TABLE_USER)) {
            return 0;
        }

        return (int) grr_sql_query1(
            "SELECT COUNT(*) FROM ".self::table(self::TABLE_USER)." WHERE item_id = ?",
            "i",
            array($itemId)
        );
    }

    public static function countActions()
    {
        if (!self::tableExists(self::TABLE_ACTION) || !self::tableExists(self::TABLE_ITEM)) {
            return 0;
        }

        return (int) grr_sql_query1(
            "SELECT COUNT(*)
            FROM ".self::table(self::TABLE_ACTION)." a
            JOIN ".self::table(self::TABLE_ITEM)." i ON i.id = a.item_id
            WHERE i.actif = 1 AND i.statut <> 'archive'"
        );
    }

    public static function countActionsForUser($login, $canViewAll)
    {
        if ($canViewAll) {
            return self::countActions();
        }

        $login = self::limit($login, 190);
        if ($login === '' || !self::tableExists(self::TABLE_ACTION) || !self::tableExists(self::TABLE_ITEM) || !self::tableExists(self::TABLE_USER)) {
            return 0;
        }

        return (int) grr_sql_query1(
            "SELECT COUNT(*)
            FROM ".self::table(self::TABLE_ACTION)." a
            JOIN ".self::table(self::TABLE_ITEM)." i ON i.id = a.item_id
            JOIN ".self::table(self::TABLE_USER)." gu ON gu.item_id = i.id
            WHERE i.actif = 1 AND i.statut <> 'archive' AND gu.login = ?",
            "s",
            array($login)
        );
    }

    public static function countUpcomingMaintenance($days = 30)
    {
        return self::countUpcoming('maintenance_prochaine', $days);
    }

    public static function countOverdueMaintenance()
    {
        return self::countOverdue('maintenance_prochaine');
    }

    public static function countUpcomingEtalonnage($days = 30)
    {
        return self::countUpcoming('etalonnage_prochain', $days);
    }

    public static function countOverdueEtalonnage()
    {
        return self::countOverdue('etalonnage_prochain');
    }

    public static function countNotificationLogs()
    {
        return self::countRows(self::TABLE_NOTIFICATION_LOG);
    }

    public static function countDocuments()
    {
        return self::countRows(self::TABLE_DOCUMENT);
    }

    public static function countUpcomingNotifications($days = 30)
    {
        return count(self::upcomingNotifications($days, false));
    }

    public static function deadlineAlertCounts($days = 30)
    {
        $maintenance = self::deadlineCountsForField('maintenance_prochaine', $days);
        $etalonnage = self::deadlineCountsForField('etalonnage_prochain', $days);

        return array(
            'maintenance_overdue' => $maintenance['overdue'],
            'maintenance_upcoming' => $maintenance['upcoming'],
            'etalonnage_overdue' => $etalonnage['overdue'],
            'etalonnage_attention' => $etalonnage['attention'],
            'etalonnage_upcoming' => $etalonnage['upcoming'],
            'total_overdue' => $maintenance['overdue'] + $etalonnage['overdue'],
            'total_attention' => $etalonnage['attention'],
            'total_upcoming' => $maintenance['upcoming'] + $etalonnage['upcoming'],
            'total' => $maintenance['overdue'] + $maintenance['upcoming'] + $etalonnage['overdue'] + $etalonnage['attention'] + $etalonnage['upcoming'],
        );
    }

    public static function deadlineAlertCountsForUser($login, $canViewAll, $days = 30)
    {
        if ($canViewAll) {
            return self::deadlineAlertCounts($days);
        }

        $maintenance = self::deadlineCountsForFieldForUser('maintenance_prochaine', $login, $days);
        $etalonnage = self::deadlineCountsForFieldForUser('etalonnage_prochain', $login, $days);

        return array(
            'maintenance_overdue' => $maintenance['overdue'],
            'maintenance_upcoming' => $maintenance['upcoming'],
            'etalonnage_overdue' => $etalonnage['overdue'],
            'etalonnage_attention' => $etalonnage['attention'],
            'etalonnage_upcoming' => $etalonnage['upcoming'],
            'total_overdue' => $maintenance['overdue'] + $etalonnage['overdue'],
            'total_attention' => $etalonnage['attention'],
            'total_upcoming' => $maintenance['upcoming'] + $etalonnage['upcoming'],
            'total' => $maintenance['overdue'] + $maintenance['upcoming'] + $etalonnage['overdue'] + $etalonnage['attention'] + $etalonnage['upcoming'],
        );
    }

    public static function deadlineAlerts($days = 30, $limit = 100)
    {
        self::ensureTables();

        $limit = self::normalizeLimit($limit, 100, 500);
        $rows = array_merge(
            self::deadlineRows('maintenance', 'maintenance_prochaine', $days),
            self::deadlineRows('etalonnage', 'etalonnage_prochain', $days)
        );

        $today = self::dayStart();
        foreach ($rows as $index => $row) {
            $echeance = isset($row['echeance']) ? (int) $row['echeance'] : 0;
            $delta = $echeance > 0 ? (int) floor(($echeance - $today) / 86400) : 0;
            $status = 'upcoming';
            if ($echeance > 0 && $echeance < $today) {
                $status = self::deadlineAlertStatus($row);
            } elseif ($echeance === $today) {
                $status = 'today';
            }

            $rows[$index]['alert_status'] = $status;
            $rows[$index]['days_delta'] = $delta;
        }

        usort($rows, function ($a, $b) {
            return self::sortDeadlineAlerts($a, $b);
        });

        return array_slice($rows, 0, $limit);
    }

    public static function deadlineAlertsForUser($login, $canViewAll, $days = 30, $limit = 100)
    {
        if ($canViewAll) {
            return self::deadlineAlerts($days, $limit);
        }

        self::ensureTables();

        $limit = self::normalizeLimit($limit, 100, 500);
        $rows = array_merge(
            self::deadlineRowsForUser('maintenance', 'maintenance_prochaine', $login, $days),
            self::deadlineRowsForUser('etalonnage', 'etalonnage_prochain', $login, $days)
        );

        $today = self::dayStart();
        foreach ($rows as $index => $row) {
            $echeance = isset($row['echeance']) ? (int) $row['echeance'] : 0;
            $delta = $echeance > 0 ? (int) floor(($echeance - $today) / 86400) : 0;
            $status = 'upcoming';
            if ($echeance > 0 && $echeance < $today) {
                $status = self::deadlineAlertStatus($row);
            } elseif ($echeance === $today) {
                $status = 'today';
            }

            $rows[$index]['alert_status'] = $status;
            $rows[$index]['days_delta'] = $delta;
        }

        usort($rows, function ($a, $b) {
            return self::sortDeadlineAlerts($a, $b);
        });

        return array_slice($rows, 0, $limit);
    }

    public static function itemStatuses()
    {
        return array(
            'en_service' => 'En service',
            'maintenance' => 'En maintenance',
            'panne' => 'En panne',
            'hors_service' => 'Hors service',
            'sans_projet' => 'Pas de projet en cours',
            'archive' => 'Archive',
        );
    }

    public static function actionTypes()
    {
        return array(
            'acquisition' => 'Acquisition',
            'maintenance' => 'Maintenance',
            'etalonnage' => 'Etalonnage',
            'controle' => 'Controle',
            'panne' => 'Panne',
            'reparation' => 'Reparation',
            'reparation_partielle' => 'Reparation partielle',
            'fin_projet' => 'Fin de projet',
            'debut_projet' => 'Debut de projet',
            'autre' => 'Autre',
        );
    }

    public static function emptyGroupValues()
    {
        return array(
            'nom' => '',
            'description' => '',
        );
    }

    public static function normalizeGroupValues($source)
    {
        if (!is_array($source)) {
            $source = array();
        }

        $values = self::emptyGroupValues();
        foreach ($values as $key => $value) {
            if (isset($source[$key])) {
                $values[$key] = trim((string) $source[$key]);
            }
        }

        $values['nom'] = self::limit($values['nom'], 190);

        return $values;
    }

    public static function validateGroupValues($values)
    {
        $errors = array();
        if (!is_array($values)) {
            $errors[] = 'Donnees invalides.';
            return $errors;
        }

        if (trim((string) $values['nom']) === '') {
            $errors[] = 'Le nom du groupe est obligatoire.';
        }

        return $errors;
    }

    public static function createGroup($values, $createdBy)
    {
        self::ensureTables();

        $values = self::normalizeGroupValues($values);
        if (count(self::validateGroupValues($values)) > 0) {
            return 0;
        }

        $now = time();
        $createdBy = self::limit(trim((string) $createdBy), 190);
        $insert = grr_sql_command(
            "INSERT INTO ".self::table(self::TABLE_GROUP)."
            (nom, description, created_by, created_at, updated_at, actif)
            VALUES (?, ?, ?, ?, ?, 1)",
            "sssii",
            array($values['nom'], $values['description'], $createdBy, $now, $now)
        );

        if ($insert === false || $insert < 0) {
            return 0;
        }

        return (int) grr_sql_insert_id();
    }

    public static function group($id)
    {
        self::ensureTables();

        $id = (int) $id;
        if ($id <= 0) {
            return array();
        }

        $result = grr_sql_query(
            "SELECT id, nom, description, created_by, created_at, updated_at, actif
            FROM ".self::table(self::TABLE_GROUP)."
            WHERE id = ? AND actif = 1",
            "i",
            array($id)
        );
        if (!$result) {
            return array();
        }

        $row = grr_sql_row_keyed($result, 0);
        return $row ? $row : array();
    }

    public static function groups($limit = 200)
    {
        self::ensureTables();

        $limit = self::normalizeLimit($limit, 200, 1000);

        return self::rows(
            "SELECT g.id, g.nom, g.description, g.created_by, g.created_at, g.updated_at,
                COUNT(i.id) AS item_count
            FROM ".self::table(self::TABLE_GROUP)." g
            LEFT JOIN ".self::table(self::TABLE_ITEM)." i ON i.groupe_id = g.id AND i.actif = 1 AND i.statut <> 'archive'
            WHERE g.actif = 1
            GROUP BY g.id, g.nom, g.description, g.created_by, g.created_at, g.updated_at
            ORDER BY g.nom, g.id
            LIMIT ".$limit
        );
    }

    public static function groupsForUser($login, $canViewAll, $limit = 200)
    {
        if ($canViewAll) {
            return self::groups($limit);
        }

        self::ensureTables();

        $login = self::limit($login, 190);
        if ($login === '') {
            return array();
        }

        $limit = self::normalizeLimit($limit, 200, 1000);

        return self::rows(
            "SELECT g.id, g.nom, g.description, g.created_by, g.created_at, g.updated_at,
                COUNT(DISTINCT i.id) AS item_count
            FROM ".self::table(self::TABLE_GROUP)." g
            JOIN ".self::table(self::TABLE_ITEM)." i ON i.groupe_id = g.id AND i.actif = 1 AND i.statut <> 'archive'
            JOIN ".self::table(self::TABLE_USER)." gu ON gu.item_id = i.id
            WHERE g.actif = 1 AND gu.login = ?
            GROUP BY g.id, g.nom, g.description, g.created_by, g.created_at, g.updated_at
            ORDER BY g.nom, g.id
            LIMIT ".$limit,
            "s",
            array($login)
        );
    }

    public static function groupOptions()
    {
        $options = array('0' => 'Sans groupe');
        foreach (self::groups(500) as $group) {
            $id = isset($group['id']) ? (int) $group['id'] : 0;
            if ($id > 0) {
                $options[(string) $id] = isset($group['nom']) ? (string) $group['nom'] : '';
            }
        }

        return $options;
    }

    public static function itemsForGroup($groupId, $login, $canViewAll, $limit = 500)
    {
        self::ensureTables();

        $groupId = (int) $groupId;
        if ($groupId <= 0) {
            return array();
        }

        $limit = self::normalizeLimit($limit, 500, 1000);
        if ($canViewAll) {
            return self::rows(
                "SELECT i.id, i.reference, i.nom, i.categorie, i.fabricant, i.modele, i.numero_serie, i.numero_inventaire, i.statut, i.localisation,
                    i.groupe_id, g.nom AS groupe_nom, i.date_acquisition, i.date_fin_garantie, i.maintenance_interval_jours, i.maintenance_prochaine,
                    i.etalonnage_interval_jours, i.etalonnage_prochain, i.created_by, i.created_at, i.updated_at
                FROM ".self::table(self::TABLE_ITEM)." i
                LEFT JOIN ".self::table(self::TABLE_GROUP)." g ON g.id = i.groupe_id AND g.actif = 1
                WHERE i.actif = 1 AND i.statut <> 'archive' AND i.groupe_id = ?
                ORDER BY i.nom, i.reference, i.id
                LIMIT ".$limit,
                "i",
                array($groupId)
            );
        }

        $login = self::limit($login, 190);
        if ($login === '') {
            return array();
        }

        return self::rows(
            "SELECT i.id, i.reference, i.nom, i.categorie, i.fabricant, i.modele, i.numero_serie, i.numero_inventaire, i.statut, i.localisation,
                i.groupe_id, g.nom AS groupe_nom, i.date_acquisition, i.date_fin_garantie, i.maintenance_interval_jours, i.maintenance_prochaine,
                i.etalonnage_interval_jours, i.etalonnage_prochain, i.created_by, i.created_at, i.updated_at
            FROM ".self::table(self::TABLE_ITEM)." i
            JOIN ".self::table(self::TABLE_USER)." gu ON gu.item_id = i.id
            LEFT JOIN ".self::table(self::TABLE_GROUP)." g ON g.id = i.groupe_id AND g.actif = 1
            WHERE i.actif = 1 AND i.statut <> 'archive' AND i.groupe_id = ? AND gu.login = ?
            ORDER BY i.nom, i.reference, i.id
            LIMIT ".$limit,
            "is",
            array($groupId, $login)
        );
    }

    public static function activeItemsForGroupSelection($limit = 1000)
    {
        self::ensureTables();

        $limit = self::normalizeLimit($limit, 1000, 2000);

        return self::rows(
            "SELECT i.id, i.reference, i.nom, i.statut, i.groupe_id, g.nom AS groupe_nom
            FROM ".self::table(self::TABLE_ITEM)." i
            LEFT JOIN ".self::table(self::TABLE_GROUP)." g ON g.id = i.groupe_id AND g.actif = 1
            WHERE i.actif = 1 AND i.statut <> 'archive'
            ORDER BY i.nom, i.reference, i.id
            LIMIT ".$limit
        );
    }

    public static function setGroupItems($groupId, $itemIds)
    {
        self::ensureTables();

        $groupId = (int) $groupId;
        if ($groupId <= 0 || !self::group($groupId)) {
            return false;
        }

        $ids = self::normalizeIds($itemIds);
        if (!self::groupItemsAreAssignable($groupId, $ids)) {
            return false;
        }

        $reset = grr_sql_command(
            "UPDATE ".self::table(self::TABLE_ITEM)." SET groupe_id = 0, updated_at = ? WHERE groupe_id = ? AND actif = 1 AND statut <> 'archive'",
            "ii",
            array(time(), $groupId)
        );
        if ($reset === false || $reset < 0) {
            return false;
        }

        if (count($ids) === 0) {
            return true;
        }

        $update = grr_sql_command(
            "UPDATE ".self::table(self::TABLE_ITEM)." SET groupe_id = ?, updated_at = ? WHERE actif = 1 AND statut <> 'archive' AND groupe_id = 0 AND id IN (".implode(',', $ids).")",
            "ii",
            array($groupId, time())
        );

        return !($update === false || $update < 0);
    }

    public static function groupAlerts($groupId, $login, $canViewAll, $days = 30)
    {
        $alerts = array();
        $items = self::itemsForGroup($groupId, $login, $canViewAll, 1000);
        $window = self::deadlineWindow($days);
        $today = $window['today'];

        foreach ($items as $item) {
            $status = isset($item['statut']) ? (string) $item['statut'] : '';
            if ($status === 'archive') {
                continue;
            }

            if (in_array($status, array('maintenance', 'panne', 'hors_service'), true)) {
                $alerts[] = self::groupStatusAlert($item, $status);
            }

            foreach (array('maintenance' => 'maintenance_prochaine', 'etalonnage' => 'etalonnage_prochain') as $type => $field) {
                $echeance = isset($item[$field]) ? (int) $item[$field] : 0;
                if ($echeance <= 0 || $echeance > $window['until']) {
                    continue;
                }

                $delta = (int) floor(($echeance - $today) / 86400);
                $alertStatus = 'upcoming';
                if ($echeance < $today) {
                    $alertStatus = self::deadlineAlertStatus(array(
                        'type_echeance' => $type,
                        'statut' => $status,
                    ));
                } elseif ($echeance === $today) {
                    $alertStatus = 'today';
                }

                $alerts[] = array(
                    'alert_type' => $type,
                    'item_id' => isset($item['id']) ? (int) $item['id'] : 0,
                    'reference' => isset($item['reference']) ? (string) $item['reference'] : '',
                    'item_nom' => isset($item['nom']) ? (string) $item['nom'] : '',
                    'statut' => $status,
                    'localisation' => isset($item['localisation']) ? (string) $item['localisation'] : '',
                    'echeance' => $echeance,
                    'alert_status' => $alertStatus,
                    'days_delta' => $delta,
                    'detail' => $type === 'maintenance' ? 'Maintenance programmee' : 'Etalonnage programme',
                );
            }
        }

        usort($alerts, function ($a, $b) {
            return self::sortGroupAlerts($a, $b);
        });

        return $alerts;
    }

    public static function groupAlertCount($groupId, $login, $canViewAll, $days = 30)
    {
        return count(self::groupAlerts($groupId, $login, $canViewAll, $days));
    }

    public static function emptyItemValues()
    {
        return array(
            'reference' => '',
            'nom' => '',
            'categorie' => '',
            'fabricant' => '',
            'modele' => '',
            'numero_serie' => '',
            'numero_inventaire' => '',
            'statut' => 'en_service',
            'localisation' => '',
            'groupe_id' => '0',
            'nouveau_groupe' => '',
            'date_acquisition' => '',
            'date_fin_garantie' => '',
            'maintenance_interval_jours' => '',
            'maintenance_prochaine' => '',
            'etalonnage_interval_jours' => '',
            'etalonnage_prochain' => '',
            'description' => '',
        );
    }

    public static function emptyActionValues()
    {
        return array(
            'type_action' => 'maintenance',
            'date_action' => date('Y-m-d'),
            'commentaire' => '',
            'cout' => '',
            'prochaine_maintenance' => '',
            'prochain_etalonnage' => '',
        );
    }

    public static function normalizeItemValues($source)
    {
        if (!is_array($source)) {
            $source = array();
        }

        $values = self::emptyItemValues();
        foreach ($values as $key => $value) {
            if (isset($source[$key])) {
                $values[$key] = trim((string) $source[$key]);
            }
        }

        $values['reference'] = self::limit($values['reference'], 100);
        $values['nom'] = self::limit($values['nom'], 190);
        $values['categorie'] = self::limit($values['categorie'], 100);
        $values['fabricant'] = self::limit($values['fabricant'], 100);
        $values['modele'] = self::limit($values['modele'], 100);
        $values['numero_serie'] = self::limit($values['numero_serie'], 100);
        $values['numero_inventaire'] = self::limit($values['numero_inventaire'], 100);
        $values['localisation'] = self::limit($values['localisation'], 190);
        $values['groupe_id'] = (string) max(0, (int) $values['groupe_id']);
        $values['nouveau_groupe'] = self::limit($values['nouveau_groupe'], 190);

        $statuses = self::itemStatuses();
        if (!isset($statuses[$values['statut']])) {
            $values['statut'] = 'en_service';
        }

        $values['maintenance_interval_jours'] = self::normalizePositiveInt($values['maintenance_interval_jours']);
        $values['etalonnage_interval_jours'] = self::normalizePositiveInt($values['etalonnage_interval_jours']);

        return $values;
    }

    public static function validateItemValues($values)
    {
        $errors = array();

        if (!is_array($values)) {
            $errors[] = 'Donnees invalides.';
            return $errors;
        }

        if (trim((string) $values['nom']) === '') {
            $errors[] = 'Le nom du materiel est obligatoire.';
        }

        if (trim((string) $values['nouveau_groupe']) === '' && (int) $values['groupe_id'] > 0 && !self::group((int) $values['groupe_id'])) {
            $errors[] = 'Le groupe selectionne est introuvable.';
        }

        foreach (array('date_acquisition', 'date_fin_garantie', 'maintenance_prochaine', 'etalonnage_prochain') as $dateField) {
            if (trim((string) $values[$dateField]) !== '' && self::dateToTimestamp($values[$dateField]) === 0) {
                $errors[] = 'La date '.$dateField.' est invalide.';
            }
        }

        return $errors;
    }

    public static function normalizeActionValues($source)
    {
        if (!is_array($source)) {
            $source = array();
        }

        $values = self::emptyActionValues();
        foreach ($values as $key => $value) {
            if (isset($source[$key])) {
                $values[$key] = trim((string) $source[$key]);
            }
        }

        $types = self::actionTypes();
        if (!isset($types[$values['type_action']])) {
            $values['type_action'] = 'autre';
        }

        $values['cout'] = self::normalizeCost($values['cout']);

        return $values;
    }

    public static function validateActionValues($itemId, $values)
    {
        $errors = array();
        $item = self::item($itemId);

        if ((int) $itemId <= 0 || !$item) {
            $errors[] = 'Le materiel est introuvable.';
        } elseif (!self::itemCanBeChanged($item)) {
            $errors[] = 'Un materiel archive ne peut pas recevoir de nouvelle action.';
        } elseif (is_array($values) && !self::actionStatusTransitionIsAllowed($item, isset($values['type_action']) ? $values['type_action'] : '')) {
            $errors[] = 'Cette action ne correspond pas au statut actuel du materiel.';
        }

        if (!is_array($values)) {
            $errors[] = 'Donnees invalides.';
            return $errors;
        }

        if (self::dateToTimestamp($values['date_action']) === 0) {
            $errors[] = 'La date de l action est obligatoire.';
        }

        foreach (array('prochaine_maintenance', 'prochain_etalonnage') as $dateField) {
            if (trim((string) $values[$dateField]) !== '' && self::dateToTimestamp($values[$dateField]) === 0) {
                $errors[] = 'La date '.$dateField.' est invalide.';
            }
        }

        return $errors;
    }

    public static function createItem($values, $createdBy)
    {
        self::ensureTables();

        $values = self::normalizeItemValues($values);
        if (count(self::validateItemValues($values)) > 0) {
            return false;
        }

        $now = time();
        $createdBy = self::limit(trim((string) $createdBy), 190);
        $groupId = self::resolveItemGroupId($values, $createdBy);
        if ($groupId < 0) {
            return false;
        }
        $dateAcquisition = self::dateToTimestamp($values['date_acquisition']);
        $maintenanceProchaine = self::dateToTimestamp($values['maintenance_prochaine']);
        $etalonnageProchain = self::dateToTimestamp($values['etalonnage_prochain']);

        if ($maintenanceProchaine <= 0 && (int) $values['maintenance_interval_jours'] > 0) {
            $maintenanceProchaine = self::calculateNextDeadline($dateAcquisition, (int) $values['maintenance_interval_jours'], $now);
        }

        if ($etalonnageProchain <= 0 && (int) $values['etalonnage_interval_jours'] > 0) {
            $etalonnageProchain = self::calculateNextDeadline($dateAcquisition, (int) $values['etalonnage_interval_jours'], $now);
        }

        $insert = grr_sql_command(
            "INSERT INTO ".self::table(self::TABLE_ITEM)."
            (reference, nom, categorie, fabricant, modele, numero_serie, numero_inventaire, statut, localisation, groupe_id,
                date_acquisition, date_fin_garantie, maintenance_interval_jours, maintenance_prochaine,
                etalonnage_interval_jours, etalonnage_prochain, description, created_by, created_at, updated_at, actif)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)",
            "sssssssssiiiiiiissii",
            array(
                $values['reference'],
                $values['nom'],
                $values['categorie'],
                $values['fabricant'],
                $values['modele'],
                $values['numero_serie'],
                $values['numero_inventaire'],
                $values['statut'],
                $values['localisation'],
                $groupId,
                $dateAcquisition,
                self::dateToTimestamp($values['date_fin_garantie']),
                (int) $values['maintenance_interval_jours'],
                $maintenanceProchaine,
                (int) $values['etalonnage_interval_jours'],
                $etalonnageProchain,
                $values['description'],
                $createdBy,
                $now,
                $now,
            )
        );

        return !($insert === false || $insert < 0);
    }

    public static function updateItem($id, $values, $updatedBy = '')
    {
        self::ensureTables();

        $id = (int) $id;
        $currentItem = self::item($id);
        if ($id <= 0 || !$currentItem || !self::itemCanBeChanged($currentItem)) {
            return false;
        }

        $values = self::normalizeItemValues($values);
        if (count(self::validateItemValues($values)) > 0) {
            return false;
        }
        $updatedBy = self::limit(trim((string) $updatedBy), 190);
        if (!self::itemGroupChangeIsAllowed($currentItem, $values)) {
            return false;
        }

        $groupId = self::resolveItemGroupId($values, $updatedBy);
        if ($groupId < 0) {
            return false;
        }

        $update = grr_sql_command(
            "UPDATE ".self::table(self::TABLE_ITEM)."
            SET reference = ?, nom = ?, categorie = ?, fabricant = ?, modele = ?, numero_serie = ?,
                numero_inventaire = ?, statut = ?, localisation = ?, groupe_id = ?, date_acquisition = ?,
                date_fin_garantie = ?, maintenance_interval_jours = ?, maintenance_prochaine = ?,
                etalonnage_interval_jours = ?, etalonnage_prochain = ?, description = ?, updated_at = ?
            WHERE id = ? AND actif = 1",
            "sssssssssiiiiiiisii",
            array(
                $values['reference'],
                $values['nom'],
                $values['categorie'],
                $values['fabricant'],
                $values['modele'],
                $values['numero_serie'],
                $values['numero_inventaire'],
                $values['statut'],
                $values['localisation'],
                $groupId,
                self::dateToTimestamp($values['date_acquisition']),
                self::dateToTimestamp($values['date_fin_garantie']),
                (int) $values['maintenance_interval_jours'],
                self::dateToTimestamp($values['maintenance_prochaine']),
                (int) $values['etalonnage_interval_jours'],
                self::dateToTimestamp($values['etalonnage_prochain']),
                $values['description'],
                time(),
                $id,
            )
        );

        return !($update === false || $update < 0);
    }

    public static function createAction($itemId, $values, $createdBy)
    {
        self::ensureTables();

        $itemId = (int) $itemId;
        $values = self::normalizeActionValues($values);
        if (count(self::validateActionValues($itemId, $values)) > 0) {
            return false;
        }

        $now = time();
        $createdBy = self::limit(trim((string) $createdBy), 190);
        $prochaineMaintenance = self::dateToTimestamp($values['prochaine_maintenance']);
        $prochainEtalonnage = self::dateToTimestamp($values['prochain_etalonnage']);

        $insert = grr_sql_command(
            "INSERT INTO ".self::table(self::TABLE_ACTION)."
            (item_id, type_action, date_action, commentaire, cout, prochaine_maintenance, prochain_etalonnage, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            "isisdiisi",
            array(
                $itemId,
                $values['type_action'],
                self::dateToTimestamp($values['date_action']),
                $values['commentaire'],
                (float) $values['cout'],
                $prochaineMaintenance,
                $prochainEtalonnage,
                $createdBy,
                $now,
            )
        );

        if ($insert === false || $insert < 0) {
            return false;
        }

        if (!self::updateItemNextDates($itemId, $prochaineMaintenance, $prochainEtalonnage)) {
            return false;
        }

        if (!self::updateItemStatusFromAction($itemId, $values['type_action'])) {
            return false;
        }

        return true;
    }

    public static function items($limit = 100)
    {
        self::ensureTables();

        $limit = (int) $limit;
        if ($limit <= 0 || $limit > 500) {
            $limit = 100;
        }

        return self::rows(
            "SELECT i.id, i.reference, i.nom, i.categorie, i.fabricant, i.modele, i.numero_serie, i.numero_inventaire, i.statut, i.localisation,
                i.groupe_id, g.nom AS groupe_nom, i.date_acquisition, i.date_fin_garantie, i.maintenance_interval_jours, i.maintenance_prochaine,
                i.etalonnage_interval_jours, i.etalonnage_prochain, i.created_by, i.created_at, i.updated_at
            FROM ".self::table(self::TABLE_ITEM)." i
            LEFT JOIN ".self::table(self::TABLE_GROUP)." g ON g.id = i.groupe_id AND g.actif = 1
            WHERE i.actif = 1 AND i.statut <> 'archive'
            ORDER BY i.nom, i.reference, i.id
            LIMIT ".$limit
        );
    }

    public static function itemsForUser($login, $canViewAll, $limit = 100)
    {
        if ($canViewAll) {
            return self::items($limit);
        }

        self::ensureTables();

        $login = self::limit($login, 190);
        $limit = (int) $limit;
        if ($login === '') {
            return array();
        }
        if ($limit <= 0 || $limit > 500) {
            $limit = 100;
        }

        return self::rows(
            "SELECT i.id, i.reference, i.nom, i.categorie, i.fabricant, i.modele, i.numero_serie, i.numero_inventaire, i.statut, i.localisation,
                i.groupe_id, g.nom AS groupe_nom, i.date_acquisition, i.date_fin_garantie, i.maintenance_interval_jours, i.maintenance_prochaine,
                i.etalonnage_interval_jours, i.etalonnage_prochain, i.created_by, i.created_at, i.updated_at
            FROM ".self::table(self::TABLE_ITEM)." i
            JOIN ".self::table(self::TABLE_USER)." gu ON gu.item_id = i.id
            LEFT JOIN ".self::table(self::TABLE_GROUP)." g ON g.id = i.groupe_id AND g.actif = 1
            WHERE i.actif = 1 AND i.statut <> 'archive' AND gu.login = ?
            ORDER BY i.nom, i.reference, i.id
            LIMIT ".$limit,
            "s",
            array($login)
        );
    }

    public static function archivedItems($limit = 500)
    {
        self::ensureTables();

        $limit = self::normalizeLimit($limit, 500, 1000);

        return self::rows(
            "SELECT i.id, i.reference, i.nom, i.categorie, i.fabricant, i.modele, i.numero_serie, i.numero_inventaire, i.statut, i.localisation,
                i.groupe_id, g.nom AS groupe_nom, i.date_acquisition, i.date_fin_garantie, i.maintenance_interval_jours, i.maintenance_prochaine,
                i.etalonnage_interval_jours, i.etalonnage_prochain, i.created_by, i.created_at, i.updated_at, i.actif
            FROM ".self::table(self::TABLE_ITEM)." i
            LEFT JOIN ".self::table(self::TABLE_GROUP)." g ON g.id = i.groupe_id AND g.actif = 1
            WHERE i.statut = 'archive'
            ORDER BY i.nom, i.reference, i.id
            LIMIT ".$limit
        );
    }

    public static function deleteItem($id)
    {
        self::ensureTables();

        $id = (int) $id;
        if ($id <= 0) {
            return false;
        }

        $exists = grr_sql_query1(
            "SELECT COUNT(*) FROM ".self::table(self::TABLE_ITEM)." WHERE id = ?",
            "i",
            array($id)
        );
        if ((int) $exists <= 0) {
            return false;
        }

        $documents = self::rows(
            "SELECT stored_name FROM ".self::table(self::TABLE_DOCUMENT)." WHERE item_id = ?",
            "i",
            array($id)
        );
        foreach ($documents as $document) {
            $path = self::documentPath(isset($document['stored_name']) ? $document['stored_name'] : '');
            if ($path !== '' && is_file($path) && !@unlink($path)) {
                return false;
            }
        }

        $delete = grr_sql_command(
            "DELETE i, gu, a, d, nl
            FROM ".self::table(self::TABLE_ITEM)." i
            LEFT JOIN ".self::table(self::TABLE_USER)." gu ON gu.item_id = i.id
            LEFT JOIN ".self::table(self::TABLE_ACTION)." a ON a.item_id = i.id
            LEFT JOIN ".self::table(self::TABLE_DOCUMENT)." d ON d.item_id = i.id
            LEFT JOIN ".self::table(self::TABLE_NOTIFICATION_LOG)." nl ON nl.item_id = i.id
            WHERE i.id = ?",
            "i",
            array($id)
        );

        return !($delete === false || $delete < 0);
    }

    public static function deleteAction($id)
    {
        self::ensureTables();

        $id = (int) $id;
        if ($id <= 0) {
            return false;
        }

        $exists = grr_sql_query1(
            "SELECT COUNT(*)
            FROM ".self::table(self::TABLE_ACTION)." a
            JOIN ".self::table(self::TABLE_ITEM)." i ON i.id = a.item_id
            WHERE a.id = ? AND i.actif = 1 AND i.statut <> 'archive'",
            "i",
            array($id)
        );
        if ((int) $exists <= 0) {
            return false;
        }

        $delete = grr_sql_command(
            "DELETE FROM ".self::table(self::TABLE_ACTION)." WHERE id = ?",
            "i",
            array($id)
        );

        return !($delete === false || $delete < 0);
    }

    public static function item($id)
    {
        self::ensureTables();

        $id = (int) $id;
        if ($id <= 0) {
            return array();
        }

        $result = grr_sql_query(
            "SELECT i.id, i.reference, i.nom, i.categorie, i.fabricant, i.modele, i.numero_serie, i.numero_inventaire, i.statut, i.localisation,
                i.groupe_id, g.nom AS groupe_nom, i.date_acquisition, i.date_fin_garantie, i.maintenance_interval_jours, i.maintenance_prochaine,
                i.etalonnage_interval_jours, i.etalonnage_prochain, i.description, i.created_by, i.created_at, i.updated_at, i.actif
            FROM ".self::table(self::TABLE_ITEM)." i
            LEFT JOIN ".self::table(self::TABLE_GROUP)." g ON g.id = i.groupe_id AND g.actif = 1
            WHERE i.id = ? AND (i.actif = 1 OR i.statut = 'archive')",
            "i",
            array($id)
        );
        if (!$result) {
            return array();
        }

        $row = grr_sql_row_keyed($result, 0);
        return $row ? $row : array();
    }

    public static function actionsForItem($itemId, $limit = 50)
    {
        self::ensureTables();

        $itemId = (int) $itemId;
        if ($itemId <= 0) {
            return array();
        }

        $limit = self::normalizeLimit($limit, 50, 500);

        return self::rows(
            "SELECT a.id, a.item_id, a.type_action, a.date_action, a.commentaire, a.cout,
                a.prochaine_maintenance, a.prochain_etalonnage, a.created_by, a.created_at,
                i.nom AS item_nom, i.reference AS item_reference
            FROM ".self::table(self::TABLE_ACTION)." a
            JOIN ".self::table(self::TABLE_ITEM)." i ON i.id = a.item_id
            WHERE a.item_id = ? AND (i.actif = 1 OR i.statut = 'archive')
            ORDER BY a.date_action DESC, a.id DESC
            LIMIT ".$limit,
            "i",
            array($itemId)
        );
    }

    public static function documentTypes()
    {
        return array(
            'mode_operatoire' => 'Mode operatoire',
            'certificat_etalonnage' => 'Certificat d etalonnage',
            'verification_periodique' => 'Verification periodique',
            'maintenance' => 'Maintenance',
            'notice_fabricant' => 'Notice fabricant',
            'autre' => 'Autre',
        );
    }

    public static function documentsForItem($itemId, $limit = 100)
    {
        self::ensureTables();

        $itemId = (int) $itemId;
        if ($itemId <= 0) {
            return array();
        }

        $limit = self::normalizeLimit($limit, 100, 500);

        return self::rows(
            "SELECT d.id, d.item_id, d.type_document, d.description, d.original_name, d.stored_name,
                d.mime_type, d.taille, d.uploaded_by, d.created_at
            FROM ".self::table(self::TABLE_DOCUMENT)." d
            JOIN ".self::table(self::TABLE_ITEM)." i ON i.id = d.item_id
            WHERE d.item_id = ? AND (i.actif = 1 OR i.statut = 'archive')
            ORDER BY d.created_at DESC, d.id DESC
            LIMIT ".$limit,
            "i",
            array($itemId)
        );
    }

    public static function document($documentId)
    {
        self::ensureTables();

        $documentId = (int) $documentId;
        if ($documentId <= 0) {
            return array();
        }

        $rows = self::rows(
            "SELECT d.id, d.item_id, d.type_document, d.description, d.original_name, d.stored_name,
                d.mime_type, d.taille, d.uploaded_by, d.created_at,
                i.statut AS item_statut, i.actif AS item_actif
            FROM ".self::table(self::TABLE_DOCUMENT)." d
            JOIN ".self::table(self::TABLE_ITEM)." i ON i.id = d.item_id
            WHERE d.id = ? AND (i.actif = 1 OR i.statut = 'archive')",
            "i",
            array($documentId)
        );

        return isset($rows[0]) ? $rows[0] : array();
    }

    public static function addDocument($itemId, $type, $description, $originalName, $storedName, $mimeType, $size, $uploadedBy)
    {
        self::ensureTables();

        $itemId = (int) $itemId;
        $item = self::item($itemId);
        $types = self::documentTypes();
        $type = self::limit($type, 50);
        $originalName = self::limit(trim((string) $originalName), 255);
        $storedName = trim((string) $storedName);
        $mimeType = self::limit(trim((string) $mimeType), 190);
        $uploadedBy = self::limit(trim((string) $uploadedBy), 190);
        $size = (int) $size;

        if (
            !$item
            || !self::itemCanBeChanged($item)
            || !isset($types[$type])
            || $originalName === ''
            || !preg_match('/^[a-f0-9]{40}$/', $storedName)
            || $size <= 0
        ) {
            return false;
        }
        if ($mimeType === '') {
            $mimeType = 'application/octet-stream';
        }

        $insert = grr_sql_command(
            "INSERT INTO ".self::table(self::TABLE_DOCUMENT)."
                (item_id, type_document, description, original_name, stored_name, mime_type, taille, uploaded_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            "isssssisi",
            array(
                $itemId,
                $type,
                trim((string) $description),
                $originalName,
                $storedName,
                $mimeType,
                $size,
                $uploadedBy,
                time(),
            )
        );

        return !($insert === false || $insert < 0);
    }

    public static function deleteDocument($documentId)
    {
        self::ensureTables();

        $document = self::document($documentId);
        if (
            !$document
            || (int) $document['item_actif'] !== 1
            || (string) $document['item_statut'] === 'archive'
        ) {
            return false;
        }

        $path = self::documentPath(isset($document['stored_name']) ? $document['stored_name'] : '');
        if ($path !== '' && is_file($path) && !@unlink($path)) {
            return false;
        }

        $delete = grr_sql_command(
            "DELETE FROM ".self::table(self::TABLE_DOCUMENT)." WHERE id = ?",
            "i",
            array((int) $document['id'])
        );

        return !($delete === false || $delete < 0);
    }

    public static function documentStorageDir()
    {
        return dirname(__DIR__).'/storage/documents';
    }

    public static function ensureDocumentStorage()
    {
        $directory = self::documentStorageDir();
        if (is_dir($directory)) {
            return is_writable($directory);
        }

        return @mkdir($directory, 0750, true);
    }

    public static function documentPath($storedName)
    {
        $storedName = trim((string) $storedName);
        if (!preg_match('/^[a-f0-9]{40}$/', $storedName)) {
            return '';
        }

        return self::documentStorageDir().'/'.$storedName;
    }

    public static function recentActions($limit = 100)
    {
        self::ensureTables();

        $limit = self::normalizeLimit($limit, 100, 500);

        return self::rows(
            "SELECT a.id, a.item_id, a.type_action, a.date_action, a.commentaire, a.cout,
                a.prochaine_maintenance, a.prochain_etalonnage, a.created_by, a.created_at,
                i.nom AS item_nom, i.reference AS item_reference
            FROM ".self::table(self::TABLE_ACTION)." a
            JOIN ".self::table(self::TABLE_ITEM)." i ON i.id = a.item_id
            WHERE i.actif = 1 AND i.statut <> 'archive'
            ORDER BY a.date_action DESC, a.id DESC
            LIMIT ".$limit
        );
    }

    public static function recentActionsForUser($login, $canViewAll, $limit = 100)
    {
        if ($canViewAll) {
            return self::recentActions($limit);
        }

        self::ensureTables();

        $login = self::limit($login, 190);
        if ($login === '') {
            return array();
        }

        $limit = self::normalizeLimit($limit, 100, 500);

        return self::rows(
            "SELECT a.id, a.item_id, a.type_action, a.date_action, a.commentaire, a.cout,
                a.prochaine_maintenance, a.prochain_etalonnage, a.created_by, a.created_at,
                i.nom AS item_nom, i.reference AS item_reference
            FROM ".self::table(self::TABLE_ACTION)." a
            JOIN ".self::table(self::TABLE_ITEM)." i ON i.id = a.item_id
            JOIN ".self::table(self::TABLE_USER)." gu ON gu.item_id = i.id
            WHERE i.actif = 1 AND i.statut <> 'archive' AND gu.login = ?
            ORDER BY a.date_action DESC, a.id DESC
            LIMIT ".$limit,
            "s",
            array($login)
        );
    }

    public static function activeUsers()
    {
        $users = self::rows(
            "SELECT login, nom, prenom, email, statut
            FROM ".TABLE_PREFIX."_utilisateurs
            WHERE etat != 'inactif'
            ORDER BY nom, prenom, login"
        );

        foreach ($users as $index => $user) {
            $users[$index]['label'] = self::userLabel($user);
        }

        return $users;
    }

    public static function assignedUsers($itemId)
    {
        self::ensureTables();

        $itemId = (int) $itemId;
        if ($itemId <= 0) {
            return array();
        }

        $users = self::rows(
            "SELECT gu.login, gu.notify_maintenance, gu.notify_etalonnage, gu.created_by, gu.created_at,
                u.nom, u.prenom, u.email
            FROM ".self::table(self::TABLE_USER)." gu
            LEFT JOIN ".TABLE_PREFIX."_utilisateurs u ON u.login = gu.login
            WHERE gu.item_id = ?
            ORDER BY u.nom, u.prenom, gu.login",
            "i",
            array($itemId)
        );

        foreach ($users as $index => $user) {
            $users[$index]['label'] = self::userLabel($user);
        }

        return $users;
    }

    public static function setAssignedUsers($itemId, $logins, $notifyMaintenanceLogins, $notifyEtalonnageLogins, $createdBy)
    {
        self::ensureTables();

        $itemId = (int) $itemId;
        $item = self::item($itemId);
        if ($itemId <= 0 || !$item || !self::itemCanBeChanged($item)) {
            return false;
        }

        $validLogins = self::validUserLogins($logins);
        $notifyMaintenance = array_flip(self::normalizeLogins($notifyMaintenanceLogins));
        $notifyEtalonnage = array_flip(self::normalizeLogins($notifyEtalonnageLogins));

        $delete = grr_sql_command(
            "DELETE FROM ".self::table(self::TABLE_USER)." WHERE item_id = ?",
            "i",
            array($itemId)
        );
        if ($delete === false || $delete < 0) {
            return false;
        }

        $createdBy = self::limit(trim((string) $createdBy), 190);
        $now = time();
        foreach ($validLogins as $login) {
            $insert = grr_sql_command(
                "INSERT INTO ".self::table(self::TABLE_USER)."
                (item_id, login, notify_maintenance, notify_etalonnage, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?)",
                "isiisi",
                array(
                    $itemId,
                    $login,
                    isset($notifyMaintenance[$login]) ? 1 : 0,
                    isset($notifyEtalonnage[$login]) ? 1 : 0,
                    $createdBy,
                    $now,
                )
            );

            if ($insert === false || $insert < 0) {
                return false;
            }
        }

        return true;
    }

    public static function validUserLogins($logins)
    {
        $valid = array();
        foreach (self::normalizeLogins($logins) as $login) {
            $count = grr_sql_query1(
                "SELECT COUNT(*) FROM ".TABLE_PREFIX."_utilisateurs WHERE login = ? AND etat != 'inactif'",
                "s",
                array($login)
            );

            if ((int) $count > 0) {
                $valid[$login] = $login;
            }
        }

        return array_values($valid);
    }

    public static function normalizeLogins($logins)
    {
        if (!is_array($logins)) {
            $logins = array($logins);
        }

        $normalized = array();
        foreach ($logins as $login) {
            $login = self::limit($login, 190);
            if ($login !== '') {
                $normalized[$login] = $login;
            }
        }

        return array_values($normalized);
    }

    private static function normalizeIds($ids)
    {
        if (!is_array($ids)) {
            $ids = array($ids);
        }

        $normalized = array();
        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $normalized[$id] = $id;
            }
        }

        return array_values($normalized);
    }

    private static function groupItemsAreAssignable($groupId, $ids)
    {
        $groupId = (int) $groupId;
        $ids = self::normalizeIds($ids);
        if (count($ids) === 0) {
            return true;
        }

        $count = grr_sql_query1(
            "SELECT COUNT(*)
            FROM ".self::table(self::TABLE_ITEM)."
            WHERE actif = 1
                AND statut <> 'archive'
                AND id IN (".implode(',', $ids).")
                AND (groupe_id = 0 OR groupe_id = ?)",
            "i",
            array($groupId)
        );

        return (int) $count === count($ids);
    }

    private static function itemGroupChangeIsAllowed($currentItem, $values)
    {
        $currentGroupId = isset($currentItem['groupe_id']) ? (int) $currentItem['groupe_id'] : 0;
        if ($currentGroupId <= 0) {
            return true;
        }

        if (isset($values['nouveau_groupe']) && trim((string) $values['nouveau_groupe']) !== '') {
            return false;
        }

        $targetGroupId = isset($values['groupe_id']) ? (int) $values['groupe_id'] : 0;
        return $targetGroupId === 0 || $targetGroupId === $currentGroupId;
    }

    private static function itemCanBeChanged($item)
    {
        return is_array($item)
            && isset($item['actif'])
            && (int) $item['actif'] === 1
            && isset($item['statut'])
            && (string) $item['statut'] !== 'archive';
    }

    private static function resolveItemGroupId($values, $createdBy)
    {
        $newGroupName = isset($values['nouveau_groupe']) ? trim((string) $values['nouveau_groupe']) : '';
        if ($newGroupName !== '') {
            $groupId = self::createGroup(
                array('nom' => $newGroupName, 'description' => ''),
                $createdBy
            );

            return $groupId > 0 ? $groupId : -1;
        }

        return isset($values['groupe_id']) ? max(0, (int) $values['groupe_id']) : 0;
    }

    private static function calculateNextDeadline($initialTimestamp, $intervalDays, $fallbackTimestamp)
    {
        $intervalDays = (int) $intervalDays;
        if ($intervalDays <= 0) {
            return 0;
        }

        $base = (int) $initialTimestamp;
        if ($base <= 0) {
            $fallbackTimestamp = (int) $fallbackTimestamp;
            $base = strtotime(date('Y-m-d 00:00:00', $fallbackTimestamp));
            if ($base === false) {
                $base = self::dayStart();
            }
        }

        return $base + ($intervalDays * 86400);
    }

    private static function groupStatusAlert($item, $status)
    {
        $labels = self::itemStatuses();
        $label = isset($labels[$status]) ? $labels[$status] : $status;

        return array(
            'alert_type' => 'statut',
            'item_id' => isset($item['id']) ? (int) $item['id'] : 0,
            'reference' => isset($item['reference']) ? (string) $item['reference'] : '',
            'item_nom' => isset($item['nom']) ? (string) $item['nom'] : '',
            'statut' => $status,
            'localisation' => isset($item['localisation']) ? (string) $item['localisation'] : '',
            'echeance' => 0,
            'alert_status' => $status === 'hors_service' ? 'overdue' : 'today',
            'days_delta' => 0,
            'detail' => 'Statut : '.$label,
        );
    }

    private static function sortGroupAlerts($a, $b)
    {
        $priority = array(
            'overdue' => 0,
            'attention' => 1,
            'today' => 2,
            'upcoming' => 3,
        );

        $statusA = isset($a['alert_status']) && isset($priority[$a['alert_status']]) ? $priority[$a['alert_status']] : 9;
        $statusB = isset($b['alert_status']) && isset($priority[$b['alert_status']]) ? $priority[$b['alert_status']] : 9;
        if ($statusA !== $statusB) {
            return $statusA < $statusB ? -1 : 1;
        }

        $dateA = isset($a['echeance']) ? (int) $a['echeance'] : 0;
        $dateB = isset($b['echeance']) ? (int) $b['echeance'] : 0;
        if ($dateA !== $dateB) {
            if ($dateA === 0) {
                return -1;
            }
            if ($dateB === 0) {
                return 1;
            }
            return $dateA < $dateB ? -1 : 1;
        }

        $itemA = isset($a['item_nom']) ? (string) $a['item_nom'] : '';
        $itemB = isset($b['item_nom']) ? (string) $b['item_nom'] : '';
        if ($itemA !== $itemB) {
            return strcmp($itemA, $itemB);
        }

        return strcmp(isset($a['alert_type']) ? (string) $a['alert_type'] : '', isset($b['alert_type']) ? (string) $b['alert_type'] : '');
    }

    public static function upcomingNotifications($days = 30, $includeAlreadySent = true)
    {
        self::ensureTables();

        $days = (int) $days;
        if ($days <= 0 || $days > 365) {
            $days = 30;
        }

        $rows = array_merge(
            self::notificationRows('maintenance', 'maintenance_prochaine', 'notify_maintenance', $days),
            self::notificationRows('etalonnage', 'etalonnage_prochain', 'notify_etalonnage', $days)
        );

        foreach ($rows as $index => $row) {
            $alreadySent = self::notificationAlreadySent(
                isset($row['item_id']) ? (int) $row['item_id'] : 0,
                isset($row['login']) ? (string) $row['login'] : '',
                isset($row['type_notification']) ? (string) $row['type_notification'] : '',
                isset($row['echeance']) ? (int) $row['echeance'] : 0
            );

            $rows[$index]['already_sent'] = $alreadySent ? 1 : 0;
            $rows[$index]['user_label'] = self::userLabel($row);
        }

        if (!$includeAlreadySent) {
            $filteredRows = array();
            foreach ($rows as $row) {
                if ((int) $row['already_sent'] !== 1) {
                    $filteredRows[] = $row;
                }
            }
            $rows = $filteredRows;
        }

        usort($rows, function ($a, $b) {
            return self::sortNotifications($a, $b);
        });

        return $rows;
    }

    public static function notificationAlreadySent($itemId, $login, $type, $echeance)
    {
        $itemId = (int) $itemId;
        $login = self::limit($login, 190);
        $type = self::limit($type, 50);
        $echeance = (int) $echeance;
        if ($itemId <= 0 || $login === '' || $type === '' || $echeance <= 0) {
            return false;
        }

        $count = grr_sql_query1(
            "SELECT COUNT(*) FROM ".self::table(self::TABLE_NOTIFICATION_LOG)."
            WHERE item_id = ? AND login = ? AND type_notification = ? AND echeance = ? AND status = 'sent'",
            "issi",
            array($itemId, $login, $type, $echeance)
        );

        return (int) $count > 0;
    }

    public static function logNotification($itemId, $login, $type, $echeance, $status, $message)
    {
        self::ensureTables();

        $itemId = (int) $itemId;
        $login = self::limit($login, 190);
        $type = self::limit($type, 50);
        $echeance = (int) $echeance;
        $status = self::limit($status, 20);

        $insert = grr_sql_command(
            "INSERT INTO ".self::table(self::TABLE_NOTIFICATION_LOG)."
            (item_id, login, type_notification, echeance, sent_at, status, message)
            VALUES (?, ?, ?, ?, ?, ?, ?)",
            "issiiss",
            array($itemId, $login, $type, $echeance, time(), $status, trim((string) $message))
        );

        return !($insert === false || $insert < 0);
    }

    public static function recentNotificationLogs($limit = 100)
    {
        self::ensureTables();

        $limit = self::normalizeLimit($limit, 100, 500);

        return self::rows(
            "SELECT l.id, l.item_id, l.login, l.type_notification, l.echeance, l.sent_at, l.status, l.message,
                i.nom AS item_nom, i.reference AS item_reference,
                u.nom, u.prenom, u.email
            FROM ".self::table(self::TABLE_NOTIFICATION_LOG)." l
            LEFT JOIN ".self::table(self::TABLE_ITEM)." i ON i.id = l.item_id
            LEFT JOIN ".TABLE_PREFIX."_utilisateurs u ON u.login = l.login
            ORDER BY l.sent_at DESC, l.id DESC
            LIMIT ".$limit
        );
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
            "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?",
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

    private static function notificationRows($type, $dateField, $notifyField, $days)
    {
        $allowed = array(
            'maintenance' => array('maintenance_prochaine', 'notify_maintenance'),
            'etalonnage' => array('etalonnage_prochain', 'notify_etalonnage'),
        );

        if (!isset($allowed[$type]) || $allowed[$type][0] !== $dateField || $allowed[$type][1] !== $notifyField) {
            return array();
        }

        $until = time() + ((int) $days * 86400);

        return self::rows(
            "SELECT i.id AS item_id, i.reference, i.nom AS item_nom, i.".$dateField." AS echeance,
                gu.login, gu.".$notifyField." AS notify_enabled,
                u.nom, u.prenom, u.email,
                '".$type."' AS type_notification
            FROM ".self::table(self::TABLE_ITEM)." i
            JOIN ".self::table(self::TABLE_USER)." gu ON gu.item_id = i.id
            JOIN ".TABLE_PREFIX."_utilisateurs u ON u.login = gu.login
            WHERE i.actif = 1
                AND i.statut NOT IN ('archive', 'hors_service', 'panne')
                AND i.".$dateField." > 0
                AND i.".$dateField." <= ?
                AND gu.".$notifyField." = 1
                AND u.etat = 'actif'
                AND u.desactive_mail = 0
            ORDER BY i.".$dateField.", i.nom, u.nom, u.prenom, gu.login",
            "i",
            array($until)
        );
    }

    private static function deadlineRows($type, $dateField, $days)
    {
        $allowed = array(
            'maintenance' => 'maintenance_prochaine',
            'etalonnage' => 'etalonnage_prochain',
        );

        if (!isset($allowed[$type]) || $allowed[$type] !== $dateField) {
            return array();
        }

        $window = self::deadlineWindow($days);

        return self::rows(
            "SELECT id AS item_id, reference, nom AS item_nom, statut, localisation, ".$dateField." AS echeance,
                '".$type."' AS type_echeance
            FROM ".self::table(self::TABLE_ITEM)."
            WHERE actif = 1
                AND statut NOT IN ('archive', 'hors_service', 'panne')
                AND ".$dateField." > 0
                AND ".$dateField." <= ?
            ORDER BY ".$dateField.", nom, reference, id",
            "i",
            array($window['until'])
        );
    }

    private static function deadlineRowsForUser($type, $dateField, $login, $days)
    {
        $allowed = array(
            'maintenance' => 'maintenance_prochaine',
            'etalonnage' => 'etalonnage_prochain',
        );

        if (!isset($allowed[$type]) || $allowed[$type] !== $dateField) {
            return array();
        }

        $login = self::limit($login, 190);
        if ($login === '') {
            return array();
        }

        $window = self::deadlineWindow($days);

        return self::rows(
            "SELECT i.id AS item_id, i.reference, i.nom AS item_nom, i.statut, i.localisation, i.".$dateField." AS echeance,
                '".$type."' AS type_echeance
            FROM ".self::table(self::TABLE_ITEM)." i
            JOIN ".self::table(self::TABLE_USER)." gu ON gu.item_id = i.id
            WHERE i.actif = 1
                AND i.statut NOT IN ('archive', 'hors_service', 'panne')
                AND i.".$dateField." > 0
                AND i.".$dateField." <= ?
                AND gu.login = ?
            ORDER BY i.".$dateField.", i.nom, i.reference, i.id",
            "is",
            array($window['until'], $login)
        );
    }

    private static function sortNotifications($a, $b)
    {
        $dateA = isset($a['echeance']) ? (int) $a['echeance'] : 0;
        $dateB = isset($b['echeance']) ? (int) $b['echeance'] : 0;
        if ($dateA !== $dateB) {
            return $dateA < $dateB ? -1 : 1;
        }

        $itemA = isset($a['item_nom']) ? (string) $a['item_nom'] : '';
        $itemB = isset($b['item_nom']) ? (string) $b['item_nom'] : '';
        if ($itemA !== $itemB) {
            return strcmp($itemA, $itemB);
        }

        return strcmp(isset($a['login']) ? (string) $a['login'] : '', isset($b['login']) ? (string) $b['login'] : '');
    }

    private static function sortDeadlineAlerts($a, $b)
    {
        $priority = array(
            'overdue' => 0,
            'attention' => 1,
            'today' => 2,
            'upcoming' => 3,
        );

        $statusA = isset($a['alert_status']) && isset($priority[$a['alert_status']]) ? $priority[$a['alert_status']] : 9;
        $statusB = isset($b['alert_status']) && isset($priority[$b['alert_status']]) ? $priority[$b['alert_status']] : 9;
        if ($statusA !== $statusB) {
            return $statusA < $statusB ? -1 : 1;
        }

        $dateA = isset($a['echeance']) ? (int) $a['echeance'] : 0;
        $dateB = isset($b['echeance']) ? (int) $b['echeance'] : 0;
        if ($dateA !== $dateB) {
            return $dateA < $dateB ? -1 : 1;
        }

        $itemA = isset($a['item_nom']) ? (string) $a['item_nom'] : '';
        $itemB = isset($b['item_nom']) ? (string) $b['item_nom'] : '';
        if ($itemA !== $itemB) {
            return strcmp($itemA, $itemB);
        }

        return strcmp(isset($a['type_echeance']) ? (string) $a['type_echeance'] : '', isset($b['type_echeance']) ? (string) $b['type_echeance'] : '');
    }

    private static function updateItemNextDates($itemId, $prochaineMaintenance, $prochainEtalonnage)
    {
        $sets = array();
        $types = '';
        $params = array();

        if ((int) $prochaineMaintenance > 0) {
            $sets[] = 'maintenance_prochaine = ?';
            $types .= 'i';
            $params[] = (int) $prochaineMaintenance;
        }

        if ((int) $prochainEtalonnage > 0) {
            $sets[] = 'etalonnage_prochain = ?';
            $types .= 'i';
            $params[] = (int) $prochainEtalonnage;
        }

        if (count($sets) === 0) {
            return true;
        }

        $sets[] = 'updated_at = ?';
        $types .= 'ii';
        $params[] = time();
        $params[] = (int) $itemId;

        $update = grr_sql_command(
            "UPDATE ".self::table(self::TABLE_ITEM)." SET ".implode(', ', $sets)." WHERE id = ? AND actif = 1",
            $types,
            $params
        );

        return !($update === false || $update < 0);
    }

    private static function updateItemStatusFromAction($itemId, $typeAction)
    {
        $statuses = array(
            'panne' => 'panne',
            'reparation' => 'en_service',
            'fin_projet' => 'sans_projet',
            'debut_projet' => 'en_service',
        );

        $typeAction = (string) $typeAction;
        if (!isset($statuses[$typeAction])) {
            return true;
        }

        $update = grr_sql_command(
            "UPDATE ".self::table(self::TABLE_ITEM)."
            SET statut = ?, updated_at = ?
            WHERE id = ? AND actif = 1 AND statut <> 'archive'",
            "sii",
            array($statuses[$typeAction], time(), (int) $itemId)
        );

        return !($update === false || $update < 0);
    }

    private static function actionStatusTransitionIsAllowed($item, $typeAction)
    {
        $typeAction = (string) $typeAction;
        $currentStatus = isset($item['statut']) ? (string) $item['statut'] : '';
        $transitions = array(
            'fin_projet' => 'en_service',
            'debut_projet' => 'sans_projet',
        );

        return !isset($transitions[$typeAction]) || $currentStatus === $transitions[$typeAction];
    }

    private static function deadlineAlertStatus($row)
    {
        $type = isset($row['type_echeance']) ? (string) $row['type_echeance'] : '';
        $status = isset($row['statut']) ? (string) $row['statut'] : '';

        return $type === 'etalonnage' && $status === 'sans_projet'
            ? 'attention'
            : 'overdue';
    }

    private static function countUpcoming($field, $days)
    {
        $counts = self::deadlineCountsForField($field, $days);
        return $counts['upcoming'];
    }

    private static function countOverdue($field)
    {
        $counts = self::deadlineCountsForField($field, 30);
        return $counts['overdue'];
    }

    private static function deadlineCountsForField($field, $days)
    {
        if (!self::tableExists(self::TABLE_ITEM)) {
            return array('overdue' => 0, 'attention' => 0, 'upcoming' => 0);
        }

        $field = (string) $field;
        if (!in_array($field, array('maintenance_prochaine', 'etalonnage_prochain'), true)) {
            return array('overdue' => 0, 'attention' => 0, 'upcoming' => 0);
        }

        $window = self::deadlineWindow($days);
        $isEtalonnage = $field === 'etalonnage_prochain';
        $overdueStatusCondition = $isEtalonnage ? " AND statut <> 'sans_projet'" : '';
        $overdue = (int) grr_sql_query1(
            "SELECT COUNT(*) FROM ".self::table(self::TABLE_ITEM)."
            WHERE actif = 1
                AND statut NOT IN ('archive', 'hors_service', 'panne')
                AND ".$field." > 0
                AND ".$field." < ?".$overdueStatusCondition,
            "i",
            array($window['today'])
        );
        $attention = $isEtalonnage
            ? (int) grr_sql_query1(
                "SELECT COUNT(*) FROM ".self::table(self::TABLE_ITEM)."
                WHERE actif = 1
                    AND statut = 'sans_projet'
                    AND ".$field." > 0
                    AND ".$field." < ?",
                "i",
                array($window['today'])
            )
            : 0;
        $upcoming = (int) grr_sql_query1(
            "SELECT COUNT(*) FROM ".self::table(self::TABLE_ITEM)."
            WHERE actif = 1
                AND statut NOT IN ('archive', 'hors_service', 'panne')
                AND ".$field." >= ?
                AND ".$field." <= ?",
            "ii",
            array($window['today'], $window['until'])
        );

        return array('overdue' => $overdue, 'attention' => $attention, 'upcoming' => $upcoming);
    }

    private static function deadlineCountsForFieldForUser($field, $login, $days)
    {
        if (!self::tableExists(self::TABLE_ITEM) || !self::tableExists(self::TABLE_USER)) {
            return array('overdue' => 0, 'attention' => 0, 'upcoming' => 0);
        }

        $field = (string) $field;
        $login = self::limit($login, 190);
        if ($login === '' || !in_array($field, array('maintenance_prochaine', 'etalonnage_prochain'), true)) {
            return array('overdue' => 0, 'attention' => 0, 'upcoming' => 0);
        }

        $window = self::deadlineWindow($days);
        $isEtalonnage = $field === 'etalonnage_prochain';
        $overdueStatusCondition = $isEtalonnage ? " AND i.statut <> 'sans_projet'" : '';
        $overdue = (int) grr_sql_query1(
            "SELECT COUNT(DISTINCT i.id)
            FROM ".self::table(self::TABLE_ITEM)." i
            JOIN ".self::table(self::TABLE_USER)." gu ON gu.item_id = i.id
            WHERE i.actif = 1
                AND i.statut NOT IN ('archive', 'hors_service', 'panne')
                AND i.".$field." > 0
                AND i.".$field." < ?
                AND gu.login = ?".$overdueStatusCondition,
            "is",
            array($window['today'], $login)
        );
        $attention = $isEtalonnage
            ? (int) grr_sql_query1(
                "SELECT COUNT(DISTINCT i.id)
                FROM ".self::table(self::TABLE_ITEM)." i
                JOIN ".self::table(self::TABLE_USER)." gu ON gu.item_id = i.id
                WHERE i.actif = 1
                    AND i.statut = 'sans_projet'
                    AND i.".$field." > 0
                    AND i.".$field." < ?
                    AND gu.login = ?",
                "is",
                array($window['today'], $login)
            )
            : 0;
        $upcoming = (int) grr_sql_query1(
            "SELECT COUNT(DISTINCT i.id)
            FROM ".self::table(self::TABLE_ITEM)." i
            JOIN ".self::table(self::TABLE_USER)." gu ON gu.item_id = i.id
            WHERE i.actif = 1
                AND i.statut NOT IN ('archive', 'hors_service', 'panne')
                AND i.".$field." >= ?
                AND i.".$field." <= ?
                AND gu.login = ?",
            "iis",
            array($window['today'], $window['until'], $login)
        );

        return array('overdue' => $overdue, 'attention' => $attention, 'upcoming' => $upcoming);
    }

    private static function deadlineWindow($days)
    {
        $days = (int) $days;
        if ($days <= 0 || $days > 365) {
            $days = 30;
        }

        $today = self::dayStart();

        return array(
            'today' => $today,
            'until' => $today + ($days * 86400),
        );
    }

    private static function dayStart()
    {
        $today = strtotime(date('Y-m-d 00:00:00'));
        return $today === false ? time() : (int) $today;
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

    private static function normalizeLimit($limit, $default, $max)
    {
        $limit = (int) $limit;
        if ($limit <= 0 || $limit > (int) $max) {
            return (int) $default;
        }

        return $limit;
    }

    private static function dateToTimestamp($date)
    {
        $date = trim((string) $date);
        if ($date === '') {
            return 0;
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return 0;
        }

        $timestamp = strtotime($date.' 00:00:00');
        return $timestamp === false ? 0 : (int) $timestamp;
    }

    private static function normalizePositiveInt($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $value = (int) $value;
        return $value > 0 ? (string) $value : '';
    }

    private static function normalizeCost($value)
    {
        $value = str_replace(',', '.', trim((string) $value));
        if ($value === '') {
            return '0';
        }

        if (!is_numeric($value)) {
            return '0';
        }

        $value = (float) $value;
        return $value > 0 ? number_format($value, 2, '.', '') : '0';
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

    private static function limit($value, $length)
    {
        return substr(trim((string) $value), 0, (int) $length);
    }
}
