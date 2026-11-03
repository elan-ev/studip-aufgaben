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
     *
     * @var string
     */
    const timeformat = '%d.%m.%Y, %H:%M:%S';

    public static function getForeignTasksForUser($user_id)
    {
        $task_users = [];

        $perms = Permissions::findByUser_id($user_id);

        foreach ($perms as $perm) {
            $task_users[] = $perm->task_user;
        }

        return $task_users;
    }

    /**
     * Return the plugin's file storage in the specified course.
     * Pass false for $create to look up storage without writing.
     */
    public static function getCourseFolder($course_id, $create = true)
    {
        $root_folder = $create
            ? \Folder::findTopFolder($course_id)
            : \Folder::findOneBySQL(
                "range_id = ? AND folder_type = 'RootFolder' AND parent_id = ''",
                [$course_id]
            );
        if (!$root_folder) {
            return null;
        }
        $root_folder = $root_folder->getTypedFolder();
        foreach ($root_folder->subfolders as $folder) {
            if (!empty($folder['data_content']['aufgabenplugin'])) {
                return $folder->getTypedFolder();
            }
        }

        if (!$create) {
            return null;
        }

        $folder = \Folder::create([
            'parent_id'    => $root_folder->getId(),
            'range_id'     => $course_id,
            'range_type'   => $root_folder->range_type,
            'description'  => 'Dateiablage des Aufgabenplugins',
            'name'         => 'Aufgaben-Plugin',
            'data_content' => ['aufgabenplugin' => '1'],
            'folder_type'  => \TaskFolder::class,
            'user_id'      => $course_id
        ]);
        $root_folder->subfolders[] = $folder;

        return $folder->getTypedFolder();
    }

    /**
     * Find an answer or feedback folder by its metadata, regardless of order.
     */
    public static function findTypeFolder($user_folder, $type)
    {
        foreach ($user_folder->subfolders as $subfolder) {
            if (($subfolder['data_content']['task_type'] ?? null) === $type) {
                return $subfolder->getTypedFolder();
            }
        }

        return null;
    }

    /**
     * Look up a task file folder without creating or updating any records.
     */
    public static function getTypedFolder($folder, $task, $task_user, $type)
    {
        if (!$task_user) {
            return null;
        }
        if ($folder->range_id != $task->seminar_id) {
            $folder = self::getCourseFolder($task->seminar_id, false);
        }
        if (!$folder) {
            return null;
        }

        foreach ($folder->subfolders as $task_folder) {
            if (($task_folder['data_content']['task_id'] ?? null) != $task->id) {
                continue;
            }
            foreach ($task_folder->subfolders as $user_folder) {
                if (($user_folder['data_content']['task_user'] ?? null) == $task_user->user_id) {
                    return self::findTypeFolder($user_folder, $type);
                }
            }
        }

        return null;
    }

    /**
     * Create missing folders and repair legacy task metadata before rendering.
     */
    public static function ensureTypedFolder($folder, $task, $task_user, $type)
    {
        if ($folder->range_id != $task->seminar_id) {
            $folder = self::getCourseFolder($task->seminar_id);
        }

        $task_folder = null;

        foreach ($folder->subfolders as $subfolder) {
            if ($subfolder['data_content']['task_id'] == $task->id) {
                $task_folder = $subfolder;
            }
        }

        if (!$task_folder) {
            $task_folder = \Folder::create([
                'parent_id'    => $folder->getId(),
                'range_id'     => $task->seminar_id,
                'range_type'   => $folder->range_type,
                'description'  => 'Aufgabenordner',
                'name'         => 'Aufgabenordner: ' . $task->title,
                'data_content' => ['task_id' => $task->id],
                'folder_type'  => \TaskFolder::class,
                'user_id'      => $task->seminar_id
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
                'range_id'     => $task->seminar_id,
                'range_type'   => $folder->range_type,
                'description'  => 'Nutzerordner',
                'name'         => get_fullname($task_user->user_id),
                'data_content' => [
                    'task_user' => $task_user->user_id,
                    'task_id'   => $task->id
                ],
                'folder_type'  => \TaskFolder::class,
                'user_id'      => $task_user->user_id
            ]);

            $task_folder->subfolders[] = $user_folder;
        } elseif (($user_folder->data_content['task_id'] ?? null) != $task->id) {
            $user_folder->data_content['task_id'] = $task->id;
            $user_folder->store();
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
                'range_id'     => $task->seminar_id,
                'range_type'   => $folder->range_type,
                'description'  => '',
                'name'         => ucfirst($type),
                'data_content' => [
                    'task_type' => $type,
                    'task_user' => $task_user->user_id,
                    'task_id'   => $task->id
                ],
                'folder_type'  => \TaskFolder::class,
                'user_id'      => $task_user->user_id
            ]);

            $user_folder->subfolders[] = $type_folder;
        } elseif (($type_folder->data_content['task_id'] ?? null) != $task->id) {
            $type_folder->data_content['task_id'] = $task->id;
            $type_folder->store();
        }

        return $type_folder->getTypedFolder();
    }

    public static function getSidebarInfos($task, $controller)
    {
        $infos = new \SidebarWidget();
        $infos->setTitle($controller->_('Aufgabeinformationen'));
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

    /**
     * Create a separate temporary folder for one upload operation.
     * The caller must delete it when the upload finishes or fails.
     *
     * @param \Folder $folder
     * @param string $course_id
     *
     * @return \Folder
     */
    public static function createTempFolder($folder, $course_id)
    {
        global $user;

        $tmp_folder = \Folder::create([
            'parent_id'    => $folder->getId(),
            'range_id'     => $course_id,
            'range_type'   => $folder->range_type,
            'description'  => 'Temporärer Ordner für Feedback-Uploads',
            'name'         => 'Aufgaben-Plugin-Tmp-' . $user->id . '-' . bin2hex(random_bytes(8)),
            'data_content' => '',
            'folder_type'  => \HiddenFolder::class,
            'user_id'      => $course_id
        ]);

        return $tmp_folder;
    }
}
