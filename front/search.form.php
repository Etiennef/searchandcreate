<?php
/**
 * @author Etiennef
 * Page dédiée à la recherche dans les mots-clés des entités
 */
include '../../../inc/includes.php';
Session::checkLoginUser();

if(isset($_POST['newprofile'])) {
	Session::changeProfile($_POST['newprofile']);
	Html::back();
}

if($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
	Html::helpHeader(PluginSearchandcreateSearch::getTypeName());
} else {
	Html::header(PluginSearchandcreateSearch::getTypeName());
}

PluginSearchandcreateSearch::showCommonHTML();

(new PluginSearchandcreateSearch())->show();

if($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
	Html::helpFooter();
} else {
	Html::footer();
}
?>