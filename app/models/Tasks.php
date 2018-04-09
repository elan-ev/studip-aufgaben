<?php
/**
 * Tasks - presents a single task
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 3 of
 * the License, or (at your option) any later version.
 * @author      Till Glöggler <tgloeggl@uos.de>
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GPL version 3
 * @category    Stud.IP
 */

namespace EPP;

class Tasks extends \SimpleORMap
{
    /**
     * creates new task, sets up relations
     * @param string $id
     */
    public function __construct($id = null)
    {
        $this->db_table = 'ep_tasks';

        $this->has_many['task_users'] = [
            'class_name'        => 'EPP\TaskUsers',
            'assoc_foreign_key' => 'ep_tasks_id'
        ];

        parent::__construct($id);
    }

    /**
     * returns a status string denoting the run-status of the current task
     * @return string|boolean
     */
    public function getStatus()
    {
        if ($this->startdate <= time() && $this->enddate >= time()) {
            return 'running';
        } else if ($this->enddate < time()) {
            return 'past';
        } else if ($this->startdate > time()) {
            return 'future';
        }

        return false;
    }

    /**
     * returns a human readable version of the run-status
     * @return string
     */
    public function getStatusText()
    {
        switch ($this->getStatus()) {
            case 'running':
                return _('läuft');
                break;

            case 'past':
                return _('beendet');
                break;

            case 'future':
                return _('läuft noch nicht');
                break;
        }
    }
}
