<?php
/*
 * Helper.php - description
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Affero General Public License
 * version 3 as published by the Free Software Foundation.
 *
 * @author      Till Glöggler <tgloeggl@uos.de>
 * @license     https://www.gnu.org/licenses/agpl-3.0.html AGPL version 3
 */

namespace EPP;

class Helper
{
    /**
     * default timeformat for all dates
     * @var string
     */
    public const timeformat = '%d.%m.%Y, %H:%M:%S';

    public static function getForeignTasksForUser($user_id)
    {
        $task_users = array();

        $perms = Permissions::findByUser_id($user_id);

        foreach ($perms as $perm) {
            $task_users[] = $perm->task_user;
        }

        return $task_users;
    }

    public static function getTypedFolder($folder, $task, $task_user, $type)
    {
        $task_folder = null;

        foreach ($folder->subfolders as $subfolder) {
            if ($subfolder['data_content']['task_id'] == $task->id) {
                $task_folder = $subfolder;
            }
        }

        if (!$task_folder) {
            $task_folder = \Folder::create([
                'parent_id'    => $folder->getId(),
                'range_id'     => \Context::getId(),
                'range_type'   => \Context::getType(),
                'description'  => 'Aufgabenordner',
                'name'         => 'Aufgabenordner: ' . $task->title,
                'data_content' => ['task_id' => $task->id],
                'folder_type'  => 'TaskFolder',
                'user_id'      => \Context::getId()
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
                'range_id'     => \Context::getId(),
                'range_type'   => \Context::getType(),
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
            if ($subfolder['data_content']['task_type'] == $type) {
                $type_folder = $subfolder;
            }
        }

        if (!$type_folder) {
            $type_folder = \Folder::create([
                'parent_id'    => $user_folder->getId(),
                'range_id'     => \Context::getId(),
                'range_type'   => \Context::getType(),
                'description'  => '',
                'name'         => ucfirst($type),
                'data_content' => [
                    'task_type' => $type,
                    'task_user' => $task_user->user_id
                ],
                'folder_type'  => 'TaskFolder',
                'user_id'      => $task_user->user_id
            ]);

            $user_folder->subfolders[] = $type_folder;
        }

        return $type_folder->getTypedFolder();
    }

    public static function getSidebarInfos($task, $controller)
    {
        $infos = new \SidebarWidget();
        $infos->setTitle(_('Aufgabeinformationen'));
        $infos->addElement(
            new \WidgetElement(
                sprintf('%s' . $controller->_('Bearbeitungszeitraum: <br>%s - %s Uhr'),
                    \Icon::create('date'),
                    strftime(self::timeformat, $task['startdate']),
                    strftime(self::timeformat, $task['enddate']))
            )
        );

        if ($task['enddate'] >= time()) {
            $infos->addElement(
                new \WidgetElement(
                    '<hr>' . \Icon::create('date')
                    . $controller->_('Aufgabe kann momentan bearbeitet werden.')
                )
            );
        } else {
            $infos->addElement(
                new \WidgetElement(
                    '<hr>' . \Icon::create('date', 'attention')
                    . $controller->_('Aufgabe kann momentan nicht bearbeitet werden!')
                )
            );
        }

        if ($task->allow_text && $task->allow_files) {
            $infos->addElement(
                new \WidgetElement(sprintf('<hr>%s %s',
                    \Icon::create('info-circle'),
                    $controller->_('Texteingabe und Dateiupload erlaubt'))
                )
            );
        } else if ($task->allow_text) {
            $infos->addElement(
                new \WidgetElement(sprintf('<hr>%s %s',
                    \Icon::create('file-text+add'),
                    $controller->_('Texteingabe erlaubt'))
                )
            );
        } else if ($task->allow_files) {
            $infos->addElement(
                new \WidgetElement(sprintf('<hr>%s %s',
                    \Icon::create('upload'),
                    $controller->_('Dateiupload erlaubt'))
                )
            );
        }

        return $infos;
    }
}
