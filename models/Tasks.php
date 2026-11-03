<?php
/**
 * Tasks - presents a single task
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 3 of
 * the License, or (at your option) any later version.
 *
 * @author      Till Glöggler <tgloeggl@uos.de>
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GPL version 3
 * @category    Stud.IP
 */

namespace EPP;

use AufgabenPlugin;

class Tasks extends \SimpleORMap
{
    /**
     * *@inherit
     */
    protected static function configure($config = [])
    {
        $config['db_table'] = 'ep_tasks';

        $config['has_many']['task_users'] = [
            'class_name'        => TaskUsers::class,
            'assoc_foreign_key' => 'ep_tasks_id'
        ];

        parent::configure($config);
    }

    /**
     * Ensure every participant has a task entry and answer and feedback folders.
     *
     * @return TaskUsers[] Task entries indexed by user ID.
     */
    public static function createMissingFolders($folder, $task)
    {
        $task_users = [];
        foreach (TaskUsers::findBySQL('ep_tasks_id = ?', [$task->id]) as $task_user) {
            $task_users[$task_user->user_id] = $task_user;
        }

        foreach (\CourseMember::findBySQL(
            'seminar_id = ? AND status != ?',
            [$task->seminar_id, 'dozent']
        ) as $participant) {
            $task_user = $task_users[$participant->user_id] ?? null;
            if (!$task_user) {
                $task_user = TaskUsers::create([
                    'user_id'     => $participant->user_id,
                    'chdate'      => 0,
                    'mkdate'      => 0,
                    'ep_tasks_id' => $task->id
                ]);
                $task_users[$participant->user_id] = $task_user;
            }

            Helper::getTypedFolder($folder, $task, $task_user, 'answer');
            Helper::getTypedFolder($folder, $task, $task_user, 'feedback');
        }

        return $task_users;
    }

    /**
     * returns a human readable version of the run-status
     *
     * @return string
     */
    public function getStatusText()
    {
        switch ($this->getStatus()) {
            case 'running':
                return dgettext(AufgabenPlugin::GETTEXT_DOMAIN, 'läuft');
            case 'past':
                return dgettext(AufgabenPlugin::GETTEXT_DOMAIN, 'beendet');
            case 'future':
                return dgettext(AufgabenPlugin::GETTEXT_DOMAIN, 'läuft noch nicht');
        }
    }

    /**
     * returns a status string denoting the run-status of the current task
     *
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
}
