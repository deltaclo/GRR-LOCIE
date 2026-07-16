<?php

class BoutonsPersoConfig
{
    const MODULE = 'boutons_perso';

    public static function get($name, $default = '')
    {
        $value = Settings::get(self::storageName($name));

        return ($value === null || $value === '') ? $default : $value;
    }

    public static function set($name, $value)
    {
        $storageName = self::storageName($name);
        if (strlen($storageName) > 32) {
            return false;
        }

        $safeName = SecuChaine::ProtectDataSql($storageName);
        $safeValue = SecuChaine::ProtectDataSql((string) $value);
        $exists = grr_sql_query1("SELECT COUNT(*) FROM ".TABLE_PREFIX."_setting WHERE NAME = '".$safeName."'");

        if ((int) $exists > 0) {
            $result = grr_sql_command("UPDATE ".TABLE_PREFIX."_setting SET VALUE = '".$safeValue."' WHERE NAME = '".$safeName."'");
        } else {
            $result = grr_sql_command("INSERT INTO ".TABLE_PREFIX."_setting SET NAME = '".$safeName."', VALUE = '".$safeValue."'");
        }

        if ($result === false || $result < 0) {
            return false;
        }

        Settings::load();
        return true;
    }

    private static function storageName($name)
    {
        $names = array(
            'enabled' => 'bperso_enabled',
            'display_name' => 'bperso_display_name',
            'show_title' => 'bperso_show_title',
            'panel_bg_color' => 'bperso_panel_bg',
            'panel_border_color' => 'bperso_panel_border',
            'account_menu_enabled' => 'bperso_acc_menu',
        );

        return isset($names[$name]) ? $names[$name] : 'bperso_'.$name;
    }

    public static function isEnabled()
    {
        $value = self::get('enabled', '');
        if ($value === '') {
            $legacy = Settings::get('boutons_perso_enabled');
            return $legacy !== '0';
        }

        return $value !== '0';
    }

    public static function displayName()
    {
        return self::get('display_name', 'Boutons perso');
    }

    public static function showTitle()
    {
        return self::get('show_title', '1') === '1';
    }

    public static function accountMenuEnabled()
    {
        return self::get('account_menu_enabled', '0') === '1';
    }

    public static function panelBgColor()
    {
        return self::normalizeColor(self::get('panel_bg_color', '#f6f8fb'), '#f6f8fb');
    }

    public static function panelBorderColor()
    {
        return self::normalizeColor(self::get('panel_border_color', '#d8dee6'), '#d8dee6');
    }

    public static function normalizeColor($color, $default)
    {
        $color = trim((string) $color);
        if (preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            return strtolower($color);
        }

        if (preg_match('/^[0-9a-fA-F]{6}$/', $color)) {
            return '#'.strtolower($color);
        }

        return $default;
    }

    public static function buttonStyles()
    {
        return array(
            'default' => 'Gris',
            'primary' => 'Bleu',
            'secondary' => 'Gris fonce',
            'success' => 'Vert',
            'info' => 'Bleu clair',
            'warning' => 'Orange',
            'danger' => 'Rouge',
            'dark' => 'Noir',
            'light' => 'Gris clair',
            'purple' => 'Violet',
            'maroon' => 'Bordeaux',
            'navy' => 'Bleu nuit',
            'teal' => 'Turquoise',
            'olive' => 'Olive',
            'custom' => 'Couleurs personnalisees',
        );
    }

    public static function targetModes()
    {
        return array(
            'current' => 'Fenetre courante',
            'new_tab' => 'Nouvel onglet',
            'new_window' => 'Nouvelle fenetre',
        );
    }
}
