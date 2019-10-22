<?php
/**
 * Transport Package Builder
 * @todo Template Variable
 */

namespace RobotJoosen\TransportPackageGenerator;

use modX;
use modPackageBuilder;
use modTransportVehicle;
use xPDOSimpleObject;
use xPDOTransport;
use modProcessor;

class Builder
{

    /**
     * @var modX
     */
    private $modx;

    /**
     * @var modPackageBuilder
     */
    private $builder;

    /**
     * @var array
     */
    private $config;

    /**
     * @var int
     */
    private $clock = 0;

    /**
     * @var array
     */
    private $errors = [];

    /**
     * @var array
     */
    private $notify = [];

    // ======================================================================
    //  Magic Methods
    // ======================================================================

    /**
     * TransportBuilder constructor.
     * @param modX $modx
     * @param array $config
     */
    public function __construct(modX &$modx, $config = [])
    {
        $this->modx = &$modx;
        $this->modx->setLogLevel(modX::LOG_LEVEL_INFO);
        $this->modx->setLogTarget('ECHO');
        $this->timer('start');
        $this->config = array_merge([
            'PKG_NAME' => null,
            'PKG_VERSION' => null,
            'PKG_RELEASE' => null,
            'PKG_PATH' => null,
            'build' => '/',
            'data' => '/data/',
            'resolvers' => '/resolvers/',
            'snippets' => []
        ], $config);
        $this->config['PKG_NAME_LOWER'] = strtolower($this->config['PKG_NAME']);
        $this->config = array_merge($this->config, [
            'chunk_path' => MODX_CORE_PATH . 'components/' . $this->config['PKG_NAME_LOWER'] . '/elements/chunks/',
            'snippet_path' => MODX_CORE_PATH . 'components/' . $this->config['PKG_NAME_LOWER'] . '/elements/snippets/',
            'plugin_path' => MODX_CORE_PATH . 'components/' . $this->config['PKG_NAME_LOWER'] . '/elements/plugins/',
            'template_path' => MODX_CORE_PATH . 'components/' . $this->config['PKG_NAME_LOWER'] . '/elements/templates/',
            'lexicon_path' => MODX_CORE_PATH . 'components/' . $this->config['PKG_NAME_LOWER'] . '/lexicon/',
            'docs_path' => MODX_CORE_PATH . 'components/' . $this->config['PKG_NAME_LOWER'] . '/docs/',
            'pages_path' => MODX_CORE_PATH . 'components/' . $this->config['PKG_NAME_LOWER'] . '/elements/pages/',
            'source_assets' => MODX_ASSETS_PATH . 'components/' . $this->config['PKG_NAME_LOWER'] . '/',
            'source_core' => MODX_CORE_PATH . 'components/' . $this->config['PKG_NAME_LOWER'] . '/'
        ]);
        $this->modx->loadClass('transport.modPackageBuilder', '', false, true);
    }

    /**
     * TransportBuilder Destruction
     */
    public function __destruct()
    {
        if (!empty($this->notify)) {
            $this->infoMessage("NOTIFICATIONS: " . count($this->notify) . " notifications: \n" . print_r($this->notify, 1));
        }
        if (!empty($this->errors)) {
            $this->infoMessage("ERRORS: " . count($this->errors) . " errors: \n" . print_r($this->errors, 1));
        }
        $this->timer('stop');
    }

    // ======================================================================
    //  Builder
    // ======================================================================

