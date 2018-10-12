<a name="jumpto_<?= $field ?>"></a>

<section class="contentbox">
    <header>
        <h1><?= $name ?></h1>
    </header>

    <? if ($edit[$field]) : ?>
        <section>
            <form action="<?= $controller->url_for($form_route) ?>" method="post">
                <?= CSRFProtection::tokenTag() ?>
                <textarea name="<?= $field ?>" class="add_toolbar" aria-labelledby="<?= $name ?>"
                          style="width: 100%; height: 400px;"><?= htmlReady($text) ?></textarea>
                <footer>
                    <?= \Studip\Button::createAccept($_('Speichern')) ?>
                    <?= \Studip\LinkButton::createCancel($_('Abbrechen'), $controller->url_for($cancel_route . '#jumpto_' . $field)) ?>
                </footer>
            </form>

        </section>
    <? else : ?>

        <section>
            <? if ($text) : ?>
                <?= formatReady($text) ?>
            <? else : ?>
                <? if ($task['allow_text'] || $field == 'feedback') : ?>
                <p style="text-align: center"><?= $_('Es wurde noch kein Text eingegeben') ?></p>
                <? endif ?>
            <? endif ?>
        </section>
        <footer>
            <? if ($task['allow_text'] || ($editable && $field == 'feedback')) : ?>
            <?= \Studip\LinkButton::createEdit($_('Bearbeiten'), $controller->url_for($cancel_route . '/' . $field
                . ($task_user_id ? '?task_user_id=' . $task_user_id : '') . '#jumpto_' . $field)) ?>
            <? endif ?>

            <? if ($editable && $task['allow_files']) : ?>
                <?= \Studip\LinkButton::create(
                    _('Datei hinzufügen'), '#',
                    [
                        'onClick' => "STUDIP.epp.refresh_enabled = true; STUDIP.Files.openAddFilesWindow('". $type_folder->getId() ."'); return false;"
                    ]
                ) ?>
            <? endif ?>
        </footer>
    <? endif; ?>
</section>
