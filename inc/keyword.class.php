<?php
/**
 * @author Etiennef
 *
 */
if(! defined('GLPI_ROOT')) {
	die('Sorry. You can\'t access directly to this file');
}


class PluginSearchandcreateKeyword extends CommonDBTM {
	static $KEYWORDABLE_ITEMS = array(
		// type => droit associé
		'Entity' => 'entity', 
		'ITILCategory' => 'entity_dropdown'
	);
	
	static function canView() {
		foreach(self::$KEYWORDABLE_ITEMS as $type => $right) {
			if(Session::haveRight($right, 'r'))
				return true;
		}
		return false;
	}
	static function canCreate() {
		foreach(self::$KEYWORDABLE_ITEMS as $type => $right) {
			if(Session::haveRight($right, 'w'))
				return true;
		}
		return false;
	}
	function canViewItem() {
		return array_key_exists($this->getField('item_type'), self::$KEYWORDABLE_ITEMS) &&
				(new $this->fields['item_type']())->can($this->fields['item_id'], 'r');
	}
	function canCreateItem() {
		return array_key_exists($this->getField('item_type'), self::$KEYWORDABLE_ITEMS) &&
				(new $this->fields['item_type']())->can($this->fields['item_id'], 'w');
	}
	
	function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
		if(array_key_exists($item->getType(), self::$KEYWORDABLE_ITEMS) &&
				$item->can($item->getID(), 'r')) {
			return __('Keywords', 'searchandcreate');
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
			echo '<textarea name="keywords" cols="120" rows="6">' . Html::cleanPostForTextArea($values['keywords']) . '</textarea>';
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
			echo '<input type="hidden" name="item_type" value=' . $item_type . '>';
			echo '<input type="hidden" name="item_id" value=' . $item_id . '>';
			echo '<input type="submit" name="update"' . _sx ( 'button', 'Upgrade' ) . ' class="submit">';
			echo '</td></tr>';
		}
		echo '</table>';
		Html::closeForm ();
	}
}

