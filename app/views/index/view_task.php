<?php
/**
 * new_task.php - Short description for file
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

$infobox_content[] = array(
    'kategorie' => _('Aktionen'),
    'eintrag'   => array(
    )
);

$infobox = array('picture' => 'infobox/schedules.jpg', 'content' => $infobox_content);
?>

<?= $this->render_partial('index/_breadcrumb', array('path' => array('overview', $task['title']))) ?>

<?= $this->render_partial('index/_task_details') ?>

<br>
<b><?= strftime($timeformat, $task['startdate']) ?> - <?= strftime($timeformat, $task['enddate']) ?></b>

<div class="buttons">
    <div class="button-group">
        <?= \Studip\LinkButton::createEdit(_('Bearbeiten'), $controller->url_for('index/edit_task/' . $task['id'])) ?>
        <?= \Studip\LinkButton::createDelete(_('Löschen'), 'javascript:STUDIP.epp.createQuestion("'. 
            _('Sind Sie sicher, dass Sie die komplette Aufgabe löschen möchten?') .'",
            "'. $controller->url_for('index/delete_task/' . $task['id']) .'");') ?>
    </div>
</div>

<?= $this->render_partial('index/_status.php', compact('participants')) ?>
