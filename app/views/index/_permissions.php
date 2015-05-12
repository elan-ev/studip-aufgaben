<form id="edit-permissions-form" data-task-user-id="<?= $task_user->id ?>">
    <label for="permissons">
        <span>Zugriff gewähren</span>
    </label>

    <div id="permission_list">

    </div>

    <div class="three-columns clearfix" id="permissions">
        <div>
            <input name="search" data-placeholder="<?= _('Nach Vorname und/oder Nachname suchen...') ?>" style="width: 80%">
            <br>
            <span class="error" style="display: none;">
            </span>
        </div>

        <div>
            <select name="permission" data-placeholder="<?= _('Berechtigung wählen') ?>" style="width: 80%">
                <? foreach ($permissions as $perm => $name) : ?>
                <option value="<?= $perm ?>"><?= $name ?></option>
                <? endforeach ?>
            </select>
            <?= tooltipIcon(_('Kommilitone/in: Kann die komplette Aufgabe einsehen, den Antworttext ändern und Dateien hochladen.'
                    . ' Nur selbst hochgeladene Dateien können wieder gelöscht werden.')) ?>
        </div>

        <div>
            <?= \Studip\LinkButton::createAccept(_('Berechtigung hinzufügen'), 'javascript:', array('id' => 'add-permission')) ?>
        </div>
    </div>
</form>