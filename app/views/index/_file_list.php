<?
/* create folder(s) for current task */
// TODO: move to better suited location

// Aufgabenordner
$task_folder = null;

foreach ($folder->subfolders as $subfolder) {
    if ($subfolder['data_content']['task_id'] == $task->id) {
        $task_folder = $subfolder;
    }
}

if (!$task_folder) {
    $task_folder = \Folder::create([
        'parent_id'    => $folder->getId(),
        'range_id'     => Context::getId(),
        'range_type'   => Context::getType(),
        'description'  => 'Aufgabenordner',
        'name'         => 'Aufgabenordner: ' . $task->title,
        'data_content' => ['task_id' => $task->id],
        'folder_type'  => 'TaskFolder',
        'user_id'      => $this->seminar_id
    ]);

    $folder->subfolders[] = $task_folder;
}

// Nutzerordner für eine Aufgabe
$user_folder = null;
foreach ($task_folder->subfolders as $subfolder) {
    if ($subfolder['data_content']['task_user'] == $task_user->user_id) {
        $user_folder = $subfolder;
    }
}

if (!$user_folder) {
    $user_folder = \Folder::create([
        'parent_id'    => $task_folder->getId(),
        'range_id'     => Context::getId(),
        'range_type'   => Context::getType(),
        'description'  => 'Nutzerordner',
        'name'         => get_fullname($task_user->user_id),
        'data_content' => ['task_user' => $task_user->user_id],
        'folder_type'  => 'TaskFolder',
        'user_id'      => $task_user->user_id
    ]);

    $task_folder->subfolders[] = $user_folder;
}

// Ordner für die Art der Datei
$type_folder = null;
foreach ($user_folder->subfolders as $subfolder) {
    if ($subfolder['data_content']['task_type'] == $type) {
        $type_folder = $subfolder;
    }
}

if (!$type_folder) {
    $type_folder = \Folder::create([
        'parent_id'    => $user_folder->getId(),
        'range_id'     => Context::getId(),
        'range_type'   => Context::getType(),
        'description'  => '',
        'name'         => ucfirst($type),
        'data_content' => [
            'task_type' => $type,
            'task_user' => $task_user->user_id
        ],
        'folder_type'  => 'TaskFolder',
        'user_id'      => $task_user->user_id
    ]);

    $user_folder->subfolders[] = $type_folder;
}
$type_folder = $type_folder->getTypedFolder();
?>

<section class="contentbox">
    <header>
        <h1><?= _('Dateien') ?></h1>
    </header>
    <!-- files already there -->
    <table class="default zebra" data-folder-id="<?= $user_folder->id ?>">
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

    <? if ($edit) : ?>
        <footer>
        <?= \Studip\LinkButton::create(
            _('Datei hinzufügen'), '#',
            [
                'onClick' => "STUDIP.epp.refresh_enabled = true; STUDIP.Files.openAddFilesWindow('". $type_folder->getId() ."'); return false;"
            ]
        ) ?>
        </footer>
    <? endif ?>
</section>

<script>
$(function() {
    $(document).on('refresh-handlers', function() {
        if (STUDIP.epp.refresh_enabled) {
            window.location.reload();
        }
    })
})
</script>
