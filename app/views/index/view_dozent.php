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

?>

<?= $this->render_partial('index/_breadcrumb', ['path' => [
    'overview', ['index/view_task/' . $task->getId(), $task['title']], get_fullname($task_user->user_id)]]) ?>

<?= $this->render_partial('index/_task_details') ?>

<? if ($task->startdate <= time()) : ?>
    <? if ($task_user->hint) : ?>
        <section class="contentbox">
            <header>
                <h1><?= _('Hinweis für diese(n) Teilnehmer(in)') ?><?= tooltipIcon(_('Sie können den Hinweistext nicht mehr verändern, da die Aufgabe bereits gestartet ist!'), true) ?></h1>
            </header>
            <section>
                <?= formatReady($task_user->hint) ?>
            </section>
        </section>
    <? endif ?>
<? else : ?>
    <?= $this->render_partial('index/_edit_text', [
        'form_route'   => 'index/update_dozent/' . $task_user->getId(),
        'cancel_route' => 'index/view_dozent/' . $task_user->getId(),
        'name'         => _('Hinweis für diese(n) Teilnehmer(in)'),
        'field'        => 'hint',
        'text'         => $task_user->hint
    ]) ?>
<? endif ?>

<?= $this->render_partial('index/_permissions') ?>

<? if ($task->startdate <= time()) : ?>

    <? if ($task['allow_text']) : ?>
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
    <? endif ?>


    <? if ($task['allow_files']) : ?>
        <? $files = $task_user->files->findBy('type', 'answer') ?>
        <? if (sizeof($files)) : ?>
            <?= $this->render_partial('index/_file_list', compact('files')) ?>
        <? endif ?>
    <? endif ?>

    <?= $this->render_partial('index/_edit_text', [
        'form_route'   => 'index/update_dozent/' . $task_user->getId(),
        'cancel_route' => 'index/view_dozent/' . $task_user->getId(),
        'name'         => _('Feedback'),
        'field'        => 'feedback',
        'text'         => $task_user->feedback
    ]) ?>

    <?= $this->render_partial('index/_file_list', [
        'files' => $task_user->files->findBy('type', 'feedback'),
        'edit'  => true
    ]) ?>

<? endif ?>

<script type="text/javascript">
    jQuery(document).ready(function () {
        <? foreach ($task_user->perms as $perm) : ?>
        STUDIP.Aufgaben.Permissions.addTemplate({
            user: '<?= get_username($perm->user_id) ?>',
            fullname: '<?= get_fullname($perm->user_id) ?>',
            perm: '<?= $perm->role ?>',
            permission: '<?= $permissions[$perm->role] ?>'
        });
        <? endforeach ?>
    });
</script>
