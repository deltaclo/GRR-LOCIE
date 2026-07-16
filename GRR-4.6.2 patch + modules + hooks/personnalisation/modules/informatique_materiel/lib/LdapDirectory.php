<?php

class InformatiqueMaterielLdapDirectory
{
    const MAX_RESULTS = 20;

    public static function isConfigured()
    {
        return function_exists('ldap_connect') && is_file(self::configPath());
    }

    public static function status()
    {
        if (!is_file(self::configPath())) {
            return 'Fichier LDAP absent';
        }
        if (!function_exists('ldap_connect')) {
            return 'Extension LDAP indisponible';
        }

        return 'LDAP disponible';
    }

    public static function test($term = '')
    {
        $result = array(
            'ok' => false,
            'status' => self::status(),
            'details' => array(),
            'entries' => array(),
        );

        if (!is_file(self::configPath())) {
            $result['details'][] = 'Le fichier personnalisation/config_ldap.inc.php est absent.';
            return $result;
        }
        if (!function_exists('ldap_connect')) {
            $result['details'][] = 'L extension PHP LDAP est indisponible.';
            return $result;
        }

        $config = self::config();
        if (trim((string) $config['ldap_adresse']) === '') {
            $result['details'][] = 'Adresse LDAP manquante.';
            return $result;
        }
        if (trim((string) $config['ldap_base']) === '') {
            $result['details'][] = 'Base LDAP manquante.';
            return $result;
        }

        $ds = self::connect($config);
        if (!$ds) {
            $result['details'][] = 'Connexion ou authentification LDAP impossible.';
            return $result;
        }

        $result['ok'] = true;
        $result['details'][] = 'Connexion et authentification LDAP reussies.';
        @ldap_unbind($ds);

        $term = trim((string) $term);
        if ($term !== '') {
            $result['entries'] = self::searchByText($term, 10);
            $result['details'][] = count($result['entries']).' resultat(s) pour "'.$term.'".';
        }

        return $result;
    }

    public static function suggestionsForPerson($prenom, $nom, $selectedLogin = '')
    {
        $suggestions = array();
        foreach (InformatiqueMaterielRepository::grrUsersForPerson($prenom, $nom, $selectedLogin) as $user) {
            $suggestions[] = self::suggestion(
                $user['login'],
                'GRR',
                $user['nom'],
                $user['prenom'],
                isset($user['email']) ? $user['email'] : ''
            );
        }

        $known = array();
        foreach ($suggestions as $suggestion) {
            $known[strtolower($suggestion['login'])] = true;
        }

        foreach (self::searchByPersonName($prenom, $nom) as $user) {
            $key = strtolower($user['login']);
            if (isset($known[$key])) {
                continue;
            }
            $known[$key] = true;
            $suggestions[] = $user;
        }

        $selectedLogin = trim((string) $selectedLogin);
        if ($selectedLogin !== '' && !isset($known[strtolower($selectedLogin)])) {
            array_unshift($suggestions, self::suggestion($selectedLogin, 'Actuel', '', '', ''));
        }

        return $suggestions;
    }

    public static function loginExists($login)
    {
        $login = trim((string) $login);
        if ($login === '' || !self::isConfigured()) {
            return false;
        }

        return count(self::searchByLogin($login, 1)) > 0;
    }

    public static function suggestionForLogin($login)
    {
        $login = trim((string) $login);
        if ($login === '' || !self::isConfigured()) {
            return array();
        }

        $rows = self::searchByLogin($login, 1);
        return isset($rows[0]) ? $rows[0] : array();
    }

    public static function searchByText($term, $limit = 10)
    {
        $term = trim((string) $term);
        if ($term === '' || !self::isConfigured()) {
            return array();
        }

        $config = self::config();
        $ds = self::connect($config);
        if (!$ds) {
            return array();
        }

        $parts = array();
        foreach (array_merge(
            self::searchAttributes(),
            array(
                self::setting('ldap_champ_nom', 'sn'),
                self::setting('ldap_champ_prenom', 'givenname'),
                self::setting('ldap_champ_email', 'mail'),
                'cn',
                'displayName',
            )
        ) as $attribute) {
            $attribute = self::attribute($attribute);
            if ($attribute !== '') {
                $parts[] = '('.$attribute.'=*'.self::escape($term).'*)';
            }
        }

        $filter = self::withAdditionalFilter($config, '(|'.implode('', $parts).')');
        $rows = self::ldapRows($ds, $config['ldap_base'], $filter, self::attributes(), (int) $limit);
        @ldap_unbind($ds);

        return self::rowsToSuggestions($rows, 'LDAP');
    }

