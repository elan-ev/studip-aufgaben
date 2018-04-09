<?php
/**
 * TaskUsers - represents an entry in task_users
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 3 of
 * the License, or (at your option) any later version.
 * @author      Till Glöggler <tgloeggl@uos.de>
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GPL version 3
 * @category    Stud.IP
 */

namespace EPP;

class TaskUsers extends \SimpleORMap
{
    /**
     * creates new task_user, sets up relations
     * @param string $id
     */
    public function __construct($id = null)
    {
        $this->db_table = 'ep_task_users';

        $this->has_many['files'] = [
            'class_name'        => 'EPP\TaskUserFiles',
            'assoc_foreign_key' => 'ep_task_users_id',
        ];

        $this->belongs_to['task'] = [
            'class_name'  => 'EPP\Tasks',
            'foreign_key' => 'ep_tasks_id',
        ];

        $this->has_many['perms'] = [
            'class_name'        => 'EPP\Permissions',
            'assoc_foreign_key' => 'ep_task_users_id',
            'on_delete'         => 'delete',
            'on_store'          => 'store'

        ];

        parent::__construct($id);
    }

    /**
     * set chdate and mkdate for the current db-entry to zero.
     * happens on initial creation since the student did not touch the task (yet)
     */
    public function clearDates()
    {
        $where_query = $this->getWhereQuery();

        // DBManager::get()->query(
        $query = "UPDATE `{$this->db_table}` SET
            chdate = 0, mkdate = 0
            WHERE " . join(" AND ", $where_query);

        \DBManager::get()->exec($query);
    }
}
