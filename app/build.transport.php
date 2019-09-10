<?php
declare(strict_types=1);

use RobotJoosen\TransportPackageGenerator\Builder;
use Symfony\Component\Yaml\Yaml;

define('ROOT_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);
define('MODX_BASE_PATH', getcwd() . DIRECTORY_SEPARATOR);
define('MODX_CORE_PATH', MODX_BASE_PATH . 'core/');

/** Check if dependencies are installed */
$autoload_path = ROOT_PATH . 'vendor/autoload.php';
if (file_exists($autoload_path)) {
    require_once $autoload_path;
} else {
    echo "Looks like dependencies have not been installed with Composer.";
    exit();
}
unset($autoload_path);

/** Check if build configurator is present */
$build_config_path = ROOT_PATH . 'build.config.php';
if (file_exists($build_config_path)) {
    require_once $build_config_path;
} else {
    echo "Build configuration file not found.\nRename build.config.sample.php to build.config.php and update values;\nOr use command below:\n~ mv build.config.sample.php build.config.php\n";
    exit();
}
unset($build_config_path);

/** Check if MODX installation is present */
$modx_config_path = MODX_CORE_PATH . 'config/config.inc.php';
$modx_class_path = MODX_CORE_PATH . 'model/modx/modx.class.php';
if (
    file_exists($modx_config_path) &&
    file_exists($modx_class_path)
) {
    require_once $modx_config_path;
    require_once $modx_class_path;

    /** Initialize Modx */
    $modx = new modX();
    $modx->initialize('mgr');
} else {
    echo "MODX was not found, check if you're in the right directory";
    exit();
}
unset($modx_config_path, $modx_class_path);

/** Get configuration settings */
$package_config_file = PKG_PATH . 'package.config.yaml';
if (file_exists($package_config_file)) {
    try {
        $config_yaml = file_get_contents($package_config_file);
        $package_config = Yaml::parse($config_yaml);
    } catch (Symfony\Component\Yaml\Exception\ParseException $exception) {
        echo "Invalid package configuration file\n";
        exit();
    }
} else {
    echo "Configuration file not found: " . PKG_PATH . "package.config.yaml\n";
    exit();
}
unset($package_config_file);

/** Start building */

$builder = new Builder($modx, $package_config);
$builder->build();
unset($builder);
