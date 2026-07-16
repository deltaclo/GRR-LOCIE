<?php

class StagiaireRepository
{
    const DEFAULT_RESERVATION_LIST_LIMIT = 50;
    const MAX_EXPORT_ROWS = 5000;

    public static function ensureTables()
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

    public static function tableExists($suffix)
    {
        $tableName = TABLE_PREFIX.'_'.trim((string) $suffix);
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

    public static function activeUsersWithStagiaireStatus()
    {
        self::ensureTables();

        $stagiaires = array_flip(self::stagiaireLogins());
        $users = self::rows(
            "SELECT login, nom, prenom, email, statut
            FROM ".TABLE_PREFIX."_utilisateurs
            WHERE etat != 'inactif'
            ORDER BY nom, prenom, login"
        );

        foreach ($users as $index => $user) {
            $login = isset($user['login']) ? (string) $user['login'] : '';
            $users[$index]['is_stagiaire'] = isset($stagiaires[$login]);
            $users[$index]['label'] = self::userLabel($user);
        }

        return $users;
    }

    public static function stagiaireLogins()
    {
        self::ensureTables();

        $rows = self::rows(
            "SELECT login
            FROM ".TABLE_PREFIX."_stagiaire_user
            ORDER BY login"
        );

        $logins = array();
        foreach ($rows as $row) {
            $login = isset($row['login']) ? trim((string) $row['login']) : '';
            if ($login !== '') {
                $logins[] = $login;
            }
        }

        return $logins;
    }

    public static function isStagiaire($login)
    {
        $login = trim((string) $login);
        if ($login === '') {
            return false;
        }

        self::ensureTables();
        $count = grr_sql_query1(
            "SELECT COUNT(*) FROM ".TABLE_PREFIX."_stagiaire_user WHERE login = ?",
            "s",
            array($login)
        );

        return (int) $count > 0;
    }

    public static function reservationData($entryId)
    {
        self::ensureTables();

        $entryId = (int) $entryId;
        if ($entryId <= 0) {
            return array();
        }

        $result = grr_sql_query(
            "SELECT entry_id, nom, prenom, email, encadrant, created_by, created_at, updated_at, mail_creation_sent, mail_moderation_sent
            FROM ".TABLE_PREFIX."_stagiaire_reservation
            WHERE entry_id = ?",
            "i",
            array($entryId)
        );
        if (!$result) {
            return array();
        }

        $row = grr_sql_row_keyed($result, 0);
        return $row ? $row : array();
    }

    public static function saveReservationData($entryId, $createdBy, $values)
    {
        self::ensureTables();

        $entryId = (int) $entryId;
        if ($entryId <= 0) {
            return false;
        }

        $values = self::normalizeReservationData($values);
        if ($values['nom'] === '' || $values['prenom'] === '' || $values['email'] === '' || $values['encadrant'] === '') {
            return false;
        }

        $createdBy = substr(trim((string) $createdBy), 0, 190);
        $now = time();
        $exists = grr_sql_query1(
            "SELECT COUNT(*) FROM ".TABLE_PREFIX."_stagiaire_reservation WHERE entry_id = ?",
            "i",
            array($entryId)
        );

        if ((int) $exists > 0) {
            $update = grr_sql_command(
                "UPDATE ".TABLE_PREFIX."_stagiaire_reservation
                SET nom = ?, prenom = ?, email = ?, encadrant = ?, created_by = ?, updated_at = ?
                WHERE entry_id = ?",
                "sssssii",
                array($values['nom'], $values['prenom'], $values['email'], $values['encadrant'], $createdBy, $now, $entryId)
            );

            return !($update === false || $update < 0);
        }

        $insert = grr_sql_command(
            "INSERT INTO ".TABLE_PREFIX."_stagiaire_reservation
            (entry_id, nom, prenom, email, encadrant, created_by, created_at, updated_at, mail_creation_sent, mail_moderation_sent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, 0)",
            "isssssii",
            array($entryId, $values['nom'], $values['prenom'], $values['email'], $values['encadrant'], $createdBy, $now, $now)
        );

        return !($insert === false || $insert < 0);
    }

    public static function saveReservationDataForEntries($entryIds, $createdBy, $values)
    {
        $savedEntryIds = array();
        foreach (self::normalizeEntryIds($entryIds) as $entryId) {
            if (self::saveReservationData($entryId, $createdBy, $values)) {
                $savedEntryIds[] = $entryId;
            }
        }

        return $savedEntryIds;
    }

    public static function markCreationMailSent($entryId)
    {
        self::ensureTables();

        $entryId = (int) $entryId;
        if ($entryId <= 0) {
            return false;
        }

        $update = grr_sql_command(
            "UPDATE ".TABLE_PREFIX."_stagiaire_reservation
            SET mail_creation_sent = 1
            WHERE entry_id = ?",
            "i",
            array($entryId)
        );

        return !($update === false || $update < 0);
    }

    public static function markCreationMailSentForEntries($entryIds)
    {
        $ok = true;
        foreach (self::normalizeEntryIds($entryIds) as $entryId) {
            if (!self::markCreationMailSent($entryId)) {
                $ok = false;
            }
        }

        return $ok;
    }

    public static function markModerationMailSent($entryId)
    {
        self::ensureTables();

        $entryId = (int) $entryId;
        if ($entryId <= 0) {
            return false;
        }

        $update = grr_sql_command(
            "UPDATE ".TABLE_PREFIX."_stagiaire_reservation
            SET mail_moderation_sent = 1
            WHERE entry_id = ?",
            "i",
            array($entryId)
        );

        return !($update === false || $update < 0);
    }

    public static function markModerationMailSentForEntries($entryIds)
    {
        $ok = true;
        foreach (self::normalizeEntryIds($entryIds) as $entryId) {
            if (!self::markModerationMailSent($entryId)) {
                $ok = false;
            }
        }

        return $ok;
    }

    public static function reservationInfo($entryId)
    {
        $entryId = (int) $entryId;
        if ($entryId <= 0) {
            return array();
        }

        $result = grr_sql_query(
            "SELECT e.id, e.name, e.description, e.start_time, e.end_time, e.room_id, e.create_by, e.beneficiaire, e.beneficiaire_ext, e.moderate,
                r.room_name, a.area_name
            FROM ".TABLE_PREFIX."_entry e
            JOIN ".TABLE_PREFIX."_room r ON r.id = e.room_id
            JOIN ".TABLE_PREFIX."_area a ON a.id = r.area_id
            WHERE e.id = ?",
            "i",
            array($entryId)
        );
        if (!$result) {
            return array();
        }

        $row = grr_sql_row_keyed($result, 0);
        return $row ? $row : array();
    }

    public static function reservationFiltersFromRequest($source)
    {
        if (!is_array($source)) {
            $source = array();
        }

        return self::normalizeReservationFilters(array(
            'date_from' => isset($source['stagiaire_date_from']) ? (string) $source['stagiaire_date_from'] : '',
            'date_to' => isset($source['stagiaire_date_to']) ? (string) $source['stagiaire_date_to'] : '',
            'room_id' => isset($source['stagiaire_room_id']) ? (int) $source['stagiaire_room_id'] : 0,
            'login' => isset($source['stagiaire_login']) ? (string) $source['stagiaire_login'] : '',
            'email' => isset($source['stagiaire_email']) ? (string) $source['stagiaire_email'] : '',
            'limit' => isset($source['stagiaire_limit']) ? (int) $source['stagiaire_limit'] : self::DEFAULT_RESERVATION_LIST_LIMIT,
        ));
    }

    public static function reservationListLimitOptions()
    {
        return array(25, 50, 100, 250);
    }

    public static function stagiaireReservations($filters, $export = false)
    {
        self::ensureTables();

        $filters = self::normalizeReservationFilters($filters);
        $clauses = array("e.supprimer = 0");
        $types = "";
        $params = array();

        if ((int) $filters['date_from_ts'] > 0) {
            $clauses[] = "e.start_time >= ?";
            $types .= "i";
            $params[] = (int) $filters['date_from_ts'];
        }

        if ((int) $filters['date_to_ts'] > 0) {
            $clauses[] = "e.start_time <= ?";
            $types .= "i";
            $params[] = (int) $filters['date_to_ts'];
        }

        if ((int) $filters['room_id'] > 0) {
            $clauses[] = "e.room_id = ?";
            $types .= "i";
            $params[] = (int) $filters['room_id'];
        }

        if ($filters['login'] !== '') {
            $clauses[] = "sr.created_by = ?";
            $types .= "s";
            $params[] = $filters['login'];
        }

        if ($filters['email'] !== '') {
            $clauses[] = "sr.email LIKE ?";
            $types .= "s";
            $params[] = "%".$filters['email']."%";
        }

        $limit = $export ? self::MAX_EXPORT_ROWS : (int) $filters['limit'];
        $sql = "SELECT sr.entry_id, sr.nom, sr.prenom, sr.email, sr.encadrant, sr.created_by, sr.created_at, sr.updated_at,
                e.name, e.start_time, e.end_time, e.room_id, e.create_by, e.beneficiaire, e.beneficiaire_ext, e.moderate,
                r.room_name, a.area_name,
                u.nom AS user_nom, u.prenom AS user_prenom, u.email AS user_email
            FROM ".TABLE_PREFIX."_stagiaire_reservation sr
            JOIN ".TABLE_PREFIX."_entry e ON e.id = sr.entry_id
            JOIN ".TABLE_PREFIX."_room r ON r.id = e.room_id
            JOIN ".TABLE_PREFIX."_area a ON a.id = r.area_id
            LEFT JOIN ".TABLE_PREFIX."_utilisateurs u ON u.login = sr.created_by
            WHERE ".implode(" AND ", $clauses)."
            ORDER BY e.start_time DESC, sr.entry_id DESC
            LIMIT ".(int) $limit;

        return self::rows($sql, $types === "" ? null : $types, count($params) === 0 ? null : $params);
    }

    public static function allResourcesForFilter()
    {
        $rows = self::rows(
            "SELECT r.id, r.room_name, a.area_name
            FROM ".TABLE_PREFIX."_room r
            JOIN ".TABLE_PREFIX."_area a ON a.id = r.area_id
            ORDER BY a.order_display, a.area_name, r.order_display, r.room_name"
        );

        foreach ($rows as $index => $row) {
            $area = isset($row['area_name']) ? trim((string) $row['area_name']) : '';
            $room = isset($row['room_name']) ? trim((string) $row['room_name']) : '';
            $rows[$index]['label'] = $area !== '' ? $area.' > '.$room : $room;
        }

        return $rows;
    }

    public static function stagiaireReservationUsersForFilter()
    {
        self::ensureTables();

        $rows = self::rows(
            "SELECT DISTINCT sr.created_by AS login, u.nom, u.prenom, u.email
            FROM ".TABLE_PREFIX."_stagiaire_reservation sr
            LEFT JOIN ".TABLE_PREFIX."_utilisateurs u ON u.login = sr.created_by
            WHERE sr.created_by <> ''
            ORDER BY u.nom, u.prenom, sr.created_by"
        );

        foreach ($rows as $index => $row) {
            $rows[$index]['label'] = self::userLabel(array(
                'login' => isset($row['login']) ? $row['login'] : '',
                'nom' => isset($row['nom']) ? $row['nom'] : '',
                'prenom' => isset($row['prenom']) ? $row['prenom'] : '',
                'email' => isset($row['email']) ? $row['email'] : '',
            ));
        }

        return $rows;
    }

    public static function setStagiaireLogins($logins, $createdBy)
    {
        self::ensureTables();

        $validLogins = self::validUserLogins($logins);
        $delete = grr_sql_command("DELETE FROM ".TABLE_PREFIX."_stagiaire_user");
        if ($delete === false || $delete < 0) {
            return false;
        }

        $now = time();
        foreach ($validLogins as $login) {
            $insert = grr_sql_command(
                "INSERT INTO ".TABLE_PREFIX."_stagiaire_user (login, created_by, created_at)
                VALUES (?, ?, ?)",
                "ssi",
                array($login, $createdBy, $now)
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
            $login = trim((string) $login);
            if ($login !== '' && strlen($login) <= 190) {
                $normalized[$login] = $login;
            }
        }

        return array_values($normalized);
    }

    public static function normalizeEntryIds($entryIds)
    {
        if (!is_array($entryIds)) {
            $entryIds = array($entryIds);
        }

        $normalized = array();
        foreach ($entryIds as $entryId) {
            $entryId = (int) $entryId;
            if ($entryId > 0) {
                $normalized[$entryId] = $entryId;
            }
        }

        return array_values($normalized);
    }

    private static function normalizeReservationFilters($filters)
    {
        if (!is_array($filters)) {
            $filters = array();
        }

        $dateFrom = isset($filters['date_from']) ? trim((string) $filters['date_from']) : '';
        $dateTo = isset($filters['date_to']) ? trim((string) $filters['date_to']) : '';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $dateFrom = '';
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $dateTo = '';
        }

        $dateFromTs = $dateFrom === '' ? 0 : strtotime($dateFrom.' 00:00:00');
        $dateToTs = $dateTo === '' ? 0 : strtotime($dateTo.' 23:59:59');
        if ($dateFromTs === false) {
            $dateFromTs = 0;
            $dateFrom = '';
        }
        if ($dateToTs === false) {
            $dateToTs = 0;
            $dateTo = '';
        }

        $roomId = isset($filters['room_id']) ? (int) $filters['room_id'] : 0;
        if ($roomId < 0) {
            $roomId = 0;
        }

        $login = isset($filters['login']) ? trim((string) $filters['login']) : '';
        if (strlen($login) > 190) {
            $login = substr($login, 0, 190);
        }

        $email = isset($filters['email']) ? trim((string) $filters['email']) : '';
        if (strlen($email) > 190) {
            $email = substr($email, 0, 190);
        }

        $limit = isset($filters['limit']) ? (int) $filters['limit'] : self::DEFAULT_RESERVATION_LIST_LIMIT;
        if (!in_array($limit, self::reservationListLimitOptions(), true)) {
            $limit = self::DEFAULT_RESERVATION_LIST_LIMIT;
        }

        return array(
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'date_from_ts' => (int) $dateFromTs,
            'date_to_ts' => (int) $dateToTs,
            'room_id' => $roomId,
            'login' => $login,
            'email' => $email,
            'limit' => $limit,
        );
    }

    private static function normalizeReservationData($values)
    {
        if (!is_array($values)) {
            $values = array();
        }

        return array(
            'nom' => substr(trim(isset($values['nom']) ? (string) $values['nom'] : ''), 0, 100),
            'prenom' => substr(trim(isset($values['prenom']) ? (string) $values['prenom'] : ''), 0, 100),
            'email' => substr(trim(isset($values['email']) ? (string) $values['email'] : ''), 0, 190),
            'encadrant' => substr(trim(isset($values['encadrant']) ? (string) $values['encadrant'] : ''), 0, 190),
        );
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

        $label = implode(' ', $parts);
        $login = isset($user['login']) ? (string) $user['login'] : '';
        if ($label === '') {
            $label = $login;
        } else {
            $label .= ' ('.$login.')';
        }

        if (isset($user['email']) && trim((string) $user['email']) !== '') {
            $label .= ' - '.trim((string) $user['email']);
        }

        return $label;
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
}
