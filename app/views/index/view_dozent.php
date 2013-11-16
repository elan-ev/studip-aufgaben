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

<?= $this->render_partial('index/_breadcrumb', array('path' => array(
    'overview', array('index/view_task/' . $task->getId(), $task['title']), get_fullname($task_user->user_id)))) ?>

<?= $this->render_partial('index/_task_details') ?>

<? if ($task->startdate <= time()) : ?>
    <? if ($task_user->hint) : ?>
    <br>
    <div class="mark">
        <b><?= _('Hinweis für diese(n) Teilnehmer(in)') ?>:</b> 
        <?= tooltipIcon(_('Sie können den Hinweistext nicht mehr verändern, da die Aufgabe bereits gestartet ist!'), true) ?><br>
        <br>
        <?= formatReady($task_user->hint) ?>
    </div>
    <? endif ?>
<!-- no edit allowed after the task has started! -->
<? else : ?>
    <br>
    <?= $this->render_partial('index/_edit_text', array(
        'form_route'   => 'index/update_dozent/' . $task_user->getId(),
        'cancel_route' => 'index/view_dozent/' . $task_user->getId(),
        'name'         =>  _('Hinweis für diese(n) Teilnehmer(in)'),
        'field'        => 'hint',
        'text'         => $task_user->hint
    )) ?>
<? endif ?>


<? if ($task->startdate <= time()) : ?>

    <? if ($task['allow_text']) : ?>
    <br>
    <div class="mark">
        <b><?= _('Antworttext') ?>:</b><br>
        <? if (!$task_user->answer) : ?>
            <br>
            <span class="empty_text"><?= _('Es wurde noch keine Antwort eingegeben.') ?></span>
        <? else : ?>
            <?= formatReady($task_user->answer) ?>
        <? endif ?>
        <br>
    </div>
    <? endif ?>

    <? if ($task['allow_files']) : ?>
        <br>
        <? $files = $task_user->files->findBy('type', 'answer') ?>
        <? if (sizeof($files)) : ?>
        <?= $this->render_partial('index/_file_list', compact('files')) ?>
        <? endif ?>
    <? endif ?>

    <br>
    <?= $this->render_partial('index/_edit_text', array(
        'form_route'   => 'index/update_dozent/' . $task_user->getId(),
        'cancel_route' => 'index/view_dozent/' . $task_user->getId(),
        'name'         =>  _('Feedback'),
        'field'        => 'feedback',
        'text'         => $task_user->feedback
    )) ?>
    <br>

    <?= $this->render_partial('index/_file_list', array(
        'files' => $task_user->files->findBy('type', 'feedback'),
        'edit'  => true
    )) ?>

<? endif ?>