<?php
/**
 * Tracking Pluggin
 */

$basePath = $modx->getOption(
    'usertracking.core_path',
    null,
    $modx->getOption('core_path', null, MODX_CORE_PATH) . 'components/usertracking/'
);
require_once $basePath . 'vendor/autoload.php';

use RobotJoosen\Tracking\Tracking;

$eventName = $modx->event->name;
switch ($eventName) {
    case 'OnWebPageInit':
        $referer_url = parse_url($_SERVER['HTTP_REFERER']);
        $parameters = [
            'request_url' => $_SERVER['REQUEST_URI'],
            'referer_url' => (in_array($_SERVER['SERVER_NAME'], $referer_url)) ? $referer_url['path'] : $_SERVER['HTTP_REFERER'],
            'beacon_id' => '_LT54822E05-BF6F-65A4-83F7-0287PAGEVIEW',
            'tracking_id' => $_COOKIE["PHPSESSID"]
        ];
        $tracking = new Tracking($modx, $parameters);
        $tracking->webBeacon();
        break;
    default:
        break;
}
return;