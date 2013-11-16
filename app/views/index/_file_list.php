<!-- files already there -->
<table class="default zebra">
    <thead>
        <tr>
            <th style="width:60%"><?= _('Datei') ?></th>
            <th style="width:10%"><?= _('Größe') ?></th>
            <th style="width:20%"><?= _('Datum') ?></th>
            <? if ($edit) : ?>
            <th style="width:10%"><?= _('Aktionen') ?></th>
            <? endif ?>
        </tr>
    </thead>
    <tbody <?= $edit ? 'id="uploaded_files"' : '' ?>>
<? if(!empty($files)) foreach($files as $file) : ?>
    <tr data-fileid="<?= $file->getId() ?>">
        <td>
            <a href="<?= GetDownloadLink($file->document->getId(), $file->document->name)?>" target="_blank">
                <?= $file->document->name ?>
            </a>
        </td>
        <td><?= round((($file->document->filesize / 1024) * 100) / 100, 2) ?> kb</td>
        <td><?= strftime($timeformat, $file->document->mkdate) ?></td>
        
        <? if ($edit) : ?>
        <td>
            <? if ($GLOBALS['user']->id == $file->document->user_id) : ?>
            <a href="javascript:STUDIP.epp.removeFile('<?= $seminar_id ?>', '<?= $file->getId() ?>')">
                <?= Assets::img('icons/16/blue/trash.png') ?>
            </a>
            <? endif ?>
        </td>
        <? endif ?>
    </tr>
<? endforeach ?>
    </tbody>
</table>
<br>

<? if ($edit) : ?>
    <?= $this->render_partial('index/_file_upload', compact('task_user')) ?>
<? endif ?>
