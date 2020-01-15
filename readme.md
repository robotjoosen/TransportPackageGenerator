# TransportPackageGenerator
The Transport Package Generator is a CLI tool to create simple transport package from a configuration file.

## Installation
Go to your favorite folder for these types of applications and execute the following lines.
```zsh
→ git clone https://github.com/robotjoosen/TransportPackageGenerator.git
→ cd TransportPackageGenerator
→ chmod +x TransportPackageGenerator
→ cd app
→ composer install
```

Make it easy to be called with just a single word.
Go and edit your `~/.bash_profile of` or `~/.zshrc` and paste the following line, don't forget to change the directory path.

```text
export PATH=/path/to/TransportPackageGenerator/:$PATH
```

## Usage

### setup
To run TPG you need to have a few files in place. First ofcourse you need your Component setup in `core/components/` and `assets/components/`.
Next you need to setup a build folder for you package. For this you need to go to the Root folder (one level higher than your MODX installation).
Create the folder `_build/`.

In your build folder you can create a folder with the same name as your Component. 
This folder needs atleast the `package.config.yaml` file, you can copy this from `examples/_build/samplecomponent/`.

### Build
To build a transport package you need to go to the base folder of your MODX installation and run the following command

```zsh
TransportPackageGenerator component_name
```

The component_name is the name of your component. If everything runs smooth you'll see the package being build with a result that looks a bit like this:

```text
[1970-01-01 00:00:00] (INFO @ /application/public_html/core/model/modx/transport/modpackagebuilder.class.php : 141) Created new transport package with signature: component_name-1.0.0-beta1
[1970-01-01 00:00:00] (INFO @ /application/public_html/core/model/modx/transport/modpackagebuilder.class.php : 212) Registered package namespace as: component_name
[1970-01-01 00:00:00] (INFO @ /application/public_html/core/model/modx/transport/modpackagebuilder.class.php : 232) Packaged namespace "component_name" into package.
Created Transport Package and Namespace.
Packaged 1 of 1 system settings.
Packaged 1 of 1 menu items.
Packaged 1 of 1 namespaces.
Packaged 0 of 0 snippets.
Packaged 1 of 1 plugin event for ComponentName.
Packaged 1 of 1 plugins.
Added files.
Added package attributes.
Packing up transport package zip...
Package Built.
Execution time: 8.6171 s
```  

#### package.config.yaml
This is a sample of a package.config.yaml. Detailed information about the configuration will follow later.

```yaml
PKG_NAME: SampleComponent
PKG_VERSION: 1.0.0
PKG_RELEASE: pl
snippets:
  - name: SampleSnippet
    description: "Sample snippet description"
    filename: sample.snippet.php
    properties:
      - name: sampleProperty
chunks:
  - name: SampleChunk
    description: "Sample chunk description"
    filename: sample.chunk.tpl
    properties:
      - name: sampleProperty
plugins:
  - name: SamplePlugin
    description: "Sample plugin description"
    filename: sample.plugin.php
    events:
      - event: OnLoadWebDocument
system_settings:
  - key: samplecomponent.sample_setting
    value: true
    xtype: combo-boolean
    namespace: samplecomponent
    area: default
  - key: samplecomponent.do_something
    value: 'Sample'
menu:
  - text: samplecomponent
    description: samplecomponent.desc
    action: index
```

#### table.resolver.php
The table resolver creates the necessary tables based on the schema of your component.
Add your tables between `// Add your table` and `// End of your tables`.

```php
<?php
/**
 * @package samplecomponent
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
            
            // Add your tables
            $manager->createObjectContainer('SampleComponentContent');
            // End of your tables

            break;
    }
}
return true;
```