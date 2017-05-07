<?php

/**
 * Created by PhpStorm.
 * User: Ozodrukh
 * Date: 4/20/17
 * Time: 10:53 AM
 *
 * @method static debug(string $tag, mixed $message)
 * @method static info(string $tag, mixed $message)
 * @method static warning(string $tag, mixed $message)
 * @method static error(string $tag, mixed $message, ?Exception $e)
 */
class Log
{

    private const DATE_FORMAT = 'Y-m-d H:i:s';
    private const ACCEPTED = [
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

    public static function __callStatic($name, $arguments)
    {
        if (in_array(strtolower($name), self::ACCEPTED)) {
            if (count($arguments) == 2) {
                $arguments[2] = null;
            }
            self::write($name, $arguments[0], $arguments[1], $arguments[2]);
            return;
        }
        throw new BadMethodCallException("Log::{$name} not found");
    }

    /**
     * @param $level string DEBUG|INFO\WARN\ERROR
     * @param $tag string Message context
     * @param $message mixed Message to print
     * @param $error Exception occurred
     *
     * @return bool
     */
    public static function write($level = "debug", string $tag, $message, ?Exception $error = null)
    {
        if (!in_array(strtolower($level), Log::ACCEPTED)) {
            throw new InvalidArgumentException("Unsupported log `level` type");
        } elseif (!Log::isLoggable($tag)) {
            return false;
        }

        $tag = substr($tag, 0, strlen($tag) > 22 ? 22 : strlen($tag));
        $date = date(Log::DATE_FORMAT);
        $level = strtoupper($level);

        if (!is_string($message) && !method_exists($message, '__toString')) {
            $message = var_export($message, true);
        }

        file_put_contents("php://stderr", "{$level} - {$date} {$tag}: $message\n");

        if (isset($error)) {
            file_put_contents('php://stderr', "{$error->getTraceAsString()}\n");
        }

        return true;
    }
}