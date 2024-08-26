<form action="" class="default">
    <fieldset>
        <legend><?= _('Feedback zip-Datei hochladen') ?></legend>
        <h2 class="dialog-subtitle">Quelle auswählen</h2>

        <div class="errorbox" style="display: none;">
            <?= MessageBox::error('<span class="errormessage"></span>')?>
        </div>

        <div class="files_source_selector" data-task_id="<?= htmlReady($task_id) ?>">
            <div class="file_select_possibilities">
                <div>
                    <a href="#" onclick="jQuery('.epp--file-selector input[type=file]').first().click(); return false;">
                        <?= Icon::create('computer')->asImg(50) ?>
                        <?= _('Mein Computer') ?>
                    </a>
                </div>
            </div>

            <div class="epp--file-selector" style="display: none">
                <input type="file" name="files[]" onchange="STUDIP.Aufgaben.upload(this.files);">
                <input type="hidden" name="cid" value="<?= $seminar_id ?>">
            </div>
        </div>
    </fieldset>
</form>
