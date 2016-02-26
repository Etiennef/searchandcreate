<?php
if(! defined('GLPI_ROOT')) {
	die("Sorry. You can't access directly to this file");
}
class PluginSearchandcreateFavorite extends CommonDBTM {
	// Give user the right to manage his own
	function isPrivate() {
		return true;
	}
}
?>