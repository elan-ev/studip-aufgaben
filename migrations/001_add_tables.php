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

class AddTables extends Migration
{
    function up()
    {
        DBManager::get()->exec("
            CREATE TABLE IF NOT EXISTS `ep_tasks` (
              `id` INT NOT NULL AUTO_INCREMENT ,
              `seminar_id` VARCHAR(32) NULL ,
              `user_id` VARCHAR(32) NULL ,
              `title` VARCHAR(255) NULL ,
              `content` MEDIUMTEXT NULL ,
              `allow_text` TINYINT(1) NULL DEFAULT 0 ,
              `allow_files` TINYINT(1) NULL DEFAULT 0 ,
              `startdate` INT NULL ,
              `enddate` INT NULL ,
              `send_mail` TINYINT(1) NULL DEFAULT 0 ,
              `chdate` INT NULL ,
              `mkdate` INT NULL ,
              PRIMARY KEY (`id`) )
        ");

        DBManager::get()->exec("
            CREATE TABLE IF NOT EXISTS `ep_task_users` (
              `id` INT NOT NULL AUTO_INCREMENT ,
              `ep_tasks_id` INT NULL ,
              `user_id` VARCHAR(32) NULL ,
              `hint` MEDIUMTEXT NULL ,
              `answer` MEDIUMTEXT NULL ,
              `feedback` MEDIUMTEXT NULL ,
              `visible` TINYINT(1) NULL DEFAULT 1 ,
              `chdate` INT NULL ,
              `mkdate` INT NULL ,
              PRIMARY KEY (`id`) ,
              INDEX `fk_ep_tasks_users_ep_tasks_idx` (`ep_tasks_id` ASC) )
        ");
        

        DBManager::get()->exec("
            CREATE TABLE IF NOT EXISTS `ep_task_user_files` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `ep_task_users_id` int(11) DEFAULT NULL,
              `dokument_id` varchar(32) NOT NULL,
              `type` enum('answer','feedback') NOT NULL DEFAULT 'answer',
              PRIMARY KEY (`id`)
            )
        ");
    }
    
    function down()
    {
        DBManager::get()->exec("DROP TABLE ep_tasks");
        DBManager::get()->exec("DROP TABLE ep_task_users");
        DBManager::get()->exec("DROP TABLE ep_task_user_files");
    }
}
