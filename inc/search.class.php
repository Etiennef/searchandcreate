<?php
/**
 * Catte classe g�re la page permettant de rechercher les cat�gories
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
				__CLASS__.'$1' => __('Selector', 'searchandcreate'),
				__CLASS__.'$2' => __('Search', 'searchandcreate'),
				__CLASS__.'$3' => __('Most used', 'searchandcreate'),
				'no_all_tab' => true
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
				self::showMostUsed();
				break;	
		}
		return false;
	}
	
	static function showSelector() {
		global $CFG_GLPI;
		
		echo '<table class="tab_cadre_fixe">';
		echo '<tr><th class="headerRow" colspan="2">' . __('Select where to create ticket', 'searchandcreate') . '</th></tr>';
		
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
		echo __('You have to chose type first', 'searchandcreate');
		echo '</span></td></tr>';

		echo '<tr><td class="center" colspan="2"><a id="searchandcreate_selector_button" class="vsubmit">' . __('Create') . '</a></td></tr>';
		
		
		$button_tooltip_valid = addslashes(__('Go to creation form', 'searchandcreate'));
		$button_tooltip_invalid = addslashes(__('You have to chose type and category first', 'searchandcreate'));
		

		//TODO 'p' +  select profile
		echo "
		<script type='text/javascript'>
			Ext.onReady(function() {
				var entities_dd = document.querySelector('#dropdown_entities_id$entities_rand');
				var type_dd = document.querySelector('#dropdown_type$type_rand');
				var createbutton = document.querySelector('#searchandcreate_selector_button');
				var category_dd = null;
						
				entities_dd.addEventListener('change', updateCategories);
				type_dd.addEventListener('change', updateCategories);
				type_dd.addEventListener('change', removeNoType);
				updateLink();
				
				function removeNoType() {
					if(type_dd.value == 0) return;
					notype = type_dd.querySelector('option[value=\"0\"]');
					if(notype) notype.remove();
				}
				
				function updateCategories() {
					if(type_dd.value == 0) return;
					
					Ext.get('zone_itilcategories').load({
						url: '$CFG_GLPI[root_doc]/ajax/dropdownTicketCategories.php',
						scripts: true,
						params: {
							'entity_restrict' : entities_dd.value,
							'type' : type_dd.value,
							'value' : category_dd ? category_dd.value : 0
						},
						callback : function() {
							category_dd = document.querySelector('#zone_itilcategories select[name=\"itilcategories_id\"]');
							updateLink();
							category_dd.onchange = updateLink;
						}
					});
				}

				function updateLink() {
					if(type_dd.value!=0 && category_dd && category_dd.value!=0) {
						createbutton.setAttribute('href', '$CFG_GLPI[root_doc]/index.php?redirect=plugin_smartredirect_create_1' +
								'e' + entities_dd.value +
								't' + type_dd.value +
								'c' + category_dd.value);
						createbutton.removeAttribute('style');
						createbutton.setAttribute('title', '$button_tooltip_valid');
					} else {
						createbutton.removeAttribute('href');
						createbutton.setAttribute('style','cursor: not-allowed; color: #b0b0b0; background-image: linear-gradient(to bottom, #d0d0d0, #c0c0c0); text-shadow: unset; border: 1px solid #b0b0b0;');
						createbutton.setAttribute('title', '$button_tooltip_invalid');
					}
				}
				
			});
		</script>";
		
		echo '</table>';
	}
	
	
	static function showCommonHTML() {
		global $GLPI_CFG;
		
		$zoneSaveFavoritesId = 'zone_save_favorites'.mt_rand();
		$saveFormName = 'form_save_favorites'.mt_rand();
		
		$translations = array(
			'Category' => __('Category'),
			'Entity' => __('Entity'),
			'Description' => __('Description'),
			'Create' => __('Create', 'searchandcreate'),
			'Favorite' => __('Favorite', 'searchandcreate')
		);
		
		//TODO prendre un compte un profil dans la conf
		$profileFormTarget = self::getFormURL();
		$profile_id = 8;
		$changeProfileMessage = __('Use of the profile blabla is recommended to create a category. Click here to change your profile', 'searchandcreate');
		if($_SESSION['glpiactiveprofile']['id'] != $profile_id) {
			echo <<<HTML
			<form method="post" action="$profileFormTarget">
				<table class="tab_cadre_fixe">
					<tr><td class="center">
						<input type="hidden" name="newprofile" value="$profile_id">
						<a class="pointer" onclick="tmp=this; while((tmp=tmp.parentNode).tagName!=='FORM'); tmp.submit();">$changeProfileMessage</a>
					</td></tr>
				</table>
HTML;
			Html::closeForm();
		}
		
		$save_favorites_message = Html::cleanInputText(__('Warning : your change on favorites are not automaticly saved, don\'t forget to save them by clicking this button', 'searchandcreate'));
		$favoritesFormTarget = PluginSearchandcreateFavorite::getFormURL();
		
		echo <<<HTML
		<form name="$saveFormName" method="post" action="$favoritesFormTarget">
			<table class="tab_cadre_fixe" id="$zoneSaveFavoritesId" style="display:none">
				<tr>
					<td style="color:red; font-weight:bold; text-align:center">
						<input type="submit" class="submit" name="save" value="$save_favorites_message" style="font-size:14px;color:#005000; border-color:#00A000; background-image:linear-gradient(to bottom, #4CAF50, #50C050)">
					</td>
				</tr>
			</table>
HTML;
		Html::closeForm();
		
		include GLPI_ROOT . '/plugins/searchandcreate/scripts/search.js.php';
	}
	
	static function showSearch() {
		$translations = array(
			'Search_criteria' => __('Search criteria', 'searchandcreate'),
			'Scope' => __('Scope', 'searchandcreate'),
			'Display_all_entities' => __('Display all entities', 'searchandcreate'),
			'Only_active_entities' => __('Only active entities', 'searchandcreate'),
			'Restrict_to_favorites' => __('Restrict to favorites', 'searchandcreate'),
			'Type' => __('Type'),
			'Incident' => __('Incident'),
			'Request' => __('Request'),
			'Keywords' => __('Keywords', 'searchandcreate')
		);
		
		$tbodyResultsId = 'tbody_results'.mt_rand();
		$tableCriteriaId = 'table_criteria'.mt_rand();
		
		echo <<<HTML
		<table id="$tableCriteriaId" class="tab_cadre_fixe">
			<tr class="headerRow">
				<th colspan="3">$translations[Search_criteria]</th>
			</tr>

			<tr class="tab_bg_1">
				<th rowspan="2">$translations[Scope]</th>
				<td><input type="radio" name="entityscope" value="all" checked>$translations[Display_all_entities]</td>
				<td><input type="radio" name="entityscope" value="active">$translations[Only_active_entities]</td>
			</tr>

			<tr class="tab_bg_1">
				<td colspan="2"><input type="checkbox" name="only_favorites">$translations[Restrict_to_favorites]</td>
			</tr>

			<tr class="tab_bg_1">
				<th>$translations[Type]</th>
				<td><input type="checkbox" name="is_incident">$translations[Incident]</td>
				<td><input type="checkbox" name="is_request">$translations[Request]</td>
			</tr>

			<tr class="tab_bg_1">
				<th>$translations[Keywords]</th>
				<td colspan="2"><input name="pattern" type="text" style="width: 100%"></td>
			</tr>
		</table>
HTML;
		
		
		echo self::showResultsHTMLTable($tbodyResultsId);
		
		echo <<<JS
		<script  type="text/javascript">
			Ext.onReady(function() {
				window.sc_onSearchTabLoad({
					tbodyResultsId: '$tbodyResultsId',
					tableCriteriaId: '$tableCriteriaId'
				});
			});
		</script>
JS;
		
		
	}
	
	static function showMostUsed() {
		$tbodyResultsId = 'tbody_results'.mt_rand();
		
		echo self::showResultsHTMLTable($tbodyResultsId);
		
		echo <<<JS
		<script  type="text/javascript">
			Ext.onReady(function() {
				window.sc_onMostUsedTabLoad({
					tbodyResultsId: '$tbodyResultsId',
				});
			});
		</script>
JS;
	}
	
	static function getEntitiesData() {
		global $DB;
		
		$entities_table = Entity::getTable();
		$keywords_table = PluginSearchandcreateKeyword::getTable();
		
		$reachableEntitiesFilter = self::getReachableEntitiesFilter();
		
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
		
		$entitiesData = array ();
		if ($result = $DB->query ( $query )) {
			if ($DB->numrows ( $result )) {
				while ( $line = $DB->fetch_assoc ( $result ) ) {
					$entitiesData [$line ['id']] = $line;
				}
			}
		}
		
		return $entitiesData;
	}
	
	static function getCategoriesData() {
		global $DB;
		
		$user_id = Session::getLoginUserID();
		$requester_type = CommonITILActor::REQUESTER;
		$entities_table = Entity::getTable();
		$categories_table = ITILCategory::getTable();
		$ticket_table = Ticket::getTable();
		$ticket_user_table = Ticket_User::getTable();
		$keywords_table = PluginSearchandcreateKeyword::getTable();
		$favorites_table = PluginSearchandcreateFavorite::getTable();
	
		$reachableEntitiesFilter = self::getReachableEntitiesFilter();
		
		// Make request to filter category on simplified interface if necessary
		if($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
			$helpdeskFilter = "`$categories_table`.`is_helpdeskvisible`='1'";
		} else {
			$helpdeskFilter = "1";
		}
		
		$categoriesFilter = "$helpdeskFilter AND (`$categories_table`.`is_incident`=1 OR `$categories_table`.`is_request`=1)";
		
		$query = "SELECT
				`$categories_table`.`id` AS `id`,
				`$categories_table`.`name` AS `name`,
				`$entities_table`.`id` AS `entities_id`,
				`$categories_table`.`comment` AS `comment`,
				`$categories_table`.`is_incident` AS `is_incident`,
				`$categories_table`.`is_request` AS `is_request`,
				`$favorites_table`.`id` AS `favorites_id`,
				`$keywords_table`.`keywords` AS `keywords`,
				COUNT(DISTINCT mytickets.id) AS `my_ticket_cnt`,
				COUNT(DISTINCT alltickets.id) AS `ticket_cnt`
			FROM `glpi_itilcategories`
			LEFT JOIN `$entities_table`
				ON `$categories_table`.`entities_id` = `$entities_table`.`id`
			LEFT JOIN `$keywords_table`
				ON `$keywords_table`.`item_type` = 'ITILCategory'
				AND `$keywords_table`.`item_id` = `$categories_table`.`id`
			LEFT JOIN `$favorites_table`
				ON `$favorites_table`.`itilcategories_id` = `$categories_table`.`id`
				AND `$favorites_table`.`users_id` = $user_id
			LEFT JOIN (SELECT 
						`$ticket_table`.`id` AS `id`,
						`$ticket_table`.`itilcategories_id` AS `itilcategories_id`
					FROM `$ticket_table`
					INNER JOIN `$ticket_user_table`
						ON `$ticket_user_table`.`tickets_id` = `$ticket_table`.`id`
						AND `$ticket_user_table`.`type` = '$requester_type'
						AND `$ticket_user_table`.`users_id` = '$user_id')
					AS mytickets
				ON mytickets.`itilcategories_id` = `$categories_table`.`id`
			LEFT JOIN `$ticket_table` AS alltickets
				ON alltickets.`itilcategories_id` = `$categories_table`.`id`
			WHERE $reachableEntitiesFilter AND $categoriesFilter 
			GROUP BY `$categories_table`.`id`
			ORDER BY `ticket_cnt` DESC, `$categories_table`.`name` ASC";
		
		$categoriesData = array ();
		if ($result = $DB->query ( $query )) {
			if ($DB->numrows ( $result )) {
				while ( $line = $DB->fetch_assoc ( $result ) ) {
					$categoriesData [$line ['id']] = $line;
				}
			}
		}
	
		return $categoriesData;
	}
	
	
	static private function getReachableEntitiesFilter() {
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
		
		return "`".Entity::getTable()."`.`id` IN ('".implode("', '", $reachableEntities)."')";
	}
	
	
	private static function showResultsHTMLTable($tbodyResultsId) {
		$translations = array(
			'Category' => __('Category'),
			'Entity' => __('Entity'),
			'Description' => __('Description'),
			'Create' => __('Create', 'searchandcreate'),
			'Favorite' => __('Favorite', 'searchandcreate')
		);
		
		echo <<<HTML
		<table class="tab_cadre_fixe">
			<tr class="headerRow">
				<th>$translations[Category]</th>
				<th>$translations[Entity]</th>
				<th>$translations[Description]</th>
				<th colspan="2">$translations[Create]</th>
				<th>$translations[Favorite]</th>
			</tr>
			<tbody id="$tbodyResultsId"></tbody>
		</table>
HTML;
	}
	
	
	
}
?>































