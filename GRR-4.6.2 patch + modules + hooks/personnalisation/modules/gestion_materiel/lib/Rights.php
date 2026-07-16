<?php

class GestionMaterielRights
{
    public static function currentLogin()
    {
        return function_exists('getUserName') ? (string) getUserName() : '';
    }

    public static function isAdmin($login = null)
    {
        if (!class_exists('SecuAccess')) {
            return false;
        }

        $login = $login === null ? self::currentLogin() : (string) $login;
        return $login !== '' && SecuAccess::UserLevel($login, -1) >= 6;
    }

    public static function canManageModule($login = null)
    {
        $login = $login === null ? self::currentLogin() : (string) $login;
        if ($login === '') {
            return false;
        }

        return self::isAdmin($login) || GestionMaterielConfig::isManager($login);
    }

    public static function canAccessModule($login = null)
    {
        $login = $login === null ? self::currentLogin() : (string) $login;
        if ($login === '') {
            return false;
        }

        return self::canManageModule($login)
            || GestionMaterielRepository::userHasAssignedItems($login);
    }

    public static function canViewItem($itemId, $login = null)
    {
        $login = $login === null ? self::currentLogin() : (string) $login;
        if ($login === '' || (int) $itemId <= 0) {
            return false;
        }

        return self::canManageModule($login)
            || GestionMaterielRepository::userIsAssignedToItem($login, (int) $itemId);
    }

    public static function canViewGroup($groupId, $login = null)
    {
        $login = $login === null ? self::currentLogin() : (string) $login;
        if ($login === '' || (int) $groupId <= 0) {
            return false;
        }

        if (self::canManageModule($login)) {
            return true;
        }

        return count(GestionMaterielRepository::itemsForGroup((int) $groupId, $login, false, 1)) > 0;
    }
}
