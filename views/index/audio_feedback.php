<form action="<?= $controller->url_for('index/upload_audio/' . $task_user->getId()) ?>"
      method="post" enctype="multipart/form-data" class="default" data-audio-feedback-form
      data-return-url="<?= $controller->url_for('index/view_dozent/' . $task_user->getId()) ?>"
      data-unsupported="<?= htmlReady($_('Audioaufnahmen werden von diesem Browser nicht unterstützt. Bitte verwenden Sie einen aktuellen Browser und HTTPS.')) ?>"
      data-microphone-error="<?= htmlReady($_('Das Mikrofon konnte nicht geöffnet werden. Bitte prüfen Sie die Mikrofonberechtigung.')) ?>"
      data-recording-error="<?= htmlReady($_('Die Aufnahme ist fehlgeschlagen. Bitte versuchen Sie es erneut.')) ?>"
      data-upload-error="<?= htmlReady($_('Die Aufnahme konnte nicht hochgeladen werden. Bitte versuchen Sie es erneut.')) ?>"
      data-recording="<?= htmlReady($_('Aufnahme läuft …')) ?>"
      data-ready="<?= htmlReady($_('Die Aufnahme kann jetzt angehört und hochgeladen werden.')) ?>"
      data-uploading="<?= htmlReady($_('Die Aufnahme wird hochgeladen …')) ?>">
    <?= CSRFProtection::tokenTag() ?>
    <p data-audio-status role="status" aria-live="polite"></p>
    <p data-audio-error role="alert" hidden></p>
    <p><audio controls preload="auto" data-audio-preview hidden aria-label="<?= htmlReady($_('Aufnahme anhören')) ?>"></audio></p>
    <div>
        <button type="button" class="button" data-audio-start><?= $_('Aufnahme starten') ?></button>
        <button type="button" class="button" data-audio-stop disabled><?= $_('Aufnahme beenden') ?></button>
        <button type="button" class="button" data-audio-reset disabled><?= $_('Aufnahme verwerfen') ?></button>
    </div>
    <footer data-dialog-button>
        <?= \Studip\Button::createAccept($_('Aufnahme hochladen'), ['data-audio-upload' => '', 'disabled' => true]) ?>
        <button type="button" class="button cancel" data-audio-close><?= $_('Abbrechen') ?></button>
    </footer>
</form>
