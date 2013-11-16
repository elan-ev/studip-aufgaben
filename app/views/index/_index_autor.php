<table class="default zebra tablesorter" id="ep_tasks">
    <thead>
        <tr class="sortable">
            <th <?= $sort == 'title' ? 'class="sort' . $order .'"': '' ?> style="width: auto">
                <a href="<?= $controller->url_for('index/index?sort_by=title' . ($order == 'desc' ? '&asc=1' : '')) ?>">
                    <?= _('Aufgabe') ?>
                </a>
            </th>
            
            <th <?= $sort == 'startdate' ? 'class="sort' . $order .'"': '' ?> style="width: 120px;">
                <a href="<?= $controller->url_for('index/index?sort_by=startdate' . ($order == 'desc' ? '&asc=1' : '')) ?>">
                    <?= _('Start') ?>
                </a>
            </th>
            
            <th <?= $sort == 'enddate' ? 'class="sort' . $order .'"': '' ?> style="width: 120px;">
                <a href="<?= $controller->url_for('index/index?sort_by=enddate' . ($order == 'desc' ? '&asc=1' : '')) ?>">
                    <?= _('Ende') ?>
                </a>
            </th>
            
            <th <?= $sort == 'enddate' ? 'class="sort' . $order .'"': '' ?> style="width: 80px;">
                <a href="<?= $controller->url_for('index/index?sort_by=enddate' . ($order == 'desc' ? '&asc=1' : '')) ?>">
                    <?= _('Status') ?>
                </a>
            </th>
            <th colspan="2" style="text-align: center">
                <?= _('Arbeit') ?>
            </th>
            <th colspan="2" style="text-align: center">
                <?= _('Feedback') ?>
            </th>
        </tr>
    </thead>
    <tbody>
        <? foreach ($tasks as $task) : ?>
        <? $task_user = $task->task_users->findOneBy('user_id', $GLOBALS['user']->id) ?>
        <tr class="<?= $task->getStatus() ?>">
            <td>
                <a href="<?= $controller->url_for('/index/view_student/' . $task['id']) ?>" title="<?= _('Diese Aufgabe anzeigen') ?>">
                    <?= htmlReady($task['title']) ?>
                </a>
            </td>
            <td>
                <?= strftime($timeformat, $task['startdate']) ?>
            </td>
            <td>
                <?= strftime($timeformat, $task['enddate']) ?>
            </td>
            <td>
                <?= $task->getStatusText() ?>
            </td>
            <td style="width: 50px; text-align: right">
                <?= (!$task_user || $task_user->answer === null) ? '0' : strlen($task_user->answer) ?>
                <?= Assets::img('icons/16/black/file-text.png', array(
                    'title' => _('Antworttext')
                )) ?>
            </td>
            <td style="width: 40px">
                <?= $task_user ? sizeof($task_user->files->findBy('type', 'answer')) : 0 ?>
                <?= Assets::img('icons/16/black/file-generic.png', array(
                    'title' => _('Hochgeladene Dateien')
                )) ?>
            </td>
            <td style="width:50px; text-align: right">
                <?= (!$task_user || $task_user->feedback === null) ? '0' : strlen($task_user->feedback) ?>
                <?= Assets::img('icons/16/black/file-text.png', array(
                    'title' => _('Antworttext')
                )) ?>
            </td>
            <td style="width: 40px">
                <?= $task_user ? sizeof($task_user->files->findBy('type', 'feedback')) : 0 ?>
                <?= Assets::img('icons/16/black/file-generic.png', array(
                    'title' => _('Hochgeladene Dateien')
                )) ?>
            </td>
        </tr>
        <? endforeach ?>
    </tbody>
</table>

<script>
    jQuery('#ep_tasks').tablesorter();
</script>
