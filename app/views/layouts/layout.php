<? $this->set_layout($GLOBALS['template_factory']->open('layouts/base')); ?>

<div id="epp">
    <?= $this->render_partial('index/_js_templates') ?>
    <script>
        var STUDIP = STUDIP || {};
        STUDIP.AufgabenConfig = STUDIP.AufgabenConfig || {};
        STUDIP.AufgabenConfig.plugin_name = 'aufgabenplugin';
    </script>

    <?= $content_for_layout ?>
</div>
