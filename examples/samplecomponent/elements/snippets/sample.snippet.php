<?php
/**
 * ParseData
 * @package parsedata
 * @author Roald Joosen <robotjoosen@gmail.com>
 */

$basePath = $modx->getOption(
        'parsedata.core_path',
        null,
        $modx->getOption('core_path', null, MODX_CORE_PATH) . 'components/parsedata/'
    );
require_once $basePath . 'vendor/autoload.php';


use RobotJoosen\ParseData;

$parseData = new ParseData($modx, $scriptProperties);
return $parseData->parse();