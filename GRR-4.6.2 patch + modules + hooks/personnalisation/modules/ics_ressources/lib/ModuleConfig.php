<?php

class GrrIcsConfig
{
    const MODULE = 'ics_ressources';

    public static function get($name, $default = '')
    {
        $value = Settings::get('ics_ressources_'.$name);
        return ($value === null || $value === '') ? $default : $value;
    }

    public static function set($name, $value)
    {
        return Settings::set('ics_ressources_'.$name, (string) $value);
    }

    public static function isEnabled()
    {
        return self::get('enabled', '1') === '1';
    }

    public static function privacy()
    {
        $privacy = self::get('privacy', 'busy');
        return in_array($privacy, array('busy', 'title', 'full'), true) ? $privacy : 'busy';
    }

    public static function pastDays()
    {
        return max(0, (int) self::get('past_days', '30'));
    }

    public static function futureDays()
    {
        return max(1, (int) self::get('future_days', '365'));
    }

    public static function includeModerated()
    {
        return self::get('include_moderated', '0') === '1';
    }

    public static function includeOption()
    {
        return self::get('include_option', '0') === '1';
    }

    public static function includeInactiveRooms()
    {
        return self::get('include_inactive_rooms', '0') === '1';
    }

    public static function disabledRooms()
    {
        $raw = self::get('disabled_rooms', '');
        if ($raw === '') {
            return array();
        }

        return array_filter(array_map('intval', explode(',', $raw)));
    }

    public static function setEnabledRooms($roomIds)
    {
        $roomIds = array_map('intval', (array) $roomIds);
        $rooms = self::rooms();
        $disabled = array();

        foreach ($rooms as $room) {
            if (!in_array((int) $room['id'], $roomIds, true)) {
                $disabled[] = (int) $room['id'];
            }
        }

        self::set('disabled_rooms', implode(',', $disabled));
    }

    public static function isRoomEnabled($roomId)
    {
        return !in_array((int) $roomId, self::disabledRooms(), true);
    }

    public static function setRoomEnabled($roomId, $enabled)
    {
        $roomId = (int) $roomId;
        $disabled = self::disabledRooms();

        if ($enabled) {
            $disabled = array_diff($disabled, array($roomId));
        } elseif (!in_array($roomId, $disabled, true)) {
            $disabled[] = $roomId;
        }

        $disabled = array_values(array_unique(array_map('intval', $disabled)));
        sort($disabled);

        self::set('disabled_rooms', implode(',', $disabled));
    }

    public static function secret()
    {
        $secret = self::get('secret', '');
        if ($secret === '') {
            $secret = self::regenerateSecret();
        }

        return $secret;
    }

    public static function regenerateSecret()
    {
        $secret = bin2hex(random_bytes(32));
        self::set('secret', $secret);
        return $secret;
    }

    public static function tokenForRoom($roomId)
    {
        return hash_hmac('sha256', (string) (int) $roomId, self::secret());
    }

    public static function isValidToken($roomId, $token)
    {
        return is_string($token) && hash_equals(self::tokenForRoom($roomId), $token);
    }

    public static function rooms()
    {
        $rooms = array();
        $sql = "SELECT r.id, r.room_name, r.statut_room, a.area_name
            FROM ".TABLE_PREFIX."_room r
            JOIN ".TABLE_PREFIX."_area a ON a.id = r.area_id
            ORDER BY a.order_display, a.area_name, r.order_display, r.room_name";
        $res = grr_sql_query($sql);

        if ($res) {
            for ($i = 0; ($row = grr_sql_row_keyed($res, $i)); $i++) {
                $rooms[] = $row;
            }
        }

        return $rooms;
    }

    public static function moduleBaseUrl()
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        $scheme = $https ? 'https' : 'http';
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        $scriptName = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', $_SERVER['SCRIPT_NAME']) : '';
        $modulePath = '/personnalisation/modules/'.self::MODULE;
        $modulePos = strpos($scriptName, $modulePath);

        if ($modulePos !== false) {
            $dir = substr($scriptName, 0, $modulePos).$modulePath;
        } else {
            $dir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
            if (substr($dir, -6) === '/admin') {
                $dir = dirname($dir);
            }
            $dir = rtrim($dir, '/').$modulePath;
        }

        return $scheme.'://'.$host.$dir;
    }

    public static function feedUrl($roomId, $baseUrl = null)
    {
        $baseUrl = $baseUrl ?: self::moduleBaseUrl();
        return rtrim($baseUrl, '/').'/feed.php?room='.(int) $roomId.'&token='.self::tokenForRoom($roomId);
    }
}
