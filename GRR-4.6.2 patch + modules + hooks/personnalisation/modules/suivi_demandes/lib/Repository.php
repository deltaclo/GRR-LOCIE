<?php

class SuiviDemandesRepository
{
    const MAX_LIST_ROWS = 100;
    const MAX_CANDIDATE_ROWS = 300;
    const MAX_USER_SELECT = 500;
    const MAX_RESERVATION_DEMANDS = 100;
    const MAX_ATTACHMENT_BYTES = 5242880;

    private static $categoryColumnAvailable = null;
    private static $commentInternalColumnAvailable = null;
    private static $attachmentCommentColumnAvailable = null;

    public static function countAll()
    {
        return self::scalar("SELECT COUNT(*) FROM ".TABLE_PREFIX."_suivi_demande");
    }

    public static function countCreatedBy($login)
    {
        $login = SecuChaine::ProtectDataSql($login);
        return self::scalar("SELECT COUNT(*) FROM ".TABLE_PREFIX."_suivi_demande WHERE createur = '".$login."'");
    }

    public static function countFollowedBy($login)
    {
        $login = SecuChaine::ProtectDataSql($login);
        return self::scalar("SELECT COUNT(*) FROM ".TABLE_PREFIX."_suivi_demande_suiveur WHERE login = '".$login."'");
    }

    public static function sameLogin($left, $right)
    {
        $left = trim((string) $left);
        $right = trim((string) $right);

        return $left !== '' && $right !== '' && strcasecmp($left, $right) === 0;
    }

