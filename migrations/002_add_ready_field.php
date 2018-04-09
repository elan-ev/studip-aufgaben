<?php
/**
 * AddTables - Migration to initialize DB-structure
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 3 of
 * the License, or (at your option) any later version.
 *
 * @author      Till Glöggler <tgloeggl@uos.de>
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GPL version 3
 * @category    Stud.IP
 */

class AddReadyField extends Migration
{
    function up()
    {
        DBManager::get()->exec("
            ALTER TABLE `ep_task_users` ADD `ready` BOOLEAN NOT NULL DEFAULT FALSE 
        ");
    }
    
    function down()
    {
        DBManager::get()->exec("ALTER TABLE `ep_task_users` ADD `ready`");
    }
}
