<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit448f79a9dadca22db354a00c02c9931a
{
    public static $prefixLengthsPsr4 = array (
        'm' => 
        array (
            'modmore\\Commerce_Slack\\' => 23,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'modmore\\Commerce_Slack\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit448f79a9dadca22db354a00c02c9931a::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit448f79a9dadca22db354a00c02c9931a::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}