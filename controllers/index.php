<?php

/**
 * IndexController - main controller for the plugin
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
class IndexController extends \EPP\Controller
{
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        // set default layout
        Navigation::activateItem('/course/feedback-aufgaben');

        PageLayout::setBodyElementId('aufgaben-plugin');

        $this->seminar_id = \Context::get()->id;

        $this->permissions = [
            'student' => $this->_('Kommilitone/in'),
        ];

        $this->folder = \EPP\Helper::getCourseFolder($this->seminar_id);
    }

    public function index_action()
    {
        if (!Request::option('sort_by')
            || in_array(Request::option('sort_by'), words('title startdate enddate')) === false
        ) {
            $this->sort  = 'enddate';
            $this->order = 'desc';
        } else {
            $this->sort  = Request::option('sort_by');
            $this->order = Request::option('asc') ? 'asc' : 'desc';
        }

        if (\EPP\Perm::has('new_task', $this->seminar_id)) {
            $this->tasks = \EPP\Tasks::findBySQL("seminar_id = ?
                ORDER BY {$this->sort} {$this->order}, startdate DESC", [$this->seminar_id]);
        } else {
            $this->tasks = \EPP\Tasks::findBySQL("seminar_id = ? /* AND startdate <= UNIX_TIMESTAMP() */
                ORDER BY {$this->sort} {$this->order}, startdate DESC", [$this->seminar_id]);

            // reorder all running tasks if necessary - the task with the shortest time frame shall be first
            if ($this->sort == 'enddate') {
                $reorder = [];
                foreach ($this->tasks as $task) {
                    $reorder[$task->getStatus()][] = $task;
                }

                if (isset($reorder['running'])) {
                    $reorder['running'] = array_reverse($reorder['running']);
                }

                $new_order = [];

                foreach (words('future running past') as $status) {
                    if (!empty($reorder[$status])) {
                        $new_order = array_merge($new_order, $reorder[$status]);
                    }
                }

                $this->tasks = $new_order;
            }
        }

        $this->own_task_users = [];
        foreach ($this->tasks as $task) {
            $task_users = \EPP\Tasks::createMissingFolders($this->folder, $task);
            if (!\EPP\Perm::has('new_task', $this->seminar_id)) {
                $task_user = $task_users[$GLOBALS['user']->id] ?? null;
                if (!$task_user) {
                    $task_user = \EPP\Tasks::getOrCreateTaskUser($task, $GLOBALS['user']->id);
                    \EPP\Tasks::ensureUserFolders($this->folder, $task, $task_user);
                }
                $this->own_task_users[$task->id] = $task_user;
            }
        }

        $this->accessible_tasks = \EPP\Helper::getForeignTasksForUser($GLOBALS['user']->id);
        foreach ($this->accessible_tasks as $task_user) {
            if ($task_user && $task_user->task) {
                \EPP\Tasks::ensureUserFolders($this->folder, $task_user->task, $task_user);
            }
        }

        if (\EPP\Perm::has('new_task', $this->seminar_id)) {
            $actions = new ActionsWidget();
            $actions->addLink(
                $this->_('Neue Aufgabe anlegen'),
                $this->url_for('index/new_task'),
                Icon::create('add')
            )->asDialog('size=50%');

            Sidebar::Get()->addWidget($actions);
        }
    }

    public function new_task_action()
    {
        PageLayout::setTitle($this->_('Neue Aufgabe anlegen'));
        \EPP\Perm::check('new_task', $this->seminar_id);

        $this->task = new \EPP\Tasks();

        $this->destination = 'index/add_task';
        $this->render_template('index/edit_task');
    }

    public function add_task_action()
    {
        \EPP\Perm::check('new_task', $this->seminar_id);

        $data = [
            'seminar_id'  => $this->seminar_id,
            'user_id'     => $GLOBALS['user']->id,
            'title'       => Request::get('title'),
            'content'     => Request::get('content'),
            'allow_text'  => Request::int('allow_text'),
            'allow_files' => Request::int('allow_files'),
            'startdate'   => strtotime(Request::get('startdate')),
            'enddate'     => strtotime(Request::get('enddate')),
            'send_mail'   => Request::int('send_mail'),
        ];

        if (\EPP\Tasks::create($data)) {
            PageLayout::postSuccess(sprintf(
                $this->_('Die Aufgabe %s wurde erfolgreich angelegt!'),
                htmlReady(Request::get('title'))
            ));
        } else {
            PageLayout::postError($this->_('Beim Anlegen der Aufgabe ist etwas schief gelaufen.
            Versuchen Sie es noch einmal oder wenden Sie sich an einen Systemadministrator'));
        }

        $this->redirect('index/index');
    }

    public function update_task_action($id)
    {
        CSRFProtection::verifyUnsafeRequest();
        \EPP\Perm::check('new_task', $this->seminar_id);
        $task = new \EPP\Tasks($id);

        if ($task->seminar_id != $this->seminar_id) {
            throw new AccessDeniedException($this->_('Die Aufgabe wurde nicht gefunden!'));
        }

        $data = [
            'seminar_id'  => $this->seminar_id,
            'user_id'     => $GLOBALS['user']->id,
            'title'       => Request::get('title'),
            'content'     => Request::get('content'),
            'allow_text'  => Request::int('allow_text'),
            'allow_files' => Request::int('allow_files'),
            'startdate'   => strtotime(Request::get('startdate')),
            'enddate'     => strtotime(Request::get('enddate')),
            'send_mail'   => Request::int('send_mail'),
        ];

        $task->setData($data);
        $task->store();

        PageLayout::postSuccess(sprintf(
            $this->_('Die Aufgabe %s wurde erfolgreich bearbeitet!'),
            htmlReady(Request::get('title'))
        ));

        $this->redirect('index/view_task/' . $id);
    }

    public function delete_task_action($id)
    {
        \EPP\Perm::check('new_task', $this->seminar_id);

        $task = new \EPP\Tasks($id);

        if ($task->seminar_id != $this->seminar_id) {
            throw new AccessDeniedException($this->_('Die Aufgabe wurde nicht gefunden!'));
        }

        $task->delete();
        $this->redirect('index/index');
    }

    public function edit_task_action($id)
    {
        PageLayout::setTitle($this->_('Aufgabe bearbeiten'));
        \EPP\Perm::check('new_task', $this->seminar_id);

        $this->task = new \EPP\Tasks($id);

        if ($this->task->seminar_id != $this->seminar_id) {
            throw new AccessDeniedException($this->_('Die Aufgabe wurde nicht gefunden!'));
        }

        $this->destination = 'index/update_task/' . $id;
        $this->render_template('index/edit_task');
    }

    public function view_dozent_action($task_user_id, $edit_field = null)
    {
        \EPP\Perm::check('new_task', $this->seminar_id);

        // if the second parameter is present, the passed field shall be edited
        if ($edit_field) {
            $this->edit[$edit_field] = true;
        }

        $this->task_user = new \EPP\TaskUsers($task_user_id);
        $this->task      = new \EPP\Tasks($this->task_user->ep_tasks_id);

        if ($this->task->seminar_id != $this->seminar_id) {
            throw new AccessDeniedException($this->_('Die Aufgabe wurde nicht gefunden!'));
        }

        \EPP\Tasks::ensureUserFolders($this->folder, $this->task, $this->task_user);

        Sidebar::get()->addWidget(\EPP\Helper::getSidebarInfos($this->task, $this));
    }

    public function update_dozent_action($task_user_id)
    {
        \EPP\Perm::check('new_task', $this->seminar_id);

        $task_user = new \EPP\TaskUsers($task_user_id);
        $task      = new \EPP\Tasks($task_user->ep_tasks_id);

        if ($task->seminar_id != $this->seminar_id) {
            throw new AccessDeniedException($this->_('Die Aufgabe wurde nicht gefunden!'));
        }

        if (Request::get('feedback') !== null && $task->startdate <= time()) {
            $task_user->feedback = Request::get('feedback');
            $task_user->store();
        } else if (Request::get('hint') !== null && $task->startdate > time()) {
            $task_user->hint = Request::get('hint');
            $task_user->store();
        }

        $this->redirect('index/view_dozent/' . $task_user_id);
    }


    public function view_task_action($id, $page = 1)
    {
        \EPP\Perm::check('new_task', $this->seminar_id);

        $this->task = new \EPP\Tasks($id);

        if ($this->task->seminar_id != $this->seminar_id) {
            throw new AccessDeniedException($this->_('Die Aufgabe wurde nicht gefunden!'));
        }

        $this->search = trim(Request::get('search', ''));
        $this->participants_per_page = Config::get()->ENTRIES_PER_PAGE;

        $participant_sql = 'LEFT JOIN auth_user_md5 USING (user_id)
            WHERE seminar_id = :seminar_id
              AND seminar_user.status != :status';
        $participant_parameters = [
            'seminar_id' => $this->seminar_id,
            'status'     => 'dozent',
        ];
        if ($this->search !== '') {
            $participant_sql .= ' AND (
                auth_user_md5.Vorname LIKE :search_first_name
                OR auth_user_md5.Nachname LIKE :search_last_name
                OR auth_user_md5.username LIKE :search_username
                OR CONCAT_WS(\' \', auth_user_md5.Vorname, auth_user_md5.Nachname) LIKE :search_full_name
                OR CONCAT_WS(\' \', auth_user_md5.Nachname, auth_user_md5.Vorname) LIKE :search_reverse_name
            )';
            $search_term = '%' . $this->search . '%';
            $participant_parameters += [
                'search_first_name'   => $search_term,
                'search_last_name'    => $search_term,
                'search_username'     => $search_term,
                'search_full_name'    => $search_term,
                'search_reverse_name' => $search_term,
            ];
        }

        $this->participant_count = CourseMember::countBySql(
            $participant_sql,
            $participant_parameters
        );
        $this->page = max(1, (int) $page);
        $last_page = max(1, (int) ceil($this->participant_count / $this->participants_per_page));
        $this->page = min($this->page, $last_page);

        $this->participants = CourseMember::findBySQL($participant_sql . '
            ORDER BY auth_user_md5.Nachname, auth_user_md5.Vorname, seminar_user.user_id
            LIMIT :offset, :limit', $participant_parameters + [
            'offset'     => ($this->page - 1) * $this->participants_per_page,
            'limit'      => $this->participants_per_page,
        ]);

        $participant_ids = SimpleCollection::createFromArray($this->participants)->pluck('user_id');
        $this->participant_users = [];
        if ($participant_ids) {
            foreach (User::findMany($participant_ids) as $participant_user) {
                $this->participant_users[$participant_user->id] = $participant_user;
            }
        }
        $this->task_users = \EPP\Tasks::createMissingFolders($this->folder, $this->task);

        $actions = new ActionsWidget();
        $actions->addLink(
            $this->_('Aufgabe bearbeiten'),
            $this->url_for('index/edit_task/' . $id),
            Icon::create('edit')
        )->asDialog('size=50%');
        $actions->addLink(
            $this->_('Aufgabe löschen'),
            $this->url_for('index/delete_task/' . $id),
            Icon::create('trash'),
            ['data-confirm' => $this->_('Sind Sie sicher, dass Sie die komplette Aufgabe löschen möchten?')]
        );

        Sidebar::get()->addWidget($actions);

        $search = new SearchWidget($this->url_for('index/view_task/' . $id));
        $search->addNeedle($this->_('Person suchen'), 'search', true);
        Sidebar::get()->addWidget($search);

        Sidebar::get()->addWidget(\EPP\Helper::getSidebarInfos($this->task, $this));
    }

    public function view_student_action($id, $edit_field = null)
    {
        // if the second parameter is present, the passed field shall be edited
        if ($edit_field) {
            $this->edit[$edit_field] = true;
        }

        $this->task = new \EPP\Tasks($id);

        if ($this->task->startdate > time() || $this->task->seminar_id != $this->seminar_id) {
            throw new AccessDeniedException($this->_('Die Aufgabe wurde nicht gefunden!'));
        }

        if ($task_user_id = Request::get('task_user_id')) {

            $this->task_user    = \EPP\TaskUsers::find($task_user_id);
            $this->task_user_id = $task_user_id;
        } else {
            $this->task_user = $this->task->task_users->findOneBy('user_id', $GLOBALS['user']->id);
        }

        if (!$this->task_user) {
            $this->task_user = \EPP\Tasks::getOrCreateTaskUser($this->task, $GLOBALS['user']->id);
        }

        $this->perms = \EPP\Perm::get($GLOBALS['user']->id, $this->task_user);

        if (!$this->perms['edit_answer']) {
            throw new AccessDeniedException($this->_('Sie haben keine Rechte zum Bearbeiten dieser Aufgabe.'));
        }

        \EPP\Tasks::ensureUserFolders($this->folder, $this->task, $this->task_user);

        Sidebar::get()->addWidget(\EPP\Helper::getSidebarInfos($this->task, $this));
    }

    public function update_student_action($task_id, $task_user_id)
    {
        $task = new \EPP\Tasks($task_id);

        if ($task->startdate > time() || $task->enddate < time()) {
            throw new AccessDeniedException($this->_('Sie dürfen diese Aufgabe nicht bearbeiten!'));
        }

        if ($task->seminar_id != $this->seminar_id) {
            throw new AccessDeniedException($this->_('Die Aufgabe wurde nicht gefunden!'));
        }

        $data = [
            'ep_tasks_id' => $task_id,
            'answer'      => Request::get('answer')
        ];

        $task_user = new \EPP\TaskUsers($task_user_id);
        $task_user->setData($data);
        $task_user->store();

        if ($task_user->user_id != $GLOBALS['user']->id) {
            $this->redirect('index/view_student/' . $task_id . '?task_user_id=' . $task_user_id);
        } else {
            $this->redirect('index/view_student/' . $task_id);
        }
    }

    public function set_ready_action($task_id)
    {
        $task = new \EPP\Tasks($task_id);

        if ($task->startdate > time() || $task->enddate < time()) {
            throw new AccessDeniedException($this->_('Sie dürfen diese Aufgabe nicht bearbeiten!'));
        }

        if ($task->seminar_id != $this->seminar_id) {
            throw new AccessDeniedException($this->_('Die Aufgabe wurde nicht gefunden!'));
        }

        $task_user = \EPP\TaskUsers::findOneBySQL('user_id = ? AND ep_tasks_id = ?',
            [$GLOBALS['user']->id, $task->getId()]
        );

        $task_user->ready = 1;
        $task_user->store();

        $this->redirect('index/view_student/' . $task->getId());
    }

    /**
     * add a permission for an user-instance of a task
     *
     * @param string $task_user_id
     */
    public function add_permission_action($task_user_id)
    {
        $this->render_nothing();

        $current_user_id = $GLOBALS['user']->id;
        ## $task_user = EPP\TaskUsers::find($task_user_id);
        $task_user = new \EPP\TaskUsers($task_user_id);

        $perms = \EPP\Perm::get($current_user_id, $task_user);
        if (!$perms['edit_settings']) {
            throw new AccessDeniedException();
        }

        $user_id = get_userid(Request::get('user'));

        // the user ist not allowed to store a perm for himself
        if ($user_id == $current_user_id) {
            $this->response->set_status(400, $this->_('Sie dürfen sich nicht selbst für eine Berechtigung eintragen!'));
            return;
        }

        // check that the submitted user has not another perm already
        foreach ($task_user->perms as $key => $perm) {
            if ($perm->user_id == $user_id) {
                $this->response->set_status(400, $this->_('Für diesen Nutzer existiert bereits eine andere Berechtigung!'));
                return;
            }
        }

        $perm = new \EPP\Permissions();

        // add new permission entry
        $perm->setData([
            'user_id' => $user_id,
            'role'    => Request::option('perm')
        ]);

        $task_user->perms[] = $perm;

        $task_user->store();
    }

    /**
     * delete a permission for an user-instance of a task
     *
     * @param int $task_user_id
     */
    public function delete_permission_action($task_user_id)
    {
        $current_user_id = $GLOBALS['user']->id;
        $task_user       = \EPP\TaskUsers::find($task_user_id);

        $perms = \EPP\Perm::get($current_user_id, $task_user);
        if (!$perms['edit_settings']) {
            throw new AccessDeniedException();
        }

        $user_id = get_userid(Request::get('user'));

        foreach ($task_user->perms as $key => $perm) {
            if ($perm->user_id == $user_id) {
                unset($task_user->perms[$key]);
            }
        }

        $task_user->store();

        $this->render_nothing();
    }

    /**
     * create a pdf from all answers to the solutions of the submitted task
     *
     * @param int $task_id
     */
    public function pdf_action($task_id)
    {
        $task = new \EPP\Tasks($task_id);
        $this->render_nothing();
        $document = new ExportPDF();
        $document->SetTitle($task->title);
        $document->setHeaderTitle($task->title);
        foreach ($task->task_users as $task_user) {
            if ($task_user->answer) {
                $document->addPage();
                $content  = sprintf('<h1>Antwort von %s</h1>', get_fullname($task_user->user_id));
                $content  .= sprintf('<p><strong>Username:</strong> %s</p>', get_username($task_user->user_id));
                $matrikel = $this->getMatrikel($task_user->user_id);

                if ($matrikel) {
                    $content .= sprintf('<p><strong>Matrikelnummer:</strong> %s</p>', $matrikel);
                }
                $content .= sprintf('<p>%s</p>', formatReady($task_user->answer));
                $document->writeHTMLCell(0, 0, '', '', $content, 0, 1, 0, true, '', true);
            }
        }
        $pdf_name = FileManager::cleanFileName($task->title . '-' . $this->_('Abgaben der Studierenden') . '.pdf');
        $document->Output($pdf_name, 'D');
    }

    /**
     * Returns the matriculation number if exists
     *
     * @param $user_id
     * @return string
     */
    protected function getMatrikel($user_id)
    {
        return DBManager::get()->fetchColumn("SELECT DISTINCT `content` FROM `datafields` df
            JOIN `datafields_entries` dfe ON dfe.`datafield_id` = df.`datafield_id`
            where lower(`name`) LIKE '%matrikel%' AND dfe.`range_id` = ?", [$user_id]);
    }

    public function upload_dialog_action($task_id)
    {
        // check perms
        \EPP\Perm::check('new_task', $this->seminar_id);

        $this->task_id = $task_id;
    }

    /**
     * create a zip from all files attached to the solutions of the submitted task
     *
     * @param int $task_id
     */
    public function zip_action($task_id)
    {
        // check perms
        \EPP\Perm::check('new_task', $this->seminar_id);

        $task = new \EPP\Tasks($task_id);
        if ($task->seminar_id != $this->seminar_id) {
            throw new AccessDeniedException($this->_('Die Aufgabe wurde nicht gefunden!'));
        }
        \EPP\Tasks::createMissingFolders($this->folder, $task);

        $archive_file_name = $task->title . '-' . _('Abgaben_der_Studierenden') . '-' . date('Ymds-Hi') . '.zip';
        $archive_path      = $GLOBALS['TMP_PATH'] . '/' . $archive_file_name;

        $task_folder = null;

        foreach ($this->folder->subfolders as $subfolder) {
            if ($subfolder['data_content']['task_id'] == $task_id) {
                $task_folder = $subfolder;
            }
        }

        if ($task_folder) {
            $result = FileArchiveManager::createArchiveFromFolder(
                $task_folder->getTypedFolder(),
                User::findCurrent(),
                $archive_path,
                false
            );

            $archive_download_link = FileManager::getDownloadURLForTemporaryFile(
                $archive_path,
                $archive_file_name
            );

            $this->redirect($archive_download_link);
        } else {
            $this->redirect('index/index');
        }
    }

    /**
     * Sorts through the data in the zip file for feedback files and attaches
     * them to the correct student.
     *
     * @param $task_id
     * @param $file_id
     */
    public function upload_zip_action($task_id)
    {
        // check perms
        \EPP\Perm::check('new_task', $this->seminar_id);

        $task = new \EPP\Tasks($task_id);
        if ($task->seminar_id != $this->seminar_id) {
            throw new AccessDeniedException($this->_('Die Aufgabe wurde nicht gefunden!'));
        }
        \EPP\Tasks::createMissingFolders($this->folder, $task);

        // get the uploaded zip and store it
        if (isset($_FILES['file']) && is_array($_FILES['file'])) {
            $tmp_folder = \EPP\Helper::createTempFolder($this->folder, $this->seminar_id);
            try {
                $validatedFiles = FileManager::handleFileUpload(
                    $_FILES['file'],
                    $tmp_folder->getTypedFolder(),
                    $GLOBALS['user']->id
                );

                if (count($validatedFiles['error']) > 0) {
                    throw new \Exception(
                        _('Beim Hochladen ist ein Fehler aufgetreten: ') .
                        implode(',', array_map('htmlready', $validatedFiles['error']))
                    );
                }

                $uploaded_zip = $validatedFiles['files'][0]->getFileref();

                if (!$uploaded_zip) {
                    \PageLayout::postError(
                        _('Fehler beim Entpacken der zip-Datei: Datei existiert nicht.')
                    );
                }

                if (!$tmp_folder) {
                    \PageLayout::postError(
                        _('Fehler beim Entpacken der zip-Datei: Ordner existiert nicht')
                    );
                }

                $archive = FileArchiveManager::extractArchiveFileToFolder(
                    $uploaded_zip->getFileType(),
                    $tmp_folder->getTypedFolder()
                );
                $feedback = [];
                $no_student_folder = [];
                foreach ($archive as $file) {
                    $feedback_dir = $file->getFolderType();
                    // extract only the feedback files from the archive
                    if (mb_strtolower($feedback_dir->name) !== 'feedback') {
                        continue;
                    }
                    // skip MacOS hidden files
                    if (strpos($file->name, '._') === 0) {
                        continue;
                    }
                    $parent = $feedback_dir->getParent();
                    if (!$parent || !$parent->name || $parent->getId() === $tmp_folder->getId()) {
                        // "Feedback" lies directly in the root of the zip, so there is no student folder
                        $no_student_folder[$file->name] = $file->name;
                        continue;
                    }
                    $key = \EPP\Helper::getFolderNameKey($parent->name);
                    $feedback[$key]['name'] = $parent->name;
                    $feedback[$key]['files'][] = $file;
                }

                $task_folder = null;
                foreach ($this->folder->subfolders as $subfolder) {
                    if ($subfolder['data_content']['task_id'] == $task_id) {
                        $task_folder = $subfolder;
                    }
                }

                if (!$task_folder) {
                    throw new \RuntimeException($this->_('Der Aufgabenordner wurde nicht gefunden!'));
                }

                $folders_by_key = [];
                foreach ($task_folder->subfolders as $userfolder) {
                    $folders_by_key[\EPP\Helper::getFolderNameKey($userfolder->name)][] = $userfolder;
                }

                $assigned_students = 0;
                $assigned_files = 0;
                $failed_files = [];
                $unassigned = [];
                $ambiguous = [];

                foreach ($feedback as $key => $entry) {
                    if (empty($folders_by_key[$key])) {
                        $unassigned[] = $entry['name'];
                        continue;
                    }
                    if (count($folders_by_key[$key]) > 1) {
                        // several students share the same name, no safe assignment possible
                        $ambiguous[] = $entry['name'];
                        continue;
                    }

                    $userfolder = $folders_by_key[$key][0];
                    $feedback_folder = \EPP\Helper::findTypeFolder($userfolder, 'feedback');
                    if (!$feedback_folder) {
                        throw new \RuntimeException($this->_('Der Feedback-Ordner wurde nicht gefunden!'));
                    }

                    foreach ($entry['files'] as $feedback_file) {
                        foreach ($feedback_folder->getFiles() as $old_file) {
                            if ($old_file->name === $feedback_file->name) {
                                // delete old feedback to avoid duplicates
                                $feedback_folder->deleteFile($old_file->id);
                            }
                        }
                        if ($feedback_folder->addFile($feedback_file)) {
                            $assigned_files++;
                        } else {
                            $failed_files[] = $entry['name'] . '/' . $feedback_file->name;
                        }
                    }
                    $assigned_students++;
                }

                if ($assigned_files > 0) {
                    \PageLayout::postSuccess(sprintf(
                        $this->_('%d Feedback-Datei(en) wurden %d Abgabe(n) zugeordnet.'),
                        $assigned_files,
                        $assigned_students
                    ));
                } elseif (!$feedback && !$no_student_folder) {
                    \PageLayout::postError(
                        $this->_('Die zip-Datei enthält keine Feedback-Dateien. Erwartet wird die Struktur "Name/Feedback/Datei".')
                    );
                } else {
                    \PageLayout::postError($this->_('Es konnte keine Feedback-Datei zugeordnet werden.'));
                }
                if ($unassigned) {
                    \PageLayout::postWarning(
                        $this->_('Zu folgenden Ordnern in der zip-Datei wurde keine Abgabe gefunden, ihre Feedback-Dateien wurden nicht zugeordnet:'),
                        array_map('htmlReady', $unassigned)
                    );
                }
                if ($no_student_folder) {
                    \PageLayout::postWarning(
                        $this->_('Diese Dateien liegen in einem Ordner "Feedback" direkt im Hauptverzeichnis der zip-Datei. Es fehlt der Ordner mit dem Namen der Studierenden (Struktur "Name/Feedback/Datei"), daher wurden sie nicht zugeordnet:'),
                        array_map('htmlReady', array_values($no_student_folder))
                    );
                }
                if ($ambiguous) {
                    \PageLayout::postWarning(
                        $this->_('Folgende Namen sind in dieser Aufgabe mehrfach vorhanden, die Feedback-Dateien wurden nicht zugeordnet:'),
                        array_map('htmlReady', $ambiguous)
                    );
                }
                if ($failed_files) {
                    \PageLayout::postError(
                        $this->_('Folgende Feedback-Dateien konnten nicht gespeichert werden:'),
                        array_map('htmlReady', $failed_files)
                    );
                }
            } finally {
                $tmp_folder->delete();
            }
        }

        $this->render_nothing();
    }
}
