<?php

namespace mpyw\Co\Internal;

class TypeUtils
{
    /**
     * Get identifier of cURL handle or Generator
     * @param  resource|object $value
     * @return string
     */
    public static function getIdOfCurlHandleOrGenerator($value): string
    {
        if (is_resource($value)) {
            return (string) $value;
        }
        return spl_object_hash($value);
    }

    /**
     * Check if value is a valid cURL handle.
     * @param  mixed $value
     * @return bool
     */
    public static function isCurl($value)
    {
        if (version_compare(phpversion(), '8.0.0', '<')) {
            return is_resource($value) && get_resource_type($value) === 'curl';
        }

        return $value instanceof \CurlHandle;
    }

    /**
     * Check if value is a valid Generator.
     * @param  mixed $value
     * @return bool
     */
    public static function isGeneratorContainer($value)
    {
        return $value instanceof GeneratorContainer;
    }

    /**
     * Check if value is a valid Generator closure.
     * @param  mixed $value
     * @return bool
     */
    public static function isGeneratorClosure($value)
    {
        return $value instanceof \Closure
            && (new \ReflectionFunction($value))->isGenerator();
    }

    /**
     * Check if value is Throwable, excluding RuntimeException.
     * @param  mixed $value
     * @return bool
     */
    public static function isFatalThrowable($value)
    {
        return !$value instanceof \RuntimeException
            && ($value instanceof \Throwable || $value instanceof \Exception);
    }
}
