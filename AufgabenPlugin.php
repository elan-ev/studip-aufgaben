<?php
/**
 * AufgabenPlugin.php - Main plugin class, routes to trailified plugin
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 3 of
 * the License, or (at your option) any later version.
 * @author      Till Glöggler <tgloeggl@uos.de>
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GPL version 3
 * @category    Stud.IP
 */

require_once 'bootstrap.php';

class AufgabenPlugin extends StudIPPlugin implements StandardPlugin
{
    /**
     * Does nothing if plugin is not activated in the current course.
     * In Stud.IP versions prior 2.5 navigation is built here
     * @return type
     */
    public function __construct()
    {
        parent::__construct();

        if (!$this->isActivated()) {
            return;
        }

        $GLOBALS['epplugin_path'] = $this->getPluginURL();
        if (Navigation::hasItem('/course') && version_compare($GLOBALS['SOFTWARE_VERSION'], '2.3', '>=')) {
            $navigation = new Navigation(_('Aufgaben'), PluginEngine::getLink('aufgabenplugin/index'));
            $navigation->setImage(Icon::create('assessment'));
            Navigation::addItem('/course/aufgabenplugin', $navigation);

            $navigation = new Navigation(_('Übersicht'), PluginEngine::getLink('aufgabenplugin/index'));
            Navigation::addItem('/course/aufgabenplugin/overview', $navigation);
        }
    }

    /**
     * Returns the in-course navigation
     * @param type $course_id
     * @return type
     */
    public function getTabNavigation($course_id)
    {
        return null;
    }

    /**
     * returns the navigation-icon for the course-overview
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
        $navigation->setImage(Icon::create('assessment', 'inactive'), [
            'title' => _('Es gibt nichts neues seit Ihrem letzten Besuch.')
        ]);

        // for lecturers show the number of new activites from their students
        if ($GLOBALS['perm']->have_studip_perm('dozent', $course_id)) {
            $tasks = EPP\Tasks::findBySQL('seminar_id = ?', [$course_id]);

            $act_num = 0;
            foreach ($tasks as $task) {
                $tu = EPP\TaskUsers::findBySQL('ep_tasks_id = ? AND mkdate >= ?', [$tasks->id, $last_visit]);
                if (!empty($tu)) {
                    $act_num += sizeof($tu);
                }
            }

            if ($act_num > 0) {
                $navigation->setImage(Icon::create('assessment', 'attention'), [
                    'title' => sprintf(_('Seit Ihrem letzten Besuch gibt es %s neue Aktivitäten'), $act_num)
                ]);
            }
        } else {    // for students show the number of new, visible, tasks
            $tasks = EPP\Tasks::findBySQL('seminar_id = ? AND mkdate >= ?
                AND startdate <= UNIX_TIMESTAMP()',
                [$course_id, $last_visit]);

            if (sizeof($tasks) > 0) {
                $navigation->setImage(Icon::create('assessment', 'attention'), [
                    'title' => sprintf(_('Seit Ihrem letzten Besuch gibt es %s neue Aufgaben.'), sizeof($tasks))
                ]);
            }
        }

        return $navigation;
    }

    /**
     * This plugin does currently not return any notification objects
     * @param type $course_id
     * @param type $since
     * @param type $user_id
     * @return type
     */
    public function getNotificationObjects($course_id, $since, $user_id)
    {
        return [];
    }

    const DEFAULT_CONTROLLER = "index";

    /**
     * route the request to the controllers
     * @param string $unconsumed_path
     */
    public function perform($unconsumed_path)
    {
        $this->addStylesheet('assets/stylesheets/epp.less');
        PageLayout::addScript($this->getPluginURL() . '/assets/javascripts/epp.js');
        PageLayout::addScript($this->getPluginURL() . '/assets/javascripts/jquery.ui.widget.js');

        if (!Config::get()->WYSIWYG) {
            PageLayout::addScript($this->getPluginURL() . '/assets/javascripts/jquery.iframe-transport.js');
            PageLayout::addScript($this->getPluginURL() . '/assets/javascripts/jquery.fileupload.js');
        }

        $trails_root        = $this->getPluginPath() . '/app';
        $dispatcher         = new Trails_Dispatcher($trails_root,
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
