<?php

class SuiviDemandesConfig
{
    const MODULE = 'suivi_demandes';
    const MAX_CATEGORIES = 30;
    const MAX_CATEGORY_LENGTH = 60;
    const DEFAULT_ATTACHMENT_EXTENSIONS = "pdf\ntxt\ncsv\njpg\njpeg\npng\ngif\nodt\nods\ndoc\ndocx\nxls\nxlsx\nzip";
    const DEFAULT_ATTACHMENT_MAX_MB = 5;
    const MIN_ATTACHMENT_MAX_MB = 1;
    const MAX_ATTACHMENT_MAX_MB = 50;

    public static function get($name, $default = '')
    {
        $value = Settings::get(self::storageName($name));
        $legacyName = 'suivi_demandes_'.$name;
        if ($value === null && $legacyName !== self::storageName($name)) {
            $value = Settings::get($legacyName);
        }

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
            'reservation_form_enabled' => 'suivi_demandes_resa_form',
            'reservation_detail_enabled' => 'suivi_demandes_resa_detail',
            'notifications_enabled' => 'suivi_demandes_notif',
            'categories_enabled' => 'suivi_demandes_cats_on',
            'category_options' => 'suivi_demandes_cats',
            'attachments_enabled' => 'suivi_demandes_attach_on',
            'attachment_max_mb' => 'suivi_demandes_attach_mb',
            'attachment_extensions' => 'suivi_demandes_attach_ext',
            'notif_open_color' => 'suivi_demandes_nopen_col',
            'notif_progress_color' => 'suivi_demandes_nprog_col',
        );

