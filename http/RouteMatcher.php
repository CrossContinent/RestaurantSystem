<?php

/**
 * Created by PhpStorm.
 * User: Ozodrukh
 * Date: 5/3/17
 * Time: 6:55 PM
 */
class RouteMatcher
{

    public const MATCHED_NONE = 0;
    public const MATCHED_PARTIAL = 1;
    public const MATCHED_FULL = 2;

    /**
     * /path/path
     *
     * @param string $dest
     * @param string $path
     * @return array|bool
     */
    public static function match(string $dest, string $path)
    {
        if ($dest[0] === '/') {
            $dest = substr($dest, 1);
        }

        if ($path[0] === '/') {
            $path = substr($path, 1);
        }

        $argsPos = strpos($path, '?');
        if ($argsPos !== false) {
            $path = substr($path, 0, $argsPos);
        }

        $matches = [];

        $seq = 0;
        $index = 0;
        $len = min(strlen($dest), strlen($path));

        // Collect all matches
        while ($index < $len) {
            if ($path[$index] !== $dest[$index]) {
                return ['status' => static::MATCHED_NONE];
            }

            if ($path[$index] === '/' && $seq > 0) {
                array_push($matches, substr($path, $index - $seq, $seq));
                $seq = 0;
            } else {
                $seq += 1;
            }

            $index++;
        }

        if ($seq > 0) {
            array_push($matches, substr($path, $index - $seq, $seq));
        }

        $status = static::MATCHED_FULL;
        $remained = strlen($dest) > strlen($path) ? $dest : $path;
        for (; $index < strlen($remained); $index++) {
            if ($remained[$index] !== '/') {
                $status = static::MATCHED_PARTIAL;
                break;
            }
        }

        // Trailing could be left in the $path
        if ($status == static::MATCHED_PARTIAL && $remained == $dest) {
            return ['status' => static::MATCHED_NONE];
        }

        return ["matches" => $matches, "status" => $status];
    }
}