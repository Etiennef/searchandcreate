<?php

/**
 * Fonction d'installation du plugin
 * @return boolean
 */
function plugin_searchandcreate_install() {
	
	include 'inc/config.class.php';
	PluginSearchandcreateConfig::install();
	
	if (! TableExists ( "glpi_plugin_searchandcreate_keywords" )) {
		/* table pour stoquer les mots-clés
			id
			item_type type d'objet auquel correspondent les mots-clés 
			item_id id de l'item associé aux mots-clés
			keywords mots-clés
		*/
		
		$query = "CREATE TABLE `glpi_plugin_searchandcreate_keywords` (
					`id` int(11) NOT NULL auto_increment,
                    `item_type` varchar(250) NOT NULL collate utf8_unicode_ci,
                    `item_id` int(11) NOT NULL default '0',
                    `keywords` varchar(20000) collate utf8_unicode_ci default '',
                    PRIMARY KEY  (`id`)
                  ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
		
		$DB->query ( $query ) or die ( $DB->error () );
	}
	
	if (! TableExists ( "glpi_plugin_searchandcreate_favorites" )) {
		/* table pour stoquer les favoris
			 id
			 user_id utilisateur auquel appartient le favori
			 item_type type d'objet auquel correspondent les mots-clés
			 item_id id de l'item associé aux mots-clés
			 order classement du favori
		 */
	
		$query = "CREATE TABLE `glpi_plugin_searchandcreate_favorites` (
					`id` int(11) NOT NULL auto_increment,
                    `users_id` int(11) NOT NULL COMMENT 'RELATION to glpi_users (id)',
                    `itilcategories_id` int(11) NOT NULL,
                    PRIMARY KEY  (`id`)
                  ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
	
		$DB->query ( $query ) or die ( $DB->error () );
	}
	
	return true;
}


/**
 * Fonction de désinstallation du plugin
 * @return boolean
 */
function plugin_searchandcreate_uninstall()
{
	include 'inc/config.class.php';
	PluginSearchandcreateConfig::install();
	
	global $DB;

	$tables = array(
			"glpi_plugin_searchandcreate_keywords",
			"glpi_plugin_searchandcreate_favorites"
	);

	foreach($tables as $table) {
		$DB->query("DROP TABLE IF EXISTS `$table`;");
	}
	
	return true;
}