    private static function searchByPersonName($prenom, $nom)
    {
        $prenom = trim((string) $prenom);
        $nom = trim((string) $nom);
        if (($prenom === '' && $nom === '') || !self::isConfigured()) {
            return array();
        }

        $config = self::config();
        $ds = self::connect($config);
        if (!$ds) {
            return array();
        }

        $nomAttr = self::setting('ldap_champ_nom', 'sn');
        $prenomAttr = self::setting('ldap_champ_prenom', 'givenname');
        $searchAttrs = self::searchAttributes();
        $parts = array();
        if ($prenom !== '' && $nom !== '') {
            $parts[] = '(&('.$prenomAttr.'=*'.self::escape($prenom).'*)('.$nomAttr.'=*'.self::escape($nom).'*))';
            $parts[] = '(&('.$prenomAttr.'=*'.self::escape($nom).'*)('.$nomAttr.'=*'.self::escape($prenom).'*))';
            $parts[] = '(cn=*'.self::escape($prenom).'*'.self::escape($nom).'*)';
            $parts[] = '(displayName=*'.self::escape($prenom).'*'.self::escape($nom).'*)';
        } else {
            $term = $prenom !== '' ? $prenom : $nom;
            $parts[] = '('.$prenomAttr.'=*'.self::escape($term).'*)';
            $parts[] = '('.$nomAttr.'=*'.self::escape($term).'*)';
            $parts[] = '(cn=*'.self::escape($term).'*)';
            $parts[] = '(displayName=*'.self::escape($term).'*)';
        }

        foreach ($searchAttrs as $attr) {
            $term = trim($prenom.' '.$nom);
            if ($term !== '') {
                $parts[] = '('.$attr.'=*'.self::escape($term).'*)';
            }
        }

        $filter = self::withAdditionalFilter($config, '(|'.implode('', $parts).')');
        $rows = self::ldapRows($ds, $config['ldap_base'], $filter, self::attributes(), self::MAX_RESULTS);
        @ldap_unbind($ds);

        return self::rowsToSuggestions($rows, 'LDAP');
    }

    private static function searchByLogin($login, $limit)
    {
        if (!self::isConfigured()) {
            return array();
        }

        $config = self::config();
        $ds = self::connect($config);
        if (!$ds) {
            return array();
        }

        $parts = array();
        foreach (self::searchAttributes() as $attr) {
            $parts[] = '('.$attr.'='.self::escape($login).')';
        }
        $filter = self::withAdditionalFilter($config, '(|'.implode('', $parts).')');
        $rows = self::ldapRows($ds, $config['ldap_base'], $filter, self::attributes(), (int) $limit);
        @ldap_unbind($ds);

        return self::rowsToSuggestions($rows, 'LDAP');
    }

    private static function rowsToSuggestions($rows, $source)
    {
        $suggestions = array();
        foreach ($rows as $row) {
            $login = self::firstValue($row, self::searchAttributes());
            if ($login === '') {
                continue;
            }
            $suggestions[] = self::suggestion(
                $login,
                $source,
                self::firstValue($row, array(self::setting('ldap_champ_nom', 'sn'), 'sn')),
                self::firstValue($row, array(self::setting('ldap_champ_prenom', 'givenname'), 'givenname')),
                self::firstValue($row, array(self::setting('ldap_champ_email', 'mail'), 'mail'))
            );
        }

        return $suggestions;
    }

    private static function suggestion($login, $source, $nom, $prenom, $email)
    {
        $label = trim((string) $prenom.' '.(string) $nom);
        if ($label === '') {
            $label = (string) $login;
        }
        $suffix = trim((string) $email);
        if ($suffix !== '') {
            $label .= ' - '.$suffix;
        }

        return array(
            'login' => (string) $login,
            'source' => (string) $source,
            'label' => '['.$source.'] '.$label.' ('.$login.')',
            'nom' => (string) $nom,
            'prenom' => (string) $prenom,
            'email' => (string) $email,
        );
    }

