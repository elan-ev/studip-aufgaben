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

if ($task_user->ready) {
    $infobox_content[] = array(
        'kategorie' => _('Informationen'),
        'eintrag'   => array(
            array(
                'icon' => 'icons/16/green/accept.png',
                'text' => 'Aufgabe ist bereits als fertig markiert!'
            )
        )
    );    
 } else {
    $infobox_content[] = array(
        'kategorie' => _('Aktionen'),
        'eintrag'   => array(
            array(
                'icon' => 'icons/16/blue/link-intern.png',
                'text' => '<a href="' . $controller->url_for('index/set_ready/' . $task->getId()) . '">Aufgabe als fertig markieren</a>'
            )
        )
    );
 }

$infobox = array('picture' => 'infobox/schedules.jpg', 'content' => $infobox_content);
?>

<?= $this->render_partial('index/_breadcrumb', array('path' => array('overview', $task['title']))) ?>

<? if ($task_user->user_id != $GLOBALS['user']->id): ?>
    <br><br>
    <span>
        <?= sprintf(_('Diese Aufgabe gehört: %s'),
            '<a href="'. URLHelper::getLink('dispatch.php/profile?username='
                . get_username($task_user->user_id)) .'">'
                . get_fullname($task_user->user_id) . '</a>'
        ) ?>
    </span>
    <br>
<? endif ?>

<?= $this->render_partial('index/_task_details') ?>

<? if ($task_user['hint']) : ?>
<br>
<div class="mark">
    <b><?= _('Hinweis DozentIn') ?>:</b><br>
    <br>
    <?= formatReady($task_user->hint) ?>
</div>
<? endif ?>

<? if ($task->allow_text) : ?>
    <br>
    <? if ($task->enddate < time()) : ?>
        <b><?= _('Antworttext') ?></b><br>
        <? if ($task_user->answer) : ?>
        <br>
        <?= formatReady($task_user->answer) ?>
        <? else : ?>
        <span class="empty_text"><?= _('Es wurde keine Antwort eingegeben') ?></span>
        <? endif ?>
        <br><br>
    <? else : ?>
        <?= $this->render_partial('index/_edit_text', array(
            'form_route'   => 'index/update_student/' . $task->getId() .'/'. $task_user->getId(),
            'cancel_route' => 'index/view_student/' . $task->getId(),
            'name'         =>  _('Antworttext'),
            'field'        => 'answer',
            'text'         => $task_user->answer
        )) ?>
    <? endif ?>
<? endif ?>

<? if ($task['allow_files']) : ?>
<?= $this->render_partial('index/_file_list', array(
    'files' => $task_user->files->findBy('type', 'answer'),
    'edit'  => ($task->enddate >= time())
)) ?>
<? endif ?>


<br>
<div class="mark">
    <b><?= _('Feedback DozentIn') ?>:</b><br>
    <? if ($task_user->feedback) : ?>
        <?= formatReady($task_user->feedback) ?>
    <? else : ?>
        <span class="empty_text"><?= _('Noch kein Feedback vorhanden') ?></span>
    <? endif ?>
    <br>

    <br>
    <? $files = $task_user->files->findBy('type', 'feedback') ?>
    <? if (sizeof($files)) : ?>
    <?= $this->render_partial('index/_file_list', compact('files')) ?>
    <? endif ?>
</div>