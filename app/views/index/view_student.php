<?php
/**
 * new_task.php - Short description for file
 * Long description for file (if any)...
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 3 of
 * the License, or (at your option) any later version.
 * @author      Till Glöggler <tgloeggl@uos.de>
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GPL version 3
 * @category    Stud.IP
 */

if ($task_user->ready) {
    $infobox_content[] = [
        'kategorie' => _('Informationen'),
        'eintrag'   => [
            [
                'icon' => 'icons/16/green/accept.png',
                'text' => 'Aufgabe ist bereits als fertig markiert!'
            ]
        ]
    ];
} else {
    $infobox_content[] = [
        'kategorie' => _('Aktionen'),
        'eintrag'   => [
            [
                'icon' => 'icons/16/blue/link-intern.png',
                'text' => '<a href="' . $controller->url_for('index/set_ready/' . $task->getId()) . '">Aufgabe als fertig markieren</a>'
            ]
        ]
    ];
}

$infobox = ['picture' => 'infobox/schedules.jpg', 'content' => $infobox_content];
?>

<?= $this->render_partial('index/_breadcrumb', ['path' => ['overview', $task['title']]]) ?>

<? if ($task_user->user_id != $GLOBALS['user']->id): ?>
    <br><br>
    <span>
        <?= sprintf(_('Diese Aufgabe gehört: %s'),
            '<a href="' . URLHelper::getLink('dispatch.php/profile?username='
                . get_username($task_user->user_id)) . '">'
            . get_fullname($task_user->user_id) . '</a>'
        ) ?>
    </span>
    <br>
<? endif ?>

<?= $this->render_partial('index/_task_details') ?>

<? if ($task_user['hint']) : ?>
    <section class="contentbox"></section>
    <header>
        <h1><?= _('Hinweis Lehrender') ?></h1>
    </header>
    <section>
        <?= formatReady($task_user->hint) ?>
    </section>
<? endif ?>

<? if ($task->allow_text) : ?>
    <? if ($task->enddate < time()) : ?>
        <section class="contentbox">
            <header>
                <h1><?= _('Antworttext') ?></h1>
            </header>
            <section>
                <? if ($task_user->answer) : ?>
                    <?= formatReady($task_user->answer) ?>
                <? else : ?>
                    <p style="text-align: center"><?= _('Es wurde keine Antwort eingegeben') ?></p>
                <? endif ?>
            </section>
        </section>
    <? else : ?>
        <?= $this->render_partial('index/_edit_text', [
            'form_route'   => 'index/update_student/' . $task->getId() . '/' . $task_user->getId(),
            'cancel_route' => 'index/view_student/' . $task->getId(),
            'name'         => _('Antworttext'),
            'field'        => 'answer',
            'text'         => $task_user->answer,
            'edit'         => ($task->enddate >= time()),
            'type_folder'  => \EPP\Helper::getTypedFolder($folder, $task, $task_user, 'answer')
        ]) ?>
    <? endif ?>
<? endif ?>

<? if ($task['allow_files']) : ?>
    <?= $this->render_partial('index/_file_list', [
        'type'  => 'answer',
    ]) ?>
<? endif ?>



<section class="contentbox">
    <header>
        <h1><?= _('Feedback DozentIn') ?></h1>
    </header>
    <section>
        <? if ($task_user->feedback) : ?>
            <?= formatReady($task_user->feedback) ?>
        <? else : ?>
            <p style="text-align: center"><?= _('Noch kein Feedback vorhanden') ?></p>
        <? endif ?>
    </section>
</section>


<? if ($task['allow_files']) : ?>
    <?= $this->render_partial('index/_file_list', [
        'type'  => 'feedback',
    ]) ?>
<? endif ?>
