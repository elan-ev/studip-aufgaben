<?php
/**
 * UserController - Short description for file
 *
 * Long description for file (if any)...
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

require_once 'epplugin_controller.php';

class UserController extends EPPluginStudipController
{

    public function search_action()
    {
        $db = DBManager::get();

        $users = array();

        $searchterm = studip_utf8decode(urldecode(Request::get('term')));

        // search a bit more intelligent
        $parts = explode(' ', $searchterm);

        if (sizeof($parts) == 1) {
            $sql = "Vorname LIKE ". $db->quote('%'. $parts[0] .'%') ." OR Nachname LIKE ". $db->quote('%'. $parts[0] .'%');
        } else {
            $zw = array();

            foreach ($parts as $search) {
                foreach ($parts as $search2) {
                    if ($search != $search2) {
                        $zw[] = "Vorname LIKE ". $db->quote('%'. $search .'%') ." AND Nachname LIKE ". $db->quote('%'. $search2 .'%');
                    }
                }
            }

            $sql = '(' . implode(') OR (', $zw) . ')';
        }

        foreach (User::findBySQL($sql . ' AND ' . get_vis_query()
                ." ORDER BY Nachname ASC, Vorname") as $user) {
            $users[] = array(
                'id'      => studip_utf8encode($user->username),
                'text'    => studip_utf8encode(get_fullname($user->id) .' ('. $user->username .')'),
                'picture' => studip_utf8encode(Avatar::getAvatar($user->id)->getImageTag(Avatar::SMALL))
            );
        }

        $this->render_json($users);
    }
}
