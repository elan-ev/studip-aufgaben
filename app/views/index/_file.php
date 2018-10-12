<?php
$permissions = [];
if ($current_folder->isFileEditable($file_ref->id, $GLOBALS['user']->id)) {
    $permissions[] = 'w';
}
if ($current_folder->isFileDownloadable($file_ref->id, $GLOBALS['user']->id)) {
    $permissions[] = 'dr';
}
?>
<tr <? if ($full_access) printf('data-file="%s"', $file_ref->id) ?> id="fileref_<?= htmlReady($file_ref->id) ?>" role="row" data-permissions="<?= implode($permissions) ?>">
    <td class="document-icon" data-sort-value="<?=crc32($file_ref->mime_type)?>">
    <? if ($current_folder->isFileDownloadable($file_ref, $GLOBALS['user']->id)) : ?>
        <a href="<?= htmlReady($file_ref->download_url) ?>" target="_blank">
            <?= Icon::create(FileManager::getIconNameForMimeType($file_ref->mime_type), Icon::ROLE_CLICKABLE)->asImg(24) ?>
        </a>
    <? else : ?>
        <?= Icon::create(FileManager::getIconNameForMimeType($file_ref->mime_type), 'inactive')->asImg(24) ?>
    <? endif ?>
    </td>
    <td data-sort-value="<?= htmlReady($file_ref->name) ?>">
    <? if ($current_folder->isFileDownloadable($file_ref, $GLOBALS['user']->id)) : ?>
        <a href="<?= htmlReady(URLHelper::getURL('dispatch.php/file/details/' . $file_ref->id)) ?>" data-dialog="">
            <?= htmlReady($file_ref->name) ?>
        </a>
    <? else : ?>
        <?= htmlReady($file_ref->name) ?>
    <? endif ?>
    <? if ($file_ref->terms_of_use && $file_ref->terms_of_use->download_condition > 0): ?>
        <?= Icon::create('lock-locked', $current_folder->isFileDownloadable($file_ref, $GLOBALS['user']->id) ? ICON::ROLE_INACTIVE : Icon::ROLE_INFO)->asImg(['class' => 'text-top', 'title' => _('Das Herunterladen dieser Datei ist nur eingeschränkt möglich.')]) ?>
    <? endif; ?>
    </td>
    <td title="<?= number_format($file_ref->size, 0, ',', '.') . ' Byte' ?>" data-sort-value="<?= $file_ref->size ?>" class="responsive-hidden">
    <? if ($file_ref->is_link) : ?>
        <?= _('Weblink') ?>
    <? else : ?>
        <?= relSize($file_ref->size, false) ?>
    <? endif ?>
    </td>
    <td data-sort-value="<?= htmlReady($file_ref->author_name) ?>" class="responsive-hidden">
    <? if ($file_ref->user_id !== $GLOBALS['user']->id && $file_ref->owner): ?>
        <a href="<?= URLHelper::getURL('dispatch.php/profile?username=' . $file_ref->owner->username) ?>">
            <?= htmlReady($file_ref->author_name) ?>
        </a>
    <? else: ?>
        <?= htmlReady($file_ref->author_name) ?>
    <? endif; ?>
    </td>
    <td title="<?= strftime('%x %X', $file_ref->chdate) ?>" data-sort-value="<?= $file_ref->chdate ?>" class="responsive-hidden">
        <?= $file_ref->chdate ? reltime($file_ref->chdate) : "" ?>
    </td>
    <td class="actions">
        <? if ($current_folder->isFileWritable($file_ref->id, $GLOBALS['user']->id)) : ?>
            <a href="javascript:STUDIP.epp.removeFile('<?= $file_ref->id ?>')"
                onclick="return STUDIP.Dialog.confirmAsPost('<?= htmlReady(sprintf(_('Soll die Datei "%s" wirklich gelöscht werden?'),
                    $file_ref->name)) ?>', this.href);"
                data-dialog
            >

            <? if ($task->enddate >= time() || $editable) : ?>
            <?= Icon::create('trash', Icon::ROLE_CLICKABLE, [
                    'size'  => 20,
                    'title' => _('Datei löschen')
                ]);
            ?>
            <? endif; ?>
            </a>
        <? endif ?>
    </td>
</tr>
