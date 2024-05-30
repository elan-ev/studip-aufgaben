<?php
/**
 * new_task.php - Short description for file
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
?>

<?= $this->render_partial('index/_breadcrumb', array('path' => array('overview', $task['title']))) ?>

<?= $this->render_partial('index/_task_details') ?>

<section class="contentbox">
    <header>
        <h1>
            <?= strftime(EPP\Helper::timeformat, $task['startdate']) ?> - <?= strftime(EPP\Helper::timeformat, $task['enddate']) ?>
        </h1>
    </header>
    <?= $this->render_partial('index/_status.php') ?>
</section>
