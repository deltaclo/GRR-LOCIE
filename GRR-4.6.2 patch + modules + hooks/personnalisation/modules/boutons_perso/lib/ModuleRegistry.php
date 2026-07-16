<?php

class BoutonsPersoModuleRegistry
{
    public static function definitions($login = null)
    {
        $definitions = array();
        foreach (self::providers() as $sourceKey => $provider) {
            $definitions[$sourceKey] = self::loadDefinition($sourceKey, $provider, $login);
        }

        return $definitions;
    }

    public static function definition($sourceKey, $login = null)
    {
        $providers = self::providers();
        $sourceKey = trim((string) $sourceKey);
        if ($sourceKey === '' || !isset($providers[$sourceKey])) {
            return array();
        }

        return self::loadDefinition($sourceKey, $providers[$sourceKey], $login);
    }

    private static function loadDefinition($sourceKey, $provider, $login)
    {
        $definition = array(
            'id' => $sourceKey,
            'module' => $provider['module'],
            'label' => $provider['label'],
            'url' => '',
            'enabled' => false,
            'can_access' => false,
            'provider_available' => false,
            'external_active' => self::externalModuleActive($provider['module']),
        );

        $file = self::modulesDir().'/'.$provider['module'].'/lib/Navigation.php';
        if (!is_file($file)) {
            return $definition;
        }

        require_once $file;
        $method = isset($provider['method']) ? (string) $provider['method'] : 'buttonDefinition';
        if (!class_exists($provider['class']) || !method_exists($provider['class'], $method)) {
            return $definition;
        }
        $definition['provider_available'] = true;

        if (!$definition['external_active']) {
            return $definition;
        }

        try {
            $provided = call_user_func(array($provider['class'], $method), $login);
        } catch (Throwable $exception) {
            error_log('Boutons perso - fournisseur '.$sourceKey.' : '.$exception->getMessage());
            return $definition;
        }

        if (!is_array($provided) || !isset($provided['id']) || (string) $provided['id'] !== $sourceKey) {
            return $definition;
        }

        foreach (array('module', 'label', 'url', 'enabled', 'can_access') as $key) {
            if (array_key_exists($key, $provided)) {
                $definition[$key] = $provided[$key];
            }
        }
        return $definition;
    }

    private static function externalModuleActive($module)
    {
        $count = grr_sql_query1(
            "SELECT COUNT(*) FROM ".TABLE_PREFIX."_modulesext WHERE nom = ? AND actif = 1",
            "s",
            array((string) $module)
        );

        return (int) $count > 0;
    }

    private static function modulesDir()
    {
        return dirname(__DIR__, 2);
    }

    private static function providers()
    {
        return array(
            'module:gestion_materiel' => array(
                'module' => 'gestion_materiel',
                'class' => 'GestionMaterielNavigation',
                'label' => 'Gestion materiel',
            ),
            'module:stock_chimique' => array(
                'module' => 'stock_chimique',
                'class' => 'StockChimiqueNavigation',
                'label' => 'Stock chimique',
            ),
            'module:suivi_demandes' => array(
                'module' => 'suivi_demandes',
                'class' => 'SuiviDemandesNavigation',
                'label' => 'Suivi des demandes',
            ),
            'module:formulaires_dynamiques' => array(
                'module' => 'formulaires_dynamiques',
                'class' => 'FormulairesDynamiquesNavigation',
                'label' => 'Formulaires dynamiques',
            ),
            'module:informatique_materiel' => array(
                'module' => 'informatique_materiel',
                'class' => 'InformatiqueMaterielNavigation',
                'label' => 'Informatique materiel',
            ),
            'module:informatique_materiel_user' => array(
                'module' => 'informatique_materiel',
                'class' => 'InformatiqueMaterielNavigation',
                'method' => 'userButtonDefinition',
                'label' => 'Mon materiel informatique',
            ),
            'module:stagiaire' => array(
                'module' => 'stagiaire',
                'class' => 'StagiaireNavigation',
                'label' => 'Stagiaire',
            ),
            'module:boutons_perso' => array(
                'module' => 'boutons_perso',
                'class' => 'BoutonsPersoNavigation',
                'label' => 'Boutons perso',
            ),
        );
    }
}
