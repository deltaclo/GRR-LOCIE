<?php

class FormulairesDynamiquesRights
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

        return self::isAdmin($login) || FormulairesDynamiquesConfig::isManager($login);
    }

    public static function canAccessModule($login = null)
    {
        $login = $login === null ? self::currentLogin() : (string) $login;
        if (!FormulairesDynamiquesConfig::isEnabled()) {
            return false;
        }

        return $login !== '';
    }

    public static function canAccessAccountPage($login = null)
    {
        return FormulairesDynamiquesConfig::accountEnabled()
            && self::canAccessModule($login);
    }
}
