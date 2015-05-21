<table class="default zebra">
    <thead>
        <tr>
            <th><?= _('TeilnehmerIn') ?></th>
            <th colspan="2" style="width: 10%; text-align: center"><?= _('in Arbeit') ?></th>
            <th style="width: 2%">Fertig?</th>
            <th style="width: 10%"><?= _('letzte Aktivität') ?></th>
            <th colspan="2" style="width: 10%; text-align: center"><?= _('Feedback') ?></th>
            <th><?= _('Hinweis') ?></th>
            <th><?= _('Aktionen') ?></th>
        </tr>
    </thead>
    <tbody>
        <? foreach ($participants as $user) : ?>
        <? if ($user->status != 'dozent') : ?>
        <? $task_user = $task->task_users->findOneBy('user_id', $user->user_id) ?>
        <? if (!$task_user) :  // create missing entries on the fly
            $task_user = EPP\TaskUsers::create(array(
                'user_id' => $user->user_id,
                'chdate' => 0,
                'mkdate' => 0,
                'ep_tasks_id' => $task->getId()
            ));
        endif ?>
        <tr>
            <td>
                <a href="<?= $controller->url_for("index/view_dozent/" . $task_user->id) ?>">
                    <?= get_fullname($user->user_id) ?>
                </a>
            </td>
            
            <td style="text-align: right">
                <?= (!$task_user || $task_user->answer === null) ? '0' : strlen($task_user->answer) ?>
                <?= Assets::img('icons/16/black/file-text.png', array(
                    'title' => _('Antworttext')
                )) ?>
            </td>
            <td>
                <?= $task_user ? sizeof($task_user->files->findBy('type', 'answer')) : 0 ?>
                <?= Assets::img('icons/16/black/file-generic.png', array(
                    'title' => _('Hochgeladene Dateien')
                )) ?>
            </td>
            
            <td>
                <?= Assets::img('icons/16/'. ($task_user->ready ? 'green/accept.png' : 'red/decline.png')) ?>
            </td>

            <td>
                <?= ($task_user && $task_user->chdate) ? strftime($timeformat, $task_user->chdate) : '-' ?>
            </td>

            <td style="text-align: right">
                <?= (!$task_user || $task_user->feedback === null) ? '0' : strlen($task_user->feedback) ?>
                <?= Assets::img('icons/16/black/file-text.png', array(
                    'title' => _('Antworttext')
                )) ?>
            </td>
            <td>
                <?= $task_user ? sizeof($task_user->files->findBy('type', 'feedback')) : 0 ?>
                <?= Assets::img('icons/16/black/file-generic.png', array(
                    'title' => _('Hochgeladene Dateien')
                )) ?>
            </td>
            <td>
                <?= ($task_user && $task_user->hint)
                        ? Assets::img('icons/16/black/file-text.png', array(
                            'title' => _('Für diese Aufgabe wurden Hinweise für sie hinterlegt!')
                        )) : '-' ?>
            </td>
            <td>
                <a href="<?= $controller->url_for("index/view_dozent/" . $task_user->id) ?>">
                    <?= Assets::img('icons/16/black/edit.png', array('title' => _('Diese Aufgabe für diesen Nutzer bearbeiten'))) ?>
                </a>
            </td>
        </tr>
        <? endif ?>
        <? endforeach ?>        
    </tbody>
</table>