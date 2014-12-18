<?php namespace Comodojo\DispatcherInstaller;

/**
 * Dispatcher installer - a simple class (static methods) to manage plugin installations
 *
 * It currently supports:
 * - dispatcher-plugin - generic plugins such as tracer, database, ...
 * - dispatcher-service-bundle - service bundles
 * 
 * @package     Comodojo dispatcher
 * @author      Marco Giovinazzi <info@comodojo.org>
 * @license     GPL-3.0+
 *
 * LICENSE:
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

use Composer\Script\Event;
use \Exception;

class DispatcherInstallerActions {

    private static $vendor = 'vendor/';

    private static $dispatcher_cfg = 'configs/dispatcher-config.php';

    private static $plugins_cfg = 'configs/plugins-config.php';

    private static $routing_cfg = 'configs/routing-config.php';

    private static $known_types = array('dispatcher-plugin', 'dispatcher-service-bundle', 'dispatcher-bundle');

    private static $reserved_folders = Array('DispatcherInstaller','cache','configs','lib','plugins','services','templates','vendor');

    private static $mask = 0644;

    private static $known_service_types = array('ROUTE', 'ERROR', 'REDIRECT');

    public static function postPackageInstall(Event $event) {

        $type = $event->getOperation()->getPackage()->getType();

        $name = $event->getOperation()->getPackage()->getName();

        $extra = $event->getOperation()->getPackage()->getExtra();

        if ( !in_array($type, self::$known_types) ) return;

        self::ascii();

        try {
            
            self::packageInstall($type, $name, $extra);

        } catch (Exception $e) {

            throw $e;
            
        }

        echo "+ DispatcherInstaller install task completed\n\n";

    }

    public static function postPackageUninstall(Event $event) {

        $type = $event->getOperation()->getPackage()->getType();

        $name = $event->getOperation()->getPackage()->getName();

        $extra = $event->getOperation()->getPackage()->getExtra();

        if ( !in_array($type, self::$known_types) ) return;

        self::ascii();

        try {
            
            self::packageUninstall($type, $name, $extra);

        } catch (Exception $e) {

            throw $e;
            
        }

        echo "- DispatcherInstaller uninstall task completed\n\n";

    }

    public static function postPackageUpdate(Event $event) {

        $initial_package = $event->getOperation()->getInitialPackage();

        $initial_package_type = $initial_package->getType();

        $initial_package_name = $initial_package->getName();

        $initial_package_extra = $initial_package->getExtra();

        $target_package  = $event->getOperation()->getTargetPackage(); 

        $target_package_type = $target_package->getType();

        $target_package_name = $target_package->getName();

        $target_package_extra = $target_package->getExtra();

        if ( !in_array($initial_package_type, self::$known_types) AND !in_array($target_package_type, self::$known_types) ) return;

        self::ascii();

        try {
            
            self::packageUninstall($initial_package_type, $initial_package_name, $initial_package_extra);

            self::packageInstall($target_package_type, $target_package_name, $target_package_extra);

        } catch (Exception $e) {
            
            throw $e;

        }

        echo "* DispatcherInstaller update task completed\n\n";

    }

    private static function packageInstall($type, $name, $extra) {

        $plugin_loaders = isset($extra["comodojo-plugin-load"]) ? $extra["comodojo-plugin-load"] : Array();

        $service_loaders = isset($extra["comodojo-service-route"]) ? $extra["comodojo-service-route"] : Array();

        $folders_to_create = isset($extra["comodojo-folders-create"]) ? $extra["comodojo-folders-create"] : Array();

        $configlines_to_create = isset($extra["comodojo-configlines-create"]) ? $extra["comodojo-configlines-create"] : Array();

        try {
            
            if ( $type == "dispatcher-plugin" ) self::loadPlugin($name, $plugin_loaders);

            if ( $type == "dispatcher-service-bundle" ) self::loadService($name, $service_loaders);

            if ( $type == "dispatcher-bundle" ) {

                self::loadPlugin($name, $plugin_loaders);

                self::loadService($name, $service_loaders);
            
            }

            self::create_folders($folders_to_create);

            self::create_configlines($name, $configlines_to_create);

        } catch (Exception $e) {
            
            throw $e;
            
        }

    }

    private static function packageUninstall($type, $name, $extra) {
        
        $folders_to_delete = isset($extra["comodojo-folders-create"]) ? $extra["comodojo-folders-create"] : Array();

        $configlines_to_delete = isset($extra["comodojo-configlines-create"]) ? $extra["comodojo-folders-create"] : Array();

        try {
            
            if ( $type == "dispatcher-plugin" ) self::unloadPlugin($name);

            if ( $type == "dispatcher-service-bundle" ) self::unloadService($name);

            if ( $type == "dispatcher-bundle" ) {

                self::unloadPlugin($name);

                self::unloadService($name);
            
            }

            self::delete_folders($folders_to_delete);

            self::delete_configlines($name, $configlines_to_delete);

        } catch (Exception $e) {
            
            throw $e;
            
        }

    }

    private static function loadPlugin($package_name, $package_loader) {

        $line_mark = "/****** PLUGIN - ".$package_name." - PLUGIN ******/";

        list($author,$name) = explode("/", $package_name);

        $plugin_path = self::$vendor.$author."/".$name."/src/";

        if ( is_array($package_loader) ) {

            $line_load = "";

            foreach ($package_loader as $loader) {

                echo "+ Enabling plugin ".$loader."\n";

                $line_load .= '$dispatcher->loadPlugin("'.$loader.'", "'.$plugin_path.'");'."\n";

            }

        }
        else {

            echo "+ Enabling plugin ".$package_loader."\n";

            $line_load = '$dispatcher->loadPlugin("'.$package_loader.'", "'.$plugin_path.'");'."\n";

        }
        
        $to_append = "\n".$line_mark."\n".$line_load.$line_mark."\n";

        $action = file_put_contents(self::$plugins_cfg, $to_append, FILE_APPEND | LOCK_EX);

        if ( $action === false ) throw new Exception("Cannot activate plugin " . $package_name);

    }

    private static function unloadPlugin($package_name) {

        echo "- Disabling plugin ".$package_name."\n";

        $line_mark = "/****** PLUGIN - ".$package_name." - PLUGIN ******/";

        $cfg = file(self::$plugins_cfg, FILE_IGNORE_NEW_LINES);

        $found = false;

        foreach ($cfg as $position => $line) {
            
            if ( stristr($line, $line_mark) ) {

                unset($cfg[$position]);

                $found = !$found;

            }

            else {

                if ( $found ) unset($cfg[$position]);
                else continue;

            }

        }

        $action = file_put_contents(self::$plugins_cfg, implode("\n", array_values($cfg)), LOCK_EX);

        if ( $action === false ) throw new Exception("Cannot deactivate plugin " . $package_name);

    }

    private static function loadService($package_name, $package_loader) {

        $line_mark = "/****** SERVICE - ".$package_name." - SERVICE ******/";

        $line_load = "";

        list($author,$name) = explode("/", $package_name);

        $service_path = self::$vendor.$author."/".$name."/services/";

        if ( is_array($package_loader) ) {

            foreach ($package_loader as $pload) {

                // if service, type or target values are not in place, throw an exception dumping the current pload
                if ( !array_key_exists("service",$pload) OR !array_key_exists("type",$pload) OR !array_key_exists("target",$pload) ) throw new Exception( "Wrong service route: " . var_export($pload,true) );

                // get service specifications
                $service = $pload["service"];

                $type = strtoupper($pload["type"]);

                $relative = ( array_key_exists("relative",$pload) ? filter_var($pload["relative"], FILTER_VALIDATE_BOOLEAN) : false;

                $parameters = ( isset($pload["parameters"]) AND @is_array($pload["parameters"]) ) ? $pload["parameters"] : null;

                // if service type is unknown, throw an error
                if ( !in_array($type, self::$known_service_types) ) throw new Exception( "Unknown service type: " . $type );

                // push service route depending on service type
                switch ($type) {

                    case 'ROUTE':
                        
                        // route can be relative, so check for it
                        $target = $relative ? $pload["target"] : $service_path.$pload["target"];

                        break;
                    
                    case 'REDIRECT':
                        
                        // redirect routes could also be relative, but in this case url composition is handled by dispatcher class
                        $target = $pload["target"];

                        break;
                    
                    case 'ERROR':

                        // target in error routes represents content and it can not be relative
                        $relative = false;

                        $target = $pload["target"];

                        break;
                    
                }

                // print route informations
                echo "+ Enabling ".($relative ? "relative" : "absolute")." route (type: ".$type.") for service ".$service."(".$package_name.")\n";

                $line_load .= '$dispatcher->setRoute("'.$service.'", "'.$type.'", "'.$target.'", ' . var_export($parameters, true) . ', '.($relative ? 'true' : 'false').');'."\n";

            }

        } else throw new Exception("Wrong service loader");
        
        $to_append = "\n".$line_mark."\n".$line_load.$line_mark."\n";

        $action = file_put_contents(self::$routing_cfg, $to_append, FILE_APPEND | LOCK_EX);

        if ( $action === false ) throw new Exception("Cannot activate service route for package " . $package_name);

    }

    private static function unloadService($package_name) {

        echo "- Disabling route for services of ".$package_name."\n";

        $line_mark = "/****** SERVICE - ".$package_name." - SERVICE ******/";

        $cfg = file(self::$routing_cfg, FILE_IGNORE_NEW_LINES);

        $found = false;

        foreach ($cfg as $position => $line) {
            
            if ( stristr($line, $line_mark) ) {

                unset($cfg[$position]);

                $found = !$found;

            }

            else {

                if ( $found ) unset($cfg[$position]);
                else continue;

            }

        }

        $action = file_put_contents(self::$routing_cfg, implode("\n", array_values($cfg)), LOCK_EX);

        if ( $action === false ) throw new Exception("Cannot deactivate route for package " . $package_name);

    }

    private static function create_folders($folders) {

        if ( empty($folders) ) return;

        if ( is_array($folders) ) {

            foreach ($folders as $folder) {
                
                if ( in_array($folder, self::$reserved_folders) ) throw new Exception("Cannot overwrite reserved folder!");

                echo "+ Creating folder ".$folder."\n";

                $action = mkdir($folder, self::$mask, true);

                if ( $action === false ) throw new Exception("Error creating folder ".$folder);

            }

        }

        else {

            if ( in_array($folders, self::$reserved_folders) ) throw new Exception("Cannot overwrite reserved folder!");

            echo "+ Creating folder ".$folders."\n";

            $action = mkdir($folders, self::$mask, true);

            if ( $action === false ) throw new Exception("Error creating folder ".$folders);

        }

        echo "+ PLEASE REMEMBER to chmod and/or chown created folders according to your needs.\n";

    }

    private static function delete_folders($folders) {

        if ( empty($folders) ) return;
        
        if ( is_array($folders) ) {

            foreach ($folders as $folder) {
                
                if ( in_array($folder, self::$reserved_folders) ) throw new Exception("Cannot delete reserved folder!");

                echo "- deleting folder ".$folder."\n";

                try {

                    self::recursive_unlink($folder);
                    
                } catch (Exception $e) {
                    
                    throw $e;

                }

            }

        }

        else {

            if ( in_array($folders, self::$reserved_folders) ) throw new Exception("Cannot overwrite reserved folder!");

            echo "- deleting folder ".$folders."\n";

            try {

                self::recursive_unlink($folders);
                
            } catch (Exception $e) {
                
                throw $e;

            }

        }

    }

    private static function recursive_unlink($folder) {

        foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($folder, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $path) {
            
            $pathname = $path->getPathname();

            if ( $path->isDir() ) {

                $action = rmdir($pathname);

            } 
            else {

                $action = unlink($pathname);

            }

            if ( $action === false ) throw new Exception("Error deleting ".$pathname." during recursive unlink of folder ".$folder);

        }

        $action = rmdir($folder);

        if ( $action === false ) throw new Exception("Error deleting folder ".$folder);

    }

    private static function create_configlines($package_name, $lines) {

        if ( empty($lines) ) return;

        echo "+ Writing configuration lines for package ".$package_name."\n";

        $line_mark = "/****** CONFIGLINES - ".$package_name." - CONFIGLINES ******/";

        $line_load = "";

        if ( is_array($lines) ) {

            foreach ($lines as $constant => $value) {
                
                if ( !is_scalar($value) ) throw new Exception("A configuration line value should be scalar, please check value of " . $constant . " in " . $package_name);

                $value = ( is_bool($value) OR is_numeric($value) ) ? $value : '"'.$value.'"';

                $line_load .= "define('".$constant."', ".$value.");"."\n";

            }

        } else throw new Exception("Wrong configuration lines for package " . $package_name);

        $to_append = "\n".$line_mark."\n".$line_load.$line_mark."\n";

        $action = file_put_contents(self::$dispatcher_cfg, $to_append, FILE_APPEND | LOCK_EX);

        if ( $action === false ) throw new Exception("Cannot write configuration lines for package " . $package_name);

    }

    private static function delete_configlines($package_name, $lines) {

        if ( empty($lines) ) return;

        echo "- Deleting configuration lines for package ".$package_name."\n";

        $line_mark = "/****** CONFIGLINES - ".$package_name." - CONFIGLINES ******/";

        $cfg = file(self::$dispatcher_cfg, FILE_IGNORE_NEW_LINES);

        $found = false;

        foreach ($cfg as $position => $line) {
            
            if ( stristr($line, $line_mark) ) {

                unset($cfg[$position]);

                $found = !$found;

            }

            else {

                if ( $found ) unset($cfg[$position]);
                else continue;

            }

        }

        $action = file_put_contents(self::$dispatcher_cfg, implode("\n", array_values($cfg)), LOCK_EX);

        if ( $action === false ) throw new Exception("Cannot delete configuration lines for package " . $package_name);

    }

    private static function ascii() {

        echo "\n   ______                          __        __          \n";
        echo "  / ____/___  ____ ___  ____  ____/ /___    / /___         \n";
        echo " / /   / __ \/ __ `__ \/ __ \/ __  / __ \  / / __ \        \n";
        echo "/ /___/ /_/ / / / / / / /_/ / /_/ / /_/ / / / /_/ /        \n";
        echo "\____/\____/_/ /_/ /_/\____/\____/\____/_/ /\____/         \n";
        echo "------------------------------------- /___/ -----          \n";
        echo "            __ _                 __       __               \n";
        echo "       ____/ /_/________  ____  / /______/ /_  ___  _____  \n";
        echo "      / __  / / ___/ __ \/ __ `/ __/ ___/ __ \/ _ \/ ___/  \n";
        echo "     / /_/ / (__  ) /_/ / /_/ / /_/ /__/ / / /  __/ /      \n";
        echo "     \____/_/____/ / __/\____/\__/\___/_/ /_/\___/_/       \n";
        echo "     ------------ /_/ ---------------------------------    \n\n";

    }

}