    /**
     * Build
     */
    public function build(): void
    {
        if (
            !is_null($this->config['PKG_NAME']) &&
            !is_null($this->config['PKG_VERSION']) &&
            !is_null($this->config['PKG_RELEASE'])
        ) {
            $this->createBuilder();
            $this->createPackage();
            $this->addSystemSettings();
            $this->addMenuItems();
            $this->addNamespaces();
//            $this->addAccessPolicies();
//            $this->addAccesPolicyTemplates();
            if ($category = $this->setCategory()) {
                $this->addSnippets($category);
                $this->addChunks($category);
                $this->addTemplates($category);
                $this->addPlugins($category);
                $vehicle = $this->createCategoryVehicle($category);
                $this->addFiles($vehicle);
                $this->builder->putVehicle($vehicle);
            } else {
                $this->errorMessage('Failed to create category');
            }
            $this->addDocs();
            $this->pack();
            exit();
        }
        $this->errorMessage('Package build failed. Missing package information.');
    }

    /**
     * Create Package
     */
    private function createPackage(): void
    {
        $this->builder->createPackage($this->config['PKG_NAME_LOWER'], $this->config['PKG_VERSION'], $this->config['PKG_RELEASE']);
        $this->builder->registerNamespace($this->config['PKG_NAME_LOWER'], false, true, '{core_path}components/' . $this->config['PKG_NAME_LOWER'] . '/');
        $this->infoMessage('Created Transport Package and Namespace.');
    }

    /**
     * Create Package Builder
     */
    private function createBuilder(): void
    {
        $this->builder = new modPackageBuilder($this->modx);
        $this->infoMessage('Transport Builder started...');
    }

    /**
     * Pack the Package
     */
    private function pack(): void
    {
        $this->infoMessage('Packing up transport package zip...');
        $this->builder->pack();
    }

    // ======================================================================
    //  Docs and Files
    // ======================================================================

    /**
     * Add Documentation
     */
    private function addDocs(): void
    {
        $docs = [
            ['name' => 'license', 'path' => $this->config['docs_path'] . 'license.md'],
            ['name' => 'readme', 'path' => $this->config['docs_path'] . 'readme.md'],
            ['name' => 'changelog', 'path' => $this->config['docs_path'] . 'changelog.md']
        ];
        $package_attributes = [];
        foreach ($docs as $doc) {
            if (file_exists($doc['path'])) {
                $package_attributes[$doc['name']] = file_get_contents($doc['path']);
            } else {
                $this->errorMessage($doc['name'] . ' file not found in ' . $doc['path']);
            }
        }
        if (!empty($package_attributes)) {
            $this->builder->setPackageAttributes($package_attributes);
            $this->infoMessage('Added package attributes.');
        } else {
            $this->infoMessage('No package attributes were set.');
        }
    }

    /**
     * Add file resolver to category
     * @param modTransportVehicle $vehicle
     */
    private function addFiles(&$vehicle)
    {
        if (file_exists($this->config['source_assets'])) {
            $vehicle->resolve('file', [
                'source' => $this->config['source_assets'],
                'target' => "return MODX_ASSETS_PATH . 'components/';"
            ]);
        } else {
            $this->notifyMessge('Assets component not found: ' . $this->config['source_assets']);
        }
        if (file_exists($this->config['source_core'])) {
            $vehicle->resolve('file', [
                'source' => $this->config['source_core'],
                'target' => "return MODX_CORE_PATH . 'components/';"
            ]);
        } else {
            $this->notifyMessge('Core component not found: ' . $this->config['source_core']);
        }
        $this->infoMessage('Added files.');
    }

    // ======================================================================
    //  Settings
    // ======================================================================

    /**
     * Add Menu/Actions
     */
    private function addMenuItems(): void
    {
        $menu_items = $this->createMenuItems($this->config['menu']);
        foreach ($menu_items as $menu_item) {
            $vehicle = $this->builder->createVehicle($menu_item, array(
                xPDOTransport::PRESERVE_KEYS => true,
                xPDOTransport::UPDATE_OBJECT => true,
                xPDOTransport::UNIQUE_KEY => 'text',
                xPDOTransport::RELATED_OBJECTS => false,
            ));
            $this->builder->putVehicle($vehicle);
            unset($vehicle);
        }
        $this->infoMessage('Packaged ' . count($menu_items) . ' of ' . count($this->config['menu']) . ' menu items.');
        flush();
    }

