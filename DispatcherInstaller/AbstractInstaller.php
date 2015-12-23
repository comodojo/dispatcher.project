<?php namespace Comodojo\DispatcherInstaller;

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

abstract class AbstractInstaller {

    protected static $known_types = array('dispatcher-plugin', 'dispatcher-service-bundle', 'comodojo-bundle');

    protected static $reserved_folders = array('DispatcherInstaller','cache','configs','lib','plugins','services','templates','vendor');

    protected static $vendor = 'vendor/';

    protected static $dispatcher_plugins_cfg = 'configs/dispatcher-plugins-config.php';

    protected static $dispatcher_routing_cfg = 'configs/dispatcher-routing-config.php';

    protected static $mask = 0644;

}
