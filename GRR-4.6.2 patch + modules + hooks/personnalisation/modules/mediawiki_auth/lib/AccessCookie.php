<?php

class GrrMediaWikiAccessCookie
{
    public static function issue($userAgent)
    {
        $token = GrrMediaWikiAccessToken::issue($userAgent);
        if ($token === false || GrrMediaWikiAccessToken::verify($token, $userAgent) === false) {
            return false;
        }

        return setcookie(
            GrrMediaWikiAuthConfig::cookieName(),
            $token,
            array(
                'expires' => time() + GrrMediaWikiAuthConfig::ttl(),
                'path' => GrrMediaWikiAuthConfig::cookiePath(),
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Strict',
            )
        );
    }

    public static function clear()
    {
        $cookieName = GrrMediaWikiAuthConfig::cookieName();
        $cleared = setcookie(
            $cookieName,
            '',
            array(
                'expires' => time() - 3600,
                'path' => GrrMediaWikiAuthConfig::cookiePath(),
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Strict',
            )
        );

        unset($_COOKIE[$cookieName]);

        return $cleared;
    }
}
