<section class="contentbox">
    <header>
        <h1><?= _('Dateien') ?></h1>
    </header>
    <!-- files already there -->
    <table class="default zebra">
        <thead>
            <tr>
                <th style="width:40%"><?= _('Datei') ?></th>
                <th style="width:10%"><?= _('Größe') ?></th>
                <th style="width:20%"><?= _('Datum') ?></th>
                <th style="width:20%"><?= _('Besitzer') ?></th>
                <? if ($edit) : ?>
                    <th style="width:10%"><?= _('Aktionen') ?></th>
                <? endif ?>
            </tr>
        </thead>
        <tbody <?= $edit ? 'id="uploaded_files"' : '' ?>>
            <? if (count($files)) : ?>
                <? foreach ($files as $file) : ?>
                    <tr data-fileid="<?= $file->getId() ?>">
                        <td>
                            <a href="<?= GetDownloadLink($file->document->getId(), $file->document->name) ?>"
                               target="_blank">
                                <?= $file->document->name ?>
                            </a>
                        </td>
                        <td><?= round((($file->document->filesize / 1024) * 100) / 100, 2) ?> kb</td>
                        <td><?= strftime($timeformat, $file->document->mkdate) ?></td>
                        <td>
                            <a href="<?= URLHelper::getLink('dispatch.php/profile?username=' . get_username($file->document->user_id)) ?>">
                                <?= get_fullname($file->document->user_id) ?>
                            </a>
                        </td>

                        <? if ($edit) : ?>
                            <td>
                                <? if ($GLOBALS['user']->id == $file->document->user_id) : ?>
                                    <a href="javascript:STUDIP.epp.removeFile('<?= $seminar_id ?>', '<?= $file->getId() ?>')">
                                        <?= Icon::create('trash') ?>
                                    </a>
                                <? endif ?>
                            </td>
                        <? endif ?>
                    </tr>
                <? endforeach ?>
            <? else : ?>
                <tr>
                    <td colspan="<?= $edit ? 5 : 4 ?>" style="text-align: center">
                        <?= _('Bisher wurden keine Dokumente hochgeladen') ?>
                    </td>
                </tr>
            <? endif ?>
        </tbody>
    </table>

    <? if ($edit) : ?>
        <footer>
            <?= $this->render_partial('index/_file_upload', compact('task_user')) ?>
        </footer>
    <? endif ?>

</section>
