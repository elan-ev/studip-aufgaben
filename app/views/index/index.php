<?php
/**
 * filename - Short description for file
 *
 * Long description for file (if any)...
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 3 of
 * the License, or (at your option) any later version.
 *
 * @author      Till Glöggler <tgloeggl@uos.de>
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GPL version 3
 * @category    Stud.IP
 */

if (EPP\Perm::has('new_task', $seminar_id)) :
    $infobox_content[] = array(
        'kategorie' => _('Aktionen'),
        'eintrag'   => array(
            array(
                'icon' => 'icons/16/black/info.png',
                'text' => sprintf(_('%sNeue Aufgabe anlegen%s'), '<a href="'.  $controller->url_for('index/new_task') .'">', '</a>')
            )
        )
    );

else :
    $infobox_content[] = array(
        'kategorie' => _('Informationen'),
        'eintrag'   => array(
            array(
                'icon' => 'icons/16/black/info.png',
                'text' => 'Bearbeiten Sie die angezeigten Aufgaben!'
            )
        )
    );    
endif;

$infobox = array('picture' => 'infobox/schedules.jpg', 'content' => $infobox_content);
?>

<? if (empty($tasks)) : ?>
    <? if (EPP\Perm::has('new_task', $seminar_id)) : ?>
    <?= MessageBox::info(sprintf(_('Sie haben noch keine Aufgaben angelegt. %sNeue Aufgabe anlegen.%s'),
        '<a href="'. $controller->url_for('index/new_task') .'">', '</a>')); ?>
    <? else : ?>
    <?= MessageBox::info(_('Es sind noch keine Aufgaben sichtbar/vorhanden')) ?>
    <? endif ?>
<br><br><br><br><br><br><br>
<? else : ?>
    <?= $this->render_partial('index/_breadcrumb', array('path' => array('overview'))) ?>
    <h2>Aufgaben</h2>
    <? if (EPP\Perm::has('new_task', $seminar_id)) : ?>
        <?= $this->render_partial('index/_index_dozent'); ?>

        <?= \Studip\LinkButton::create(_('Neue Aufgabe anlegen'), $controller->url_for('index/new_task')) ?>
    <? else : ?>
        <?= $this->render_partial('index/_index_autor'); ?>
    <? endif; ?>
<? endif ?>