<?php

require_once __DIR__.'/Config.php';
require_once __DIR__.'/Repository.php';
require_once __DIR__.'/Security.php';

class InformatiqueMaterielNavigation
{
    const BUTTON_ID = 'module:informatique_materiel';
    const USER_BUTTON_ID = 'module:informatique_materiel_user';

    public static function buttonDefinition($login = null)
    {
        $login = $login === null ? InformatiqueMaterielSecurity::currentLogin() : (string) $login;

        return array(
            'id' => self::BUTTON_ID,
            'module' => InformatiqueMaterielConfig::MODULE,
            'label' => InformatiqueMaterielConfig::displayName(),
            'url' => self::accountUrl(),
            'enabled' => InformatiqueMaterielConfig::isEnabled(),
            'can_access' => InformatiqueMaterielSecurity::canAccess($login),
        );
    }

    public static function userButtonDefinition($login = null)
    {
        $login = $login === null ? InformatiqueMaterielSecurity::currentLogin() : (string) $login;

        return array(
            'id' => self::USER_BUTTON_ID,
            'module' => InformatiqueMaterielConfig::MODULE,
            'label' => 'Mon materiel informatique',
            'url' => self::accountUrl(array('view' => 'user')),
            'enabled' => InformatiqueMaterielConfig::isEnabled(),
            'can_access' => InformatiqueMaterielSecurity::canViewUserEquipment($login)
                && InformatiqueMaterielRepository::loginHasOpenEquipment($login),
        );
    }

    private static function accountUrl($params = array())
    {
        $script = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', (string) $_SERVER['SCRIPT_NAME']) : '';
        $base = strpos($script, '/compte/') !== false ? 'compte.php' : 'compte/compte.php';
        $query = array_merge(array('pc' => InformatiqueMaterielConfig::MODULE), $params);

        return $base.'?'.http_build_query($query, '', '&');
    }
}
