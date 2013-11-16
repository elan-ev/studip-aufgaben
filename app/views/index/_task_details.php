<h2><?= htmlReady($task['title']) ?></h2>

<div class="mark">
    <?= formatReady($task['content']) ?><br>
    <br>
    
    <hr>

    <b>Aufgabe bearbeitbar bis:</b><br>
    <?= strftime($timeformat, $task['startdate']) ?> - <?= strftime($timeformat, $task['enddate']) ?> <?= _('Uhr') ?><br>

    <? if ($task->allow_text && $task->allow_files) : ?>
        <br><?= _('Texteingabe und Dateiupload erlaubt') ?><br>
    <? elseif ($task->allow_text) : ?>
        <br><?= _('Texteingabe erlaubt') ?><br>
    <? elseif ($task->allow_files) : ?>
        <br><?= _('Dateiupload erlaubt') ?><br>
    <? endif ?>

    <? /*
    <? if ($task->send_mail) : ?>
        <br><?= _('Es wird eine Mail an alle TeilnehmerInnen verschickt, sobald die Aufgabe sichtbar ist.') ?><br>
    <? endif ?> */ ?>
</div>