    private function createMenuItems($items)
    {
        $menu_items = [];
        $i = 1;
        foreach ($items as $item) {
            if ($item['text']) {
                $action = null;
                $menu_items[$i] = $this->modx->newObject('modMenu');
                $menu_items[$i]->fromArray([
                    'text' => $item['text'],
                    'parent' => $item['parent'] ?? 'components',
                    'description' => $item['description'] ?? '',
                    'icon' => $item['icon'] ?? '',
                    'menuindex' => $item['menuindex'] ?? 0,
                    'params' => $item['params'] ?? '',
                    'handler' => $item['handler'] ?? '',
                    'namespace' => $item['namespace'] ?? $this->config['PKG_NAME_LOWER'],
                    'action' => $item['action'] ?? '',
                ], '', true, true);
                $i++;
                unset($action);
            }
        }
        return $menu_items;
    }

    /**
     *
     */
    private function addNamespaces(): void
    {
        $i = 0;
        foreach ($this->config['namespaces'] as $properties) {
            if (isset($properties['name'])) {
                $namespace = $this->modx->newObject('modNamespace');
                $namespace->set('name', $properties['name']);
                $namespace->set('path', $properties['path'] ?? '{core_path}components/' . $properties['name'] . '/');
                if (isset($properties['assets_path'])) {
                    $namespace->set('assets_path', $properties['assets_path']);
                }
                $vehicle = $this->builder->createVehicle($namespace, array(
                    xPDOTransport::UNIQUE_KEY => 'name',
                    xPDOTransport::PRESERVE_KEYS => true,
                    xPDOTransport::UPDATE_OBJECT => true,
                ));
                $this->builder->putVehicle($vehicle);
                flush();
                unset($vehicle, $namespace);
                $i++;
            }
        }
        $this->infoMessage('Packaged ' . $i . ' of ' . count($this->config['namespaces']) . ' namespaces.');
        unset($i);
    }

    /**
     *
     */
    private function addAccessPolicies(): void
    {
//        $attributes = array (
//            xPDOTransport::PRESERVE_KEYS => false,
//            xPDOTransport::UNIQUE_KEY => array('name'),
//            xPDOTransport::UPDATE_OBJECT => true,
//        );
//        $policies = include $sources['data'].'transport.policies.php';
//        if (!is_array($policies)) { $modx->log(modX::LOG_LEVEL_FATAL,'Adding policies failed.'); }
//        foreach ($policies as $policy) {
//            $vehicle = $builder->createVehicle($policy,$attributes);
//            $builder->putVehicle($vehicle);
//        }
//        $modx->log(modX::LOG_LEVEL_INFO,'Packaged in '.count($policies).' Access Policies.'); flush();
//        unset($policies,$policy,$attributes);
    }

    /**
     *
     */
    private function addAccesPolicyTemplates(): void
    {
//        $templates = include dirname(__FILE__).'/data/transport.policytemplates.php';
//        $attributes = array (
//            xPDOTransport::PRESERVE_KEYS => false,
//            xPDOTransport::UNIQUE_KEY => array('name'),
//            xPDOTransport::UPDATE_OBJECT => true,
//            xPDOTransport::RELATED_OBJECTS => true,
//            xPDOTransport::RELATED_OBJECT_ATTRIBUTES => array (
//                'Permissions' => array (
//                    xPDOTransport::PRESERVE_KEYS => false,
//                    xPDOTransport::UPDATE_OBJECT => true,
//                    xPDOTransport::UNIQUE_KEY => array ('template','name'),
//                ),
//            )
//        );
//        if (is_array($templates)) {
//            foreach ($templates as $template) {
//                $vehicle = $builder->createVehicle($template,$attributes);
//                $builder->putVehicle($vehicle);
//            }
//            $modx->log(modX::LOG_LEVEL_INFO,'Packaged in '.count($templates).' Access Policy Templates.'); flush();
//        } else {
//            $modx->log(modX::LOG_LEVEL_ERROR,'Could not package in Access Policy Templates.');
//        }
//        unset ($templates,$template,$idx,$ct,$attributes);
    }

