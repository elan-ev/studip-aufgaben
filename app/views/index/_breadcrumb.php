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

$breadcrumb = array();

foreach ($path as $step) :
    if (is_array($step)) :
        $breadcrumb[] = '<a href="'. $controller->url_for($step[0]) .'">'
                      . htmlReady($step[1]) . '</a>';
    else :
        switch ($step) :
            case 'overview':
                $breadcrumb[] = '<a href="'. $controller->url_for('index/index') .'">'
                              . _('Übersicht') . '</a>';
            break;
            case 'view_dozent':
                $breadcrumb[] = $step;
            break;

            default:
                $breadcrumb[] = htmlReady($step);
            break;
       endswitch;
   endif;
endforeach; ?>

<span><?= _('Sie befinden sich hier:') ?></span>
<span><?= implode(' &gt; ', $breadcrumb) ?></span>
