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

class AddPermissions extends Migration
{
    function up()
    {
        $db = DBManager::get();

        $db->exec("
            CREATE  TABLE IF NOT EXISTS `ep_permissions` (
              `ep_task_users_id` INT NOT NULL ,
              `user_id` VARCHAR(32) NULL ,
              `role` ENUM('tutor','followup-tutor','student') NULL ,
              PRIMARY KEY (`ep_task_users_id`, `user_id`)
            );
        ");

        SimpleORMap::expireTableScheme();
    }

    function down()
    {
        DBManager::get()->exec("DROP TABLE `ep_permissions`");

        SimpleORMap::expireTableScheme();
    }
}
