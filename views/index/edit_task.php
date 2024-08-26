<form action="<?= $controller->url_for($destination) ?>" method="post" class="default">
    <?= CSRFProtection::tokenTag() ?>
    <fieldset>
        <legend>
            <?= $task ? $_('Aufgabe bearbeiten') : $_('Aufgabe anlegen') ?>
        </legend>
        <label>
            <span class="required">
                <?= $_('Titel') ?>
            </span>
            <input type="text" name="title" required value="<?= $task ? htmlReady($task['title']) : '' ?>">
        </label>

        <label>

            <span class="required"><?= $_('Aufgabenbeschreibung') ?></span>
            <textarea name="content" required><?= $task ? htmlReady($task['content']) : '' ?></textarea>
        </label>

        <label>
            <?= $_('Sichtbar und bearbeitbar ab') ?>:<br>
            <input type="text" data-datetime-picker name="startdate" placeholder="<?= $_('tt.mm.jjjj ss:mm') ?>" required
                   class="size-s" value="<?= $task['startdate'] ? strftime('%d.%m.%Y %R', $task['startdate']) : '' ?>">
        </label>

        <label>
            <?= $_('Bearbeitbar bis') ?>:<br>
            <input type="text" data-datetime-picker name="enddate" placeholder="<?= $_('tt.mm.jjjj ss:mm') ?>" required
                   class="size-s" value="<?= $task['enddate'] ? strftime('%d.%m.%Y %R', $task['enddate']) : '' ?>">
        </label>


        <label>
            <input type="checkbox" name="allow_text" value="1" <?= $task &&  $task['allow_text'] ? 'checked="checked"' : '' ?>>
            <?= $_('Texteingabe erlauben') ?>
        </label>

        <label>
            <input type="checkbox" name="allow_files" value="1" <?= $task && $task['allow_files'] ? 'checked="checked"' : '' ?>>
            <?= $_('Dateiupload erlauben') ?>
        </label>
    </fieldset>

    <footer data-dialog-button>
        <?= \Studip\Button::createAccept(_('Speichern')) ?>
        <? if ($task) : ?>
            <?= \Studip\LinkButton::createCancel(_('Abbrechen'), $controller->url_for('index/view_task/' . $task['id'])) ?>
        <? else: ?>
            <?= \Studip\LinkButton::createCancel(_('Abbrechen'), $controller->url_for('index')) ?>
        <? endif ?>
    </footer>
</form>
