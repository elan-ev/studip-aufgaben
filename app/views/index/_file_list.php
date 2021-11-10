<?
/* create folder(s) for current task */
// TODO: move to better suited location

// Aufgabenordner
$type_folder = \EPP\Helper::getTypedFolder($folder, $task, $task_user, $type);
?>
<span id="files-index">
    <!-- files already there -->
    <table class="default">
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
</span>
