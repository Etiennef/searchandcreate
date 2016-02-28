<?php
include ("../../../inc/includes.php");

$fav = new PluginSearchandcreateFavorite();
$user_id = Session::getLoginUserID();

if(isset($_POST['add_fav'])) {
	$category = new ITILCategory();
	
	foreach($_POST['add_fav'] as $cat_id) {
		if(preg_match("/\d+/", $cat_id) && 
				$category->getFromDB($cat_id) && 
				!$fav->getFromDBByQuery("WHERE `itilcategories_id` = '$cat_id' AND `users_id` = '$user_id'")) {
			$fav->add(array(
				'itilcategories_id' => $cat_id,
				'users_id' => $user_id
				));
		}
	}
}

if(isset($_POST['delete_fav'])) {
	foreach($_POST['delete_fav'] as $cat_id) {
		if(preg_match("/\d+/", $cat_id) && 
				$fav->getFromDBByQuery("WHERE `itilcategories_id` = '$cat_id' AND `users_id` = '$user_id'")) {
			$fav->delete(array('id' => $fav->getId()));
		}
	}
}

Html::back();