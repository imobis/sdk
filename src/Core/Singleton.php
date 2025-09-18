<?php

namespace Nexus\Message\Sdk\Core;

class Singleton
{
    private static array $instances = [];

    protected function __construct() {}

    protected function __clone() {}

    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }

    public static function getInstance(...$args)
    {
        $subclass = static::class;
        if (!isset(self::$instances[$subclass])) {
            self::$instances[$subclass] = new static(...$args);
        }

        return self::$instances[$subclass];
    }

    public function forgetInstance(): bool
    {
        $subclass = static::class;
        if (isset(self::$instances[$subclass])) {
            unset(self::$instances[$subclass]);

            return true;
        }

        return false;
    }
}