<?php
/**
 * AddTables - Migration to initialize DB-structure
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

require_once dirname(__FILE__) . '/../app/models/TaskUserFiles.php';
require_once dirname(__FILE__) . '/../app/models/TaskUsers.php';
require_once dirname(__FILE__) . '/../app/models/Tasks.php';

class MigrateToMoadb extends Migration
{
    function up()
    {
        $db = DBManager::get();

        // check, if table dokumente exists, to preserve data. Otherwise defaults are used
        $dok_table = false;
        if (sizeof($db->query("SHOW TABLES LIKE 'dokumente'")->fetchAll())) {
            $dok_table = true;
            $dok_stmt = $db->prepare("SELECT * FROM dokumente WHERE dokument_id = ?");
        };

        $result = $db->query("SELECT * FROM ep_task_user_files");

        while($data = $result->fetch(PDO::FETCH_ASSOC)) {

            $task_user_files = EPP\TaskUserFiles::find($data['id']);
            $task_user       = $task_user_files->task_user;
            $task            = $task_user_files->task_user->task;
            $seminar_id      = $task_user->task->seminar_id;

            $context_type = '';
            switch (get_object_type($seminar_id)) {
                case 'sem': $context_type = 'course';break;
                case 'inst':
                case 'fak' : $context_type = 'institute';break;
            }

            // set up hidden folder for files to store (if not already present)
            $aufgaben_folder = null;

            $r_folder = \Folder::findTopFolder($seminar_id);

            if ($r_folder) {
                $root_folder = $r_folder->getTypedFolder();

                foreach ($root_folder->subfolders as $folder) {
                    if ($folder['data_content']['aufgabenplugin']) {
                        $aufgaben_folder = $folder;
                    }
                }

                if (!$aufgaben_folder) {
                    $aufgaben_folder = \Folder::create([
                        'parent_id'    => $root_folder->getId(),
                        'range_id'     => $seminar_id,
                        'range_type'   => $context_type,
                        'description'  => 'Dateiablage des Aufgabenplugins',
                        'name'         => 'Aufgaben-Plugin',
                        'data_content' => ['aufgabenplugin' => '1'],
                        'folder_type'  => 'TaskFolder',
                        'user_id'      => $seminar_id
                    ]);
                }

                $folder = $aufgaben_folder->getTypedFolder();

                // Aufgabenordner
                $task_folder = null;

                foreach ($folder->subfolders as $subfolder) {
                    if ($subfolder['data_content']['task_id'] == $task->id) {
                        $task_folder = $subfolder;
                    }
                }

                if (!$task_folder) {
                    $task_folder = \Folder::create([
                        'parent_id'    => $folder->getId(),
                        'range_id'     => $seminar_id,
                        'range_type'   => $context_type,
                        'description'  => 'Aufgabenordner',
                        'name'         => 'Aufgabenordner: ' . $task->title,
                        'data_content' => ['task_id' => $task->id],
                        'folder_type'  => 'TaskFolder',
                        'user_id'      => $seminar_id
                    ]);

                    $folder->subfolders[] = $task_folder;
                }

                // Nutzerordner für eine Aufgabe
                $user_folder = null;
                foreach ($task_folder->subfolders as $subfolder) {
                    if ($subfolder['data_content']['task_user'] == $task_user->user_id) {
                        $user_folder = $subfolder;
                    }
                }

                if (!$user_folder) {
                    $user_folder = \Folder::create([
                        'parent_id'    => $task_folder->getId(),
                        'range_id'     => $seminar_id,
                        'range_type'   => $context_type,
                        'description'  => 'Nutzerordner',
                        'name'         => get_fullname($task_user->user_id),
                        'data_content' => ['task_user' => $task_user->user_id],
                        'folder_type'  => 'TaskFolder',
                        'user_id'      => $task_user->user_id
                    ]);

                    $task_folder->subfolders[] = $user_folder;
                }

                // Ordner für die Art der Datei
                $type_folder = null;
                foreach ($user_folder->subfolders as $subfolder) {
                    if ($subfolder['data_content']['task_type'] == $task_user_files->type) {
                        $type_folder = $subfolder;
                    }
                }

                if (!$type_folder) {
                    $type_folder = \Folder::create([
                        'parent_id'    => $user_folder->getId(),
                        'range_id'     => $seminar_id,
                        'range_type'   => $context_type,
                        'description'  => '',
                        'name'         => ucfirst($task_user_files->type),
                        'data_content' => [
                            'task_type' => $task_user_files->type,
                            'task_user' => $task_user->user_id
                        ],
                        'folder_type'  => 'TaskFolder',
                        'user_id'      => $task_user->user_id
                    ]);

                    $user_folder->subfolders[] = $type_folder;
                }

                $type_folder = $type_folder->getTypedFolder();

                // set file-data, depending on existence of dokumente-table
                $dok_metadata = [
                    'mime_type' => 'application/octet-stream',
                    'name'      => 'dokument_' . $task_user_files->getId(),
                    'size'      => 0
                ];

                if ($dok_table) {
                    $dok_stmt->execute([$task_user_files->dokument_id]);
                    $data = $dok_stmt->fetch(PDO::FETCH_ASSOC);

                    if ($data) {
                        $dok_metadata = [
                            'mime_type' => get_mime_type($data['filename']),
                            'name'      => $data['filename'],
                            'size'      => $data['filesize']
                        ];
                    }
                }

                // create file in type_folder
                $file = new \File();
                $file->setData($data = [
                    'id'        => $task_user_files->dokument_id,
                    'user_id'   => $task_user->user_id,
                    'mime_type' => $dok_metadata['mime_type'],
                    'name'      => $dok_metadata['name'],
                    'size'      => $dok_metadata['size'],
                    'storage'   => 'disk',
                ]);
                $file->store();

                $file_ref = new \FileRef();
                $file_ref->setData($data = [
                    'file_id'   => $task_user_files->dokument_id,
                    'folder_id' => $type_folder->getId(),
                    'user_id'   => $task_user->user_id,
                    'name'      => $dok_metadata['name']
                ]);
                $file_ref->store();
            }
        }

        unset($task_user_files);
        unset($task_user);

        SimpleORMap::expireTableScheme();
    }

    function down()
    {


        SimpleORMap::expireTableScheme();
    }
}
