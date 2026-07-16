<?php

class GrrIcsFeed
{
    public static function output($roomId, $token)
    {
        $roomId = (int) $roomId;

        if ($roomId <= 0) {
            self::status(400, 'Ressource invalide');
        }

        if (!GrrIcsConfig::isEnabled()) {
            self::status(403, 'Feeds ICS desactives');
        }

        if (!GrrIcsConfig::isValidToken($roomId, $token)) {
            self::status(403, 'Token invalide');
        }

        if (!GrrIcsConfig::isRoomEnabled($roomId)) {
            self::status(403, 'Feed desactive pour cette ressource');
        }

        $room = self::room($roomId);
        if (!$room) {
            self::status(404, 'Ressource introuvable');
        }

        if (!GrrIcsConfig::includeInactiveRooms() && (string) $room['statut_room'] !== '1') {
            self::status(403, 'Ressource inactive');
        }

        $rangeStart = time() - (GrrIcsConfig::pastDays() * 86400);
        $rangeEnd = time() + (GrrIcsConfig::futureDays() * 86400);
        $entries = self::entries($roomId, $rangeStart, $rangeEnd);
        $calendar = self::calendar($room, $entries);
        $fileName = 'grr-ressource-'.$roomId.'.ics';

        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: inline; filename="'.$fileName.'"');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        echo $calendar;
        exit;
    }

    private static function room($roomId)
    {
        $sql = "SELECT r.id, r.room_name, r.statut_room, a.area_name
            FROM ".TABLE_PREFIX."_room r
            JOIN ".TABLE_PREFIX."_area a ON a.id = r.area_id
            WHERE r.id = '".(int) $roomId."'";
        $res = grr_sql_query($sql);

        if ($res && grr_sql_count($res) === 1) {
            return grr_sql_row_keyed($res, 0);
        }

        return null;
    }

    private static function entries($roomId, $rangeStart, $rangeEnd)
    {
        $where = array(
            "e.room_id = '".(int) $roomId."'",
            "e.supprimer = 0",
            "e.end_time > e.start_time",
            "e.end_time >= '".(int) $rangeStart."'",
            "e.start_time <= '".(int) $rangeEnd."'"
        );

        if (!GrrIcsConfig::includeOption()) {
            $where[] = "e.option_reservation <= 0";
        }

        if (!GrrIcsConfig::includeModerated()) {
            $where[] = "e.moderate = 0";
        }

        $entries = array();
        $sql = "SELECT e.id, e.start_time, e.end_time, e.name, e.description, e.beneficiaire, e.beneficiaire_ext, e.create_by, e.timestamp, e.moderate, e.option_reservation
            FROM ".TABLE_PREFIX."_entry e
            WHERE ".implode(' AND ', $where)."
            ORDER BY e.start_time, e.end_time, e.id";
        $res = grr_sql_query($sql);

        if ($res) {
            for ($i = 0; ($row = grr_sql_row_keyed($res, $i)); $i++) {
                $entries[] = $row;
            }
        }

        return $entries;
    }

    private static function calendar($room, $entries)
    {
        $lines = array(
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//GRR//ICS Ressources 1.0.3//FR',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-TIMEZONE:UTC',
            'X-WR-CALNAME:'.self::escapeText('GRR - '.$room['room_name']),
            'X-WR-CALDESC:'.self::escapeText('Reservations GRR pour '.$room['room_name']),
        );

        foreach ($entries as $entry) {
            $lines = array_merge($lines, self::event($room, $entry));
        }

        $lines[] = 'END:VCALENDAR';

        return self::join($lines);
    }

