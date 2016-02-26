<?php
if (!defined('GLPI_ROOT')) {
	die("Sorry. You can't access directly to this file");
}

class PluginSearchandcreateConfig  extends PluginConfigmanagerConfig {
	
	static function makeConfigParams() {
		return array(
			'_title' => array(
				'type' => 'readonly text',
				'text' => self::makeHeaderLine(__('Configuration for Searchandcreate', 'serachandcreate')),
				'types' => array(self::TYPE_USER, self::TYPE_GLOBAL),
			),
		);
	}
	
	
}