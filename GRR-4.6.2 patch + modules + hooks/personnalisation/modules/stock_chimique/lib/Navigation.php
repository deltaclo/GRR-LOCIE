<?php

require_once __DIR__.'/Config.php';
require_once __DIR__.'/Repository.php';
require_once __DIR__.'/Security.php';

class StockChimiqueNavigation
{
    const BUTTON_ID = 'module:stock_chimique';

    public static function buttonDefinition($login = null)
    {
        $login = $login === null ? StockChimiqueSecurity::currentLogin() : (string) $login;

        return array(
            'id' => self::BUTTON_ID,
            'module' => StockChimiqueConfig::MODULE,
            'label' => StockChimiqueConfig::displayName(),
            'url' => self::accountUrl(),
            'enabled' => StockChimiqueConfig::isEnabled(),
            'can_access' => StockChimiqueSecurity::canAccess($login),
        );
    }

    private static function accountUrl()
    {
        $script = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', (string) $_SERVER['SCRIPT_NAME']) : '';
        $base = strpos($script, '/compte/') !== false ? 'compte.php' : 'compte/compte.php';

        return $base.'?pc='.rawurlencode(StockChimiqueConfig::MODULE);
    }
}
