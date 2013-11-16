<a name="jumpto_<?= $field ?>"></a>

<? if ($edit[$field]) : ?>

<form action="<?= $controller->url_for($form_route) ?>" method="post">
    <b><?= $name ?>:</b><br>
    <br>
    <textarea name="<?= $field ?>" class="add_toolbar" style="width: 100%; height: 400px;"><?= htmlReady($text) ?></textarea>
    <br>

    <div class="buttons">
        <div class="button-group">
            <?= \Studip\Button::createAccept(_('Speichern')) ?>
            <?= \Studip\LinkButton::createCancel(_('Abbrechen'), $controller->url_for($cancel_route .'#jumpto_'. $field)) ?>
        </div>
    </div>
</form>

<? else : ?>

    <b><?= $name ?>:</b><br>
    <? if ($text) : ?>
    <br>
    <?= formatReady($text) ?><br>
    <? else : ?>
    <span class="empty_text"><?= _('Es wurde noch kein Text eingegeben') ?></span>
    <? endif ?>

    <div class="buttons">
        <div class="button-group">
            <?= \Studip\LinkButton::createEdit(_('Bearbeiten'), $controller->url_for($cancel_route .'/'. $field .'#jumpto_'. $field)) ?>
            <? /* <?= $delete_route ? \Studip\LinkButton::createDelete(_('Löschen'), $controller->url_for($delete)) : '' */ ?>
        </div>
    </div>    

<? endif; ?>
