<?php

include ("../../../inc/includes.php");

if (isset($_POST['update']) && 
		isset($_POST['id']) && 
		isset($_POST['item_type']) && 
		isset($_POST['item_id'])) {
	
	// Check rights
	if(!in_array($_POST['item_type'], PluginSearchandcreateKeyword::KEYWORDABLE_ITEMS)) {
		Html::displayRightError();
	}
	$item = new $_POST['item_type']();
	if(!$item->can($_POST['item_id'], 'w')) {
		Html::displayRightError();
	}
	
	// Create or update
	$keyword = new PluginSearchandcreateKeyword();
	if($keyword->can($_POST['id'], 'w', $_POST)) {
		if($_POST['id'] == -1) {
			$keyword->add($_POST);
		} else {
			$keyword->update($_POST);
		}
		Html::back();
	} else {
		Html::displayRightError();
	}
}
Html::back();