    /**
     * Add System Settings
     */
    private function addSystemSettings(): void
    {
        $settings = $this->createSystemSettings($this->config['system_settings'], 'modSystemSetting');
        if (!is_array($settings)) {
            $this->errorMessage('Adding settings failed.');
            exit();
        }
        $attributes = [
            xPDOTransport::UNIQUE_KEY => 'key',
            xPDOTransport::PRESERVE_KEYS => true,
            xPDOTransport::UPDATE_OBJECT => false,
        ];
        foreach ($settings as $setting) {
            $vehicle = $this->builder->createVehicle($setting, $attributes);
            $this->builder->putVehicle($vehicle);
        }
        $this->infoMessage('Packaged ' . count($settings) . ' of ' . count($this->config['system_settings']) . ' system settings.');
        flush();
        unset($settings, $setting, $attributes);
    }

    /**
     * @param $properties
     * @param $class
     * @return array
     */
    private function createSystemSettings($properties, $class): array
    {
        $rsp = [];
        if (is_array($properties)) {
            $i = 1;
            foreach ($properties as $property) {
                if (isset($property['key'])) {
                    $rsp[$i] = $this->modx->newObject($class);
                    $rsp[$i]->fromArray([
                        'key' => $property['key'],
                        'value' => $property['value'] ?? '',
                        'xtype' => $property['xtype'] ?? 'textfield',
                        'namespace' => $property['namespace'] ?? $this->config['PKG_NAME_LOWER'],
                        'area' => $property['area'] ?? 'default'
                    ], '', true, true);
                    $i++;
                }
            }
        }
        unset($i);
        return $rsp;
    }

    // ======================================================================
    //  Elements
    // ======================================================================

    /**
     * Add Snippets to Category
     * @param xPDOSimpleObject $category
     */
    private function addSnippets(&$category): void
    {
        if (isset($this->config['snippets'])) {
            $elements = $this->createElement($this->config['snippets'], 'modSnippet', $this->config['snippet_path']);
            if (!is_array($elements)) {
                $this->errorMessage('Could not package in snippets.');
            } else {
                $category->addMany($elements);
                $this->infoMessage('Packaged ' . count($elements) . ' of ' . count($this->config['snippets']) . ' snippets.');
            }
            unset($elements);
        }
    }

    /**
     * Add Chunks to Category
     * @param xPDOSimpleObject $category
     */
    private function addChunks(&$category): void
    {
        if (isset($this->config['chunks'])) {
            $elements = $this->createElement($this->config['chunks'], 'modChunk', $this->config['chunk_path']);
            if (!is_array($elements)) {
                $this->errorMessage('Could not package in chunks.');
            } else {
                $category->addMany($elements);
                $this->infoMessage('Packaged ' . count($elements) . ' of ' . count($this->config['chunks']) . ' chunks.');
            }
            unset($elements);
        }
    }

    /**
     * @param xPDOSimpleObject $category
     */
    private function addTemplates(&$category): void
    {
        if (isset($this->config['templates'])) {
            $elements = $this->createElement($this->config['templates'], 'modTemplate', $this->config['template_path']);
            if (!is_array($elements)) {
                $this->errorMessage('Could not package in templates.');
            } else {
                $category->addMany($elements);
                $this->infoMessage('Packaged ' . count($elements) . ' of ' . count($this->config['templates']) . ' templates.');
            }
            unset($elements);
        }
    }

