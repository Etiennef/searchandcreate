<?php
/**
 * Gère les appels de l'onglet favoris :
 * 	- réccupération des données
 *  - modifications (ordre, ajout, suppression)
 * @author Etiennef
*/

include ('../../../inc/includes.php');
include ('../inc/favorite.class.php');
Session::checkLoginUser();
header("Content-Type: application/json; charset=UTF-8");
Html::header_nocache ();

$favorite = new PluginSearchandcreateFavorite();

switch($_POST['action']) {
	case 'add' : 
		if($favorite->can(-1, 'w', $_POST)) {
			$success = $favorite->add($_POST);
			$ret = $favorite->getID();
		}
		break;
	case 'delete' :
		if($favorite->can($_POST['id'], 'd')) {
			$success = $favorite->delete($_POST);
			$ret = '';
		}
		break;
}

if(!$success) {
	http_response_code(400);
} else {
	echo $ret;
}

?>

