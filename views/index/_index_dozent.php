<table class="default zebra tablesorter">
    <caption><?= $_('Aufgaben') ?></caption>
    <colgroup>
        <col style="width: 50%">
        <col>
        <col>
        <col>
        <col style="witdh: 120px">
    </colgroup>
    <thead>
        <tr class="sortable">
            <th style="width: 60%" <?= $sort == 'title' ? 'class="sort' . $order . '"' : '' ?>>
                <a href="<?= $controller->link_for('index/index?sort_by=title' . ($order == 'desc' ? '&asc=1' : '')) ?>">
                    <?= $_('Aufgabe') ?>
                </a>
            </th>

            <th <?= $sort == 'startdate' ? 'class="sort' . $order . '"' : '' ?>>
                <a href="<?= $controller->url_for('index/index?sort_by=startdate' . ($order == 'desc' ? '&asc=1' : '')) ?>">
                    <?= $_('Start') ?>
                </a>
            </th>

            <th <?= $sort == 'enddate' ? 'class="sort' . $order . '"' : '' ?>>
                <a href="<?= $controller->link_for('index/index?sort_by=enddate' . ($order == 'desc' ? '&asc=1' : '')) ?>">
                    <?= $_('Ende') ?>
                </a>
            </th>

            <th <?= $sort == 'enddate' ? 'class="sort' . $order . '"' : '' ?>>
                <a href="<?= $controller->link_for('index/index?sort_by=enddate' . ($order == 'desc' ? '&asc=1' : '')) ?>">
                    <?= $_('Status') ?>
                </a>
            </th>
            <th class="actions"><?= $_('Aktionen') ?></th>
        </tr>
    </thead>
    <tbody>
        <? foreach ($tasks as $task) : ?>
            <tr class="<?= $task->getStatus() ?>">
                <td>
                    <a href="<?= $controller->link_for('index/view_task/' . $task['id']) ?>"
                       title="<?= $_('Diese Aufgabe anzeigen') ?>">
                        <?= htmlReady($task['title']) ?>
                    </a>
                </td>
                <td>
                    <?= strftime(EPP\Helper::timeformat, $task['startdate']) ?>
                </td>
                <td>
                    <?= strftime(EPP\Helper::timeformat, $task['enddate']) ?>
                </td>
                <td>
                    <?= $task->getStatusText() ?>
                </td>
                <td class="actions">
                    <? $actions = ActionMenu::get() ?>

                    <?
                    $actions->addLink(
                        $controller->url_for('index/edit_task/' . $task['id']),
                        $_('Aufgabe bearbeiten'),
                        Icon::create('edit'),
                        ['data-dialog' => 'size=50%']);
                    $actions->addLink(
                        $controller->url_for('index/zip/' . $task['id']),
                        $_('Hochgeladene Aufgabenabgaben herunterladen'),
                        Icon::create('file-archive+move_down'));
                    $actions->addLink(
                        $controller->url_for('index/pdf/' . $task['id']),
                        $_('Textantworten als PDF herunterladen'),
                        Icon::create('file-pdf+move_down'));

                    $link = new StudipLink(
                        $controller->url_for('index/upload_dialog/' . $task['id']),
                        $_('Feedback zip-Datei hochladen'),
                        Icon::create('file-archive+move_up')
                    );

                    $link->attributes['data-dialog'] = 'size=auto;reload-on-close';

                    $actions->addLink($link);

                    $actions->addLink(
                        $controller->url_for('index/delete_task/' . $task['id']),
                        $_('Aufgabe löschen'),
                        Icon::create('trash'),
                        ['data-confirm' => $_('Sind Sie sicher, dass Sie die komplette Aufgabe löschen möchten?')]
                    )
                    ?>
                    <?= $actions ?>
                </td>
            </tr>
        <? endforeach ?>
    </tbody>
</table>
