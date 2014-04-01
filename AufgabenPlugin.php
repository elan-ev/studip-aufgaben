<?php
/**
 * AufgabenPlugin.php - Main plugin class, routes to trailified plugin
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

// load legacy code for older Stud.IP-Versions
if (version_compare($GLOBALS['SOFTWARE_VERSION'], "2.4", '<=')) {
    $main_version = substr($GLOBALS['SOFTWARE_VERSION'], 0, 3);
    require_once 'compat/'. $main_version .'/StudipArrayObject.php';
    require_once 'compat/'. $main_version .'/EPP_SimpleCollection.php';
    require_once 'compat/'. $main_version .'/EPP_SimpleORMapCollection.php';
    require_once 'compat/'. $main_version .'/EPP_SimpleORMap.php';
    require_once 'compat/'. $main_version .'/EPP_StudipDocument.php';
    require_once 'compat/'. $main_version .'/CourseMember.php';
} else {
    // for version starting from 2.5 use the same stub
    require_once 'compat/2.5/EPP_SimpleCollection.php';
    require_once 'compat/2.5/EPP_SimpleORMapCollection.php';
    require_once 'compat/2.5/EPP_SimpleORMap.php';
    require_once 'compat/2.5/EPP_StudipDocument.php';
}

require_once 'app/models/Tasks.php';
require_once 'app/models/TaskUsers.php';

class AufgabenPlugin extends StudIPPlugin implements StandardPlugin
{
    /**
     * Does nothing if plugin is not activated in the current course.
     * In Stud.IP versions prior 2.5 navigation is built here
     * 
     * @return type
     */
    function __construct()
    {
        parent::__construct();

        if (!$this->isActivated()) {
            return;
        }
        
        $GLOBALS['epplugin_path'] = $this->getPluginURL(); 
        if (Navigation::hasItem("/course") && version_compare($GLOBALS['SOFTWARE_VERSION'], "2.3", '>=')) {
            $navigation = $this->getTabNavigation(Request::get('cid', $GLOBALS['SessSemName'][1]));
            Navigation::insertItem('/course/aufgabenplugin', $navigation['aufgabenplugin'], 'members');
        }
    }

    /**
     * Returns the in-course navigation
     * 
     * @param type $course_id
     * @return type
     */
    public function getTabNavigation($course_id)
    {   
        $navigation = new Navigation(_('Aufgaben'), PluginEngine::getLink('aufgabenplugin/index'));
        $navigation->setImage('icons/16/white/assessment.png');

        return array('aufgabenplugin' => $navigation);
    }

    /**
     * returns the navigation-icon for the course-overview
     * 
     * @param type $course_id
     * @param type $last_visit
     * @param type $user_id
     * @return \Navigation
     */
    public function getIconNavigation($course_id, $last_visit, $user_id = null)
    {
        if (!$this->isActivated($course_id)) {
            return;
        }   

        $navigation = new Navigation('aufgabenplugin', PluginEngine::getLink('aufgabenplugin/index'));
        $navigation->setImage('icons/16/grey/assessment.png', array(
            'title' => _('Es gibt nichts neues seit Ihrem letzten Besuch.')
        ));

        // for lecturers show the number of new activites from their students
        if ($GLOBALS['perm']->have_studip_perm('dozent', $course_id)) {
            $tasks = EPP\Tasks::findBySQL('seminar_id = ?', array($course_id));

            $act_num = 0;
            foreach ($tasks as $task) {
                $tu = EPP\TaskUsers::findBySQL('ep_tasks_id = ? AND mkdate >= ?', array($tasks->id, $last_visit));
                if (!empty($tu)) {
                    $act_num += sizeof($tu);
                }
            }

            if ($act_num > 0) {
                $navigation->setImage('icons/16/red/assessment.png', array(
                    'title' => sprintf(_('Seit Ihrem letzten Besuch gibt es %s neue Aktivitäten'), $act_num)
                ));
            }
        } else {    // for students show the number of new, visible, tasks
            $tasks = EPP\Tasks::findBySQL('seminar_id = ? AND mkdate >= ?
                AND startdate <= UNIX_TIMESTAMP()',
                array($course_id, $last_visit));

            if (sizeof($tasks) > 0) {
                $navigation->setImage('icons/16/red/assessment.png', array(
                    'title' => sprintf(_('Seit Ihrem letzten Besuch gibt es %s neue Aufgaben.'), sizeof($tasks))
                ));
            }
        }

        #$navigation->setBadgeNumber($num_entries);

        return $navigation;
    }

    /**
     * This plugin does currently not return any notification objects
     * 
     * @param type $course_id
     * @param type $since
     * @param type $user_id
     * @return type
     */
    function getNotificationObjects($course_id, $since, $user_id)
    {
        return array();
    }

    const DEFAULT_CONTROLLER = "index";

    /**
     * route the request to the controllers
     * 
     * @param string $unconsumed_path
     */
    function perform($unconsumed_path)
    {
        $trails_root = $this->getPluginPath() . "/app";
        $dispatcher = new Trails_Dispatcher($trails_root,
                                            rtrim(PluginEngine::getURL($this, null, ''), '/'),
                                            self::DEFAULT_CONTROLLER);
        $dispatcher->plugin = $this;
        $dispatcher->dispatch($unconsumed_path);
    }

    public function getInfoTemplate($course_id)
    {
        return null;
    }
}
