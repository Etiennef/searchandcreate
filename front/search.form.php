<?php
/**
 * @author Etiennef
 * Page dédié à la recherche dans les mots-clés des entités
 */
include '../../../inc/includes.php';
Session::checkLoginUser();

if($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
	Html::helpHeader(PluginSearchandcreateSearch::getTypeName());
} else {
	Html::header(PluginSearchandcreateSearch::getTypeName());
}

PluginSearchandcreateSearch::show($options);

if($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
	Html::helpFooter();
} else {
	Html::footer();
}
?>