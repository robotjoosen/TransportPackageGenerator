<?php
declare(strict_types=1);

use RobotJoosen\TransportPackageGenerator\Builder;
use Symfony\Component\Yaml\Yaml;

define('ROOT_PATH', dirname(__FILE__) . '/');
require_once ROOT_PATH . 'vendor/autoload.php';

/** Check if build configurator is present */
if (file_exists(ROOT_PATH . 'build.config.php')) {
    require_once ROOT_PATH . 'build.config.php';
} else {
    echo "Build configuration file not found in " . ROOT_PATH . 'build.config.php' . ".\nRename build.config.sample.php to build.config.php and update values;\nOr use command below:\n~ mv build.config.sample.php build.config.php\n";
    exit();
}

/** Check if MODX installation is present */
if (
    file_exists(MODX_CORE_PATH . 'config/config.inc.php')
    && file_exists(MODX_CORE_PATH . 'model/modx/modx.class.php')
) {
    require_once MODX_CORE_PATH . 'config/config.inc.php';
    require_once MODX_CORE_PATH . 'model/modx/modx.class.php';

    /** Initialize Modx */
    $modx = new modX();
    $modx->initialize('mgr');
} else {
    echo "MODX Installation not found";
    exit();
}

/** Get configuration settings */
if (file_exists(PKG_PATH . 'package.config.yaml')) {
    try {
        $config_yaml = file_get_contents(PKG_PATH . 'package.config.yaml');
        $package_config = Yaml::parse($config_yaml);
    } catch (Exception $exception) {
        echo "Invalid package configuration file\n";
        exit();
    }
} else {
    echo "Configuration file not found: " . PKG_PATH . "package.config.yaml\n";
    exit();
}

/** Start building */
$builder = new Builder($modx, $package_config);
$builder->build();
unset($builder);
