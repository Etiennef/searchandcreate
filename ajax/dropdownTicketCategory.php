<?php
if (strpos($_SERVER['PHP_SELF'],"dropdownTicketCategories.php")) {
   include ('../inc/includes.php');
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
}
if (!defined('GLPI_ROOT')) {
   die("Can not acces directly to this file");
}

$opt = array('entity' => $_POST['entity']);


if ($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
	$opt['condition'] = "`is_helpdeskvisible`='1' AND ";
} else {
	$opt['condition'] = '';
}

$currentcateg = new ItilCategory();
$currentcateg->getFromDB($_POST['value']);

switch($_POST['type']) {
	case -1 : 
		echo __('You have to select a type before chosing category', 'searchandcreate');
		break;
	case Ticket::INCIDENT_TYPE :
		$cond = 'is_incident';
		break;
	case Ticket::DEMAND_TYPE :
		$cond = 'is_request';
		break;
	default : die();
}

if ($_POST["type"] != -1) {
	$opt['condition'] = "`$cond`='1'";
	if ($currentcateg->getField($cond) == 1) {
		$opt['value'] = $_POST['value'];
	}
	if ($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
		$opt['condition'] .= " AND `is_helpdeskvisible`='1'";
	}
	
	ItilCategory::dropdown($opt);
}

?>