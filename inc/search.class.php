<?php
/**
 * Catte classe gère la page permettant de rechercher les catégories
 * @author Etiennef
 */
if(! defined('GLPI_ROOT')) {
	die("Sorry. You can't access directly to this file");
}

// class Central
class PluginSearchandcreateSearch extends CommonGLPI {

	static function getTypeName($nb = 0) {
		return __('Search', 'searchandcreate');
	}

	function defineTabs($options = array()) {
		return array(
				__CLASS__.'$1' => 'Selector',
				__CLASS__.'$2' => 'Search',
				__CLASS__.'$3' => 'Favorites',
				);
	}
	
	static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {
		switch($tabnum) {
			case 1 : 
				self::showSelector();
				break;
			case 2 :
				self::showSearch();
				break;
			case 3 :
				self::showFavorites();
				break;	
		}
		return false;
	}
	
	static function showSelector() {
		echo '<table class="tab_cadre_central">';
		echo '<tr><th class="headerRow">' . __('Select where to create ticket', 'searchandcreate') . '</th></tr>';
		
		echo '<tr><td>' . __('Entity') . '</td><td>';
		$entities_rand = Entity::dropdown(array(
				'name' => 'entities_id',
				'display_emptychoice' => false
				));
		echo '</td></tr>';
		
		echo '<tr><td>' . __('Type') . '</td><td>';
		$type_rand = Ticket::dropdownType('type', array('toadd'=>array(0=>'-----')));
		echo '</td></tr>';
		
		echo '<tr><td>' . __('Category') . '</td><td><span id="zone_itilcategories">';
		$itilcategories_rand = ITILCategory::dropdown(array(
				'name' => 'itilcategories_id',
				'display_emptychoice' => true
		));
		echo '</span></td></tr>';
		
		Ajax::updateItemOnSelectEvent(array(
				'entities_id' => 'dropdown_entities_id'.$entities_rand,
				'type' => 'dropdown_type'.$type_rand
			), 
			'zone_itilcategories', 
			$CFG_GLPI["root_doc"]."plugin/searchandcreate/ajax/dropdownTicketCategories.php", 
			array(
				'entity_restrict' => '__VALUEentities_id__',
				'type' => '__VALUEtype__',
				'value' => '__VALUE__'
			)
		);
		
		echo '<tr><td class="center"><a id="searchandcreate_selector_button" class="vsubmit"></td></tr>';
		?>
		
<script>
Ext.onReady(function() {
	document.querySelector('#searchandcreate_selector_button').onclick = function () {
		var entities = document.querySelector('select[id="<?php echo 'dropdown_entities_id'.$entities_rand;?>"');
		var type = document.querySelector('select[id="<?php echo 'dropdown_type'.$type_rand;?>"');
		var categories = document.querySelector('select[id="<?php echo 'dropdown_itilcategories_id'.$itilcategories_rand;?>"');
	
		if(entities && type && category) {
			location.href = '<?php echo $CFG_GLPI["root_doc"]?>'+
				+'/index.php?redirect=plugin_smartredirect_create_1'+
				//'p' + TODO select profile
				'e' + entities.value
				't' + type.value
				'c' + category.value;
		}
	}
}
</script>
		
		<?php 
		echo '</table>';
	}
	
	
	static function showSearch() {
		$entitiesData = self::getEntitiesData();
		$categoriesData = self::getCategoriesData();
		
		
		?>
		<table class="tab_cadre_central"><tr><td class="central">
			<table class="tab_cadrehov" style="width: 90%">
				<tr class="headerRow">
					<th colspan="3"><?php echo __('Search criteria', 'searchandcreate') ?></th>
				</tr>
	
				<tr class="tab_bg_1">
					<th rowspan="2"><?php echo __('Scope', 'searchandcreate') ?></th>
					<td><input type="radio" name="plugin_searchandcreate_entityscope"
						id="plugin_searchandcreate_entityscope_all"
						onchange="plugin_searchandcreate.refreshView()" checked><?php echo __('Display all entities', 'searchandcreate')?>
					</td>
					<td><input type="radio" name="plugin_searchandcreate_entityscope"
						id="plugin_searchandcreate_entityscope_active"
						onchange="plugin_searchandcreate.refreshView()"><?php echo __('Only active entities', 'searchandcreate')?>
					</td>
				</tr>
	
				<tr class="tab_bg_1">
					<td colspan="2"><input type="checkbox"
						id="plugin_searchandcreate_onlyfavorites"
						onchange="plugin_searchandcreate.refreshView()"><?php echo __('Restrict to favorites', 'searchandcreate')?>
					</td>
					<script>console.log('toto'); Ext.get('plugin_searchandcreate_onlyfavorites').addListener('change',function(ev, el){console.log(Ext.get('plugin_searchandcreate_onlyfavorites').getValue());});</script>
					
				</tr>
	
				<tr class="tab_bg_1">
					<th><?php echo __('Type') ?></th>
					<td><input type="checkbox"
						id="plugin_searchandcreate_type_isIncident"
						onchange="plugin_searchandcreate.refreshView()"><?php echo __('Incident')?>
					</td>
					<td><input type="checkbox"
						id="plugin_searchandcreate_type_isRequest"
						onchange="plugin_searchandcreate.refreshView()"><?php echo __('Request')?>
					</td>
				</tr>
	
				<tr class="tab_bg_1">
					<th><?php echo __('Keywords', 'searchandcreate') ?></th>
					<td colspan="2"><input id="plugin_searchandcreate_searchField" type="text" style="width: 100%"></td>
				</tr>
			</table>
			</td></tr>
			<tr><td class="central">
				<table class="tab_cadrehov" style="width: 90%">
					<tr class="headerRow">
						<th><?php echo __('Category') ?></th>
						<th><?php echo __('Entity') ?></th>
						<th><?php echo __('Description') ?></th>
						<th colspan="2"><?php echo __('Create', 'searchandcreate') ?></th>
						<th><?php echo __('Favorite', 'searchandcreate') ?></th>
					</tr>
					<tbody id="plugin_searchandcreate_tbody"></tbody>
				</table>
			</td></tr>
		</table>
		
		<?php 
		include '../scripts/search.js.php';
		
	}
	
	
	static function getSearchData() {
		global $DB;
		
		$user_id = Session::getLoginUserID();
		$entities_table = Entity::getTable();
		$keywords_table = PluginSearchandcreateKeyword::getTable();
		$category_table = ITILCategory::getTable();
		$favorites_table = PluginSearchandcreateFavorite::getTable();
		
		// Make request to get all categories in all entities reachable with this profile
		$reachableEntities = array();
		foreach ($_SESSION['glpiactiveprofile']['entities'] as $key => $val) {
			$reachableEntities[$val['id']] = $val['id'];
			if ($val['is_recursive']) {
				$entities = getSonsOf("glpi_entities", $val['id']);
				if (count($entities)) {
					foreach ($entities as $key2 => $val2) {
						$reachableEntities[$key2] = $key2;
					}
				}
			}
		}
		$reachableEntities = "'".implode("', '", $reachableEntities)."'";
		$reachableEntitiesFilter = "`$entities_table`.`id` IN ($reachableEntities)";
		
		$query = "SELECT
			`$entities_table`.`id` AS `id`,
			`$entities_table`.`name` AS `name`,
			`$entities_table`.`comment` AS `comment`,
			`$keywords_table`.`keywords` AS `keywords`
		FROM `$entities_table`
		LEFT JOIN `$keywords_table`
		ON `$keywords_table`.`item_type` = 'Entity'
		AND `$keywords_table`.`item_id` = `$entities_table`.`id`
		WHERE  $reachableEntitiesFilter";
		
		$entities_data = array ();
		if ($result = $DB->query ( $query )) {
			if ($DB->numrows ( $result )) {
				while ( $line = $DB->fetch_assoc ( $result ) ) {
					$entities_data [$line ['id']] = $line;
				}
			}
		}
		
		// Make request to filter category on simplified interface if necessary
		if($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
			$helpdeskFilter = " AND `$category_table`.`is_helpdeskvisible`='1'";
		} else {
			$helpdeskFilter = "";
		}
		
		$query = "SELECT
			`$category_table`.`id` AS `id`,
			`$category_table`.`name` AS `name`,
			`$entities_table`.`id` AS `entities_id`,
			`$category_table`.`comment` AS `comment`,
			`$category_table`.`is_incident` AS `is_incident`,
			`$category_table`.`is_request` AS `is_request`,
			`$keywords_table`.`keywords` AS `keywords`,
			`$favorites_table`.`id` AS `favorites_id`
		FROM `$category_table`
		LEFT JOIN `$entities_table`
			ON `$category_table`.`entities_id` = `$entities_table`.`id`
		LEFT JOIN `$keywords_table`
			ON `$keywords_table`.`item_type` = 'ITILCategory'
				AND `$keywords_table`.`item_id` = `$category_table`.`id`
		LEFT JOIN `$favorites_table`
			ON `$favorites_table`.`itilcategories_id` = `$category_table`.`id`
				AND `$favorites_table`.`users_id` = $user_id
		WHERE  $reachableEntitiesFilter
			$helpdeskFilter
			AND (`$category_table`.`is_incident`=1 OR `$category_table`.`is_request`=1)
		ORDER BY `$category_table`.`name`";
		
		$categories_data = array ();
		if ($result = $DB->query ( $query )) {
			if ($DB->numrows ( $result )) {
				while ( $line = $DB->fetch_assoc ( $result ) ) {
					$categories_data [$line ['id']] = $line;
				}
			}
		}
		
		return array(
				'entities' => $entities_data,
				'categories' => $categories_data
		);
	}
}
?>































