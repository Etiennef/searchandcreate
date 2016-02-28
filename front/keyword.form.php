<?php

include ("../../../inc/includes.php");

if (isset($_POST['update']) && 
		isset($_POST['id']) && 
		isset($_POST['item_type']) && 
		isset($_POST['item_id'])) {
	
	// Create or update
	$keyword = new PluginSearchandcreateKeyword();
	if($keyword->can($_POST['id'], 'w', $_POST)) {
		if($_POST['id'] == -1) {
			unset($_POST['id']);
			$keyword->add($_POST);
		} else {
			$keyword->update(array(
				'id' => $_POST['id'],
				'keywords' => $_POST['keywords']
			));
		}
		Html::back();
	} else {
		Html::displayRightError();
	}
}
Html::back();