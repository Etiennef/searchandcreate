<?php

/**
 * Fonction de définition de la version du plugin
 * @return array description du plugin
 */
function plugin_version_searchandcreate()
{
	return array('name'           => "Search and create",
			'version'        => '0.0.1',
			'author'         => 'Etiennef',
			'license'        => 'GPLv2+',
			'homepage'       => 'https://github.com/Etiennef/searchandcreate',
			'minGlpiVersion' => '0.84');
}

/**
 * Fonction de vérification des prérequis
 * @return boolean le plugin peut s'exécuter sur ce GLPI
 */
function plugin_searchandcreate_check_prerequisites()
{
	if (version_compare(GLPI_VERSION,'0.84.8','lt') || version_compare(GLPI_VERSION,'0.85','ge')) {
		echo __("Plugin has been tested only for GLPI 0.84.8", 'searchandcreate');
		return false;
	}
	
	if(!(new Plugin())->isActivated('configmanager')) {
		echo __("Plugin requires ConfigManager 1.0", 'searchandcreate');
		return false;
	}

	if(!(new Plugin())->isActivated('smartredirect')) {
		echo __("Plugin requires Smartredirect 1.0", 'searchandcreate');
		return false;
	}
	// ajouter éventuellement la présence d'autres plugins
	
	return true;
}


/**
 * Fonction de vérification de la configuration initiale
 * @param type $verbose
 * @return boolean la config est faite
 */
function plugin_searchandcreate_check_config($verbose=false)
{
	if (true) { //TODO faire un vrai test
		return true;
	}
	if ($verbose) {
		echo 'Installed / not configured';
	}
	return false;
}


/**
 * Fonction d'initialisation du plugin.
 * @global array $PLUGIN_HOOKS
 */
function plugin_init_searchandcreate()
{
	global $PLUGIN_HOOKS;

	$PLUGIN_HOOKS['csrf_compliant']['searchandcreate'] = true;
	
	Plugin::registerClass('PluginSearchandcreateConfig', array('addtabon' => array(
			'User',
			'Preference',
			'Config'
		)));
	if((new Plugin())->isActivated('searchandcreate')) {
		$PLUGIN_HOOKS['config_page']['searchandcreate'] = "../../front/config.form.php?forcetab=" . urlencode('PluginSearchandcreateConfig$1');
	}   
	
	Plugin::registerClass('PluginSearchandcreateSearch');
	Plugin::registerClass('PluginSearchandcreateFavorite');
	
	// Ajoute l'onget dans les Entités et catégories
	Plugin::registerClass('PluginSearchandcreateKeyword', array(
		'addtabon' => array('Entity'),
		'addtabon' => array('ITILCategory')));
	
	// Ajout du menu
	$PLUGIN_HOOKS['menu_entry']['searchandcreate'] = 'front/search.php';
	$PLUGIN_HOOKS['helpdesk_menu_entry']['searchandcreate'] = '/front/search.php';
}