    /**
     * @param xPDOSimpleObject $category
     */
    private function addPlugins(&$category): void
    {
        if (isset($this->config['plugins'])) {
            $elements = $this->createElement($this->config['plugins'], 'modPlugin', $this->config['plugin_path']);
            if (!is_array($elements)) {
                $this->errorMessage('Could not package in plugins.');
            } else {
                $category->addMany($elements);
                $this->infoMessage('Packaged ' . count($elements) . ' of ' . count($this->config['plugins']) . ' plugins.');
            }
            unset($elements);
        }
    }

    /**
     * Create Element
     * @param array $properties
     * @param string $class
     * @param string $path
     * @return array
     */
    private function createElement($properties, $class, $path): array
    {
        $rsp = [];
        if (is_array($properties)) {
            $i = 1;
            foreach ($properties as $property) {
                if (file_exists($path . $property['filename'])) {
                    $rsp[$i] = $this->modx->newObject($class);
                    $rsp[$i]->set(($class == 'modTemplate') ? 'templatename' : 'name', $property['name']);
                    $rsp[$i]->set('content', $this->getFileContent($path . $property['filename']));
                    if (isset($chunk_property['description'])) {
                        $rsp[$i]->set('description', $property['description']);
                    }
                    if (isset($property['properties'])) {
                        $this->setElementProperties($rsp[$i], $property['properties']);
                    }
                    if (isset($property['events']) && $class === 'modPlugin') {
                        $this->setPluginEvents($rsp[$i], $property['events']);
                    }
                } else {
                    $this->errorMessage("File $class not found in $path" . $property['filename']);
                }
                $i++;
            }
            unset($i);
        }
        return $rsp;
    }

    /**
     * Get element properties
     * @param array $properties
     * @param modProcessor $element
     */
    private function setElementProperties(&$element, $properties): void
    {
        $rsp = [];
        foreach ($properties as $property) {
            if (isset($property['name'])) {
                $rsp[] = [
                    'name' => $property['name'],
                    'desc' => $property['desc'] ?? 'prop_' . $this->config['PKG_NAME_LOWER'] . '.' . strtolower($property['name']) . '_desc',
                    'type' => $property['type'] ?? 'textfield',
                    'options' => $property['options'] ?? '',
                    'value' => $property['value'] ?? '',
                    'lexicon' => $property['lexicon'] ?? $this->config['PKG_NAME_LOWER'] . ':properties'
                ];
            }
        }
        $element->setProperties($rsp);
    }

    /**
     * @param xPDOSimpleObject $element
     * @param array $events
     */
    private function setPluginEvents(&$element, $events): void
    {
        if (is_array($events) && !empty($events)) {
            $element_events = [];
            $i = 0;
            foreach ($events as $event) {
                if (isset($event['event'])) {
                    $element_events[$i] = $this->modx->newObject('modPluginEvent');
                    $element_events[$i]->set('event', $event['event']);
                    $element_events[$i]->fromArray([
                        'priority' => $event['priority'] ?? 0,
                        'propertyset' => $event['propertyset'] ?? 0,
                    ]);
                    $i++;
                }
            }
            $element->addMany($element_events);
            $this->infoMessage('Packaged ' . $i . ' of ' . count($events) . ' plugin event for ' . $element->get('name') . '.');
            unset($i, $element_events, $events);
            flush();
        } else {
            $this->errorMessage('Could not find plugin events for ' . $element->get('name') . '.');
        }
    }

    // ======================================================================
    //  Category
    // ======================================================================

    /**
     * Set category object
     * @return xPDOSimpleObject
     */
    private function setCategory()
    {
        /** @var xPDOSimpleObject $category */
        $category = $this->modx->newObject('modCategory');
        $category->set('id', 1);
        $category->set('category', $this->config['PKG_NAME']);
        return $category;
    }

