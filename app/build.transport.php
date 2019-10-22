<?php
declare(strict_types=1);

use RobotJoosen\TransportPackageGenerator\Builder;
use Symfony\Component\Yaml\Yaml;

define('ROOT_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);

define('MODX_BASE_URL', getcwd() . DIRECTORY_SEPARATOR);
define('MODX_MANAGER_URL', MODX_BASE_PATH . 'manager/');
define('MODX_ASSETS_URL', MODX_BASE_PATH . 'assets/');
define('MODX_CONNECTORS_URL', MODX_BASE_PATH . 'connectors/');

define('MODX_CORE_PATH', MODX_BASE_PATH . 'core/');
define('MODX_BASE_PATH', MODX_BASE_URL);
define('MODX_MANAGER_PATH', MODX_MANAGER_URL);
define('MODX_CONNECTORS_PATH', MODX_CONNECTORS_URL);
define('MODX_ASSETS_PATH', MODX_ASSETS_URL);

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
$arguments = [];
if (isset($argc)) {
    for ($i = 0; $i < $argc; $i++) {
        $arguments[] = $argv[$i];
    }
} else {
    echo "argc and argv disabled\n";
    exit();
}
if (isset($arguments[1])) {
    define('PKG_NAME_LOWER', $arguments[1]);
    define('PKG_PATH', MODX_CORE_PATH . 'components/' . $arguments[1] . '/');
} else {
    echo 'Package not defined, please use: TransportPackageGenerator package_name';
    exit();
}

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

if (!class_exists('modX')) {
    echo "Oops, something went wrong. Failed to initialize MODX";
    exit();
}

/** Get configuration settings */
$package_config_file = MODX_BASE_PATH . '../_build/' . PKG_NAME_LOWER . '/package.config.yaml';
if (file_exists($package_config_file)) {
    try {
        $config_yaml = file_get_contents($package_config_file);
        $package_config = Yaml::parse($config_yaml);
    } catch (Symfony\Component\Yaml\Exception\ParseException $exception) {
        echo "Invalid package configuration file\n";
        exit();
    }
} else {
    echo "Configuration file not found: " . $package_config_file;
    exit();
}
unset($package_config_file);

/** Start building */
$builder = new Builder($modx, $package_config);
$builder->build();
unset($builder);
