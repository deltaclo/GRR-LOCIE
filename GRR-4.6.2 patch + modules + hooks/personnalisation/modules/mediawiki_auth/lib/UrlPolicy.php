<?php

class GrrMediaWikiAuthUrlPolicy
{
    public static function normalizeReturnTarget($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return GrrMediaWikiAuthConfig::allowedPath();
        }
        if ($value[0] !== '/' || strpos($value, '//') === 0) {
            return null;
        }
        if (strpos($value, "\r") !== false || strpos($value, "\n") !== false) {
            return null;
        }

        $parts = parse_url($value);
        if ($parts === false
            || isset($parts['scheme'])
            || isset($parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])
        ) {
            return null;
        }

        $path = isset($parts['path']) ? (string) $parts['path'] : '';
        $decodedPath = rawurldecode($path);
        if (preg_match('/[\x00-\x1F\x7F]/', $decodedPath)
            || strpos($decodedPath, "\\") !== false
            || self::containsParentSegment($decodedPath)
        ) {
            return null;
        }
        if (strpos($decodedPath, GrrMediaWikiAuthConfig::allowedPath()) !== 0) {
            return null;
        }

        $target = $path;
        if (isset($parts['query']) && $parts['query'] !== '') {
            $target .= '?'.$parts['query'];
        }

        return $target;
    }

    private static function containsParentSegment($path)
    {
        $segments = explode('/', (string) $path);
        foreach ($segments as $segment) {
            if ($segment === '..') {
                return true;
            }
        }

        return false;
    }
}
