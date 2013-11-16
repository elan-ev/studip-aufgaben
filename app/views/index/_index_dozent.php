<table class="default zebra tablesorter">
    <thead>
        <tr class="sortable">
            <th style="width: 60%" <?= $sort == 'title' ? 'class="sort' . $order .'"': '' ?>>
                <a href="<?= $controller->url_for('index/index?sort_by=title' . ($order == 'desc' ? '&asc=1' : '')) ?>">
                    <?= _('Aufgabe') ?>
                </a>
            </th>
            
            <th <?= $sort == 'startdate' ? 'class="sort' . $order .'"': '' ?>>
                <a href="<?= $controller->url_for('index/index?sort_by=startdate' . ($order == 'desc' ? '&asc=1' : '')) ?>">
                    <?= _('Start') ?>
                </a>
            </th>
            
            <th <?= $sort == 'enddate' ? 'class="sort' . $order .'"': '' ?>>
                <a href="<?= $controller->url_for('index/index?sort_by=enddate' . ($order == 'desc' ? '&asc=1' : '')) ?>">
                    <?= _('Ende') ?>
                </a>
            </th>
            
            <th <?= $sort == 'enddate' ? 'class="sort' . $order .'"': '' ?>>
                <a href="<?= $controller->url_for('index/index?sort_by=enddate' . ($order == 'desc' ? '&asc=1' : '')) ?>">
                    <?= _('Status') ?>
                </a>
            </th>
            <th style="width: 50px"><?= _('Aktionen') ?></th>
        </tr>
    </thead>
    <tbody>
        <? foreach ($tasks as $task) : ?>
        <tr class="<?= $task->getStatus() ?>">
            <td>
                <a href="<?= $controller->url_for('/index/view_task/' . $task['id']) ?>" title="<?= _('Diese Aufgabe anzeigen') ?>">
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
            <td>
                <a href="<?= $controller->url_for('/index/edit_task/' . $task['id']) ?>" title="<?= _('Diese Aufgabe bearbeiten') ?>">
                    <?= Assets::img('icons/16/blue/edit.png') ?>
                </a>
            </td>
        </tr>
        <? endforeach ?>
    </tbody>
</table>