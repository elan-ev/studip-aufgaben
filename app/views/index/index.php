<? if (empty($tasks)) : ?>
    <? if (EPP\Perm::has('new_task', $seminar_id)) : ?>
        <?= MessageBox::info(sprintf($_('Sie haben noch keine Aufgaben angelegt. %sNeue Aufgabe anlegen.%s'),
            '<a href="' . $controller->url_for('index/new_task') . '" data-dialog="size=50%">', '</a>')); ?>
    <? else : ?>
        <?= MessageBox::info($_('Es sind noch keine Aufgaben sichtbar/vorhanden')) ?>
    <? endif ?>
<? else : ?>
    <?= $this->render_partial('index/_breadcrumb', ['path' => ['overview']]) ?>

    <? if (EPP\Perm::has('new_task', $seminar_id)) : ?>
        <?= $this->render_partial('index/_index_dozent'); ?>
    <? else : ?>
        <?= $this->render_partial('index/_index_autor'); ?>
    <? endif; ?>
<? endif ?>

<? if (!empty($accessible_tasks)) : ?>
    <?= $this->render_partial('index/_index_autor_accessible', ['tasks' => $accessible_tasks]); ?>
<? endif; ?>
