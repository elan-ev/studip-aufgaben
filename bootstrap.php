<?
require_once 'app/controllers/studip_controller.php';
StudipAutoloader::addAutoloadPath(__DIR__ . '/classes');
StudipAutoloader::addAutoloadPath(__DIR__ . '/classes', 'EPP');
StudipAutoloader::addAutoloadPath(__DIR__ . '/app/models');
StudipAutoloader::addAutoloadPath(__DIR__ . '/app/models', 'EPP');