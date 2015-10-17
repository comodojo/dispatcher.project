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

class FileInstaller extends AbstractInstaller {

	public static function createFolders($folders) {

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

    public static function deleteFolders($folders) {
        
        if ( is_array($folders) ) {

            foreach ($folders as $folder) {
                
                if ( in_array($folder, self::$reserved_folders) ) throw new Exception("Cannot delete reserved folder!");

                echo "- deleting folder ".$folder."\n";

                try {

                    self::recursiveUnlink($folder);
                    
                } catch (Exception $e) {
                    
                    throw $e;

                }

            }

        }

        else {

            if ( in_array($folders, self::$reserved_folders) ) throw new Exception("Cannot overwrite reserved folder!");

            echo "- deleting folder ".$folders."\n";

            try {

                self::recursiveUnlink($folders);
                
            } catch (Exception $e) {
                
                throw $e;

            }

        }

    }

    public static function recursiveUnlink($folder) {

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

}
