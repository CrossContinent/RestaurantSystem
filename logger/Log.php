<?php

/**
 * Created by PhpStorm.
 * User: Ozodrukh
 * Date: 4/20/17
 * Time: 10:53 AM
 */
class Log
{

    const DATE_FORMAT = 'Y-m-d H:i:s';
    const ACCEPTED = [
        "debug",
        "info",
        "warning",
        "error"
    ];

    private static $ALLOWED_TAGS = [];

    /**
     * Sets whether specific tag can be loggable or not
     *
     * @param $tag string Tag name
     * @param $value bool True if logging enabled, False when not
     */
    static function setLoggable($tag, $value)
    {
        Log::$ALLOWED_TAGS[$tag] = $value;
    }

    /**
     * @param $tag string
     * @return bool
     */
    static function isLoggable($tag)
    {
        return !isset(Log::$ALLOWED_TAGS[$tag]) || Log::$ALLOWED_TAGS[$tag];
    }

    /**
     * @param $level string DEBUG|INFO\WARN\ERROR
     * @param $tag string Message context
     * @param $message string Message to print
     * @param $error Exception occurred
     *
     * @return bool
     */
    public static function write($level = "debug", $tag, $message, $error = null)
    {
        if (!in_array(strtolower($level), Log::ACCEPTED)) {
            throw new InvalidArgumentException("Unsupported log `level` type");
        } elseif (!Log::isLoggable($tag)) {
            return false;
        }

        $tag = substr($tag, 0, strlen($tag) > 22 ? 22 : strlen($tag));
        $date = date(Log::DATE_FORMAT);
        $level = strtoupper($level);

        file_put_contents('php://stderr', "{$level} - {$date} {$tag}: $message\n");

        if (isset($error)) {
            file_put_contents('php://stderr', $error->getTrace());
        }

        return true;
    }
}