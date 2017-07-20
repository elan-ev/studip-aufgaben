<section class="contentbox">
    <header>
        <h1><?= _('Zugriff gewähren') ?></h1>
    </header>
    <section>
        <form id="edit-permissions-form" data-task-user-id="<?= $task_user->id ?>" class="default">

            <div id="permission_list"></div>

            <div class="three-columns" id="permissions">
                <div>
                    <select name="search" data-placeholder="<?= _('Nach Vorname und/oder Nachname suchen...') ?>">
                    </select>
                    <br>
                    <span class="error" style="display: none;"></span>
                </div>
                <div>
                    <select name="permission" data-placeholder="<?= _('Berechtigung wählen') ?>">
                        <? foreach ($permissions as $perm => $name) : ?>
                            <option value="<?= $perm ?>"><?= $name ?></option>
                        <? endforeach ?>
                    </select>
                    <?= tooltipIcon(_('Kommilitone/in: Kann die komplette Aufgabe einsehen, den Antworttext ändern und Dateien hochladen.'
                        . ' Nur selbst hochgeladene Dateien können wieder gelöscht werden.')) ?>
                </div>

                <div>
                    <?= \Studip\LinkButton::createAccept(_('Berechtigung hinzufügen'), 'javascript:', ['id' => 'add-permission']) ?>
                </div>
            </div>
        </form>
    </section>
</section>