    public static function create($login, $title, $description, $priority, $roomIds, $category = '', $actorLogin = '')
    {
        $now = time();
        $status = 'ouverte';
        $actorLogin = $actorLogin === '' ? $login : $actorLogin;
        if (!SuiviDemandesConfig::categoriesEnabled()) {
            $category = '';
        }
        $hasCategory = self::ensureCategoryColumn();

        if ($hasCategory) {
            $insert = grr_sql_command(
                "INSERT INTO ".TABLE_PREFIX."_suivi_demande
                    (titre, description, statut, priorite, categorie, createur, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                "ssssssii",
                array($title, $description, $status, $priority, $category, $login, $now, $now)
            );
        } else {
            $insert = grr_sql_command(
                "INSERT INTO ".TABLE_PREFIX."_suivi_demande
                    (titre, description, statut, priorite, createur, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)",
                "sssssii",
                array($title, $description, $status, $priority, $login, $now, $now)
            );
        }

        if ($insert < 0) {
            return 0;
        }

        $demandeId = (int) grr_sql_insert_id();
        foreach ($roomIds as $roomId) {
            grr_sql_command(
                "INSERT IGNORE INTO ".TABLE_PREFIX."_suivi_demande_ressource (demande_id, room_id) VALUES (?, ?)",
                "ii",
                array($demandeId, (int) $roomId)
            );
        }

        $details = 'Demande creee';
        if (!self::sameLogin($actorLogin, $login)) {
            $details .= ' pour '.$login;
        }
        self::addHistory($demandeId, $actorLogin, 'creation', $details);

        return $demandeId;
    }

    public static function ensureCategoryColumn()
    {
        if (self::$categoryColumnAvailable !== null) {
            return self::$categoryColumnAvailable;
        }

        $table = TABLE_PREFIX."_suivi_demande";
        $columnExists = grr_sql_query1(
            "SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
                AND COLUMN_NAME = 'categorie'",
            "s",
            array($table)
        );
        if ((int) $columnExists > 0) {
            self::$categoryColumnAvailable = true;
            return true;
        }

        $alter = grr_sql_command("ALTER TABLE `".$table."` ADD `categorie` varchar(60) NOT NULL DEFAULT '' AFTER `priorite`");
        if ($alter < 0) {
            self::$categoryColumnAvailable = false;
            return false;
        }

        grr_sql_command("ALTER TABLE `".$table."` ADD KEY `categorie` (`categorie`)");
        self::$categoryColumnAvailable = true;
        return true;
    }

    public static function ensureCommentInternalColumn()
    {
        if (self::$commentInternalColumnAvailable !== null) {
            return self::$commentInternalColumnAvailable;
        }

        $table = TABLE_PREFIX."_suivi_demande_commentaire";
        $columnExists = grr_sql_query1(
            "SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
                AND COLUMN_NAME = 'interne'",
            "s",
            array($table)
        );
        if ((int) $columnExists > 0) {
            self::$commentInternalColumnAvailable = true;
            return true;
        }

        $alter = grr_sql_command("ALTER TABLE `".$table."` ADD `interne` tinyint(1) NOT NULL DEFAULT 0 AFTER `commentaire`");
        if ($alter < 0) {
            self::$commentInternalColumnAvailable = false;
            return false;
        }

        self::$commentInternalColumnAvailable = true;
        return true;
    }

    public static function ensureReservationTable()
    {
        $result = grr_sql_command("CREATE TABLE IF NOT EXISTS `".TABLE_PREFIX."_suivi_demande_reservation` (
            `demande_id` int(11) NOT NULL,
            `entry_id` int(11) NOT NULL,
            `room_id` int(11) NOT NULL,
            `created_at` int(11) NOT NULL,
            PRIMARY KEY (`demande_id`, `entry_id`),
            KEY `entry_id` (`entry_id`),
            KEY `room_id` (`room_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");

        return $result >= 0;
    }

    public static function ensureRoomConfigTable()
    {
        $result = grr_sql_command("CREATE TABLE IF NOT EXISTS `".TABLE_PREFIX."_suivi_demande_room_config` (
            `room_id` int(11) NOT NULL,
            `enabled` tinyint(1) NOT NULL DEFAULT 1,
            `updated_at` int(11) NOT NULL,
            PRIMARY KEY (`room_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");

        return $result >= 0;
    }

    public static function ensureUserConfigTable()
    {
        $result = grr_sql_command("CREATE TABLE IF NOT EXISTS `".TABLE_PREFIX."_suivi_demande_user_config` (
            `login` varchar(190) NOT NULL,
            `enabled` tinyint(1) NOT NULL DEFAULT 1,
            `updated_at` int(11) NOT NULL,
            PRIMARY KEY (`login`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");

        return $result >= 0;
    }

    public static function ensureAttachmentTable()
    {
        $result = grr_sql_command("CREATE TABLE IF NOT EXISTS `".TABLE_PREFIX."_suivi_demande_fichier` (
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

        return $result >= 0;
    }

    public static function ensureAttachmentCommentColumn()
    {
        if (self::$attachmentCommentColumnAvailable !== null) {
            return self::$attachmentCommentColumnAvailable;
        }

        if (!self::ensureAttachmentTable()) {
            self::$attachmentCommentColumnAvailable = false;
            return false;
        }

        $table = TABLE_PREFIX."_suivi_demande_fichier";
        $columnExists = grr_sql_query1(
            "SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = ?
                AND COLUMN_NAME = 'commentaire_id'",
            "s",
            array($table)
        );
        if ((int) $columnExists > 0) {
            self::$attachmentCommentColumnAvailable = true;
            return true;
        }

        $alter = grr_sql_command("ALTER TABLE `".$table."` ADD `commentaire_id` int(11) NOT NULL DEFAULT 0 AFTER `demande_id`");
        if ($alter < 0) {
            self::$attachmentCommentColumnAvailable = false;
            return false;
        }

        grr_sql_command("ALTER TABLE `".$table."` ADD KEY `commentaire_id` (`commentaire_id`)");
        self::$attachmentCommentColumnAvailable = true;
        return true;
    }

    public static function roomModuleEnabled($roomId)
    {
        $roomId = (int) $roomId;
        if ($roomId <= 0) {
            return false;
        }

        if (!self::ensureRoomConfigTable()) {
            return true;
        }

        $enabled = grr_sql_query1(
            "SELECT enabled FROM ".TABLE_PREFIX."_suivi_demande_room_config WHERE room_id = ?",
            "i",
            array($roomId)
        );

        if ($enabled === null || $enabled === '' || (string) $enabled === '-1') {
            return true;
        }

        return (int) $enabled === 1;
    }

    public static function setRoomModuleEnabled($roomId, $enabled)
    {
        $roomId = (int) $roomId;
        if ($roomId <= 0 || !self::ensureRoomConfigTable()) {
            return false;
        }

        $result = grr_sql_command(
            "REPLACE INTO ".TABLE_PREFIX."_suivi_demande_room_config (room_id, enabled, updated_at) VALUES (?, ?, ?)",
            "iii",
            array($roomId, $enabled ? 1 : 0, time())
        );

        return $result >= 0;
    }

    public static function allResourcesWithModuleState()
    {
        $resources = self::visibleResourcesWithoutRightFilter();
        foreach ($resources as $index => $resource) {
            $resources[$index]['enabled'] = self::roomModuleEnabled((int) $resource['id']);
        }

        return $resources;
    }

    public static function setRoomModuleStates($enabledIds, $disabledIds)
    {
        if (!self::ensureRoomConfigTable()) {
            return false;
        }

        $knownIds = array();
        foreach (self::visibleResourcesWithoutRightFilter() as $resource) {
            $knownIds[(int) $resource['id']] = true;
        }

        $enabledIds = self::normalizeRoomIdList($enabledIds);
        $disabledIds = self::normalizeRoomIdList($disabledIds);
        $now = time();
        $ok = true;

        foreach ($enabledIds as $roomId) {
            if (!isset($knownIds[$roomId]) || isset($disabledIds[$roomId])) {
                continue;
            }

            $result = grr_sql_command(
                "REPLACE INTO ".TABLE_PREFIX."_suivi_demande_room_config (room_id, enabled, updated_at) VALUES (?, 1, ?)",
                "ii",
                array($roomId, $now)
            );
            if ($result < 0) {
                $ok = false;
            }
        }

        foreach ($disabledIds as $roomId) {
            if (!isset($knownIds[$roomId])) {
                continue;
            }

            $result = grr_sql_command(
                "REPLACE INTO ".TABLE_PREFIX."_suivi_demande_room_config (room_id, enabled, updated_at) VALUES (?, 0, ?)",
                "ii",
                array($roomId, $now)
            );
            if ($result < 0) {
                $ok = false;
            }
        }

        return $ok;
    }

    public static function userModuleEnabled($login)
    {
        $login = trim((string) $login);
        if ($login === '') {
            return false;
        }

        if (!self::ensureUserConfigTable()) {
            return true;
        }

        $enabled = grr_sql_query1(
            "SELECT enabled FROM ".TABLE_PREFIX."_suivi_demande_user_config WHERE login = ?",
            "s",
            array($login)
        );

        if ($enabled === null || $enabled === '' || (string) $enabled === '-1') {
            return true;
        }

        return (int) $enabled === 1;
    }

    public static function allUsersWithModuleState()
    {
        $users = self::activeUsersWithoutModuleFilter();
        foreach ($users as $index => $user) {
            $users[$index]['enabled'] = self::userModuleEnabled((string) $user['login']);
        }

        return $users;
    }

    public static function activeUsersAvailableAsRequesters($includeLogin = '')
    {
        if (!self::ensureUserConfigTable()) {
            return array();
        }

        $users = self::rows(
            "SELECT login, nom, prenom
            FROM ".TABLE_PREFIX."_utilisateurs
            WHERE etat != 'inactif'
                AND login NOT IN (
                    SELECT login
                    FROM ".TABLE_PREFIX."_suivi_demande_user_config
                    WHERE enabled = 0
                )
            ORDER BY nom, prenom, login
            LIMIT ".self::MAX_USER_SELECT
        );

        $includeLogin = (string) $includeLogin;
        if ($includeLogin === '') {
            return $users;
        }

        foreach ($users as $user) {
            if (isset($user['login']) && (string) $user['login'] === $includeLogin) {
                return $users;
            }
        }

        if (!self::activeUserExists($includeLogin)) {
            return $users;
        }

        $included = self::rows(
            "SELECT login, nom, prenom
            FROM ".TABLE_PREFIX."_utilisateurs
            WHERE login = ?
                AND etat != 'inactif'
            LIMIT 1",
            "s",
            array($includeLogin)
        );

        if (count($included) > 0) {
            array_unshift($users, $included[0]);
        }

        return $users;
    }

    public static function setUserModuleStates($enabledLogins, $disabledLogins)
    {
        if (!self::ensureUserConfigTable()) {
            return false;
        }

        $knownLogins = array();
        foreach (self::activeUsersWithoutModuleFilter() as $user) {
            $knownLogins[(string) $user['login']] = true;
        }

        $enabledLogins = self::normalizeLoginList($enabledLogins);
        $disabledLogins = self::normalizeLoginList($disabledLogins);
        $now = time();
        $ok = true;

        foreach ($enabledLogins as $login) {
            if (!isset($knownLogins[$login]) || isset($disabledLogins[$login])) {
                continue;
            }

            $result = grr_sql_command(
                "REPLACE INTO ".TABLE_PREFIX."_suivi_demande_user_config (login, enabled, updated_at) VALUES (?, 1, ?)",
                "si",
                array($login, $now)
            );
            if ($result < 0) {
                $ok = false;
            }
        }

        foreach ($disabledLogins as $login) {
            if (!isset($knownLogins[$login])) {
                continue;
            }

            $result = grr_sql_command(
                "REPLACE INTO ".TABLE_PREFIX."_suivi_demande_user_config (login, enabled, updated_at) VALUES (?, 0, ?)",
                "si",
                array($login, $now)
            );
            if ($result < 0) {
                $ok = false;
            }
        }

        return $ok;
    }

    public static function attachableDemandsForUser($login)
    {
        $login = trim((string) $login);
        if ($login === '') {
            return array();
        }

        if (self::userIsAdmin($login)) {
            return self::rows(
                "SELECT ".self::demandSelectFields('', 'id, titre, statut, updated_at')."
                FROM ".TABLE_PREFIX."_suivi_demande
                WHERE statut <> 'cloturee'
                ORDER BY updated_at DESC, id DESC
                LIMIT ".self::MAX_RESERVATION_DEMANDS
            );
        }

        $visible = array();
        $candidates = self::rows(
            "SELECT ".self::demandSelectFields('', 'id, titre, statut, createur, updated_at')."
            FROM ".TABLE_PREFIX."_suivi_demande
            WHERE statut <> 'cloturee'
            ORDER BY updated_at DESC, id DESC
            LIMIT ".self::MAX_CANDIDATE_ROWS
        );

        foreach ($candidates as $demand) {
            if (self::demandAttachableByUser($demand, $login)) {
                $visible[] = $demand;
            }

            if (count($visible) >= self::MAX_RESERVATION_DEMANDS) {
                break;
            }
        }

        return $visible;
    }

    public static function canAttachDemandToReservation($demandeId, $login)
    {
        $demand = self::findById((int) $demandeId);

        return self::demandAttachableByUser($demand, $login);
    }

    public static function associateReservation($demandeId, $entryId, $roomId, $login)
    {
        $demandeId = (int) $demandeId;
        $entryId = (int) $entryId;
        $roomId = (int) $roomId;

        if ($demandeId <= 0 || $entryId <= 0) {
            return false;
        }

        if (!self::ensureReservationTable()) {
            return false;
        }

        $insert = grr_sql_command(
            "INSERT IGNORE INTO ".TABLE_PREFIX."_suivi_demande_reservation
                (demande_id, entry_id, room_id, created_at)
            VALUES (?, ?, ?, ?)",
            "iiii",
            array($demandeId, $entryId, $roomId, time())
        );

        if ($insert < 0) {
            return false;
        }

        if (!self::addResourceToDemand($demandeId, $roomId)) {
            return false;
        }

        if ($insert > 0) {
            self::updateDemandTimestamp($demandeId);
            self::addHistory($demandeId, $login, 'reservation_associee', 'Reservation associee : #'.$entryId);
        }

        return true;
    }

    public static function demandsForReservation($entryId)
    {
        if (!self::ensureReservationTable()) {
            return array();
        }

        return self::rows(
            "SELECT ".self::demandSelectFields('d', 'id, titre, statut, priorite, createur, created_at, updated_at, closed_at')."
            FROM ".TABLE_PREFIX."_suivi_demande_reservation dr
            JOIN ".TABLE_PREFIX."_suivi_demande d ON d.id = dr.demande_id
            WHERE dr.entry_id = ?
            ORDER BY d.updated_at DESC, d.id DESC
            LIMIT ".self::MAX_RESERVATION_DEMANDS,
            "i",
            array((int) $entryId)
        );
    }

    public static function reservationsForDemand($demandeId)
    {
        if (!self::ensureReservationTable()) {
            return array();
        }

        return self::rows(
            "SELECT dr.entry_id, dr.room_id AS linked_room_id,
                e.name, e.start_time, e.end_time, e.room_id,
                r.room_name, a.area_name
            FROM ".TABLE_PREFIX."_suivi_demande_reservation dr
            LEFT JOIN ".TABLE_PREFIX."_entry e ON e.id = dr.entry_id
            LEFT JOIN ".TABLE_PREFIX."_room r ON r.id = e.room_id
            LEFT JOIN ".TABLE_PREFIX."_area a ON a.id = r.area_id
            WHERE dr.demande_id = ?
            ORDER BY e.start_time DESC, dr.entry_id DESC",
            "i",
            array((int) $demandeId)
        );
    }

    public static function findById($demandeId)
    {
        $rows = self::rows(
            "SELECT ".self::demandSelectFields('', 'id, titre, description, statut, priorite, createur, created_at, updated_at, closed_at')."
            FROM ".TABLE_PREFIX."_suivi_demande
            WHERE id = ?",
            "i",
            array((int) $demandeId)
        );

        return isset($rows[0]) ? $rows[0] : null;
    }

    public static function findVisibleForUser($login, $isAdmin, $filters = array())
    {
        $filters = self::normalizeDemandFilters($filters);
        $displayLimit = (int) $filters['limit'];

        if ($isAdmin) {
            $limitSql = self::hasDemandFilters($filters) ? '' : ' LIMIT '.$displayLimit;
            $sql = "SELECT ".self::demandSelectFields('', 'id, titre, statut, priorite, createur, created_at, updated_at, closed_at')."
                FROM ".TABLE_PREFIX."_suivi_demande
                ORDER BY updated_at DESC, id DESC".$limitSql;
            return self::filterDemands(self::rows($sql), $filters, $login);
        }

        $visible = array();
        $limitSql = self::hasDemandFilters($filters) ? '' : ' LIMIT '.self::MAX_CANDIDATE_ROWS;
        $candidates = self::rows(
            "SELECT ".self::demandSelectFields('', 'id, titre, statut, priorite, createur, created_at, updated_at, closed_at')."
            FROM ".TABLE_PREFIX."_suivi_demande
            ORDER BY updated_at DESC, id DESC".$limitSql
        );

        foreach ($candidates as $demand) {
            $demandeId = (int) $demand['id'];
            if (self::sameLogin($demand['createur'], $login)
                || self::isFollower($demandeId, $login)
                || self::userManagesDemandResource($demandeId, $login)) {
                if (self::demandMatchesFilters($demand, $filters, $login)) {
                    $visible[] = $demand;
                }
            }

            if (count($visible) >= $displayLimit) {
                break;
            }
        }

        return $visible;
    }

    public static function dashboardCountsForUser($login, $isAdmin)
    {
        $counts = array(
            'ouverte' => 0,
            'en_cours' => 0,
            'cloturee' => 0,
            'haute' => 0,
            'created' => 0,
            'followed' => 0,
        );

        if ($login === '') {
            return $counts;
        }

        $rows = self::rows(
            "SELECT ".self::demandSelectFields('', 'id, titre, statut, priorite, createur, created_at, updated_at, closed_at')."
            FROM ".TABLE_PREFIX."_suivi_demande
            ORDER BY updated_at DESC, id DESC"
        );

        foreach ($rows as $demand) {
            $demandeId = (int) $demand['id'];
            $isCreated = isset($demand['createur']) && self::sameLogin($demand['createur'], $login);
            $isFollower = self::isFollower($demandeId, $login);
            $isVisible = $isAdmin || $isCreated || $isFollower || self::userManagesDemandResource($demandeId, $login);

            if (!$isVisible) {
                continue;
            }

            $status = isset($demand['statut']) ? (string) $demand['statut'] : '';
            if (isset($counts[$status])) {
                $counts[$status]++;
            }

            if (isset($demand['priorite']) && $demand['priorite'] === 'haute') {
                $counts['haute']++;
            }

            if ($isCreated) {
                $counts['created']++;
            }

            if ($isFollower) {
                $counts['followed']++;
            }
        }

        return $counts;
    }

    public static function visibleStatusCountsForUser($login, $isAdmin)
    {
        $counts = array(
            'ouverte' => 0,
            'en_cours' => 0,
        );

        if ($login === '') {
            return $counts;
        }

        $rows = self::rows(
            "SELECT id, statut, createur
            FROM ".TABLE_PREFIX."_suivi_demande
            WHERE statut IN ('ouverte', 'en_cours')
            ORDER BY updated_at DESC, id DESC"
        );

        if ($isAdmin) {
            foreach ($rows as $demand) {
                $status = isset($demand['statut']) ? (string) $demand['statut'] : '';
                if (isset($counts[$status])) {
                    $counts[$status]++;
                }
            }

            return $counts;
        }

        foreach ($rows as $demand) {
            $status = isset($demand['statut']) ? (string) $demand['statut'] : '';
            if (!isset($counts[$status])) {
                continue;
            }

            $demandeId = (int) $demand['id'];
            if (self::sameLogin($demand['createur'], $login)
                || self::isFollower($demandeId, $login)
                || self::userManagesDemandResource($demandeId, $login)) {
                $counts[$status]++;
            }
        }

        return $counts;
    }

    public static function filtersFromRequest($source)
    {
        if (!is_array($source)) {
            $source = array();
        }

        return self::normalizeDemandFilters(array(
            'status' => isset($source['suivi_statut']) ? (string) $source['suivi_statut'] : '',
            'priority' => isset($source['suivi_priorite']) ? (string) $source['suivi_priorite'] : '',
            'category' => isset($source['suivi_categorie']) ? (string) $source['suivi_categorie'] : '',
            'search' => isset($source['suivi_recherche']) ? (string) $source['suivi_recherche'] : '',
            'limit' => isset($source['suivi_limite']) ? (int) $source['suivi_limite'] : self::MAX_LIST_ROWS,
            'scope' => isset($source['suivi_perimetre']) ? (string) $source['suivi_perimetre'] : '',
        ));
    }

    public static function filterDemands($demands, $filters, $login = '')
    {
        $filters = self::normalizeDemandFilters($filters);
        $displayLimit = (int) $filters['limit'];

        $filtered = array();
        foreach ($demands as $demand) {
            if (self::demandMatchesFilters($demand, $filters, $login)) {
                $filtered[] = $demand;
            }

            if (count($filtered) >= $displayLimit) {
                break;
            }
        }

        return $filtered;
    }

    public static function listLimitOptions()
    {
        return array(10, 25, 50, 100);
    }

    public static function statisticsFiltersFromRequest($source)
    {
        return self::normalizeStatisticsFilters($source);
    }

    public static function statisticsCreatorOptions()
    {
        return self::rows(
            "SELECT DISTINCT createur
            FROM ".TABLE_PREFIX."_suivi_demande
            WHERE createur <> ''
            ORDER BY createur"
        );
    }

    public static function statisticsResourceOptions()
    {
        return self::visibleResourcesWithoutRightFilter();
    }

    public static function statisticsForAdmin($filters)
    {
        $filters = self::normalizeStatisticsFilters($filters);
        $rows = self::rows(
            "SELECT ".self::demandSelectFields('', 'id, titre, statut, priorite, createur, created_at, updated_at, closed_at')."
            FROM ".TABLE_PREFIX."_suivi_demande
            ORDER BY created_at DESC, id DESC"
        );

        $resourcesByDemand = self::resourcesByDemandForStatistics();
        $firstStarts = self::historyFirstActionTimes('passage_en_cours');
        $reopens = self::historyActionCounts('reouverture');
        self::ensureAttachmentTable();
        self::ensureReservationTable();
        $comments = self::countsByDemand('suivi_demande_commentaire');
        $attachments = self::countsByDemand('suivi_demande_fichier');
        $reservations = self::countsByDemand('suivi_demande_reservation');
        $followers = self::countsByDemand('suivi_demande_suiveur');
        $stats = self::emptyStatistics($filters);
        $responseDurations = array();
        $closureDurations = array();

        foreach ($rows as $demand) {
            $demandeId = (int) $demand['id'];
            if (!self::demandMatchesStatisticsFilters($demand, $filters, $resourcesByDemand)) {
                continue;
            }

            $stats['total']++;
            $status = isset($demand['statut']) ? (string) $demand['statut'] : '';
            if (isset($stats['by_status'][$status])) {
                $stats['by_status'][$status]++;
            }

            $priority = isset($demand['priorite']) ? (string) $demand['priorite'] : '';
            if (isset($stats['by_priority'][$priority])) {
                $stats['by_priority'][$priority]++;
            }

            $category = isset($demand['categorie']) ? trim((string) $demand['categorie']) : '';
            if (SuiviDemandesConfig::categoriesEnabled()) {
                $categoryLabel = $category === '' ? 'Sans categorie' : $category;
                if (!isset($stats['by_category'][$categoryLabel])) {
                    $stats['by_category'][$categoryLabel] = 0;
                }
                $stats['by_category'][$categoryLabel]++;
            }

            $creator = isset($demand['createur']) ? (string) $demand['createur'] : '';
            if (!isset($stats['by_creator'][$creator])) {
                $stats['by_creator'][$creator] = 0;
            }
            $stats['by_creator'][$creator]++;

            if (isset($resourcesByDemand[$demandeId])) {
                foreach ($resourcesByDemand[$demandeId] as $resource) {
                    $roomId = (int) $resource['id'];
                    if (!isset($stats['by_resource'][$roomId])) {
                        $stats['by_resource'][$roomId] = array('label' => $resource['label'], 'count' => 0);
                    }
                    $stats['by_resource'][$roomId]['count']++;
                }
            }

            if ($status === 'cloturee') {
                $stats['closed']++;
            }

            $createdAt = isset($demand['created_at']) ? (int) $demand['created_at'] : 0;
            $closedAt = isset($demand['closed_at']) ? (int) $demand['closed_at'] : 0;
            $responseAt = isset($firstStarts[$demandeId]) ? (int) $firstStarts[$demandeId] : 0;
            if ($responseAt <= 0 && $closedAt > 0) {
                $responseAt = $closedAt;
            }
            if ($createdAt > 0 && $responseAt >= $createdAt) {
                $responseDurations[] = $responseAt - $createdAt;
            }
            if ($createdAt > 0 && $closedAt >= $createdAt) {
                $closureDurations[] = $closedAt - $createdAt;
            }

            if (isset($reopens[$demandeId]) && (int) $reopens[$demandeId] > 0) {
                $stats['reopened_demands']++;
                $stats['reopen_events'] += (int) $reopens[$demandeId];
            }

            self::addStatisticsCounter($stats, 'comments', $comments, $demandeId);
            self::addStatisticsCounter($stats, 'attachments', $attachments, $demandeId);
            self::addStatisticsCounter($stats, 'reservations', $reservations, $demandeId);
            self::addStatisticsCounter($stats, 'followers', $followers, $demandeId);
        }

        $stats['response_time'] = self::durationStatistics($responseDurations);
        $stats['closure_time'] = self::durationStatistics($closureDurations);
        arsort($stats['by_creator']);
        uasort($stats['by_resource'], array('SuiviDemandesRepository', 'sortStatisticResource'));

        return $stats;
    }

    public static function visibleResources($login)
    {
        $resources = array();
        $multisite = Settings::get("module_multisite") == "Oui";

        if ($multisite) {
            $sql = "SELECT r.id, r.room_name, a.area_name, s.sitename
                FROM ((`".TABLE_PREFIX."_room` r JOIN `".TABLE_PREFIX."_area` a ON r.area_id = a.id)
                JOIN ".TABLE_PREFIX."_j_site_area jsa ON a.id = jsa.id_area)
                JOIN ".TABLE_PREFIX."_site s ON s.id = jsa.id_site
                ORDER BY s.sitename, a.order_display, a.area_name, r.order_display, r.room_name";
        } else {
            $sql = "SELECT r.id, r.room_name, a.area_name
                FROM `".TABLE_PREFIX."_room` r JOIN `".TABLE_PREFIX."_area` a ON r.area_id = a.id
                ORDER BY a.order_display, a.area_name, r.order_display, r.room_name";
        }

        $result = grr_sql_query($sql);
        if (!$result) {
            return $resources;
        }

        for ($i = 0; ($row = grr_sql_row_keyed($result, $i)); $i++) {
            $roomId = (int) $row['id'];
            if (!SecuAccess::UserResource($login, $roomId) || !self::roomModuleEnabled($roomId)) {
                continue;
            }

            $label = $row['area_name'].' > '.$row['room_name'];
            if ($multisite) {
                $label = $row['sitename'].' > '.$label;
            }

            $resources[] = array(
                'id' => $roomId,
                'label' => $label,
            );
        }

        return $resources;
    }

    public static function resourcesForDemand($demandeId)
    {
        $resources = array();
        foreach (self::resourcesWithIdsForDemand($demandeId) as $resource) {
            $resources[] = $resource['label'];
        }

        return $resources;
    }

    public static function resourcesWithIdsForDemand($demandeId)
    {
        $resources = array();
        $demandeId = (int) $demandeId;
        $multisite = Settings::get("module_multisite") == "Oui";

        if ($multisite) {
            $sql = "SELECT r.id, r.room_name, a.area_name, s.sitename
                FROM ".TABLE_PREFIX."_suivi_demande_ressource dr
                JOIN ".TABLE_PREFIX."_room r ON r.id = dr.room_id
                JOIN ".TABLE_PREFIX."_area a ON a.id = r.area_id
                JOIN ".TABLE_PREFIX."_j_site_area jsa ON jsa.id_area = a.id
                JOIN ".TABLE_PREFIX."_site s ON s.id = jsa.id_site
                WHERE dr.demande_id = ?
                ORDER BY s.sitename, a.order_display, a.area_name, r.order_display, r.room_name";
        } else {
            $sql = "SELECT r.id, r.room_name, a.area_name
                FROM ".TABLE_PREFIX."_suivi_demande_ressource dr
                JOIN ".TABLE_PREFIX."_room r ON r.id = dr.room_id
                JOIN ".TABLE_PREFIX."_area a ON a.id = r.area_id
                WHERE dr.demande_id = ?
                ORDER BY a.order_display, a.area_name, r.order_display, r.room_name";
        }

        $result = grr_sql_query($sql, "i", array($demandeId));
        if (!$result) {
            return $resources;
        }

        for ($i = 0; ($row = grr_sql_row_keyed($result, $i)); $i++) {
            $label = $row['area_name'].' > '.$row['room_name'];
            if ($multisite) {
                $label = $row['sitename'].' > '.$label;
            }
            $resources[] = array(
                'id' => (int) $row['id'],
                'label' => $label,
            );
        }

        return $resources;
    }

    public static function managedResources($login)
    {
        $resources = self::visibleResourcesWithoutRightFilter();
        $managed = array();

        foreach ($resources as $resource) {
            $roomId = (int) $resource['id'];
            if (self::userManagesResource($login, $roomId) && self::roomModuleEnabled($roomId)) {
                $managed[] = $resource;
            }
        }

        return $managed;
    }

    public static function resourcesAvailableToAdd($login, $demandeId)
    {
        $demandeId = (int) $demandeId;
        $alreadyLinked = array_flip(self::resourceIdsForDemand($demandeId));
        $available = array();
        $sourceResources = (SecuAccess::UserLevel($login, -1) >= 6 || self::userManagesDemandResource($demandeId, $login))
            ? self::visibleResourcesWithoutRightFilter()
            : self::managedResources($login);

        foreach ($sourceResources as $resource) {
            $roomId = (int) $resource['id'];
            if (isset($alreadyLinked[$roomId]) || !self::roomModuleEnabled($roomId)) {
                continue;
            }

            $available[] = $resource;
        }

        return $available;
    }

    public static function resourceAvailableToAdd($login, $demandeId, $roomId)
    {
        $roomId = (int) $roomId;
        if ($roomId <= 0) {
            return false;
        }

        foreach (self::resourcesAvailableToAdd($login, (int) $demandeId) as $resource) {
            if ((int) $resource['id'] === $roomId) {
                return true;
            }
        }

        return false;
    }

    public static function userMailInfo($login)
    {
        $rows = self::rows(
            "SELECT login, email
            FROM ".TABLE_PREFIX."_utilisateurs
            WHERE login = ?
                AND etat = 'actif'
                AND desactive_mail = 0
                AND email <> ''",
            "s",
            array($login)
        );

        return isset($rows[0]) ? $rows[0] : null;
    }

    public static function roomInfo($roomId)
    {
        $rows = self::rows(
            "SELECT r.id, r.room_name, a.area_name
            FROM ".TABLE_PREFIX."_room r
            JOIN ".TABLE_PREFIX."_area a ON a.id = r.area_id
            WHERE r.id = ?",
            "i",
            array((int) $roomId)
        );

        return isset($rows[0]) ? $rows[0] : null;
    }

    public static function resourceIdsForDemand($demandeId)
    {
        $roomIds = array();
        $result = grr_sql_query(
            "SELECT room_id FROM ".TABLE_PREFIX."_suivi_demande_ressource WHERE demande_id = ?",
            "i",
            array((int) $demandeId)
        );

        if (!$result) {
            return $roomIds;
        }

        for ($i = 0; ($row = grr_sql_row_keyed($result, $i)); $i++) {
            $roomIds[] = (int) $row['room_id'];
        }

        return $roomIds;
    }

    public static function historyForDemand($demandeId, $includeInternal = false)
    {
        $internalFilter = $includeInternal ? '' : " AND action NOT IN ('commentaire_interne', 'piece_jointe_interne_ajout', 'piece_jointe_interne_retrait')";

        return self::rows(
            "SELECT id, auteur, action, details, created_at
            FROM ".TABLE_PREFIX."_suivi_demande_historique
            WHERE demande_id = ?".$internalFilter."
            ORDER BY created_at ASC, id ASC",
            "i",
            array((int) $demandeId)
        );
    }

    public static function isFollower($demandeId, $login)
    {
        $count = grr_sql_query1(
            "SELECT COUNT(*) FROM ".TABLE_PREFIX."_suivi_demande_suiveur WHERE demande_id = ? AND login = ?",
            "is",
            array((int) $demandeId, $login)
        );

        return (int) $count > 0;
    }

    public static function userManagesDemandResource($demandeId, $login)
    {
        $roomIds = self::resourceIdsForDemand($demandeId);
        foreach ($roomIds as $roomId) {
            if (self::userManagesResource($login, $roomId)) {
                return true;
            }
        }

        return false;
    }

    public static function userManagesResource($login, $roomId)
    {
        if ($login === '' || (int) $roomId <= 0) {
            return false;
        }

        return SecuAccess::UserLevel($login, -1) >= 6
            || SecuAccess::UserLevel($login, (int) $roomId) >= 3;
    }

    public static function demandHasResource($demandeId, $roomId)
    {
        $count = grr_sql_query1(
            "SELECT COUNT(*) FROM ".TABLE_PREFIX."_suivi_demande_ressource WHERE demande_id = ? AND room_id = ?",
            "ii",
            array((int) $demandeId, (int) $roomId)
        );

        return (int) $count > 0;
    }

    public static function countResourcesForDemand($demandeId)
    {
        $count = grr_sql_query1(
            "SELECT COUNT(*) FROM ".TABLE_PREFIX."_suivi_demande_ressource WHERE demande_id = ?",
            "i",
            array((int) $demandeId)
        );

        return (int) $count;
    }

    public static function resourceHasReservationLink($demandeId, $roomId)
    {
        if (!self::ensureReservationTable()) {
            return false;
        }

        $count = grr_sql_query1(
            "SELECT COUNT(*) FROM ".TABLE_PREFIX."_suivi_demande_reservation WHERE demande_id = ? AND room_id = ?",
            "ii",
            array((int) $demandeId, (int) $roomId)
        );

        return (int) $count > 0;
    }

    public static function addResource($demandeId, $roomId, $login)
    {
        if (!self::addResourceToDemand((int) $demandeId, (int) $roomId)) {
            return false;
        }

        self::updateDemandTimestamp((int) $demandeId);
        self::addHistory((int) $demandeId, $login, 'ressource_ajout', 'Ressource ajoutee : #'.(int) $roomId);
        return true;
    }

    public static function removeResource($demandeId, $roomId, $login)
    {
        $delete = grr_sql_command(
            "DELETE FROM ".TABLE_PREFIX."_suivi_demande_ressource WHERE demande_id = ? AND room_id = ?",
            "ii",
            array((int) $demandeId, (int) $roomId)
        );

        if ($delete < 0) {
            return false;
        }

        if ($delete > 0) {
            self::updateDemandTimestamp((int) $demandeId);
            self::addHistory((int) $demandeId, $login, 'ressource_retrait', 'Ressource retiree : #'.(int) $roomId);
        }

        return true;
    }

    public static function closeDemand($demandeId, $login)
    {
        $now = time();
        $updated = grr_sql_command(
            "UPDATE ".TABLE_PREFIX."_suivi_demande
            SET statut = 'cloturee', updated_at = ?, closed_at = ?
            WHERE id = ?",
            "iii",
            array($now, $now, (int) $demandeId)
        );

        if ($updated < 0) {
            return false;
        }

        self::addHistory((int) $demandeId, $login, 'cloture', 'Demande cloturee');
        return true;
    }

    public static function startDemand($demandeId, $login)
    {
        $now = time();
        $updated = grr_sql_command(
            "UPDATE ".TABLE_PREFIX."_suivi_demande
            SET statut = 'en_cours', updated_at = ?
            WHERE id = ?",
            "ii",
            array($now, (int) $demandeId)
        );

        if ($updated < 0) {
            return false;
        }

        self::addHistory((int) $demandeId, $login, 'passage_en_cours', 'Demande passee en cours');
        return true;
    }

    public static function reopenDemand($demandeId, $login, $status)
    {
        if (!in_array($status, array('ouverte', 'en_cours'), true)) {
            return false;
        }

        $now = time();
        $updated = grr_sql_command(
            "UPDATE ".TABLE_PREFIX."_suivi_demande
            SET statut = ?, updated_at = ?, closed_at = NULL
            WHERE id = ?",
            "sii",
            array($status, $now, (int) $demandeId)
        );

        if ($updated < 0) {
            return false;
        }

        self::addHistory(
            (int) $demandeId,
            $login,
            'reouverture',
            'Demande reouverte avec le statut : '.SuiviDemandesConfig::statusLabel($status)
        );
        return true;
    }

    public static function commentsForDemand($demandeId, $includeInternal = false)
    {
        if (self::ensureCommentInternalColumn()) {
            $internalFilter = $includeInternal ? '' : ' AND interne = 0';
            return self::rows(
                "SELECT id, auteur, commentaire, interne, created_at
                FROM ".TABLE_PREFIX."_suivi_demande_commentaire
                WHERE demande_id = ?".$internalFilter."
                ORDER BY created_at ASC, id ASC",
                "i",
                array((int) $demandeId)
            );
        }

        return self::rows(
            "SELECT id, auteur, commentaire, 0 AS interne, created_at
            FROM ".TABLE_PREFIX."_suivi_demande_commentaire
            WHERE demande_id = ?
            ORDER BY created_at ASC, id ASC",
            "i",
            array((int) $demandeId)
        );
    }

    public static function commentForDemand($commentId, $demandeId)
    {
        $commentId = (int) $commentId;
        $demandeId = (int) $demandeId;
        if ($commentId <= 0 || $demandeId <= 0) {
            return null;
        }

        if (self::ensureCommentInternalColumn()) {
            $rows = self::rows(
                "SELECT id, demande_id, auteur, commentaire, interne, created_at
                FROM ".TABLE_PREFIX."_suivi_demande_commentaire
                WHERE id = ? AND demande_id = ?",
                "ii",
                array($commentId, $demandeId)
            );
        } else {
            $rows = self::rows(
                "SELECT id, demande_id, auteur, commentaire, 0 AS interne, created_at
                FROM ".TABLE_PREFIX."_suivi_demande_commentaire
                WHERE id = ? AND demande_id = ?",
                "ii",
                array($commentId, $demandeId)
            );
        }

        return isset($rows[0]) ? $rows[0] : null;
    }

    public static function commentIsInternal($commentId)
    {
        $commentId = (int) $commentId;
        if ($commentId <= 0 || !self::ensureCommentInternalColumn()) {
            return false;
        }

        $internal = grr_sql_query1(
            "SELECT interne FROM ".TABLE_PREFIX."_suivi_demande_commentaire WHERE id = ?",
            "i",
            array($commentId)
        );

        return (int) $internal === 1;
    }

    public static function addComment($demandeId, $login, $comment, $internal = 0)
    {
        $demandeId = (int) $demandeId;
        $now = time();
        $internal = $internal ? 1 : 0;

        if (self::ensureCommentInternalColumn()) {
            $insert = grr_sql_command(
                "INSERT INTO ".TABLE_PREFIX."_suivi_demande_commentaire
                    (demande_id, auteur, commentaire, interne, created_at)
                VALUES (?, ?, ?, ?, ?)",
                "issii",
                array($demandeId, $login, $comment, $internal, $now)
            );
        } else {
            if ($internal) {
                return false;
            }

            $insert = grr_sql_command(
                "INSERT INTO ".TABLE_PREFIX."_suivi_demande_commentaire
                    (demande_id, auteur, commentaire, created_at)
                VALUES (?, ?, ?, ?)",
                "issi",
                array($demandeId, $login, $comment, $now)
            );
            $internal = 0;
        }

        if ($insert < 0) {
            return false;
        }

        self::updateDemandTimestamp($demandeId, $now);
        self::addHistory(
            $demandeId,
            $login,
            $internal ? 'commentaire_interne' : 'commentaire',
            $internal ? 'Commentaire interne ajoute' : 'Commentaire ajoute'
        );
        return true;
    }

    public static function attachmentsForDemand($demandeId, $includeInternal = false)
    {
        if (!self::ensureAttachmentTable()) {
            return array();
        }

        if (self::ensureAttachmentCommentColumn()) {
            $hasInternalColumn = self::ensureCommentInternalColumn();
            if (!$hasInternalColumn) {
                return self::rows(
                    "SELECT id, demande_id, commentaire_id, original_name, stored_name, mime_type, taille, uploader, created_at
                    FROM ".TABLE_PREFIX."_suivi_demande_fichier
                    WHERE demande_id = ?
                    ORDER BY created_at DESC, id DESC",
                    "i",
                    array((int) $demandeId)
                );
            }

            $internalFilter = '';
            if (!$includeInternal) {
                $internalFilter = " AND (f.commentaire_id = 0 OR c.id IS NULL OR COALESCE(c.interne, 0) = 0)";
            }

            return self::rows(
                "SELECT f.id, f.demande_id, f.commentaire_id, f.original_name, f.stored_name, f.mime_type, f.taille, f.uploader, f.created_at
                FROM ".TABLE_PREFIX."_suivi_demande_fichier f
                LEFT JOIN ".TABLE_PREFIX."_suivi_demande_commentaire c ON c.id = f.commentaire_id
                WHERE f.demande_id = ?".$internalFilter."
                ORDER BY f.created_at DESC, f.id DESC",
                "i",
                array((int) $demandeId)
            );
        }

        return self::rows(
            "SELECT id, demande_id, 0 AS commentaire_id, original_name, stored_name, mime_type, taille, uploader, created_at
            FROM ".TABLE_PREFIX."_suivi_demande_fichier
            WHERE demande_id = ?
            ORDER BY created_at DESC, id DESC",
            "i",
            array((int) $demandeId)
        );
    }

    public static function attachmentById($attachmentId)
    {
        if (!self::ensureAttachmentTable()) {
            return null;
        }

        if (self::ensureAttachmentCommentColumn()) {
            $rows = self::rows(
                "SELECT id, demande_id, commentaire_id, original_name, stored_name, mime_type, taille, uploader, created_at
                FROM ".TABLE_PREFIX."_suivi_demande_fichier
                WHERE id = ?",
                "i",
                array((int) $attachmentId)
            );

            return isset($rows[0]) ? $rows[0] : null;
        }

        $rows = self::rows(
            "SELECT id, demande_id, 0 AS commentaire_id, original_name, stored_name, mime_type, taille, uploader, created_at
            FROM ".TABLE_PREFIX."_suivi_demande_fichier
            WHERE id = ?",
            "i",
            array((int) $attachmentId)
        );

        return isset($rows[0]) ? $rows[0] : null;
    }

    public static function addAttachment($demandeId, $login, $originalName, $storedName, $mimeType, $size, $commentId = 0)
    {
        $demandeId = (int) $demandeId;
        $commentId = (int) $commentId;
        if ($demandeId <= 0 || !self::ensureAttachmentTable()) {
            return false;
        }

        $now = time();
        if (self::ensureAttachmentCommentColumn()) {
            $insert = grr_sql_command(
                "INSERT INTO ".TABLE_PREFIX."_suivi_demande_fichier
                    (demande_id, commentaire_id, original_name, stored_name, mime_type, taille, uploader, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                "iisssisi",
                array($demandeId, $commentId, $originalName, $storedName, $mimeType, (int) $size, $login, $now)
            );
        } else {
            if ($commentId > 0) {
                return false;
            }

            $insert = grr_sql_command(
                "INSERT INTO ".TABLE_PREFIX."_suivi_demande_fichier
                    (demande_id, original_name, stored_name, mime_type, taille, uploader, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)",
                "isssisi",
                array($demandeId, $originalName, $storedName, $mimeType, (int) $size, $login, $now)
            );
        }

        if ($insert < 0) {
            return false;
        }

        self::updateDemandTimestamp($demandeId, $now);
        $internal = self::commentIsInternal($commentId);
        $details = 'Piece jointe ajoutee : '.$originalName;
        if ($commentId > 0) {
            $details .= ' (commentaire #'.$commentId.')';
        }
        self::addHistory(
            $demandeId,
            $login,
            $internal ? 'piece_jointe_interne_ajout' : 'piece_jointe_ajout',
            $details
        );
        return true;
    }

    public static function deleteAttachment($attachment, $login)
    {
        if (!$attachment || !isset($attachment['id']) || !self::ensureAttachmentTable()) {
            return false;
        }

        $path = self::attachmentPath(isset($attachment['stored_name']) ? $attachment['stored_name'] : '');
        if ($path !== '' && is_file($path) && !@unlink($path)) {
            return false;
        }

        $delete = grr_sql_command(
            "DELETE FROM ".TABLE_PREFIX."_suivi_demande_fichier WHERE id = ?",
            "i",
            array((int) $attachment['id'])
        );

        if ($delete < 0) {
            return false;
        }

        $demandeId = isset($attachment['demande_id']) ? (int) $attachment['demande_id'] : 0;
        self::updateDemandTimestamp($demandeId);
        $commentId = isset($attachment['commentaire_id']) ? (int) $attachment['commentaire_id'] : 0;
        $internal = self::commentIsInternal($commentId);
        $details = 'Piece jointe retiree : '.(isset($attachment['original_name']) ? $attachment['original_name'] : '');
        if ($commentId > 0) {
            $details .= ' (commentaire #'.$commentId.')';
        }
        self::addHistory(
            $demandeId,
            $login,
            $internal ? 'piece_jointe_interne_retrait' : 'piece_jointe_retrait',
            $details
        );
        return true;
    }

    public static function deleteDemand($demandeId)
    {
        $demandeId = (int) $demandeId;
        if ($demandeId <= 0 || !self::findById($demandeId)) {
            return false;
        }

        if (!self::ensureAttachmentTable() || !self::ensureReservationTable()) {
            return false;
        }

        $attachments = self::attachmentsForDemand($demandeId, true);
        foreach ($attachments as $attachment) {
            $path = self::attachmentPath(isset($attachment['stored_name']) ? $attachment['stored_name'] : '');
            if ($path !== '' && is_file($path) && !@unlink($path)) {
                return false;
            }
        }

        $tables = array(
            'suivi_demande_fichier',
            'suivi_demande_commentaire',
            'suivi_demande_historique',
            'suivi_demande_reservation',
            'suivi_demande_suiveur',
            'suivi_demande_ressource',
        );

        foreach ($tables as $table) {
            if (!self::deleteRowsForDemand($table, $demandeId)) {
                return false;
            }
        }

        $delete = grr_sql_command(
            "DELETE FROM ".TABLE_PREFIX."_suivi_demande WHERE id = ?",
            "i",
            array($demandeId)
        );

        return $delete >= 0;
    }

    public static function attachmentStorageDir()
    {
        return dirname(__DIR__).'/storage/attachments';
    }

    public static function ensureAttachmentStorage()
    {
        $dir = self::attachmentStorageDir();
        if (is_dir($dir)) {
            return is_writable($dir);
        }

        return @mkdir($dir, 0750, true);
    }

    public static function attachmentPath($storedName)
    {
        $storedName = (string) $storedName;
        if (!preg_match('/^[a-f0-9]{40}$/', $storedName)) {
            return '';
        }

        return self::attachmentStorageDir().'/'.$storedName;
    }

    public static function followersForDemand($demandeId)
    {
        return self::rows(
            "SELECT s.login, u.nom, u.prenom
            FROM ".TABLE_PREFIX."_suivi_demande_suiveur s
            LEFT JOIN ".TABLE_PREFIX."_utilisateurs u ON u.login = s.login
            WHERE s.demande_id = ?
            ORDER BY u.nom, u.prenom, s.login",
            "i",
            array((int) $demandeId)
        );
    }

    public static function activeUsersAvailableAsFollowers($demandeId, $creatorLogin, $search = '')
    {
        self::ensureUserConfigTable();
        $search = trim((string) $search);
        if (strlen($search) > 80) {
            $search = substr($search, 0, 80);
        }

        $sql = "SELECT login, nom, prenom
            FROM ".TABLE_PREFIX."_utilisateurs
            WHERE etat != 'inactif'
                AND login <> ?
                AND login NOT IN (
                    SELECT login
                    FROM ".TABLE_PREFIX."_suivi_demande_user_config
                    WHERE enabled = 0
                )
                AND login NOT IN (
                    SELECT login
                    FROM ".TABLE_PREFIX."_suivi_demande_suiveur
                    WHERE demande_id = ?
                )";
        $types = "si";
        $params = array($creatorLogin, (int) $demandeId);

        if ($search !== '') {
            $pattern = '%'.$search.'%';
            $sql .= " AND (login LIKE ? OR nom LIKE ? OR prenom LIKE ?)";
            $types .= "sss";
            $params[] = $pattern;
            $params[] = $pattern;
            $params[] = $pattern;
        }

        $sql .= " ORDER BY nom, prenom, login
            LIMIT ".self::MAX_USER_SELECT;

        return self::rows($sql, $types, $params);
    }

    public static function activeUserExists($login)
    {
        return self::activeUserLogin($login) !== '';
    }

    public static function activeUserLogin($login)
    {
        $login = trim((string) $login);
        if ($login === '' || strlen($login) > 190) {
            return '';
        }

        $rows = self::rows(
            "SELECT login
            FROM ".TABLE_PREFIX."_utilisateurs
            WHERE login = ?
                AND etat != 'inactif'
            LIMIT 1",
            "s",
            array($login)
        );

        if (count($rows) === 0 || !isset($rows[0]['login'])) {
            return '';
        }

        $canonicalLogin = (string) $rows[0]['login'];
        if (!self::userModuleEnabled($canonicalLogin)) {
            return '';
        }

        return $canonicalLogin;
    }

    public static function addFollower($demandeId, $followerLogin, $actorLogin)
    {
        $demandeId = (int) $demandeId;
        if (self::isFollower($demandeId, $followerLogin)) {
            return true;
        }

        $insert = grr_sql_command(
            "INSERT INTO ".TABLE_PREFIX."_suivi_demande_suiveur (demande_id, login) VALUES (?, ?)",
            "is",
            array($demandeId, $followerLogin)
        );

        if ($insert < 0) {
            return false;
        }

        self::updateDemandTimestamp($demandeId);
        self::addHistory($demandeId, $actorLogin, 'suiveur_ajout', 'Suiveur ajoute : '.$followerLogin);
        return true;
    }

    public static function removeFollower($demandeId, $followerLogin, $actorLogin)
    {
        $demandeId = (int) $demandeId;
        if (!self::isFollower($demandeId, $followerLogin)) {
            return true;
        }

        $delete = grr_sql_command(
            "DELETE FROM ".TABLE_PREFIX."_suivi_demande_suiveur WHERE demande_id = ? AND login = ?",
            "is",
            array($demandeId, $followerLogin)
        );

        if ($delete < 0) {
            return false;
        }

        self::updateDemandTimestamp($demandeId);
        self::addHistory($demandeId, $actorLogin, 'suiveur_retrait', 'Suiveur retire : '.$followerLogin);
        return true;
    }

    public static function addHistory($demandeId, $login, $action, $details)
    {
        grr_sql_command(
            "INSERT INTO ".TABLE_PREFIX."_suivi_demande_historique
                (demande_id, auteur, action, details, created_at)
            VALUES (?, ?, ?, ?, ?)",
            "isssi",
            array((int) $demandeId, $login, $action, $details, time())
        );
    }

    private static function demandAttachableByUser($demand, $login)
    {
        $login = trim((string) $login);
        if (!$demand || $login === '' || !isset($demand['id']) || !isset($demand['statut'])) {
            return false;
        }

        $demandeId = (int) $demand['id'];
        if ($demandeId <= 0 || (string) $demand['statut'] === 'cloturee') {
            return false;
        }

        if (self::userIsAdmin($login)) {
            return true;
        }

        if (isset($demand['createur']) && self::sameLogin($demand['createur'], $login)) {
            return true;
        }

        return self::isFollower($demandeId, $login)
            || self::userManagesDemandResource($demandeId, $login);
    }

    private static function userIsAdmin($login)
    {
        return trim((string) $login) !== ''
            && SecuAccess::UserLevel($login, -1) >= 6;
    }

    private static function scalar($sql)
    {
        $value = grr_sql_query1($sql);
        if ($value === -1 || $value === null || $value === '') {
            return 0;
        }

        return (int) $value;
    }

    private static function deleteRowsForDemand($tableSuffix, $demandeId)
    {
        $delete = grr_sql_command(
            "DELETE FROM ".TABLE_PREFIX."_".$tableSuffix." WHERE demande_id = ?",
            "i",
            array((int) $demandeId)
        );

        return $delete >= 0;
    }

    private static function updateDemandTimestamp($demandeId, $timestamp = null)
    {
        $timestamp = $timestamp === null ? time() : (int) $timestamp;
        grr_sql_command(
            "UPDATE ".TABLE_PREFIX."_suivi_demande SET updated_at = ? WHERE id = ?",
            "ii",
            array($timestamp, (int) $demandeId)
        );
    }

    private static function addResourceToDemand($demandeId, $roomId)
    {
        $roomId = (int) $roomId;
        if ($roomId <= 0) {
            return true;
        }

        $insert = grr_sql_command(
            "INSERT IGNORE INTO ".TABLE_PREFIX."_suivi_demande_ressource (demande_id, room_id) VALUES (?, ?)",
            "ii",
            array((int) $demandeId, $roomId)
        );

        return $insert >= 0;
    }

    private static function visibleResourcesWithoutRightFilter()
    {
        $resources = array();
        $multisite = Settings::get("module_multisite") == "Oui";

        if ($multisite) {
            $sql = "SELECT r.id, r.room_name, a.area_name, s.sitename
                FROM ((`".TABLE_PREFIX."_room` r JOIN `".TABLE_PREFIX."_area` a ON r.area_id = a.id)
                JOIN ".TABLE_PREFIX."_j_site_area jsa ON a.id = jsa.id_area)
                JOIN ".TABLE_PREFIX."_site s ON s.id = jsa.id_site
                ORDER BY s.sitename, a.order_display, a.area_name, r.order_display, r.room_name";
        } else {
            $sql = "SELECT r.id, r.room_name, a.area_name
                FROM `".TABLE_PREFIX."_room` r JOIN `".TABLE_PREFIX."_area` a ON r.area_id = a.id
                ORDER BY a.order_display, a.area_name, r.order_display, r.room_name";
        }

        $result = grr_sql_query($sql);
        if (!$result) {
            return $resources;
        }

        for ($i = 0; ($row = grr_sql_row_keyed($result, $i)); $i++) {
            $label = $row['area_name'].' > '.$row['room_name'];
            if ($multisite) {
                $label = $row['sitename'].' > '.$label;
            }

            $resources[] = array(
                'id' => (int) $row['id'],
                'label' => $label,
            );
        }

        return $resources;
    }

    private static function activeUsersWithoutModuleFilter()
    {
        return self::rows(
            "SELECT login, nom, prenom
            FROM ".TABLE_PREFIX."_utilisateurs
            WHERE etat != 'inactif'
            ORDER BY nom, prenom, login"
        );
    }

    private static function normalizeStatisticsFilters($source)
    {
        if (!is_array($source)) {
            $source = array();
        }

        $from = self::normalizeStatisticsDate(
            isset($source['from']) ? $source['from'] : (isset($source['suivi_stats_from']) ? $source['suivi_stats_from'] : ''),
            false
        );
        $to = self::normalizeStatisticsDate(
            isset($source['to']) ? $source['to'] : (isset($source['suivi_stats_to']) ? $source['suivi_stats_to'] : ''),
            true
        );

        $status = isset($source['status']) ? (string) $source['status'] : (isset($source['suivi_stats_status']) ? (string) $source['suivi_stats_status'] : '');
        $statuses = SuiviDemandesConfig::statusDefinitions();
        if ($status !== '' && !isset($statuses[$status])) {
            $status = '';
        }

        $priority = isset($source['priority']) ? (string) $source['priority'] : (isset($source['suivi_stats_priority']) ? (string) $source['suivi_stats_priority'] : '');
        $priorities = SuiviDemandesConfig::priorityDefinitions();
        if ($priority !== '' && !isset($priorities[$priority])) {
            $priority = '';
        }

        $category = SuiviDemandesConfig::categoriesEnabled()
            ? trim((string) (isset($source['category']) ? $source['category'] : (isset($source['suivi_stats_category']) ? $source['suivi_stats_category'] : '')))
            : '';
        if ($category !== '' && $category !== '__none__' && !SuiviDemandesConfig::isValidCategory($category)) {
            $category = '';
        }

        $roomId = isset($source['room_id']) ? (int) $source['room_id'] : (isset($source['suivi_stats_room']) ? (int) $source['suivi_stats_room'] : 0);
        if ($roomId < 0) {
            $roomId = 0;
        }

        $creator = trim((string) (isset($source['creator']) ? $source['creator'] : (isset($source['suivi_stats_creator']) ? $source['suivi_stats_creator'] : '')));
        if (strlen($creator) > 190) {
            $creator = substr($creator, 0, 190);
        }

        return array(
            'from' => $from['value'],
            'from_ts' => $from['timestamp'],
            'to' => $to['value'],
            'to_ts' => $to['timestamp'],
            'status' => $status,
            'priority' => $priority,
            'category' => $category,
            'room_id' => $roomId,
            'creator' => $creator,
        );
    }

    private static function normalizeStatisticsDate($value, $endOfDay)
    {
        $value = trim((string) $value);
        if ($value === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return array('value' => '', 'timestamp' => 0);
        }

        $parts = explode('-', $value);
        $year = (int) $parts[0];
        $month = (int) $parts[1];
        $day = (int) $parts[2];
        if (!checkdate($month, $day, $year)) {
            return array('value' => '', 'timestamp' => 0);
        }

        $hour = $endOfDay ? 23 : 0;
        $minute = $endOfDay ? 59 : 0;
        $second = $endOfDay ? 59 : 0;

        return array('value' => $value, 'timestamp' => mktime($hour, $minute, $second, $month, $day, $year));
    }

    private static function resourcesByDemandForStatistics()
    {
        $resources = array();
        $multisite = Settings::get("module_multisite") == "Oui";

        if ($multisite) {
            $sql = "SELECT dr.demande_id, r.id, r.room_name, a.area_name, s.sitename
                FROM ".TABLE_PREFIX."_suivi_demande_ressource dr
                JOIN ".TABLE_PREFIX."_room r ON r.id = dr.room_id
                JOIN ".TABLE_PREFIX."_area a ON a.id = r.area_id
                JOIN ".TABLE_PREFIX."_j_site_area jsa ON jsa.id_area = a.id
                JOIN ".TABLE_PREFIX."_site s ON s.id = jsa.id_site
                ORDER BY s.sitename, a.order_display, a.area_name, r.order_display, r.room_name";
        } else {
            $sql = "SELECT dr.demande_id, r.id, r.room_name, a.area_name
                FROM ".TABLE_PREFIX."_suivi_demande_ressource dr
                JOIN ".TABLE_PREFIX."_room r ON r.id = dr.room_id
                JOIN ".TABLE_PREFIX."_area a ON a.id = r.area_id
                ORDER BY a.order_display, a.area_name, r.order_display, r.room_name";
        }

        foreach (self::rows($sql) as $row) {
            $demandeId = (int) $row['demande_id'];
            $label = $row['area_name'].' > '.$row['room_name'];
            if ($multisite) {
                $label = $row['sitename'].' > '.$label;
            }
            if (!isset($resources[$demandeId])) {
                $resources[$demandeId] = array();
            }
            $resources[$demandeId][] = array(
                'id' => (int) $row['id'],
                'label' => $label,
            );
        }

        return $resources;
    }

    private static function historyFirstActionTimes($action)
    {
        $times = array();
        $rows = self::rows(
            "SELECT demande_id, MIN(created_at) AS first_at
            FROM ".TABLE_PREFIX."_suivi_demande_historique
            WHERE action = ?
            GROUP BY demande_id",
            "s",
            array($action)
        );

        foreach ($rows as $row) {
            $times[(int) $row['demande_id']] = (int) $row['first_at'];
        }

        return $times;
    }

    private static function historyActionCounts($action)
    {
        $counts = array();
        $rows = self::rows(
            "SELECT demande_id, COUNT(*) AS total
            FROM ".TABLE_PREFIX."_suivi_demande_historique
            WHERE action = ?
            GROUP BY demande_id",
            "s",
            array($action)
        );

        foreach ($rows as $row) {
            $counts[(int) $row['demande_id']] = (int) $row['total'];
        }

        return $counts;
    }

    private static function countsByDemand($tableSuffix)
    {
        $counts = array();
        $rows = self::rows(
            "SELECT demande_id, COUNT(*) AS total
            FROM ".TABLE_PREFIX."_".$tableSuffix."
            GROUP BY demande_id"
        );

        foreach ($rows as $row) {
            $counts[(int) $row['demande_id']] = (int) $row['total'];
        }

        return $counts;
    }

    private static function emptyStatistics($filters)
    {
        $byStatus = array();
        foreach (SuiviDemandesConfig::statusDefinitions() as $status => $label) {
            $byStatus[$status] = 0;
        }

        $byPriority = array();
        foreach (SuiviDemandesConfig::priorityDefinitions() as $priority => $label) {
            $byPriority[$priority] = 0;
        }

        return array(
            'filters' => $filters,
            'total' => 0,
            'closed' => 0,
            'by_status' => $byStatus,
            'by_priority' => $byPriority,
            'by_category' => array(),
            'by_creator' => array(),
            'by_resource' => array(),
            'comments_total' => 0,
            'comments_demands' => 0,
            'attachments_total' => 0,
            'attachments_demands' => 0,
            'reservations_total' => 0,
            'reservations_demands' => 0,
            'followers_total' => 0,
            'followers_demands' => 0,
            'reopened_demands' => 0,
            'reopen_events' => 0,
            'response_time' => self::durationStatistics(array()),
            'closure_time' => self::durationStatistics(array()),
        );
    }

    private static function demandMatchesStatisticsFilters($demand, $filters, $resourcesByDemand)
    {
        $createdAt = isset($demand['created_at']) ? (int) $demand['created_at'] : 0;
        if ((int) $filters['from_ts'] > 0 && $createdAt < (int) $filters['from_ts']) {
            return false;
        }
        if ((int) $filters['to_ts'] > 0 && $createdAt > (int) $filters['to_ts']) {
            return false;
        }
        if ($filters['status'] !== '' && (!isset($demand['statut']) || $demand['statut'] !== $filters['status'])) {
            return false;
        }
        if ($filters['priority'] !== '' && (!isset($demand['priorite']) || $demand['priorite'] !== $filters['priority'])) {
            return false;
        }
        if (SuiviDemandesConfig::categoriesEnabled() && $filters['category'] !== '') {
            $category = isset($demand['categorie']) ? trim((string) $demand['categorie']) : '';
            if ($filters['category'] === '__none__' && $category !== '') {
                return false;
            }
            if ($filters['category'] !== '__none__' && $category !== $filters['category']) {
                return false;
            }
        }
        if ($filters['creator'] !== '' && (!isset($demand['createur']) || $demand['createur'] !== $filters['creator'])) {
            return false;
        }
        if ((int) $filters['room_id'] > 0) {
            $found = false;
            $demandeId = (int) $demand['id'];
            if (isset($resourcesByDemand[$demandeId])) {
                foreach ($resourcesByDemand[$demandeId] as $resource) {
                    if ((int) $resource['id'] === (int) $filters['room_id']) {
                        $found = true;
                        break;
                    }
                }
            }
            if (!$found) {
                return false;
            }
        }

        return true;
    }

    private static function addStatisticsCounter(&$stats, $key, $counts, $demandeId)
    {
        $count = isset($counts[$demandeId]) ? (int) $counts[$demandeId] : 0;
        if ($count <= 0) {
            return;
        }

        $stats[$key.'_total'] += $count;
        $stats[$key.'_demands']++;
    }

    private static function durationStatistics($durations)
    {
        $count = count($durations);
        if ($count === 0) {
            return array('count' => 0, 'average' => 0, 'min' => 0, 'max' => 0);
        }

        return array(
            'count' => $count,
            'average' => (int) round(array_sum($durations) / $count),
            'min' => min($durations),
            'max' => max($durations),
        );
    }

    public static function sortStatisticResource($left, $right)
    {
        if ((int) $left['count'] === (int) $right['count']) {
            return strcmp((string) $left['label'], (string) $right['label']);
        }

        return (int) $left['count'] > (int) $right['count'] ? -1 : 1;
    }

    private static function normalizeRoomIdList($roomIds)
    {
        $ids = array();
        if (!is_array($roomIds)) {
            $roomIds = array($roomIds);
        }

        foreach ($roomIds as $roomId) {
            $roomId = (int) $roomId;
            if ($roomId > 0) {
                $ids[$roomId] = $roomId;
            }
        }

        return $ids;
    }

    private static function normalizeLoginList($logins)
    {
        $normalized = array();
        if (!is_array($logins)) {
            $logins = array($logins);
        }

        foreach ($logins as $login) {
            $login = trim((string) $login);
            if ($login !== '' && strlen($login) <= 190) {
                $normalized[$login] = $login;
            }
        }

        return $normalized;
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

    private static function demandSelectFields($alias, $fields)
    {
        $prefix = $alias === '' ? '' : $alias.'.';
        $select = array();

        foreach (explode(',', $fields) as $field) {
            $field = trim($field);
            if ($field !== '') {
                $select[] = $prefix.$field;
            }
        }

        if (self::ensureCategoryColumn()) {
            $select[] = $prefix.'categorie';
        } else {
            $select[] = "'' AS categorie";
        }

        return implode(', ', $select);
    }

    private static function normalizeDemandFilters($filters)
    {
        if (!is_array($filters)) {
            $filters = array();
        }

        $status = isset($filters['status']) ? (string) $filters['status'] : '';
        $statuses = SuiviDemandesConfig::statusDefinitions();
        if ($status !== '' && !isset($statuses[$status])) {
            $status = '';
        }

        $priority = isset($filters['priority']) ? (string) $filters['priority'] : '';
        $priorities = SuiviDemandesConfig::priorityDefinitions();
        if ($priority !== '' && !isset($priorities[$priority])) {
            $priority = '';
        }

        $category = SuiviDemandesConfig::categoriesEnabled() && isset($filters['category']) ? trim((string) $filters['category']) : '';
        if ($category !== '' && $category !== '__none__' && !SuiviDemandesConfig::isValidCategory($category)) {
            $category = '';
        }

        $search = isset($filters['search']) ? trim((string) $filters['search']) : '';
        if (strlen($search) > 100) {
            $search = substr($search, 0, 100);
        }

        $limit = isset($filters['limit']) ? (int) $filters['limit'] : self::MAX_LIST_ROWS;
        if (!in_array($limit, self::listLimitOptions(), true)) {
            $limit = self::MAX_LIST_ROWS;
        }

        $scope = isset($filters['scope']) ? (string) $filters['scope'] : '';
        if (!in_array($scope, array('', 'created', 'followed'), true)) {
            $scope = '';
        }

        return array(
            'status' => $status,
            'priority' => $priority,
            'category' => $category,
            'search' => $search,
            'limit' => $limit,
            'scope' => $scope,
        );
    }

    private static function hasDemandFilters($filters)
    {
        $filters = self::normalizeDemandFilters($filters);

        return $filters['status'] !== ''
            || $filters['priority'] !== ''
            || $filters['category'] !== ''
            || $filters['search'] !== ''
            || $filters['scope'] !== '';
    }

    private static function demandMatchesFilters($demand, $filters, $login = '')
    {
        $filters = self::normalizeDemandFilters($filters);

        if ($filters['scope'] === 'created' && (!isset($demand['createur']) || !self::sameLogin($demand['createur'], $login))) {
            return false;
        }

        if ($filters['scope'] === 'followed' && ($login === '' || !self::isFollower((int) $demand['id'], $login))) {
            return false;
        }

        if ($filters['status'] !== '' && (!isset($demand['statut']) || $demand['statut'] !== $filters['status'])) {
            return false;
        }

        if ($filters['priority'] !== '' && (!isset($demand['priorite']) || $demand['priorite'] !== $filters['priority'])) {
            return false;
        }

        $category = '';
        if (SuiviDemandesConfig::categoriesEnabled()) {
            $category = isset($demand['categorie']) ? trim((string) $demand['categorie']) : '';
            if ($filters['category'] === '__none__' && $category !== '') {
                return false;
            }
            if ($filters['category'] !== '' && $filters['category'] !== '__none__' && $category !== $filters['category']) {
                return false;
            }
        }

        if ($filters['search'] !== '') {
            $haystack = '#'.(isset($demand['id']) ? $demand['id'] : '').' '
                .(isset($demand['titre']) ? $demand['titre'] : '').' '
                .(isset($demand['createur']) ? $demand['createur'] : '').' '
                .SuiviDemandesConfig::statusLabel(isset($demand['statut']) ? $demand['statut'] : '').' '
                .SuiviDemandesConfig::priorityLabel(isset($demand['priorite']) ? $demand['priorite'] : '');

            if (SuiviDemandesConfig::categoriesEnabled()) {
                $haystack .= ' '.SuiviDemandesConfig::categoryLabel($category);
            }

            if (stripos($haystack, $filters['search']) === false) {
                return false;
            }
        }

        return true;
    }
}
