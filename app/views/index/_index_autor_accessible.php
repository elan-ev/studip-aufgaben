<table class="default zebra tablesorter" id="ep_tasks">
    <caption><?= $_('Für mich freigegebene Aufgaben') ?></caption>
    <thead>
        <tr class="sortable">
            <th <?= $sort == 'title' ? 'class="sort' . $order . '"' : '' ?> style="width: auto">
                <a href="<?= $controller->url_for('index/index?sort_by=title' . ($order == 'desc' ? '&asc=1' : '')) ?>">
                    <?= $_('Aufgabe') ?>
                </a>
            </th>
            <th>
                <?= $_('Besitzer') ?>
            </th>
            <th <?= $sort == 'startdate' ? 'class="sort' . $order . '"' : '' ?> style="width: 120px;">
                <a href="<?= $controller->url_for('index/index?sort_by=startdate' . ($order == 'desc' ? '&asc=1' : '')) ?>">
                    <?= $_('Start') ?>
                </a>
            </th>
            <th <?= $sort == 'enddate' ? 'class="sort' . $order . '"' : '' ?> style="width: 120px;">
                <a href="<?= $controller->url_for('index/index?sort_by=enddate' . ($order == 'desc' ? '&asc=1' : '')) ?>">
                    <?= $_('Ende') ?>
                </a>
            </th>
            <th <?= $sort == 'enddate' ? 'class="sort' . $order . '"' : '' ?> style="width: 80px;">
                <a href="<?= $controller->url_for('index/index?sort_by=enddate' . ($order == 'desc' ? '&asc=1' : '')) ?>">
                    <?= $_('Status') ?>
                </a>
            </th>
            <th colspan="2" style="text-align: center">
                <?= $_('Arbeit') ?>
            </th>
            <th colspan="2" style="text-align: center">
                <?= $_('Feedback') ?>
            </th>
        </tr>
    </thead>
    <tbody>
        <? foreach ($tasks as $task_user) : ?>
            <tr class="<?= $task_user->task->getStatus() ?>">
                <td>
                    <? if ($task_user->task->startdate <= time()) : ?>
                        <a href="<?= $controller->url_for('/index/view_student/' . $task_user->task['id'] . '?task_user_id=' . $task_user->id) ?>"
                           title="<?= $_('Diese Aufgabe anzeigen') ?>">
                            <?= htmlReady($task_user->task['title']) ?>
                        </a>
                    <? else : ?>
                        <?= htmlReady($task_user->task['title']) ?>
                    <? endif ?>
                </td>
                <td>
                    <?= htmlReady(get_fullname($task_user->user_id)) ?>
                </td>

                <td>
                    <?= strftime(EPP\Helper::timeformat, $task_user->task['startdate']) ?>
                </td>
                <td>
                    <?= strftime(EPP\Helper::timeformat, $task_user->task['enddate']) ?>
                </td>
                <td>
                    <?= $task_user->task->getStatusText() ?>
                </td>
                <td style="width: 50px; text-align: right">
                    <?= (!$task_user || $task_user->answer === null) ? '0' : strlen($task_user->answer) ?>
                    <?= Icon::create('file-text', 'info', tooltip2($_('Abgabe'))) ?>
                </td>
                <td style="width: 40px">
                    <? $type_folder = \EPP\Helper::getTypedFolder($folder, $task_user->task, $task_user, 'answer'); ?>
                    <?= $type_folder ? count($type_folder->getFiles()) : 0 ?>
                    <?= Icon::create('file-generic', 'info', tooltip2($_('Hochgeladene Dateien'))) ?>
                </td>
                <td style="width:50px; text-align: right">
                    <?= (!$task_user || $task_user->feedback === null) ? '0' : strlen($task_user->feedback) ?>
                    <?= Icon::create('file-text', 'info', tooltip2($_('Abgabe'))) ?>
                </td>
                <td style="width: 40px">
                    <? $type_folder = \EPP\Helper::getTypedFolder($folder, $task_user->task, $task_user, 'feedback'); ?>
                    <?= $type_folder ? count($type_folder->getFiles()) : 0 ?>
                    <?= Icon::create('file-generic', 'info', tooltip2($_('Hochgeladene Dateien'))) ?>
                </td>
            </tr>
        <? endforeach ?>
    </tbody>
</table>
