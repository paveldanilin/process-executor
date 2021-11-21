<?php

namespace Paveldanilin\ProcessExecutor\Log;

use Psr\Log\LogLevel;

abstract class LevelHelper
{
    public const DEBUG = 100;
    public const INFO = 200;
    public const NOTICE = 250;
    public const WARNING = 300;
    public const ERROR = 400;
    public const CRITICAL = 500;
    public const ALERT = 550;
    public const EMERGENCY = 600;

    private static array $MAP_LEVEL_INT = [
        LogLevel::DEBUG => self::DEBUG,
        LogLevel::INFO => self::INFO,
        LogLevel::NOTICE => self::NOTICE,
        LogLevel::WARNING => self::WARNING,
        LogLevel::ERROR => self::ERROR,
        LogLevel::CRITICAL => self::CRITICAL,
        LogLevel::ALERT => self::ALERT,
        LogLevel::EMERGENCY => self::EMERGENCY,
    ];

    public static function getIntValue(string $level): int
    {
        return static::$MAP_LEVEL_INT[\strtolower($level)] ?? 0;
    }
}
