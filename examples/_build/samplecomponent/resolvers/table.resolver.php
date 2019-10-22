<?php
/**
 * @package example
 */

if ($object->xpdo) {
    $modx =& $object->xpdo;
    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
        case xPDOTransport::ACTION_UPGRADE:
            $namespace = $options['namespace'];
            $modelPath = $modx->getOption($namespace . '.core_path', null, $modx->getOption('core_path') . 'components/' . $namespace . '/') . 'model/';
            $modx->addPackage($namespace, $modelPath, null);

            $manager = $modx->getManager();
            $manager->createObjectContainer('SampleComponentContent');

            break;
    }
}
return true;