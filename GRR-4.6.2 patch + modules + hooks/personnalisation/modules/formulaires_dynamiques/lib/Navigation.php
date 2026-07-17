<?php

require_once __DIR__.'/Config.php';
require_once __DIR__.'/Rights.php';

class FormulairesDynamiquesNavigation
{
    const BUTTON_ID = 'module:formulaires_dynamiques';

    public static function buttonDefinition($login = null)
    {
        $login = $login === null && function_exists('getUserName')
            ? (string) getUserName()
            : (string) $login;

        return array(
            'id' => self::BUTTON_ID,
            'module' => FormulairesDynamiquesConfig::MODULE,
            'label' => FormulairesDynamiquesConfig::displayName(),
            'url' => self::accountUrl(),
            'enabled' => FormulairesDynamiquesConfig::isEnabled() && FormulairesDynamiquesConfig::accountEnabled(),
            'can_access' => FormulairesDynamiquesRights::canAccessAccountPage($login),
        );
    }

    private static function accountUrl()
    {
        $script = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', (string) $_SERVER['SCRIPT_NAME']) : '';
        $base = strpos($script, '/compte/') !== false ? 'compte.php' : 'compte/compte.php';

        return $base.'?pc='.rawurlencode(FormulairesDynamiquesConfig::MODULE);
    }
}
