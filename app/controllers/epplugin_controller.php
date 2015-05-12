<?php
/**
 * EPPluginStudipController - pimp the controller to work neatly in plugins
 *
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

require_once 'app/controllers/studip_controller.php';

class EPPluginStudipController extends StudipController
{
    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        $this->plugin = $this->dispatcher->plugin;

        // default timeformat for all dates
        $this->timeformat = '%d.%m.%Y, %R';

        $this->flash = Trails_Flash::instance();

        PageLayout::addStylesheet($this->getPluginURL() . '/assets/stylesheets/epp.css');

        PageLayout::addScript($this->getPluginURL() . '/assets/javascripts/epp.js');
        PageLayout::addScript($this->getPluginURL() . '/assets/javascripts/jquery.ui.widget.js');
        PageLayout::addScript($this->getPluginURL() . '/assets/javascripts/jquery.iframe-transport.js');
        PageLayout::addScript($this->getPluginURL() . '/assets/javascripts/jquery.fileupload.js');
        PageLayout::addScript($this->getPluginURL() . '/assets/javascripts/jquery-ui-timepicker-1.1.1.js');

        PageLayout::addStylesheet($this->getPluginURL().'/assets/javascripts/vendor/select2-3.5.1/select2.min.css');
        PageLayout::addScript($this->getPluginURL().'/assets/javascripts/vendor/select2-3.5.1/select2.min.js');
        PageLayout::addScript($this->getPluginURL().'/assets/javascripts/vendor/select2-3.5.1/select2_locale_de.js');
    }

    /**
     * a wrapper to allow retrieving the plugin-url in the controllers
     * 
     * @return string 
     */
    function getPluginURL()
    {
        return $GLOBALS['epplugin_path'];
    }

    /**
     * overwrite the default url_for to enable to it work in plugins
     * 
     * @param type $to
     * @return type
     */
    function url_for($to)
    {
        $args = func_get_args();

        // find params
        $params = array();
        if (is_array(end($args))) {
            $params = array_pop($args);
        }

        // urlencode all but the first argument
        $args = array_map('urlencode', $args);
        $args[0] = $to;

        return PluginEngine::getURL($this->dispatcher->plugin, $params, join('/', $args));
    }

    /**
     * Throw an array at this function and it will call render_text to output
     * the json-version of that array while setting an appropriate http-header
     * 
     * @param array $data
     */
    function render_json($data)
    {
        $this->response->add_header('Content-Type', 'application/json');
        $this->render_text(json_encode($data));
    }


    /**
     * Return the Content-Type of the HTTP request.
     * 
     * @return string the content type
     */
    function contentType()
    {
        if (preg_match('/^([^,\;]*)/', @$_SERVER['CONTENT_TYPE'], $matches)) {
            return strtolower(trim($matches[1]));
        }
        return null;
    }
    
    /**
     * checks all possible locations of a valid seminar_id and retuns it if found
     * 
     * @return string the found seminar_id
     */
    public function getSeminarId()
    {
        if (!Request::option('cid')) {
            if ($GLOBALS['SessionSeminar']) {
                URLHelper::bindLinkParam('cid', $GLOBALS['SessionSeminar']);
                return $GLOBALS['SessionSeminar'];
            }

            return false;
        }

        return Request::option('cid');
    }
}