    private static function event($room, $entry)
    {
        $summary = 'Occupe';
        $description = '';
        $privacy = GrrIcsConfig::privacy();

        if ($privacy === 'title' || $privacy === 'full') {
            $summary = $entry['name'] !== '' ? $entry['name'] : 'Reservation';
        }

        if ($privacy === 'full') {
            $descriptionParts = array();
            if ($entry['description'] !== '') {
                $descriptionParts[] = $entry['description'];
            }
            if ($entry['beneficiaire'] !== '') {
                $descriptionParts[] = 'Beneficiaire: '.$entry['beneficiaire'];
            } elseif ($entry['beneficiaire_ext'] !== '') {
                $descriptionParts[] = 'Beneficiaire externe: '.$entry['beneficiaire_ext'];
            }
            if ($entry['create_by'] !== '') {
                $descriptionParts[] = 'Cree par: '.$entry['create_by'];
            }
            $description = implode("\n", $descriptionParts);
        }

        $modified = self::sqlDateUtc($entry['timestamp']);
        $status = ((int) $entry['moderate'] === 1 || (int) $entry['option_reservation'] > 0) ? 'TENTATIVE' : 'CONFIRMED';
        $lines = array(
            'BEGIN:VEVENT',
            'UID:'.self::uid($room, $entry),
            'DTSTAMP:'.$modified,
            'DTSTART:'.gmdate('Ymd\THis\Z', (int) $entry['start_time']),
            'DTEND:'.gmdate('Ymd\THis\Z', (int) $entry['end_time']),
            'SUMMARY:'.self::escapeText($summary),
            'LOCATION:'.self::escapeText($room['room_name']),
            'STATUS:'.$status,
            'TRANSP:OPAQUE',
            'LAST-MODIFIED:'.$modified
        );

        if ($description !== '') {
            $lines[] = 'DESCRIPTION:'.self::escapeText($description);
        }

        $lines[] = 'END:VEVENT';

        return $lines;
    }

    private static function escapeText($value)
    {
        $value = self::cleanText($value);
        $value = str_replace('\\', '\\\\', $value);
        $value = str_replace(array("\r\n", "\r", "\n"), '\n', $value);
        return str_replace(array(';', ','), array('\;', '\,'), $value);
    }

    private static function cleanText($value)
    {
        $value = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = strip_tags($value);
        $value = str_replace("\xc2\xa0", ' ', $value);
        $value = preg_replace('/[^\P{C}\t\r\n]/u', '', $value);

        return $value === null ? '' : trim($value);
    }

    private static function sqlDateUtc($value)
    {
        $time = strtotime((string) $value);
        if ($time === false) {
            $time = time();
        }

        return gmdate('Ymd\THis\Z', $time);
    }

    private static function uid($room, $entry)
    {
        return 'grr-room-'.(int) $room['id'].'-entry-'.(int) $entry['id'].'@'.self::uidHost();
    }

    private static function uidHost()
    {
        $source = (string) Settings::get('grr_url');
        if ($source === '' && isset($_SERVER['HTTP_HOST'])) {
            $source = (string) $_SERVER['HTTP_HOST'];
        }

        $host = parse_url($source, PHP_URL_HOST);
        if ($host === null || $host === false || $host === '') {
            $host = preg_replace('/[^A-Za-z0-9.-]/', '', $source);
        }

        return $host !== '' ? $host : 'grr.local';
    }

    private static function join($lines)
    {
        $folded = array();
        foreach ($lines as $line) {
            $folded[] = self::fold($line);
        }

        return implode("\r\n", $folded)."\r\n";
    }

    private static function fold($line)
    {
        if (strlen($line) <= 75) {
            return $line;
        }

        $chars = preg_split('//u', $line, -1, PREG_SPLIT_NO_EMPTY);
        if ($chars === false) {
            return self::foldBytes($line);
        }

        $lines = array();
        $current = '';
        $limit = 75;
        foreach ($chars as $char) {
            if ($current !== '' && strlen($current.$char) > $limit) {
                $lines[] = $current;
                $current = $char;
                $limit = 74;
            } else {
                $current .= $char;
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return implode("\r\n ", $lines);
    }

    private static function foldBytes($line)
    {
        $lines = array();
        $limit = 75;
        while (strlen($line) > $limit) {
            $lines[] = substr($line, 0, $limit);
            $line = substr($line, $limit);
            $limit = 74;
        }

        if ($line !== '') {
            $lines[] = $line;
        }

        return implode("\r\n ", $lines);
    }

    private static function status($code, $message)
    {
        http_response_code($code);
        header('Content-Type: text/plain; charset=utf-8');
        echo $message;
        exit;
    }
}
