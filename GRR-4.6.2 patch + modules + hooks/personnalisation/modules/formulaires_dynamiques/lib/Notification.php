<?php

class FormulairesDynamiquesNotification
{
    public static function mailEnabled()
    {
        if (!class_exists('Settings') || !FormulairesDynamiquesConfig::notificationsEnabled()) {
            return false;
        }

        $method = Settings::get('grr_mail_method');

        return Settings::get('automatic_mail') === 'yes'
            && ($method === 'smtp' || $method === 'mail');
    }
}
