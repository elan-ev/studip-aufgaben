<!-- multi file upload -->
<? 
$art = $GLOBALS['SessSemName']["art_num"];
if (!$GLOBALS['UPLOAD_TYPES'][$art]) $art = 'default';

$max = $GLOBALS['UPLOAD_TYPES'][$art]["file_sizes"][$GLOBALS['perm']->get_studip_perm($GLOBALS['SessSemName'][1])]
?>

<script>
    STUDIP.epp.maxFilesize = <?= $max ?>;
</script>

<div style="position: relative; display: inline-block;">
    <a class="button" style="overflow: hidden; position: relative;">
        <?= _('Datei(en) hinzufügen') ?>
        <input id="fileupload" type="file" multiple name="file" 
            data-url="<?= $controller->url_for('index/post_files/' . $task_user->id .'/'. $type) ?>"
            data-sequential-uploads="true"
            style="opacity: 0; position: absolute; left: -2px; top: -2px; height: 105%; cursor: pointer;">
    </a>
</div>

<?= \Studip\LinkButton::create(_('Datei(en) hochladen'), "javascript:STUDIP.epp.upload()", 
        array('id' => 'upload_button', 'class' => 'disabled')) ?>

<b><?= _('Maximal erlaubte Größe pro Datei') ?>: <?= round($max / 1024 / 1024, 2) ?> MB</b><br>

<table class="default zebra">
    <tbody id="files_to_upload">
    </tbody>
</table>