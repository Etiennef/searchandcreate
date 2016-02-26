<?php
/**
 * @author Etiennef
 *
 */
if(! defined('GLPI_ROOT')) {
	die('Sorry. You can\'t access directly to this file');
}


class PluginSearchandcreateKeyword extends CommonDBTM {
	const KEYWORDABLE_ITEMS = array('Entity', 'ITILCategory');
	
	static function canView() {
		foreach(KEYWORDABLE_ITEMS as $type) {
			if(Session::hasRight($type, 'r'))
				return true;
		}
		return false;
	}
	static function canCreate() {
		foreach(KEYWORDABLE_ITEMS as $type) {
			if(Session::hasRight($type, 'w'))
				return true;
		}
		return false;
	}
	function canViewItem() {
		return in_array($this->fields['item_type'], self::KEYWORDABLE_ITEMS) &&
				new($this->fields['item_type'])()->can($this->fields['item_id'], 'r'));
	}
	function canCreateItem() {
		return in_array($this->fields['item_type'], self::KEYWORDABLE_ITEMS) &&
				new($this->fields['item_type'])()->can($this->fields['item_id'], 'w'));
	}
	
	function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
		if(in_array($item->getType(), KEYWORDABLE_ITEMS) &&
				$item->can($item->getID(), 'r')) {
			return __('Search and create', 'searchandcreate');
		}
		return '';
	}
	
	static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
		$instance = new self();
		$canedit = $item->can($item->getID(), 'w');
		
		$item_type = $item->getType();
		$item_id = $item->getField('id');
		
		if ($instance->getFromDBByQuery("WHERE `item_type`='$item_type' AND `item_id`='$item_id'")) {
			$values = $instance->fields;
		} else {
			$values = array (
					'id' => -1,
					'item_type' => $item_type,
					'item_id' => $item_id,
					'keywords' => '' 
			);
		}
		
		echo '<form action="' . self::getFormURL() . '" method="post">';
		echo '<table class="tab_cadre_fixe">';
		
		echo '<tr><th class="center b" colspan="2">' . __('Keywords for this item', 'searchandcreate') . '</th></tr>';
		
		echo '<tr><td style="text-align:center" colspan="2">';
		if($canedit) {
			echo '<textarea name="keywords" cols="120" rows="6">' . Html::cleanTextForArea($values['keywords']) . '</textarea>';
		} else {
			if($values['keywords'] == '') {
				echo '<strong>' . __('No keywords are defined for this item', 'searchandcreate') . '</strong>';
			} else {
				echo $values['keywords'];
			}
		}
		
		echo '</td></tr>';
		
		if ($canedit) {
			echo '<tr><td class="center" colspan="2">';
			echo '<input type="hidden" name="id" value=' . $values['id'] . '>';
			if($values['id'] == -1) {
				echo '<input type="hidden" name="item_type" value=' . $item_type . '>';
				echo '<input type="hidden" name="item_id" value=' . $item_id . '>';
			}
			echo '<input type="submit" name="update"' . _sx ( 'button', 'Upgrade' ) . ' class="submit">';
			echo '</td></tr>';
		}
		echo '</table>';
		Html::closeForm ();
	}
}