        return isset($names[$name]) ? $names[$name] : 'suivi_demandes_'.$name;
    }

    public static function isEnabled()
    {
        return self::get('enabled', '1') === '1';
    }

    public static function displayName()
    {
        return self::get('display_name', 'Suivi des demandes');
    }

    public static function accountEnabled()
    {
        return self::get('account_enabled', '1') === '1';
    }

    public static function reservationFormEnabled()
    {
        return self::get('reservation_form_enabled', '1') === '1';
    }

    public static function reservationDetailEnabled()
    {
        return self::get('reservation_detail_enabled', '1') === '1';
    }

    public static function notificationsEnabled()
    {
        return self::get('notifications_enabled', '1') === '1';
    }

    public static function categoriesEnabled()
    {
        return self::get('categories_enabled', '1') === '1';
    }

    public static function attachmentsEnabled()
    {
        return self::get('attachments_enabled', '1') === '1';
    }

    public static function attachmentMaxMb()
    {
        $maxMb = (int) self::get('attachment_max_mb', (string) self::DEFAULT_ATTACHMENT_MAX_MB);
        if ($maxMb < self::MIN_ATTACHMENT_MAX_MB) {
            return self::MIN_ATTACHMENT_MAX_MB;
        }

        if ($maxMb > self::MAX_ATTACHMENT_MAX_MB) {
            return self::MAX_ATTACHMENT_MAX_MB;
        }

        return $maxMb;
    }

    public static function attachmentMaxBytes()
    {
        return self::attachmentMaxMb() * 1048576;
    }

    public static function creationRight()
    {
        $right = self::get('create_right', 'all');
        $rights = self::creationRightModes();

        return isset($rights[$right]) ? $right : 'all';
    }

    public static function closeRight()
    {
        $right = self::get('close_right', 'creator_manager_admin');
        $rights = self::closeRightModes();

        return isset($rights[$right]) ? $right : 'creator_manager_admin';
    }

    public static function creationRightModes()
    {
        return array(
            'all' => 'Tout utilisateur connecte',
            'resource' => 'Utilisateur ayant acces a au moins une ressource',
            'manager' => 'Gestionnaire de ressource ou administrateur',
            'admin' => 'Administrateur uniquement',
        );
    }

    public static function closeRightModes()
    {
        return array(
            'creator_manager_admin' => 'Createur, gestionnaire ou administrateur',
            'manager_admin' => 'Gestionnaire ou administrateur',
            'admin' => 'Administrateur uniquement',
        );
    }

    public static function notificationTypes()
    {
        return array(
            'created' => 'Creation d une demande',
            'comment' => 'Nouveau commentaire',
            'status' => 'Changement de statut',
            'follower' => 'Ajout ou retrait de suiveur',
            'resource' => 'Ajout ou retrait de ressource',
            'attachment' => 'Ajout ou retrait de piece jointe',
        );
    }

    public static function notificationTypeEnabled($type)
    {
        $key = self::notificationKey($type);
        if ($key === '') {
            return true;
        }

        return self::get($key, '1') === '1';
    }

    public static function setNotificationTypeEnabled($type, $enabled)
    {
        $key = self::notificationKey($type);
        if ($key === '') {
            return false;
        }

        return self::set($key, $enabled ? '1' : '0');
    }

    public static function notificationLinkColorDefaults()
    {
        return array(
            'ouverte' => '#5bc0de',
            'en_cours' => '#f0ad4e',
        );
    }

    public static function notificationLinkColor($status)
    {
        $defaults = self::notificationLinkColorDefaults();
        if (!isset($defaults[$status])) {
            return '#5bc0de';
        }

        $key = self::notificationLinkColorKey($status);
        return self::normalizeColor(self::get($key, $defaults[$status]), $defaults[$status]);
    }

    public static function setNotificationLinkColor($status, $color)
    {
        $defaults = self::notificationLinkColorDefaults();
        if (!isset($defaults[$status])) {
            return false;
        }

        $color = self::normalizeColor($color, '');
        if ($color === '') {
            return false;
        }

        return self::set(self::notificationLinkColorKey($status), $color);
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

    private static function notificationLinkColorKey($status)
    {
        return $status === 'en_cours' ? 'notif_progress_color' : 'notif_open_color';
    }

    private static function notificationKey($type)
    {
        $keys = array(
            'created' => 'n_created',
            'comment' => 'n_comment',
            'status' => 'n_status',
            'follower' => 'n_follower',
            'resource' => 'n_resource',
            'attachment' => 'n_attachment',
        );

        return isset($keys[$type]) ? $keys[$type] : '';
    }

    public static function statuses()
    {
        $statuses = array();
        foreach (self::statusDefinitions() as $value => $defaultLabel) {
            $statuses[$value] = self::statusLabel($value);
        }

        return $statuses;
    }

    public static function statusDefinitions()
    {
        return array(
            'ouverte' => 'Ouverte',
            'en_cours' => 'En cours',
            'cloturee' => 'Cloturee',
        );
    }

    public static function setStatusLabel($status, $label)
    {
        $definitions = self::statusDefinitions();
        if (!isset($definitions[$status])) {
            return false;
        }

        return self::set('status_'.$status, $label);
    }

    public static function priorities()
    {
        $priorities = array();
        foreach (self::priorityDefinitions() as $value => $defaultLabel) {
            if (self::priorityEnabled($value)) {
                $priorities[$value] = self::priorityLabel($value);
            }
        }

        if (count($priorities) === 0) {
            $priorities['normale'] = self::priorityLabel('normale');
        }

        return $priorities;
    }

    public static function priorityDefinitions()
    {
        return array(
            'basse' => 'Basse',
            'normale' => 'Normale',
            'haute' => 'Haute',
        );
    }

    public static function priorityEnabled($priority)
    {
        $definitions = self::priorityDefinitions();
        if (!isset($definitions[$priority])) {
            return false;
        }

        return self::get('prio_'.$priority.'_on', '1') === '1';
    }

    public static function setPriorityEnabled($priority, $enabled)
    {
        $definitions = self::priorityDefinitions();
        if (!isset($definitions[$priority])) {
            return false;
        }

        return self::set('prio_'.$priority.'_on', $enabled ? '1' : '0');
    }

    public static function setPriorityLabel($priority, $label)
    {
        $definitions = self::priorityDefinitions();
        if (!isset($definitions[$priority])) {
            return false;
        }

        return self::set('prio_'.$priority, $label);
    }

    public static function defaultPriority()
    {
        if (self::priorityEnabled('normale')) {
            return 'normale';
        }

        foreach (self::priorityDefinitions() as $value => $label) {
            if (self::priorityEnabled($value)) {
                return $value;
            }
        }

        return 'normale';
    }

    public static function statusLabel($status)
    {
        $statuses = self::statusDefinitions();
        if (!isset($statuses[$status])) {
            return $status;
        }

        return self::get('status_'.$status, $statuses[$status]);
    }

    public static function priorityLabel($priority)
    {
        $priorities = self::priorityDefinitions();
        if (!isset($priorities[$priority])) {
            return $priority;
        }

        return self::get('prio_'.$priority, $priorities[$priority]);
    }

    public static function categoryOptions()
    {
        return self::categoryOptionsFromText(self::get('category_options', 'General'));
    }

    public static function categoryOptionsText()
    {
        return implode("\n", self::categoryOptions());
    }

    public static function categoryOptionsFromText($raw)
    {
        $raw = str_replace("\r", "\n", (string) $raw);
        $categories = array();
        $seen = array();

        foreach (explode("\n", $raw) as $line) {
            $category = trim($line);
            if ($category === '' || isset($seen[$category])) {
                continue;
            }

            $seen[$category] = true;
            $categories[] = $category;
        }

        return $categories;
    }

    public static function setCategoryOptions($raw)
    {
        $categories = self::categoryOptionsFromText($raw);

        return self::set('category_options', implode("\n", $categories));
    }

    public static function categoryLabel($category)
    {
        $category = trim((string) $category);

        return $category === '' ? 'Sans categorie' : $category;
    }

    public static function isValidCategory($category)
    {
        $category = trim((string) $category);
        if (!self::categoriesEnabled()) {
            return $category === '';
        }

        if ($category === '') {
            return true;
        }

        foreach (self::categoryOptions() as $option) {
            if ($category === $option) {
                return true;
            }
        }

        return false;
    }

    public static function isValidPriority($priority)
    {
        $priorities = self::priorities();
        return isset($priorities[$priority]);
    }

    public static function attachmentExtensions()
    {
        $extensions = self::attachmentExtensionsFromText(self::get('attachment_extensions', self::DEFAULT_ATTACHMENT_EXTENSIONS));
        if (count($extensions) === 0) {
            $extensions = self::attachmentExtensionsFromText(self::DEFAULT_ATTACHMENT_EXTENSIONS);
        }

        return $extensions;
    }

    public static function attachmentExtensionsText()
    {
        return implode("\n", self::attachmentExtensions());
    }

    public static function setAttachmentExtensions($raw)
    {
        $extensions = self::attachmentExtensionsFromText($raw);
        if (count($extensions) === 0) {
            return false;
        }

        return self::set('attachment_extensions', implode("\n", $extensions));
    }

    public static function attachmentExtensionsFromText($raw)
    {
        $extensions = array();
        $seen = array();
        foreach (self::attachmentExtensionTokens($raw) as $token) {
            $extension = self::normalizeAttachmentExtension($token);
            if ($extension === '' || isset($seen[$extension])) {
                continue;
            }

            $seen[$extension] = true;
            $extensions[] = $extension;
        }

        return $extensions;
    }

    public static function invalidAttachmentExtensionsFromText($raw)
    {
        $invalid = array();
        $seen = array();
        foreach (self::attachmentExtensionTokens($raw) as $token) {
            $clean = strtolower(trim((string) $token));
            $clean = ltrim($clean, '.');
            if ($clean === '') {
                continue;
            }

            if (self::normalizeAttachmentExtension($token) === '' && !isset($seen[$clean])) {
                $seen[$clean] = true;
                $invalid[] = $clean;
            }
        }

        return $invalid;
    }

    private static function attachmentExtensionTokens($raw)
    {
        $raw = strtolower((string) $raw);
        return preg_split('/[\s,;]+/', $raw, -1, PREG_SPLIT_NO_EMPTY);
    }

    private static function normalizeAttachmentExtension($extension)
    {
        $extension = strtolower(trim((string) $extension));
        $extension = ltrim($extension, '.');
        if (!preg_match('/^[a-z0-9]{1,12}$/', $extension)) {
            return '';
        }

        if (in_array($extension, self::forbiddenAttachmentExtensions(), true)) {
            return '';
        }

        return $extension;
    }

    private static function forbiddenAttachmentExtensions()
    {
        return array('php', 'php3', 'php4', 'php5', 'phtml', 'phar', 'cgi', 'pl', 'py', 'sh', 'bash', 'exe', 'bat', 'cmd', 'com', 'msi', 'js', 'html', 'htm', 'svg');
    }
}
