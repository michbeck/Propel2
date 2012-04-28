<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Generator\Config;

/**
 * Runtime configuration converter
 * From array to PHP string
 */
class ArrayToPhpConverter
{
    /**
     * Create a PHP configuration from an array
     *
     * @param Array $configuration The array configuration
     *
     * @return String
     */
    public static function convert($c)
    {
      $conf = "\$serviceContainer = \Propel\Runtime\Propel::getServiceContainer();";
      // set datasources
      if (isset($c['datasources'])) {
            foreach ($c['datasources'] as $name => $params) {
                if (!is_array($params)) {
                    continue;
                }
                // set adapters
                if (isset($params['adapter'])) {
                    $conf .= "
\$serviceContainer->setAdapterClass('{$name}', '{$params['adapter']}');";
                }
                // set connection settings
                if (isset($params['slaves'])) {
                    $conf .= "
\$manager = new \Propel\Runtime\Connection\ConnectionManagerMasterSlave();
\$manager->setReadConfiguration(" . var_export($params['slaves'], true). ");";
                } elseif (isset($params['connection'])) {
                    $conf .= "
\$manager = new \Propel\Runtime\Connection\ConnectionManagerSingle();";
                } else {
                    continue;
                }
                if (isset($params['connection'])) {
                    $masterConfigurationSetter = isset($params['slaves']) ? 'setWriteConfiguration' : 'setConfiguration';
                    $conf .= "
\$manager->{$masterConfigurationSetter}(". var_export($params['connection'], true) . ");";
                }
                $conf .= "
\$manager->setName('{$name}');
\$serviceContainer->setConnectionManager('{$name}', \$manager);";
            }
            // set default datasource
            if (isset($c['datasources']['default'])) {
                $defaultDatasource = $c['datasources']['default'];
            } elseif (isset($c['datasources']) && is_array($c['datasources'])) {
                // fallback to the first datasource
                $datasourceNames = array_keys($c['datasources']);
                $defaultDatasource = $datasourceNames[0];
            }
            $conf .= "
\$serviceContainer->setDefaultDatasource('{$defaultDatasource}');";
        }
        // set profiler
        if (isset($c['profiler'])) {
            $profilerConf = $c['profiler'];
            if (isset($profilerConf['class'])) {
                $conf .= "
\$serviceContainer->setProfilerClass('{$profilerConf['class']}');";
                unset($profilerConf['class']);
            }
            if ($profilerConf) {
                $conf .= "
\$serviceContainer->setProfilerConfiguration(" . var_export($profilerConf, true) . ");";
            }
            unset($c['profiler']);
        }
        // set logger
        if (isset($c['log'])) {
            $loggerConfiguration = $c['log'];
            $name = 'default';
            if (isset($loggerConfiguration['name'])) {
                $name = $loggerConfiguration['name'];
                unset($loggerConfiguration['name']);
            }
            $conf .= "
\$serviceContainer->setLoggerConfiguration('{$name}', " . var_export($loggerConfiguration, true) . ");";
            unset($c['log']);
        }
        return $conf;
    }

}
