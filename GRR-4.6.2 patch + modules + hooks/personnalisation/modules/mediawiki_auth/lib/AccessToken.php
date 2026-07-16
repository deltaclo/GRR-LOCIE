<?php

class GrrMediaWikiAccessToken
{
    public static function issue($userAgent, $now = null)
    {
        $now = $now === null ? time() : (int) $now;
        $payload = array(
            'v' => 1,
            'aud' => GrrMediaWikiAuthConfig::audience(),
            'iat' => $now,
            'exp' => $now + GrrMediaWikiAuthConfig::ttl(),
            'ua' => hash('sha256', (string) $userAgent),
            'jti' => self::randomIdentifier(),
        );

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
        if (!is_string($json)) {
            return false;
        }

        $encodedPayload = self::base64UrlEncode($json);
        $signature = hash_hmac(
            'sha256',
            $encodedPayload,
            GrrMediaWikiAuthConfig::secret(),
            true
        );

        return $encodedPayload.'.'.self::base64UrlEncode($signature);
    }

    public static function verify($token, $userAgent, $now = null)
    {
        if (!is_string($token) || substr_count($token, '.') !== 1) {
            return false;
        }

        list($encodedPayload, $encodedSignature) = explode('.', $token, 2);
        $signature = self::base64UrlDecode($encodedSignature);
        if ($signature === false) {
            return false;
        }

        $expected = hash_hmac(
            'sha256',
            $encodedPayload,
            GrrMediaWikiAuthConfig::secret(),
            true
        );
        if (!hash_equals($expected, $signature)) {
            return false;
        }

        $json = self::base64UrlDecode($encodedPayload);
        $payload = is_string($json) ? json_decode($json, true) : null;
        if (!is_array($payload)) {
            return false;
        }

        $now = $now === null ? time() : (int) $now;
        if (!isset($payload['v'], $payload['aud'], $payload['iat'], $payload['exp'], $payload['ua'])) {
            return false;
        }
        if ((int) $payload['v'] !== 1
            || !hash_equals(GrrMediaWikiAuthConfig::audience(), (string) $payload['aud'])
            || (int) $payload['iat'] > $now + 30
            || (int) $payload['exp'] < (int) $payload['iat']
            || (int) $payload['exp'] < $now
            || (int) $payload['exp'] > $now + 600
            || !hash_equals(hash('sha256', (string) $userAgent), (string) $payload['ua'])
        ) {
            return false;
        }

        return $payload;
    }

    private static function randomIdentifier()
    {
        try {
            return bin2hex(random_bytes(8));
        } catch (Throwable $exception) {
            return substr(hash('sha256', uniqid('', true).mt_rand()), 0, 16);
        }
    }

    private static function base64UrlEncode($value)
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private static function base64UrlDecode($value)
    {
        if (!is_string($value) || !preg_match('/^[A-Za-z0-9_-]+$/', $value)) {
            return false;
        }

        $padding = strlen($value) % 4;
        if ($padding > 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        return base64_decode(strtr($value, '-_', '+/'), true);
    }
}
