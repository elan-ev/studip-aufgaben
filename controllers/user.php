<?php

use EPP;


/**
 * UserController - Short description for file
 * Long description for file (if any)...
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 3 of
 * the License, or (at your option) any later version.
 *
 * @author      Till Glöggler <tgloeggl@uos.de>
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GPL version 3
 * @category    Stud.IP
 */
class UserController extends EPP\Controller
{
    public function search_action()
    {
        $searchterm = Request::get('term');

        // search a bit more intelligent
        $parts = explode(' ', $searchterm);

        if (sizeof($parts) == 1) {
            $sql = "Vorname LIKE " . DBManager::get()->quote('%' . $parts[0] . '%') . " OR Nachname LIKE " . DBManager::get()->quote('%' . $parts[0] . '%');
        } else {
            $zw = [];

            foreach ($parts as $search) {
                foreach ($parts as $search2) {
                    if ($search != $search2) {
                        $zw[] = "Vorname LIKE " . DBManager::get()->quote('%' . $search . '%') . " AND Nachname LIKE " . DBManager::get()->quote('%' . $search2 . '%');
                    }
                }
            }

            $sql = '(' . implode(') OR (', $zw) . ')';
        }

        $db = DBManager::get()->prepare('SELECT user_id, username FROM auth_user_md5 WHERE ' . $sql . ' AND ' . get_vis_query() . ' ORDER BY Nachname ASC, Vorname');
        $db->execute();
        $users = $db->fetchAll(PDO::FETCH_ASSOC);

        foreach ($users as $index => $user) {
            $users[$index] = [
                'id'   => $user['username'],
                'text' => get_fullname($user['user_id']) . ' (' . $user['username'] . ')',
            ];
        }

        $this->render_json($users);
    }
}
