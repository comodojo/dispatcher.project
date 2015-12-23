<?php namespace Comodojo\DispatcherInstaller;

use Comodojo\DispatcherInstaller\AbstractInstaller;

/**
 * Dispatcher Installer
 *
 * @package     Comodojo dispatcher
 * @author      Marco Giovinazzi <marco.giovinazzi@comodojo.org>
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

class DispatcherInstaller extends AbstractInstaller {

    public static function loadPlugin($package_name, $package_loader) {

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

        $action = file_put_contents(self::$dispatcher_plugins_cfg, $to_append, FILE_APPEND | LOCK_EX);

        if ( $action === false ) throw new Exception("Cannot activate plugin");

    }

    public static function unloadPlugin($package_name) {

        echo "- Disabling plugin ".$package_name."\n";

        $line_mark = "/****** PLUGIN - ".$package_name." - PLUGIN ******/";

        $cfg = file(self::$dispatcher_plugins_cfg, FILE_IGNORE_NEW_LINES);

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

        $action = file_put_contents(self::$dispatcher_plugins_cfg, implode("\n", array_values($cfg)), LOCK_EX);

        if ( $action === false ) throw new Exception("Cannot deactivate plugin");

    }

    public static function loadService($package_name, $package_loader) {

        $line_mark = "/****** SERVICE - ".$package_name." - SERVICE ******/";

        $line_load = "";

        list($author,$name) = explode("/", $package_name);

        $service_path = self::$vendor.$author."/".$name."/services/";

        if ( is_array($package_loader) ) {

            foreach ($package_loader as $pload) {

                if ( !array_key_exists("service",$pload) OR !array_key_exists("type",$pload) OR !array_key_exists("target",$pload) ) throw new Exception("Wrong service route");

                $service = $pload["service"];
                $type = $pload["type"];

                if ( array_key_exists("relative",$pload) ) $relative = filter_var($pload["relative"], FILTER_VALIDATE_BOOLEAN);
                else $relative = false;

                if ( $relative ) $target = $pload["target"];
                else $target = $service_path.$pload["target"];

                echo "+ Enabling ".($relative ? "relative" : "absolute")." route for service ".$service."(".$package_name.")\n";

                if ( isset($pload["parameters"]) AND @is_array($pload["parameters"]) ) {
                    $line_load .= '$dispatcher->setRoute("'.$service.'", "'.$type.'", "'.$target.'", ' . var_export($pload["parameters"], true) . ', '.($relative ? 'true' : 'false').');'."\n";
                }
                else {
                    $line_load .= '$dispatcher->setRoute("'.$service.'", "'.$type.'", "'.$target.'", array(), '.($relative ? 'true' : 'false').');'."\n";
                }

            }

        }
        else throw new Exception("Wrong service loader");

        $to_append = "\n".$line_mark."\n".$line_load.$line_mark."\n";

        $action = file_put_contents(self::$dispatcher_routing_cfg, $to_append, FILE_APPEND | LOCK_EX);

        if ( $action === false ) throw new Exception("Cannot activate service route");

    }

    public static function unloadService($package_name) {

        echo "- Disabling route for services of ".$package_name."\n";

        $line_mark = "/****** SERVICE - ".$package_name." - SERVICE ******/";

        $cfg = file(self::$dispatcher_routing_cfg, FILE_IGNORE_NEW_LINES);

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

        $action = file_put_contents(self::$dispatcher_routing_cfg, implode("\n", array_values($cfg)), LOCK_EX);

        if ( $action === false ) throw new Exception("Cannot deactivate route");

    }

}
