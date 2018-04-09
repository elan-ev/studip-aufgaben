<?php
# Lifter007: TODO
# Lifter003: TEST
# Lifter010: TODO
// +---------------------------------------------------------------------------+
// This file is part of Stud.IP
//
// Copyright (C) 2005 André Noack <noack@data-quest>,
// Suchi & Berg GmbH <info@data-quest.de>
// +---------------------------------------------------------------------------+
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or any later version.
// +---------------------------------------------------------------------------+
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// +---------------------------------------------------------------------------+

/**
 * StudipDocument.class.php
 * give access to the table dokumente
 *
 *
 * @author   André Noack <noack@data-quest>, Suchi & Berg GmbH <info@data-quest.de>
 * @access   public
 * 
 * @property string dokument_id database column
 * @property string id alias column for dokument_id
 * @property string range_id database column
 * @property string user_id database column
 * @property string seminar_id database column
 * @property string name database column
 * @property string description database column
 * @property string filename database column
 * @property string mkdate database column
 * @property string chdate database column
 * @property string filesize database column
 * @property string autor_host database column
 * @property string downloads database column
 * @property string url database column
 * @property string protected database column
 * @property string priority database column
 * @property string author_name database column
 */
class EPP_StudipDocument extends EPP_SimpleORMap {

    /**
     * returns array of StudipDocument-objects of given course id
     * @param string cid: course_id in the db (Seminar_id) with which all
     * StudipDocuments should be filtered
     * @return array of all StudipDocument from the course with the given course_id
     */
    static function findByCourseId($cid)
    {
        return self::findBySeminar_id($cid);
    }

    /**
     * returns array of document-objects of given folder with id folder_id
     * @param string folder_id: id of a folder whose documents we want to catch
     * @return array of StudipDocument objects of the given folder_id's folder
     * or empty if that folder contains no documents.
     */
    static function findByFolderId($folder_id)
    {
         return self::findByFolder_id($folder_id);
    }

    /**
     * constructor
     * @param string id: primary key of table dokumente
     * @return null
     */
    function __construct($id = null)
    {
        $this->db_table = 'dokumente';
        $this->default_values['description'] = '';
        parent::__construct($id);
    }

    /**
     * Delete entry from database.
     * The object is cleared and turned to new state.
     * Posts the Notifications "Document(Will|Did)Delete" if successful.
     * The subject of the notification is the former document.
     *
     * @return boolean  always true
     */
    function delete()
    {
        $to_delete = clone $this;
        NotificationCenter::postNotification('DocumentWillDelete', $to_delete);
        if ($ret = parent::delete()) {
            NotificationCenter::postNotification('DocumentDidDelete', $to_delete);
        }
        return $ret;
    }

    /**
     * checks access to the document for user with given user_id
     * the number of deleted rows.
     * @param string user_id: id of the user
     * @return boolean: true if user has access to the document
     */
    public function checkAccess ($user_id) {
        if (!$this->getValue('dokument_id')) return false;
        $object_type = get_object_type($this->getValue('seminar_id'));
        $access = false;
        if (in_array($object_type, array('inst', 'fak'))) {
            //download from institute and user is always allowed
            if (get_config('ENABLE_FREE_ACCESS') || $GLOBALS['perm']->have_perm('user', $user_id)) {
                $access = true;
            } else { //check external download module (types 6 and 10)
                $result = DBManager::get()->query("SELECT * FROM extern_config WHERE range_id = '"
                        . $this->getValue('seminar_id') . "' AND config_type IN (6,10)")->fetchColumn();
                    $access = (boolean) $result;
            }
        } else if($object_type == 'sem') {
            //download from course is allowed if course is free for all or user is participant
            if (Seminar::GetInstance($this->getValue('seminar_id'))->isPublic()) {
                $access = true;
            } else {
                $access = $GLOBALS['perm']->have_studip_perm('user', $this->getValue('seminar_id'), $user_id);
            }
        } else if ($object_type == 'user') {
            // message attachement
            $st = DBManager::get()->prepare("SELECT message_user.user_id FROM dokumente
                INNER JOIN message_user ON message_id=range_id
                WHERE dokument_id = ?");
            $st->execute(array($this->getValue('dokument_id')));
            $message_user = $st->fetchAll(PDO::FETCH_COLUMN);
            if (count($message_user)) {
                $access = in_array($user_id, $message_user);
            } else { //Blubberdatei aus persönlichem Blubb
                $access = $GLOBALS['perm']->have_perm('user', $user_id);
            }
        }
        //if allowed basically, check for closed folders and protected documents
        if ($access && in_array($object_type, array('inst', 'fak', 'sem'))) {
            $folder_tree = TreeAbstract::GetInstance('StudipDocumentTree', array('range_id' => $this->getValue('seminar_id')));
            if (!$folder_tree->isDownloadFolder($this->getValue('range_id'), $user_id)) {
                $access = false;
            }
        }
        return $access;
    }


    /**
     * Create a new document using the given file and metadata.
     * This method makes sure that there are no inconsistencies between a real
     * file and its database entry. Only if the file were copied/moved to the
     * documents folder, the database entry is written. If this fails too, the
     * file will be unlinked again.
     * The first parameter can either be an uploaded file or the path to an
     * already existing one. This file will either be moved using
     * move_uploaded_file or it will be copied.
     * The destination is determined this way: If the second parameter $data
     * already contains a "dokument_id", this will be used as the file's
     * destination. This is usually the case when refreshing a file.
     * If there is no such parameter, a new "dokument_id" is generated as usual
     * and is used as the file's destination.
     *
     * Before a document (and its file) is created, the notification
     * "DocumentWillCreate" will be posted.
     * If the document was created successfuly, the notification
     * "DocumentDidCreate" will be posted.
     * It the document was updated rather than created (see above), the
     * notifications will be "DocumentWillUpdate" and "DocumentDidUpdate".
     * The subject of the notification will always be that document.
     *
     * @param  $file  string  full path to a file (either uploaded or already existing)
     * @param  $data  array   an array containing the metadata of the document;
     *                        just use the same way as StudipDocument::setData
     * @return StudipDocument|null  if successful the created document, null otherwise
     */
    static function createWithFile($file, $data)
    {
        $doc = new StudipDocument(@$data['dokument_id']);
        $doc->setData($data);

        // create new ID (and thus path)
        if (!$doc->getId()) {
            $doc->setId($doc->getNewId());
        }

        $notifications = !isset($data['dokument_id'])
            ? array('DocumentWillCreate', 'DocumentDidCreate')
            : array('DocumentWillUpdate', 'DocumentDidUpdate');

        // send DocumentWill(Create|Update) notification
        NotificationCenter::postNotification($notifications[0], $doc);

        if (!$doc->attachFile($file) || !$doc->safeStore()) {
            return null;
        }

        // send DocumentDid(Create|Update) notification
        NotificationCenter::postNotification($notifications[1], $doc);

        return $doc;
    }

    // attach a file to a document by moving or copying
    private function attachFile($file)
    {
        $newpath = get_upload_file_path($this->getId());

        // try moving uploaded file
        if (is_uploaded_file($file)) {
            if (!@move_uploaded_file($file, $newpath)) {
                return false;
            }
        }
        // copy regular file
        else if (!@copy($file, $newpath)) {
            return false;
        }

        return true;
    }

    // store a document, making sure that the file is unlinked on failure
    private function safeStore()
    {
        try {
            $result = $this->store();
        } catch (Exception $e) {
            $result = false;
        }

        if ($result === false) {
            @unlink(get_upload_file_path($this->getId()));
        }
        return $result !== false;
    }
}
