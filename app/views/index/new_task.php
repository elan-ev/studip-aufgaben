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

<?= $this->render_partial('index/_breadcrumb', array('path' => array('overview', 'new_task'))) ?>

<h2><?= _('Neue Aufgabe anlegen') ?></h2>

<form action="<?= $controller->url_for('index/add_task') ?>" method="post">
    <div class="task">
        <span class="label"><?= _('Titel') ?></span>
        <input type="text" name="title" required><br>
        <br>

        <span class="label"><?= _('Aufgabenbeschreibung') ?></span>
        <textarea name="content" required></textarea><br>

        <label>
            <input type="checkbox" name="allow_text" value="1">
            <?= _('Texteingabe erlauben') ?>
        </label>

        <label>
            <input type="checkbox" name="allow_files" value="1">
            <?= _('Dateiupload erlauben') ?>
        </label>
    </div>

    <div class="visibility">
        <div>
            <?= _('Sichtbar und bearbeitbar ab') ?>:<br>
            <input type="datetime" name="startdate" placeholder="<?= _('tt.mm.jjjj ss:mm') ?>" required>
        </div>

        <div>
            <?= _('Bearbeitbar bis') ?>:<br>
            <input type="datetime" name="enddate" placeholder="<?= _('tt.mm.jjjj ss:mm') ?>" required>
        </div>


    </div>
    <br style="clear: both">

    <? /*
    <label>
        <input type="checkbox" name="send_mail" value="1">
        <?= _('Mail an alle sobald sichtbar') ?>
    </label>
    */ ?>

    <div class="buttons">
        <div class="button-group">
            <?= \Studip\Button::createAccept(_('Anlegen')) ?>
            <?= \Studip\LinkButton::createCancel(_('Abbrechen'), $controller->url_for('index/index')) ?>
        </div>
    </div>
</form>

<script>
    jQuery(document).ready(function() {
        if (typeof Modernizr === 'undefined' || !Modernizr.inputtypes.datetime) {
            jQuery('input[type=datetime]').datetimepicker();
        }
    });
</script>
