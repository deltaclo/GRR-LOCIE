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

    public static function canCreateForms($login = null)
    {
        return self::canManageModule($login);
    }

    public static function canManageForm($login = null, $formId = 0)
    {
        $login = $login === null ? self::currentLogin() : (string) $login;
        $formId = (int) $formId;
        if ($login === '' || $formId <= 0) {
            return false;
        }

        if (self::canManageModule($login)) {
            return true;
        }

        return class_exists('FormulairesDynamiquesRepository')
            && FormulairesDynamiquesRepository::userCanManageForm($login, $formId);
    }

    public static function hasManagedForms($login = null)
    {
        $login = $login === null ? self::currentLogin() : (string) $login;
        if ($login === '') {
            return false;
        }

        return class_exists('FormulairesDynamiquesRepository')
            && FormulairesDynamiquesRepository::userManagesAnyForm($login);
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
            && (self::canManageModule($login) || self::hasManagedForms($login));
    }
}
