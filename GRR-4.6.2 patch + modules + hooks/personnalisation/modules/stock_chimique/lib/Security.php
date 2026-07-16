<?php

class StockChimiqueSecurity
{
    const CSRF_SESSION_KEY = 'stock_chimique_csrf';

    public static function currentLogin()
    {
        return function_exists('getUserName') ? trim((string) getUserName()) : '';
    }

    public static function isAdmin($login = null)
    {
        $login = $login === null ? self::currentLogin() : trim((string) $login);
        return $login !== ''
            && class_exists('SecuAccess')
            && SecuAccess::UserLevel($login, -1) >= 6;
    }

    public static function role($login = null)
    {
        $login = $login === null ? self::currentLogin() : trim((string) $login);
        if (self::isAdmin($login)) {
            return 'administrateur';
        }

        return StockChimiqueRepository::roleForLogin($login);
    }

    public static function canAccess($login = null)
    {
        return self::role($login) !== '';
    }

    public static function canOperate($login = null)
    {
        return in_array(self::role($login), array('operateur', 'gestionnaire', 'administrateur'), true);
    }

    public static function canManage($login = null)
    {
        return in_array(self::role($login), array('gestionnaire', 'administrateur'), true);
    }

    public static function token()
    {
        if (!isset($_SESSION[self::CSRF_SESSION_KEY]) || !preg_match('/^[a-f0-9]{64}$/', (string) $_SESSION[self::CSRF_SESSION_KEY])) {
            try {
                $_SESSION[self::CSRF_SESSION_KEY] = bin2hex(random_bytes(32));
            } catch (Throwable $exception) {
                $_SESSION[self::CSRF_SESSION_KEY] = hash('sha256', uniqid('', true).mt_rand());
            }
        }

        return (string) $_SESSION[self::CSRF_SESSION_KEY];
    }

    public static function field()
    {
        return '<input type="hidden" name="sc_csrf" value="'.htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8').'">';
    }

    public static function validatePost()
    {
        $expected = isset($_SESSION[self::CSRF_SESSION_KEY]) ? (string) $_SESSION[self::CSRF_SESSION_KEY] : '';
        $received = isset($_POST['sc_csrf']) ? (string) $_POST['sc_csrf'] : '';
        return $expected !== '' && $received !== '' && hash_equals($expected, $received);
    }

    public static function requestToken()
    {
        try {
            return bin2hex(random_bytes(32));
        } catch (Throwable $exception) {
            return hash('sha256', uniqid('', true).mt_rand());
        }
    }
}

