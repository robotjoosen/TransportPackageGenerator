<?php
/**
 * Define the MODX path constants necessary for core installation
 */
 define('MODX_BASE_PATH', 'path/to/modx/'); //TODO: Set correct path to your MODX Installation
 define('MODX_CORE_PATH', MODX_BASE_PATH . 'core/');
 define('MODX_MANAGER_PATH', MODX_BASE_PATH . 'manager/');
 define('MODX_CONNECTORS_PATH', MODX_BASE_PATH . 'connectors/');
 define('MODX_ASSETS_PATH', MODX_BASE_PATH . 'assets/');
 define('MODX_CONFIG_KEY', 'config');

 define('MODX_BASE_URL','/');
 define('MODX_CORE_URL', MODX_BASE_URL . 'core/');
 define('MODX_MANAGER_URL', MODX_BASE_URL . 'manager/');
 define('MODX_CONNECTORS_URL', MODX_BASE_URL . 'connectors/');
 define('MODX_ASSETS_URL', MODX_BASE_URL . 'assets/');

 define('PKG_PATH', MODX_CORE_PATH . 'components/component_name/'); // TODO: Set path to your component