    private static function ldapRows($ds, $base, $filter, $attributes, $limit)
    {
        $base = trim((string) $base);
        if (!$ds || $base === '') {
            return array();
        }

        $result = @ldap_search($ds, $base, $filter, $attributes, 0, (int) $limit);
        if (!$result) {
            return array();
        }

        $entries = @ldap_get_entries($ds, $result);
        if (!is_array($entries) || !isset($entries['count'])) {
            return array();
        }

        $rows = array();
        for ($i = 0; $i < (int) $entries['count']; $i++) {
            if (isset($entries[$i]) && is_array($entries[$i])) {
                $rows[] = $entries[$i];
            }
        }

        return $rows;
    }

    private static function connect($config)
    {
        $uri = (string) $config['ldap_adresse'];
        if ((string) $config['ldap_port'] !== '') {
            $uri .= ':'.(string) $config['ldap_port'];
        }
        $ds = @ldap_connect($uri);
        if (!$ds) {
            return false;
        }

        @ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
        @ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
        if (!empty($config['use_tls']) && !@ldap_start_tls($ds)) {
            return false;
        }

        if ((string) $config['ldap_login'] !== '') {
            return @ldap_bind($ds, $config['ldap_login'], $config['ldap_pwd']) ? $ds : false;
        }

        return @ldap_bind($ds) ? $ds : false;
    }

    private static function attributes()
    {
        $attributes = array_merge(
            self::searchAttributes(),
            array(
                self::setting('ldap_champ_nom', 'sn'),
                self::setting('ldap_champ_prenom', 'givenname'),
                self::setting('ldap_champ_email', 'mail'),
                'cn',
                'displayName',
                'sn',
                'givenname',
                'mail',
            )
        );

        $clean = array();
        foreach ($attributes as $attribute) {
            $attribute = self::attribute($attribute);
            if ($attribute !== '') {
                $clean[$attribute] = $attribute;
            }
        }

        return array_values($clean);
    }

    private static function searchAttributes()
    {
        $value = self::setting('ldap_champ_recherche', 'uid');
        $attributes = array();
        foreach (explode('|', $value) as $attribute) {
            $attribute = self::attribute($attribute);
            if ($attribute !== '') {
                $attributes[] = $attribute;
            }
        }

        return count($attributes) > 0 ? $attributes : array('uid');
    }

    private static function attribute($attribute)
    {
        $attribute = trim((string) $attribute);
        return preg_match('/^[a-zA-Z0-9._-]{1,80}$/', $attribute) === 1 ? $attribute : '';
    }

    private static function firstValue($row, $attributes)
    {
        foreach ($attributes as $attribute) {
            $key = strtolower((string) $attribute);
            if (isset($row[$key][0])) {
                return trim((string) $row[$key][0]);
            }
        }

        return '';
    }

    private static function withAdditionalFilter($config, $filter)
    {
        $extra = trim((string) $config['ldap_filter']);
        if ($extra === '') {
            return $filter;
        }

        return '(&'.$filter.$extra.')';
    }

    private static function escape($value)
    {
        if (function_exists('ldap_escape')) {
            return ldap_escape((string) $value, '', LDAP_ESCAPE_FILTER);
        }

        return str_replace(
            array('\\', '*', '(', ')', "\x00"),
            array('\5c', '\2a', '\28', '\29', '\00'),
            (string) $value
        );
    }

    private static function setting($name, $default)
    {
        $value = class_exists('Settings') ? Settings::get($name) : null;
        $value = trim((string) $value);

        return $value === '' ? $default : $value;
    }

    private static function config()
    {
        $ldap_adresse = '';
        $ldap_port = '';
        $ldap_login = '';
        $ldap_pwd = '';
        $ldap_base = '';
        $ldap_filter = '';
        $use_tls = false;

        include self::configPath();

        return array(
            'ldap_adresse' => (string) $ldap_adresse,
            'ldap_port' => (string) $ldap_port,
            'ldap_login' => (string) $ldap_login,
            'ldap_pwd' => (string) $ldap_pwd,
            'ldap_base' => (string) $ldap_base,
            'ldap_filter' => (string) $ldap_filter,
            'use_tls' => (bool) $use_tls,
        );
    }

    private static function configPath()
    {
        $root = defined('GRR_INFORMATIQUE_MATERIEL_ROOT')
            ? GRR_INFORMATIQUE_MATERIEL_ROOT
            : dirname(__DIR__, 4);

        return $root.'/personnalisation/config_ldap.inc.php';
    }
}
