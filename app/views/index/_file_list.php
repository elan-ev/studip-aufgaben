<?
/* create folder(s) for current task */
// TODO: move to better suited location

// Aufgabenordner
$type_folder = \EPP\Helper::getTypedFolder($folder, $task, $task_user, $type);
?>

    <!-- files already there -->
    <table class="default zebra" <? /* data-folder-id="<?= $user_folder->id ?>" */ ?>>
        <tbody>
        <? if (count($type_folder->getFiles())) : ?>
            <? foreach ($type_folder->getFiles() as $file_ref) : ?>
                <?= $this->render_partial('index/_file', [
                    'current_folder' => $type_folder,
                    'file_ref'       => $file_ref,
                ]) ?>
            <? endforeach; ?>
        <? endif; ?>
        </tbody>
    </table>


<script>
$(function() {
    $(document).on('refresh-handlers', function() {
        if (STUDIP.epp.refresh_enabled) {
            window.location.reload();
        }
    })
})
</script>
