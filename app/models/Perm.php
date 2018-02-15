<?php
/**
 * Perm - handles the permissions for the visibility and actions avaiable
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

namespace EPP;

use AufgabenPlugin;

class Perm
{

    /**
     * Check, if the a user has the passed permission in a seminar.
     * Possible permissions are:
     *   new_task     - Create a new task for the participants of the course<br>
     *
     * @param string $perm        one of the modular permissions
     * @param string $seminar_id  the seminar to check for
     * @param string $user_id     the user to check for
     * @return boolean  true, if the user has the perms, false otherwise
     */
    public static function has($perm, $seminar_id, $user_id = null)
    {
        static $permissions = array();

        // if no user-id is passed, use the current user (for your convenience)
        if (!$user_id) {
            $user_id = $GLOBALS['user']->id;
        }

        // get the status for the user in the passed seminar
        if (!$permissions[$seminar_id][$user_id]) {
            $permissions[$seminar_id][$user_id] = $GLOBALS['perm']->get_studip_perm($seminar_id, $user_id);
        }

        $status = $permissions[$seminar_id][$user_id];

        // root and admins have all possible perms
        if (in_array($status, words('root admin')) !== false) {
            return true;
        }

        // check the status and the passed permission
        if (($status == 'dozent' || $status == 'tutor') && in_array($perm,
            words('new_task')
        ) !== false) {
            return true;
        } else if (($status == 'autor' || $status == 'user') && in_array($perm,
            words('')) !== false) {
            return true;
        }

        // user has no permission
        return false;
    }

    /**
     * If the user has not the passed perm in a seminar, an AccessDeniedException
     * is thrown.
     * An optional topic_id can be passed which is checked against the passed
     * seminar if the topic_id belongs to that seminar
     *
     * @param string $perm        for the list of possible perms and their function see @ForumPerm::hasPerm()
     * @param string $seminar_id  the seminar to check for
     * @param string $topic_id    if passed, this topic_id is checked if it belongs to the passed seminar
     *
     * @throws AccessDeniedException
     */
    public function check($perm, $seminar_id)
    {
        if (!self::has($perm, $seminar_id)) {
            throw new \AccessDeniedException(sprintf(
                dgettext(
                    AufgabenPlugin::GETTEXT_DOMAIN,
                    "Sie haben keine Berechtigung für diese Aktion! Benötigte Berechtigung: %s"
                ),
                $perm)
            );
        }
    }

    /**
     * get permissions for passed user for the passed task
     * returns a permission matrix of the following style:
     *  array(
     *     'edit_task' =>
     *     'edit_answer']   =>
     *     'edit_feedback'] =>
     *     'edit_goal']     =>
     *     'close_task']    =>
     *     'view_answer']   =>
     *     'view_feedback'] =>
     *     'view_goal']     =>
     * )
     *
     * @param string $user_id
     * @param object $task
     *
     * @return array
     */
    public static function get($user_id, $task_user) {
        $perms = array(
            'edit_task'     => false,
            'edit_settings' => true,
            'edit_answer'   => true,
            'edit_feedback' => false,
            'edit_goal'     => false,
            'close_task'    => false,
            'view_answer'   => true,
            'view_feedback' => true,
            'view_goal'     => true
        );

         // as owner of a task, one may administer the task, but not the feedback
        if ($user_id == $task_user->user_id) {
            $perms['edit_task'] = true;

        // for non-owners get role-specific perms
        } else {
            $perms['edit_task'] = false;

            foreach($task_user->perms as $perm) {
                if ($perm->user_id == $user_id) {
                    switch($perm->role) {
                        case 'student':
                            $perms['edit_settings'] = false;
                            $perms['edit_answer']   = true;
                            $perms['edit_feedback'] = false;
                            $perms['view_answer']   = true;
                            $perms['view_feedback'] = true;
                        break;
                    }
                }
            }
        }

        return $perms;
    }
}
