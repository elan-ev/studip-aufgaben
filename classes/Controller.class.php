<?php

/**
 * EPPluginStudipController - pimp the controller to work neatly in plugins
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of
 * the License, or (at your option) any later version.
 *
 * @author      Till Glöggler <tgloeggl@uos.de>
 * @author      Marcus Lunzenauer <mlunzena@uos.de>
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL version 2
 * @category    Stud.IP
 */


namespace EPP;

use Request;
use RuntimeException;
use Trails_Flash;


class Controller extends \PluginController
{
    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        $this->flash = Trails_Flash::instance();

        // Localization
        $this->_ = function ($string) {
            return call_user_func_array(
                [$this->plugin, '_'],
                func_get_args()
            );
        };

        $this->_n = function ($string0, $tring1, $n) {
            return call_user_func_array(
                [$this->plugin, '_n'],
                func_get_args()
            );
        };
    }

    /**
     * Intercepts all non-resolvable method calls in order to correctly handle
     * calls to _ and _n.
     *
     * @param string $method
     * @param array $arguments
     * @return mixed
     * @throws RuntimeException when method is not found
     */
    public function __call($method, $arguments)
    {
        if (isset($this->_template_variables[$method]) && is_callable($this->_template_variables[$method])) {
            return call_user_func_array($this->_template_variables[$method], $arguments);
        }
        return parent::__call($method, $arguments);
    }

    /**
     * Return the Content-Type of the HTTP request.
     *
     * @return string the content type
     */
    public function contentType()
    {
        if (preg_match('/^([^,\;]*)/', @$_SERVER['CONTENT_TYPE'], $matches)) {
            return strtolower(trim($matches[1]));
        }
        return null;
    }

    public function render_template($template_name, $layout = null)
    {
        $layout_file = Request::isXhr()
            ? 'layouts/dialog.php'
            : 'layouts/base.php';
        $layout      = $GLOBALS['template_factory']->open($layout_file);

        parent::render_template($template_name, $layout);
    }
}
