<?php
/**
 * IndexController - main controller for the plugin
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 3 of
 * the License, or (at your option) any later version.
 *
 * @author      Till Glöggler <tgloeggl@uos.de>
 * @author      Ramus Fuhse <fuhse@data-quest.de>
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GPL version 3
 * @category    Stud.IP
 */

require_once 'epplugin_controller.php';

require_once $this->trails_root .'/models/Tasks.php';
require_once $this->trails_root .'/models/TaskUsers.php';
require_once $this->trails_root .'/models/TaskUserFiles.php';
require_once $this->trails_root .'/models/Perm.php';
    
class IndexController extends EPPluginStudipController
{
    function before_filter(&$action, &$args) {
        parent::before_filter($action, $args);
        
        // set default layout
        $this->set_layout('layouts/layout');
        
        $nav = Navigation::getItem('course/aufgabenplugin');
        $nav->setImage('icons/16/black/assessment.png');
        Navigation::activateItem('course/aufgabenplugin');
        
        $this->seminar_id = $this->getSeminarId();
        
        // #TODO: remove the following line from production code
        SimpleORMap::expireTableScheme();
    }

    function index_action()
    {
        if (!Request::option('sort_by') 
            || in_array(Request::option('sort_by'), words('title startdate enddate')) === false) {
            $this->sort  = 'enddate';
            $this->order = 'desc';
        } else {
            $this->sort  = Request::option('sort_by');
            $this->order = Request::option('asc') ? 'asc' : 'desc';
        }
        
        if (EPP\Perm::has('new_task', $this->seminar_id)) {
            $this->tasks = EPP\Tasks::findBySQL("seminar_id = ? 
                ORDER BY {$this->sort} {$this->order}, startdate DESC", array($this->seminar_id));
        } else {
            $this->tasks = EPP\Tasks::findBySQL("seminar_id = ? /* AND startdate <= UNIX_TIMESTAMP() */
                ORDER BY {$this->sort} {$this->order}, startdate DESC", array($this->seminar_id));

            // reorder all running tasks if necessary - the task with the shortest time frame shall be first
            if ($this->sort == 'enddate') {
                foreach ($this->tasks as $task) {
                    $reorder[$task->getStatus()][] = $task;
                }
                $reorder['running'] = array_reverse($reorder['running']);

                $new_order = array();

                foreach (words('future running past') as $status) {
                    if (!empty($reorder[$status])) {
                        $new_order = array_merge($new_order, $reorder[$status]);
                    }
                }

                $this->tasks = $new_order;
            }
        }
    }
    
    function new_task_action()
    {
        EPP\Perm::check('new_task', $this->seminar_id);
    }
    
    function add_task_action()
    {
        EPP\Perm::check('new_task', $this->seminar_id);

        $data = array(
            'seminar_id'  => $this->seminar_id,
            'user_id'     => $GLOBALS['user']->id,
            'title'       => Request::get('title'),
            'content'     => Request::get('content'),
            'allow_text'  => Request::int('allow_text'),
            'allow_files' => Request::int('allow_files'),
            'startdate'   => strtotime(Request::get('startdate')),
            'enddate'     => strtotime(Request::get('enddate')),
            'send_mail'   => Request::int('send_mail'),
        );

        $task = \EPP\Tasks::create($data);
        
        $this->redirect('index/index');
    }
    
    function update_task_action($id)
    {
        EPP\Perm::check('new_task', $this->seminar_id);
        
        $task = new EPP\Tasks($id);

        if ($task->seminar_id != $this->seminar_id) {
            throw new AccessDeniedException(_('Die Aufgabe wurde nicht gefunden!'));
        }
        
        $data = array(
            'seminar_id'  => $this->seminar_id,
            'user_id'     => $GLOBALS['user']->id,
            'title'       => Request::get('title'),
            'content'     => Request::get('content'),
            'allow_text'  => Request::int('allow_text'),
            'allow_files' => Request::int('allow_files'),
            'startdate'   => strtotime(Request::get('startdate')),
            'enddate'     => strtotime(Request::get('enddate')),
            'send_mail'   => Request::int('send_mail'),
        );

        $task->setData($data);
        $task->store();
        
        $this->redirect('index/view_task/' . $id);
    }
    
    function delete_task_action($id)
    {
        EPP\Perm::check('new_task', $this->seminar_id);
        
        $task = new EPP\Tasks($id);
        
        if ($task->seminar_id != $this->seminar_id) {
            throw new AccessDeniedException(_('Die Aufgabe wurde nicht gefunden!'));
        }

        $task->delete();
        
        $this->redirect('index/index');
    }
    
    function edit_task_action($id)
    {
        EPP\Perm::check('new_task', $this->seminar_id);

        $this->task = new EPP\Tasks($id);
        
        if ($this->task->seminar_id != $this->seminar_id) {
            throw new AccessDeniedException(_('Die Aufgabe wurde nicht gefunden!'));
        }
    }
    
    function view_dozent_action($task_user_id, $edit_field = null)
    {
        EPP\Perm::check('new_task', $this->seminar_id);

        // if the second parameter is present, the passed field shall be edited
        if ($edit_field) {
            $this->edit[$edit_field] = true;
        }
        
        $this->task_user = new \EPP\TaskUsers($task_user_id);
        $this->task      = new \EPP\Tasks($this->task_user->ep_tasks_id);
        
        if ($this->task->seminar_id != $this->seminar_id) {
            throw new AccessDeniedException(_('Die Aufgabe wurde nicht gefunden!'));
        }        
    }
    
    function update_dozent_action($task_user_id)
    {
        EPP\Perm::check('new_task', $this->seminar_id);

        $task_user = new \EPP\TaskUsers($task_user_id);
        $task      = new \EPP\Tasks($task_user->ep_tasks_id);
        
        if ($task->seminar_id != $this->seminar_id) {
            throw new AccessDeniedException(_('Die Aufgabe wurde nicht gefunden!'));
        }
        
        if (Request::get('feedback') !== null && $task->startdate <= time()) {
            $task_user->feedback = Request::get('feedback');
            $task_user->store();
        } elseif (Request::get('hint') !== null && $task->startdate > time()) {
            $task_user->hint = Request::get('hint');
            $task_user->store();
        }
        
        $this->redirect('index/view_dozent/' . $task_user_id);
    }
    
    
    function view_task_action($id)
    {
        EPP\Perm::check('new_task', $this->seminar_id);

        $this->task = new EPP\Tasks($id);
        $this->participants = CourseMember::findByCourse($this->seminar_id);
        
        if ($this->task->seminar_id != $this->seminar_id) {
            throw new AccessDeniedException(_('Die Aufgabe wurde nicht gefunden!'));
        }

    }
    
    function view_student_action($id, $edit_field = null)
    {
        // if the second parameter is present, the passed field shall be edited
        if ($edit_field) {
            $this->edit[$edit_field] = true;
        }
        
        $this->task = new EPP\Tasks($id);
        
        if ($this->task->startdate > time() || $this->task->seminar_id != $this->seminar_id) {
            throw new AccessDeniedException(_('Die Aufgabe wurde nicht gefunden!'));
        }
                
        $this->task_user = $this->task->task_users->findOneBy('user_id', $GLOBALS['user']->id);

        if (!$this->task_user) {
            $data = array(
                'ep_tasks_id' => $id,
                'user_id'     => $GLOBALS['user']->id
            );

            $this->task_user = EPP\TaskUsers::create($data);
        }

    }
    
    function update_student_action($task_id, $task_user_id)
    {
        $task = new EPP\Tasks($task_id);

        if ($task->startdate > time() || $task->enddate < time()) {
            throw new AccessDeniedException(_('Sie dürfen diese Aufgabe nicht bearbeiten!'));
        }

        if ($task->seminar_id != $this->seminar_id) {
            throw new AccessDeniedException(_('Die Aufgabe wurde nicht gefunden!'));
        }

        $data = array(
            'ep_tasks_id' => $task_id,
            'user_id'     => $GLOBALS['user']->id,
            'answer'      => Request::get('answer')
        );

        $task_user = new EPP\TaskUsers($task_user_id);
        $task_user->setData($data);
        $task_user->store();
        
        $this->redirect('index/view_student/' . $task_id);
    }
    
    function remove_file_action($file_id)
    {
        $file = new \EPP\TaskUserFiles($file_id);

        if (($file->task_user->task->startdate > time() || $file->task_user->task->enddate < time())
                && !$GLOBALS['perm']->have_studip_perm('dozent', $this->seminar_id)) {
            throw new AccessDeniedException(_('Sie dürfen diese Aufgabe nicht bearbeiten!'));
        }

        // only delete file, if it belongs to the current user
        if ($file->document->user_id == $GLOBALS['user']->id) {
            delete_document($file->document->getId());
            $file->delete();
        }
            
        $this->render_nothing();
    }
    
    function post_files_action($task_user_id, $type)
    {
        $task_user = new \EPP\TaskUsers($task_user_id);
        $task      = new \EPP\Tasks($task_user->ep_tasks_id);

        if (($task->startdate > time() || $task->enddate < time()) && !$GLOBALS['perm']->have_studip_perm('dozent', $this->seminar_id)) {
            throw new AccessDeniedException(_('Sie dürfen diese Aufgabe nicht bearbeiten!'));
        }

        if ($task->seminar_id != $this->seminar_id) {
            throw new AccessDeniedException(_('Die Aufgabe wurde nicht gefunden!'));
        }
        
        // user adds file(s) to its solution of the task
        if ($task_user->user_id == $GLOBALS['user']->id && $GLOBALS['perm']->have_studip_perm('autor', $this->seminar_id)) {
            $type = 'answer';
        } else if ($GLOBALS['perm']->have_studip_perm('dozent', $this->seminar_id)) {    // dozent adds feedback for the user
            $type = 'feedback';
        } else { // not author/tutor nor dozent, so access is denied
            throw new AccessDeniedException(_('Sie haben keine Rechte zum Bearbeiten dieser Aufgabe'));
        }

        if (!Request::isPost()
                || !$GLOBALS['perm']->have_studip_perm("autor", $this->seminar_id)) {
            throw new AccessDeniedException("Kein Zugriff");
        }
        
        $output = array();

        foreach ($_FILES as $file) {
            $GLOBALS['msg'] = '';
            validate_upload($file);

            if ($GLOBALS['msg']) {
                $output['errors'][] = $file['name'] . ': ' . studip_utf8encode(decodeHTML(trim(substr($GLOBALS['msg'],6, -1), '?')));
                continue;
            }

            if ($file['size']) {
                $dokument_id = md5(uniqid());

                $document['dokument_id'] = $dokument_id;
                $document['name'] = $document['filename'] = studip_utf8decode(strtolower($file['name']));
                $document['user_id'] = $GLOBALS['user']->id;
                $document['author_name'] = get_fullname();
                $document['seminar_id'] = $GLOBALS['user']->id; // use the user_id here, prevents showing 
                                                                // the file under "all files" while preserving downloadibility
                $document['range_id'] = $this->seminar_id;
                $document['filesize'] = $file['size'];
                
                $data = array(
                    'ep_task_users_id' => $task_user_id,
                    'dokument_id'      => $dokument_id,
                    'type'             => $type
                );
                
                $taskfile = \EPP\TaskUserFiles::create($data);
                
                if ($newfile = StudipDocument::createWithFile($file['tmp_name'], $document)) {
                    $output[] = array(
                        'url'        => GetDownloadLink($newfile->getId(), $newfile['filename']),
                        'id'         => $taskfile->getId(),
                        'name'       => $newfile->name,
                        'date'       => strftime($this->timeformat, time()),
                        'size'       => $newfile->filesize,
                        'seminar_id' => $this->seminar_id
                    );
                }
            }
        }

        $this->render_json($output);
    }
}
