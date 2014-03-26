<?php

namespace Rf\Aws;

/**
* クラスローダー
*
*/
class AutoLoader
{
    private static $base_dir;

    /**
     * ディレクトリを登録します。
     *
     * @param string $dir ディレクトリ名
     */
    public static function register($dir = __DIR__)
    {
        if (!is_dir($dir)) {
            throw new Exception("Directory Not Found. ($dir)");
        }

        static::$base_dir = $dir;
        spl_autoload_register(array(__CLASS__, '_load'));
    }

    /**
     * クラスをロードします。既にロードされている場合は無視します。
     *
     * @param  string $classname クラス名
     * @return void
     */
    private static function _load($classname)
    {
        if (class_exists($classname, false) || interface_exists($classname, false)) {
            return false;
        }

        $parts = explode("\\", $classname);
        array_shift($parts);
        array_shift($parts);
        $expected_path = join(DIRECTORY_SEPARATOR, array(static::$base_dir, join(DIRECTORY_SEPARATOR, $parts) . ".php"));

        if (is_file($expected_path)) {
            require $expected_path;
            return true;
        }

        return false;
    }
}