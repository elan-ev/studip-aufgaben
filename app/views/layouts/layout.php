<? $this->set_layout($GLOBALS['template_factory']->open('layouts/base')); ?>
<div id="epp">
<?= $this->render_partial('index/_js_templates') ?>
<?= $content_for_layout ?>
</div>
