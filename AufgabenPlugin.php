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

require_once __DIR__ .'/bootstrap.php';

class AufgabenPlugin extends StudIPPlugin implements StandardPlugin, SystemPlugin
{
    const GETTEXT_DOMAIN = 'aufgaben';

    /**
     * Does nothing if plugin is not activated in the current course.
     * In Stud.IP versions prior 2.5 navigation is built here
     */
    public function __construct()
    {
        parent::__construct();

        bindtextdomain(static::GETTEXT_DOMAIN, $this->getPluginPath() . '/locale');
        bind_textdomain_codeset(static::GETTEXT_DOMAIN, 'ISO-8859-1');

    }

    /**
     * Plugin localization for a single string.
     * This method supports sprintf()-like execution if you pass additional
     * parameters.
     *
     * @param String $string String to translate
     * @return string string
     */
    public function _($string)
    {
        $result = static::GETTEXT_DOMAIN === null
                ? $string
                : dcgettext(static::GETTEXT_DOMAIN, $string, LC_MESSAGES);
        if ($result === $string) {
            $result = _($string);
        }

        if (func_num_args() > 1) {
            $arguments = array_slice(func_get_args(), 1);
            $result = vsprintf($result, $arguments);
        }

        return $result;
    }

    /**
     * Plugin localization for plural strings.
     * This method supports sprintf()-like execution if you pass additional
     * parameters.
     *
     * @param String $string0 String to translate (singular)
     * @param String $string1 String to translate (plural)
     * @param mixed  $n       Quantity factor (may be an array or array-like)
     * @return string string
     */
    public function _n($string0, $string1, $n)
    {
        if (is_array($n)) {
            $n = count($n);
        }

        $result = static::GETTEXT_DOMAIN === null
                ? $string0
                : dngettext(static::GETTEXT_DOMAIN, $string0, $string1, $n);
        if ($result === $string0 || $result === $string1) {
            $result = ngettext($string0, $string1, $n);
        }

        if (func_num_args() > 3) {
            $arguments = array_slice(func_get_args(), 3);
            $result = vsprintf($result, $arguments);
        }

        return $result;
    }

    /**
     * Returns the in-course navigation
     * @param string $course_id
     * @return array
     */
    public function getTabNavigation($course_id)
    {
        $navigation = new Navigation(_('Aufgaben'), PluginEngine::getLink('aufgabenplugin/index'));
        $navigation->setImage(Icon::create('assessment'));
        return [
            'aufgaben' => $navigation
        ];
    }


    /**
     * returns the navigation-icon for the course-overview
     * @param string $course_id
     * @param string $last_visit
     * @param string $user_id
     * @return \Navigation
     */
    public function getIconNavigation($course_id, $last_visit, $user_id = null)
    {
        if (!$this->isActivated($course_id)) {
            return;
        }

        $navigation = new Navigation('aufgabenplugin', PluginEngine::getLink('aufgabenplugin/index'));
        $navigation->setImage(Icon::create('assessment', 'clickable'), [
            'title' => $this->_('Es gibt nichts neues seit Ihrem letzten Besuch.')
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
                    'title' => sprintf($this->_('Seit Ihrem letzten Besuch gibt es %s neue Aktivitäten'), $act_num)
                ]);
            }
        } else {    // for students show the number of new, visible, tasks
            $tasks = EPP\Tasks::findBySQL('seminar_id = ? AND mkdate >= ?
                AND startdate <= UNIX_TIMESTAMP()',
                [$course_id, $last_visit]);

            if (sizeof($tasks) > 0) {
                $navigation->setImage(Icon::create('assessment', 'attention'), [
                    'title' => sprintf($this->_('Seit Ihrem letzten Besuch gibt es %s neue Aufgaben.'), sizeof($tasks))
                ]);
            }
        }

        return $navigation;
    }

    /**
     * This plugin does currently not return any notification objects
     * @param string $course_id
     * @param string $since
     * @param string $user_id
     * @return array
     */
    public function getNotificationObjects($course_id, $since, $user_id)
    {
        return [];
    }


    /**
     * route the request to the controllers
     * @param string $unconsumed_path
     */
    public function perform($unconsumed_path)
    {
        $this->addStylesheet('assets/stylesheets/epp.scss');
        PageLayout::addScript($this->getPluginURL() . '/assets/javascripts/epp.js');
        PageLayout::setTitle(Context::getHeaderLine() .' - '. _('Aufgaben'));
        parent::perform($unconsumed_path);
    }

    public function getInfoTemplate($course_id)
    {
        return null;
    }

    function getMetadata()
    {
        $metadata = parent::getMetadata();
        $metadata['pluginname'] = dgettext("aufgaben", "AufgabenPlugin");
        $metadata['displayname'] = dgettext("aufgaben", "Aufgaben (Reflexion)");
        $metadata['descriptionlong'] = dgettext("aufgaben", "Mit diesem Plugin können Sie Reflexionsaufgaben an die Teilnehmenden einer Veranstaltung verteilen, Textantworten entgegennehmen und den Studierenden Ihrerseits Feedback zu ihren Antworten geben. Texte können direkt eingegeben oder in Dateiform hochgeladen werden. Eine Übersicht über die Anzahl eingegebener Zeichen bzw. hochgeladener Dateien erleichtert Ihnen einen Überblick über den Bearbeitungsstand der Aufgaben. Ideal, wenn Sie mit einer eher kleinen Teilnehmerzahl über das Semester hinweg mehrere Reflexionsaufgaben nacheinander bearbeiten möchten.");
        $metadata['descriptionshort'] = dgettext("aufgaben", "Erstellung und Bereitstellung zeitgesteuerter Reflexionsaufgaben für Studierende");
        $metadata['keywords'] = dgettext("aufgaben", "Zeitgesteuerte Reflexionsaufgaben;Abgabe als Text oder in Dateiform;Individuelles Feedback für Studierende möglich");
        return $metadata;
    }
}
