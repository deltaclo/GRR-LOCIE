<?php

class BoutonsPersoRepository
{
    const TABLE_BUTTON = 'boutons_perso_button';
    const SOURCE_CUSTOM = 'custom';
    const SOURCE_MODULE = 'module';

    public static function ensureTables()
    {
        grr_sql_command("CREATE TABLE IF NOT EXISTS `".self::table(self::TABLE_BUTTON)."` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `source_type` varchar(20) NOT NULL DEFAULT 'custom',
            `source_key` varchar(80) DEFAULT NULL,
            `label` varchar(120) NOT NULL DEFAULT '',
            `url` varchar(500) NOT NULL DEFAULT '',
            `target_mode` varchar(20) NOT NULL DEFAULT 'current',
            `button_style` varchar(30) NOT NULL DEFAULT 'default',
            `custom_bg_color` varchar(7) NOT NULL DEFAULT '',
            `custom_text_color` varchar(7) NOT NULL DEFAULT '',
            `window_width` int(11) NOT NULL DEFAULT 1000,
            `window_height` int(11) NOT NULL DEFAULT 700,
            `window_name` varchar(80) NOT NULL DEFAULT '',
            `confirm_message` varchar(190) NOT NULL DEFAULT '',
            `position_order` int(11) NOT NULL DEFAULT 0,
            `active` tinyint(1) NOT NULL DEFAULT 1,
            `account_menu_active` tinyint(1) NOT NULL DEFAULT 1,
            `account_position_order` int(11) NOT NULL DEFAULT 0,
            `tooltip` varchar(190) NOT NULL DEFAULT '',
            `created_at` int(11) NOT NULL DEFAULT 0,
            `updated_at` int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            KEY `active` (`active`),
            KEY `position_order` (`position_order`),
            UNIQUE KEY `source_button` (`source_type`, `source_key`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8");

        self::ensureColumn(self::TABLE_BUTTON, 'source_type', "`source_type` varchar(20) NOT NULL DEFAULT 'custom' AFTER `id`");
        self::ensureColumn(self::TABLE_BUTTON, 'source_key', "`source_key` varchar(80) DEFAULT NULL AFTER `source_type`");
        self::ensureColumn(self::TABLE_BUTTON, 'button_style', "`button_style` varchar(30) NOT NULL DEFAULT 'default' AFTER `target_mode`");
        self::ensureColumn(self::TABLE_BUTTON, 'custom_bg_color', "`custom_bg_color` varchar(7) NOT NULL DEFAULT '' AFTER `button_style`");
        self::ensureColumn(self::TABLE_BUTTON, 'custom_text_color', "`custom_text_color` varchar(7) NOT NULL DEFAULT '' AFTER `custom_bg_color`");
        self::ensureColumn(self::TABLE_BUTTON, 'window_width', "`window_width` int(11) NOT NULL DEFAULT 1000 AFTER `custom_text_color`");
        self::ensureColumn(self::TABLE_BUTTON, 'window_height', "`window_height` int(11) NOT NULL DEFAULT 700 AFTER `window_width`");
        self::ensureColumn(self::TABLE_BUTTON, 'window_name', "`window_name` varchar(80) NOT NULL DEFAULT '' AFTER `window_height`");
        self::ensureColumn(self::TABLE_BUTTON, 'confirm_message', "`confirm_message` varchar(190) NOT NULL DEFAULT '' AFTER `window_name`");
        self::ensureAccountMenuColumns();
        self::ensureIndex(
            self::TABLE_BUTTON,
            'source_button',
            "UNIQUE KEY `source_button` (`source_type`, `source_key`)"
        );
        if (
            self::columnExists(self::TABLE_BUTTON, 'source_type')
            && self::columnExists(self::TABLE_BUTTON, 'source_key')
            && self::indexExists(self::TABLE_BUTTON, 'source_button')
        ) {
            self::ensureModuleButtons();
        }
    }

    public static function expectedTables()
    {
        return array(
            self::TABLE_BUTTON => 'Boutons personnalises et boutons de module',
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
        $diagnostics[] = array(
            'label' => 'Colonne source_type',
            'table' => self::table(self::TABLE_BUTTON).'.source_type',
            'exists' => self::columnExists(self::TABLE_BUTTON, 'source_type'),
        );
        $diagnostics[] = array(
            'label' => 'Colonne source_key',
            'table' => self::table(self::TABLE_BUTTON).'.source_key',
            'exists' => self::columnExists(self::TABLE_BUTTON, 'source_key'),
        );
        $diagnostics[] = array(
            'label' => 'Index unique source_button',
            'table' => self::table(self::TABLE_BUTTON).'.source_button',
            'exists' => self::indexExists(self::TABLE_BUTTON, 'source_button'),
        );
        $diagnostics[] = array(
            'label' => 'Colonne account_menu_active',
            'table' => self::table(self::TABLE_BUTTON).'.account_menu_active',
            'exists' => self::columnExists(self::TABLE_BUTTON, 'account_menu_active'),
        );
        $diagnostics[] = array(
            'label' => 'Colonne account_position_order',
            'table' => self::table(self::TABLE_BUTTON).'.account_position_order',
            'exists' => self::columnExists(self::TABLE_BUTTON, 'account_position_order'),
        );

        return $diagnostics;
    }

    public static function emptyButtonValues()
    {
        return array(
            'label' => '',
            'url' => '',
            'target_mode' => 'current',
            'button_style' => 'default',
            'custom_bg_color' => '',
            'custom_text_color' => '',
            'window_width' => '1000',
            'window_height' => '700',
            'window_name' => '',
            'confirm_message' => '',
            'position_order' => '0',
            'active' => '1',
            'account_menu_active' => '1',
            'account_position_order' => '0',
            'tooltip' => '',
        );
    }

    public static function normalizeButtonValues($source)
    {
        if (!is_array($source)) {
            $source = array();
        }

        $values = self::emptyButtonValues();
        foreach ($values as $key => $value) {
            if (isset($source[$key])) {
                $values[$key] = trim((string) $source[$key]);
            }
        }

        $values['label'] = self::limit($values['label'], 120);
        $values['url'] = self::limit($values['url'], 500);
        $values['tooltip'] = self::limit($values['tooltip'], 190);
        $values['window_name'] = self::limit($values['window_name'], 80);
        $values['confirm_message'] = self::limit($values['confirm_message'], 190);
        $values['position_order'] = (string) max(0, (int) $values['position_order']);
        $values['active'] = isset($source['active']) ? '1' : '0';

        if (!in_array($values['target_mode'], array_keys(BoutonsPersoConfig::targetModes()), true)) {
            $values['target_mode'] = 'current';
        }

        if (!in_array($values['button_style'], array_keys(BoutonsPersoConfig::buttonStyles()), true)) {
            $values['button_style'] = 'default';
        }

        $values['custom_bg_color'] = BoutonsPersoConfig::normalizeColor($values['custom_bg_color'], '');
        $values['custom_text_color'] = BoutonsPersoConfig::normalizeColor($values['custom_text_color'], '');
        $values['window_width'] = (string) self::normalizeWindowSize($values['window_width'], 1000, 300, 2400);
        $values['window_height'] = (string) self::normalizeWindowSize($values['window_height'], 700, 300, 1600);

        return $values;
    }

    public static function validateButtonValues($values)
    {
        $errors = array();
        if (!is_array($values)) {
            $errors[] = 'Donnees invalides.';
            return $errors;
        }

        if (trim((string) $values['label']) === '') {
            $errors[] = 'Le libelle du bouton est obligatoire.';
        }

        if (trim((string) $values['url']) === '') {
            $errors[] = 'L URL du bouton est obligatoire.';
        } elseif (!self::isAllowedUrl($values['url'])) {
            $errors[] = 'L URL doit etre relative a GRR, absolue sur le meme site, ou commencer par http:// ou https://.';
        }

        if ($values['button_style'] === 'custom') {
            if ($values['custom_bg_color'] === '') {
                $errors[] = 'La couleur de fond personnalisee est obligatoire avec le style personnalise.';
            }
            if ($values['custom_text_color'] === '') {
                $errors[] = 'La couleur du texte personnalisee est obligatoire avec le style personnalise.';
            }
        }

        if ((int) $values['window_width'] < 300 || (int) $values['window_width'] > 2400) {
            $errors[] = 'La largeur de fenetre doit etre comprise entre 300 et 2400 pixels.';
        }

        if ((int) $values['window_height'] < 300 || (int) $values['window_height'] > 1600) {
            $errors[] = 'La hauteur de fenetre doit etre comprise entre 300 et 1600 pixels.';
        }

        return $errors;
    }

    public static function createButton($values)
    {
        self::ensureTables();
        $values = self::normalizeButtonValues($values);
        if (count(self::validateButtonValues($values)) > 0) {
            return false;
        }

        $now = time();
        $insert = grr_sql_command(
            "INSERT INTO ".self::table(self::TABLE_BUTTON)."
            (source_type, source_key, label, url, target_mode, button_style, custom_bg_color, custom_text_color, window_width, window_height, window_name, confirm_message, position_order, active, tooltip, created_at, updated_at)
            VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            "sssssssiissiisii",
            array(
                self::SOURCE_CUSTOM,
                $values['label'],
                $values['url'],
                $values['target_mode'],
                $values['button_style'],
                $values['custom_bg_color'],
                $values['custom_text_color'],
                (int) $values['window_width'],
                (int) $values['window_height'],
                $values['window_name'],
                $values['confirm_message'],
                (int) $values['position_order'],
                (int) $values['active'],
                $values['tooltip'],
                $now,
                $now,
            )
        );

        return !($insert === false || $insert < 0);
    }

    public static function updateButton($id, $values)
    {
        self::ensureTables();
        $id = (int) $id;
        if ($id <= 0 || !self::button($id, true)) {
            return false;
        }

        $values = self::normalizeButtonValues($values);
        if (count(self::validateButtonValues($values)) > 0) {
            return false;
        }

        $update = grr_sql_command(
            "UPDATE ".self::table(self::TABLE_BUTTON)."
            SET label = ?, url = ?, target_mode = ?, button_style = ?, custom_bg_color = ?, custom_text_color = ?,
                window_width = ?, window_height = ?, window_name = ?, confirm_message = ?,
                position_order = ?, active = ?, tooltip = ?, updated_at = ?
            WHERE id = ? AND source_type = ?",
            "ssssssiissiisiis",
            array(
                $values['label'],
                $values['url'],
                $values['target_mode'],
                $values['button_style'],
                $values['custom_bg_color'],
                $values['custom_text_color'],
                (int) $values['window_width'],
                (int) $values['window_height'],
                $values['window_name'],
                $values['confirm_message'],
                (int) $values['position_order'],
                (int) $values['active'],
                $values['tooltip'],
                time(),
                $id,
                self::SOURCE_CUSTOM,
            )
        );

        return !($update === false || $update < 0);
    }

    public static function deleteButton($id)
    {
        self::ensureTables();
        $id = (int) $id;
        if ($id <= 0 || !self::button($id, true)) {
            return false;
        }

        $delete = grr_sql_command(
            "DELETE FROM ".self::table(self::TABLE_BUTTON)." WHERE id = ? AND source_type = ?",
            "is",
            array($id, self::SOURCE_CUSTOM)
        );

        return !($delete === false || $delete < 0);
    }

    public static function button($id, $includeInactive = false)
    {
        self::ensureTables();
        $id = (int) $id;
        if ($id <= 0) {
            return array();
        }

        $whereActive = $includeInactive ? '' : ' AND active = 1';
        $result = grr_sql_query(
            "SELECT id, source_type, source_key, label, url, target_mode, button_style, custom_bg_color, custom_text_color,
                window_width, window_height, window_name, confirm_message,
                position_order, active, account_menu_active, account_position_order, tooltip, created_at, updated_at
            FROM ".self::table(self::TABLE_BUTTON)."
            WHERE id = ? AND source_type = ?".$whereActive,
            "is",
            array($id, self::SOURCE_CUSTOM)
        );
        if (!$result) {
            return array();
        }

        $row = grr_sql_row_keyed($result, 0);
        return $row ? $row : array();
    }

    public static function buttons($includeInactive = false)
    {
        self::ensureTables();

        $whereActive = $includeInactive ? '' : ' AND active = 1';
        return self::rows(
            "SELECT id, source_type, source_key, label, url, target_mode, button_style, custom_bg_color, custom_text_color,
                window_width, window_height, window_name, confirm_message,
                position_order, active, account_menu_active, account_position_order, tooltip, created_at, updated_at
            FROM ".self::table(self::TABLE_BUTTON)."
            WHERE source_type = '".self::SOURCE_CUSTOM."'".$whereActive."
            ORDER BY position_order, label, id"
        );
    }

    public static function allButtons($includeInactive = false)
    {
        self::ensureTables();

        $whereActive = $includeInactive ? '' : 'WHERE active = 1';
        return self::rows(
            "SELECT id, source_type, source_key, label, url, target_mode, button_style, custom_bg_color, custom_text_color,
                window_width, window_height, window_name, confirm_message,
                position_order, active, account_menu_active, account_position_order, tooltip, created_at, updated_at
            FROM ".self::table(self::TABLE_BUTTON)."
            ".$whereActive."
            ORDER BY position_order, label, id"
        );
    }

    public static function moduleButton($sourceKey, $includeInactive = true)
    {
        self::ensureTables();
        $sourceKey = self::limit($sourceKey, 80);
        if ($sourceKey === '') {
            return array();
        }

        $whereActive = $includeInactive ? '' : ' AND active = 1';
        $result = grr_sql_query(
            "SELECT id, source_type, source_key, label, url, target_mode, button_style, custom_bg_color, custom_text_color,
                window_width, window_height, window_name, confirm_message,
                position_order, active, account_menu_active, account_position_order, tooltip, created_at, updated_at
            FROM ".self::table(self::TABLE_BUTTON)."
            WHERE source_type = ? AND source_key = ?".$whereActive,
            "ss",
            array(self::SOURCE_MODULE, $sourceKey)
        );
        if (!$result) {
            return array();
        }

        $row = grr_sql_row_keyed($result, 0);
        return $row ? $row : array();
    }

    public static function accountMenuModuleButtons($includeInactive = false)
    {
        self::ensureTables();

        $whereActive = $includeInactive ? '' : ' AND account_menu_active = 1';
        return self::rows(
            "SELECT id, source_type, source_key, label, url, target_mode, button_style, custom_bg_color, custom_text_color,
                window_width, window_height, window_name, confirm_message,
                position_order, active, account_menu_active, account_position_order, tooltip, created_at, updated_at
            FROM ".self::table(self::TABLE_BUTTON)."
            WHERE source_type = '".self::SOURCE_MODULE."'".$whereActive."
            ORDER BY account_position_order, label, id"
        );
    }

    public static function normalizeModuleButtonValues($source, $current)
    {
        if (!is_array($source)) {
            $source = array();
        }
        if (!is_array($current)) {
            $current = array();
        }

        $merged = $current;
        foreach (array(
            'target_mode',
            'button_style',
            'custom_bg_color',
            'custom_text_color',
            'window_width',
            'window_height',
            'window_name',
            'confirm_message',
            'position_order',
            'account_position_order',
            'tooltip',
        ) as $key) {
            if (isset($source[$key])) {
                $merged[$key] = $source[$key];
            }
        }

        $values = self::normalizeButtonValues($merged);
        $values['active'] = isset($source['active']) && (string) $source['active'] !== '0' ? '1' : '0';
        $values['account_menu_active'] = isset($source['account_menu_active']) && (string) $source['account_menu_active'] !== '0' ? '1' : '0';
        $values['account_position_order'] = (string) max(0, (int) $values['account_position_order']);

        return $values;
    }

    public static function validateModuleButtonValues($values)
    {
        return self::validateButtonValues($values);
    }

    public static function updateModuleButton($sourceKey, $values)
    {
        self::ensureTables();
        $sourceKey = self::limit($sourceKey, 80);
        $current = self::moduleButton($sourceKey, true);
        if ($sourceKey === '' || !$current) {
            return false;
        }

        $values = self::normalizeModuleButtonValues($values, $current);
        if (count(self::validateModuleButtonValues($values)) > 0) {
            return false;
        }

        $update = grr_sql_command(
            "UPDATE ".self::table(self::TABLE_BUTTON)."
            SET target_mode = ?, button_style = ?, custom_bg_color = ?, custom_text_color = ?,
                window_width = ?, window_height = ?, window_name = ?, confirm_message = ?,
                position_order = ?, active = ?, account_menu_active = ?, account_position_order = ?,
                tooltip = ?, updated_at = ?
            WHERE source_type = ? AND source_key = ?",
            "ssssiissiiiisiss",
            array(
                $values['target_mode'],
                $values['button_style'],
                $values['custom_bg_color'],
                $values['custom_text_color'],
                (int) $values['window_width'],
                (int) $values['window_height'],
                $values['window_name'],
                $values['confirm_message'],
                (int) $values['position_order'],
                (int) $values['active'],
                (int) $values['account_menu_active'],
                (int) $values['account_position_order'],
                $values['tooltip'],
                time(),
                self::SOURCE_MODULE,
                $sourceKey,
            )
        );

        return !($update === false || $update < 0);
    }

    public static function countButtons()
    {
        self::ensureTables();
        return (int) grr_sql_query1(
            "SELECT COUNT(*) FROM ".self::table(self::TABLE_BUTTON)." WHERE source_type = ?",
            "s",
            array(self::SOURCE_CUSTOM)
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

    public static function indexExists($suffix, $index)
    {
        $tableName = self::table($suffix);
        $index = trim((string) $index);
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

    public static function table($suffix)
    {
        return TABLE_PREFIX.'_'.trim((string) $suffix);
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

    private static function isAllowedUrl($url)
    {
        $url = trim((string) $url);
        if ($url === '') {
            return false;
        }

        $lower = strtolower($url);
        if (strpos($lower, 'javascript:') === 0 || strpos($lower, 'data:') === 0) {
            return false;
        }

        if (preg_match('/^https?:\/\//i', $url)) {
            return true;
        }

        if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $url)) {
            return false;
        }

        if (strpos($url, '//') === 0) {
            return false;
        }

        return strpos($url, '..') === false;
    }

    private static function limit($value, $length)
    {
        return substr(trim((string) $value), 0, (int) $length);
    }

    private static function ensureColumn($suffix, $column, $definition)
    {
        if (self::tableExists($suffix) && !self::columnExists($suffix, $column)) {
            grr_sql_command("ALTER TABLE `".self::table($suffix)."` ADD ".$definition);
        }
    }

    private static function ensureIndex($suffix, $index, $definition)
    {
        if (self::tableExists($suffix) && !self::indexExists($suffix, $index)) {
            grr_sql_command("ALTER TABLE `".self::table($suffix)."` ADD ".$definition);
        }
    }

    private static function ensureAccountMenuColumns()
    {
        $hadAccountMenuActive = self::columnExists(self::TABLE_BUTTON, 'account_menu_active');
        $hadAccountPositionOrder = self::columnExists(self::TABLE_BUTTON, 'account_position_order');

        self::ensureColumn(self::TABLE_BUTTON, 'account_menu_active', "`account_menu_active` tinyint(1) NOT NULL DEFAULT 1 AFTER `active`");
        self::ensureColumn(self::TABLE_BUTTON, 'account_position_order', "`account_position_order` int(11) NOT NULL DEFAULT 0 AFTER `account_menu_active`");

        if (!$hadAccountPositionOrder && self::columnExists(self::TABLE_BUTTON, 'account_position_order')) {
            grr_sql_command(
                "UPDATE ".self::table(self::TABLE_BUTTON)."
                SET account_position_order = position_order
                WHERE account_position_order = 0"
            );
        }

        if (!$hadAccountMenuActive && self::columnExists(self::TABLE_BUTTON, 'account_menu_active')) {
            grr_sql_command(
                "UPDATE ".self::table(self::TABLE_BUTTON)."
                SET account_menu_active = active
                WHERE source_type = ?",
                "s",
                array(self::SOURCE_MODULE)
            );

            foreach (self::moduleButtonDefaults() as $button) {
                if (!isset($button['account_menu_active']) || (int) $button['account_menu_active'] !== 0) {
                    continue;
                }
                grr_sql_command(
                    "UPDATE ".self::table(self::TABLE_BUTTON)."
                    SET account_menu_active = 0
                    WHERE source_type = ? AND source_key = ?",
                    "ss",
                    array(self::SOURCE_MODULE, $button['source_key'])
                );
            }
        }
    }

    private static function ensureModuleButtons()
    {
        $position = (int) grr_sql_query1(
            "SELECT COALESCE(MAX(position_order), 0) FROM ".self::table(self::TABLE_BUTTON)
        );
        $now = time();

        foreach (self::moduleButtonDefaults() as $button) {
            $exists = grr_sql_query1(
                "SELECT COUNT(*) FROM ".self::table(self::TABLE_BUTTON)." WHERE source_type = ? AND source_key = ?",
                "ss",
                array(self::SOURCE_MODULE, $button['source_key'])
            );
            if ((int) $exists > 0) {
                continue;
            }

            $position += 10;
            $calendarActive = isset($button['active']) ? (int) $button['active'] : 1;
            $accountMenuActive = isset($button['account_menu_active']) ? (int) $button['account_menu_active'] : 1;
            $accountPosition = isset($button['account_position_order']) ? (int) $button['account_position_order'] : $position;
            grr_sql_command(
                "INSERT INTO ".self::table(self::TABLE_BUTTON)."
                (source_type, source_key, label, url, target_mode, button_style, position_order, active, account_menu_active, account_position_order, tooltip, created_at, updated_at)
                VALUES (?, ?, ?, ?, 'current', 'primary', ?, ?, ?, ?, ?, ?, ?)",
                "ssssiiiisii",
                array(
                    self::SOURCE_MODULE,
                    $button['source_key'],
                    $button['label'],
                    $button['url'],
                    $position,
                    $calendarActive,
                    $accountMenuActive,
                    $accountPosition,
                    $button['tooltip'],
                    $now,
                    $now,
                )
            );
        }
    }

    private static function moduleButtonDefaults()
    {
        return array(
            array(
                'source_key' => 'module:gestion_materiel',
                'label' => 'Gestion materiel',
                'url' => 'compte/compte.php?pc=gestion_materiel',
                'tooltip' => 'Ouvrir Gestion materiel',
            ),
            array(
                'source_key' => 'module:stock_chimique',
                'label' => 'Stock chimique',
                'url' => 'compte/compte.php?pc=stock_chimique',
                'tooltip' => 'Ouvrir Stock chimique',
            ),
            array(
                'source_key' => 'module:suivi_demandes',
                'label' => 'Suivi des demandes',
                'url' => 'compte/compte.php?pc=suivi_demandes',
                'tooltip' => 'Ouvrir Suivi des demandes',
            ),
            array(
                'source_key' => 'module:formulaires_dynamiques',
                'label' => 'Formulaires dynamiques',
                'url' => 'compte/compte.php?pc=formulaires_dynamiques',
                'tooltip' => 'Ouvrir Formulaires dynamiques',
            ),
            array(
                'source_key' => 'module:informatique_materiel',
                'label' => 'Informatique materiel',
                'url' => 'compte/compte.php?pc=informatique_materiel',
                'tooltip' => 'Ouvrir Informatique materiel',
            ),
            array(
                'source_key' => 'module:informatique_materiel_user',
                'label' => 'Mon materiel informatique',
                'url' => 'compte/compte.php?pc=informatique_materiel&view=user',
                'tooltip' => 'Afficher mon materiel informatique',
                'account_menu_active' => 0,
            ),
            array(
                'source_key' => 'module:stagiaire',
                'label' => 'Stagiaire',
                'url' => 'compte/compte.php?pc=stagiaire',
                'tooltip' => 'Ouvrir Stagiaire',
                'active' => 0,
                'account_menu_active' => 1,
            ),
            array(
                'source_key' => 'module:boutons_perso',
                'label' => 'Boutons perso',
                'url' => 'compte/compte.php?pc=boutons_perso',
                'tooltip' => 'Configurer Boutons perso',
                'active' => 0,
                'account_menu_active' => 1,
            ),
        );
    }

    private static function normalizeWindowSize($value, $default, $min, $max)
    {
        $value = (int) $value;
        if ($value <= 0) {
            return (int) $default;
        }

        if ($value < $min) {
            return (int) $min;
        }

        if ($value > $max) {
            return (int) $max;
        }

        return $value;
    }
}