    /**
     * Create category vehicle
     * @param $category
     * @return modTransportVehicle
     */
    private function createCategoryVehicle(&$category): modTransportVehicle
    {
        $attr = [
            xPDOTransport::UNIQUE_KEY => 'category',
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::RELATED_OBJECTS => true,
            xPDOTransport::RELATED_OBJECT_ATTRIBUTES => [
                'Children' => [
                    xPDOTransport::PRESERVE_KEYS => false,
                    xPDOTransport::UPDATE_OBJECT => true,
                    xPDOTransport::UNIQUE_KEY => 'category',
                    xPDOTransport::RELATED_OBJECTS => true,
                    xPDOTransport::RELATED_OBJECT_ATTRIBUTES => [
                        'Snippets' => [
                            xPDOTransport::PRESERVE_KEYS => false,
                            xPDOTransport::UPDATE_OBJECT => true,
                            xPDOTransport::UNIQUE_KEY => 'name'
                        ],
                        'Chunks' => [
                            xPDOTransport::PRESERVE_KEYS => false,
                            xPDOTransport::UPDATE_OBJECT => true,
                            xPDOTransport::UNIQUE_KEY => 'name'
                        ],
                    ],
                ],
                'Snippets' => [
                    xPDOTransport::PRESERVE_KEYS => false,
                    xPDOTransport::UPDATE_OBJECT => true,
                    xPDOTransport::UNIQUE_KEY => 'name',
                ],
                'Chunks' => [
                    xPDOTransport::PRESERVE_KEYS => false,
                    xPDOTransport::UPDATE_OBJECT => true,
                    xPDOTransport::UNIQUE_KEY => 'name'
                ],
                'Plugins' => [
                    xPDOTransport::PRESERVE_KEYS => false,
                    xPDOTransport::UPDATE_OBJECT => true,
                    xPDOTransport::UNIQUE_KEY => 'name',
                    xPDOTransport::RELATED_OBJECT_ATTRIBUTES => [
                        'PluginEvents' => [
                            xPDOTransport::PRESERVE_KEYS => true,
                            xPDOTransport::UPDATE_OBJECT => false,
                            xPDOTransport::UNIQUE_KEY => ['pluginid', 'event']
                        ]
                    ]
                ],
                'Templates' => [
                    xPDOTransport::PRESERVE_KEYS => false,
                    xPDOTransport::UPDATE_OBJECT => true,
                    xPDOTransport::UNIQUE_KEY => 'templatename'
                ]
            ]
        ];
        return $this->builder->createVehicle($category, $attr);
    }

    // ======================================================================
    //  Helpers
    // ======================================================================

    /**
     * @param string $action
     */
    private function timer($action = 'start'): void
    {
        $mtime = microtime();
        $mtime = explode(' ', $mtime);
        $mtime = $mtime[1] + $mtime[0];
        switch ($action) {
            case 'start' :
                $this->clock = $mtime;
                set_time_limit(0);
                break;
            case 'stop' :
                $totalTime = ($mtime - $this->clock);
                $this->infoMessage("Package Built.");
                $this->infoMessage('Execution time: ' . sprintf("%2.4f s", $totalTime));
                break;
        }
        unset($mtime);
    }

    /**
     * @param $msg
     */
    private function errorMessage($msg)
    {
        $this->errors[] = $msg;
//        $this->modx->log(modX::LOG_LEVEL_ERROR, $msg);
        echo "ERROR: $msg\n";
        unset($msg);
    }

    /**
     * @param $msg
     */
    private function infoMessage($msg)
    {
//        $this->modx->log(modX::LOG_LEVEL_INFO, "\n" . $msg);
        echo "$msg\n";
        unset($msg);
    }

    /**
     * @param $msg
     */
    private function notifyMessge($msg)
    {
        $this->notify[] = $msg;
        echo "NOTIFY: $msg\n";
        unset($msg);
    }

    /**
     * @param $path
     * @return string
     */
    private function getFileContent($path): string
    {
        if (file_exists($path)) {
            $o = file_get_contents($path);
            $o = str_replace('<?php', '', $o);
            $o = str_replace('?>', '', $o);
            $o = trim($o);
            return $o;
        }
        return '';
    }

}
