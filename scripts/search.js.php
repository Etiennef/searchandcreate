<?php 
/**
* @author Etiennef
* Script permettant de rechercher les cat�gories
*/
global $GLPI_CFG;


$activeEntities = json_encode($_SESSION['glpiactiveentities'], JSON_FORCE_OBJECT);
$entitiesData = json_encode(self::getEntitiesData(), JSON_FORCE_OBJECT);
$categoriesData = json_encode(self::getCategoriesData(), JSON_FORCE_OBJECT);

$translations = array(
	'root_doc' => $GLPI_CFG['root_doc'],
	'Incident' => __('Incident'),
	'Request' => __('Request'),
	'Remove_from_favorites' => __('Remove from favorites', 'searchandcreate'),
	'Add_to_favorites' => __('Add to favorites', 'searchandcreate'),
	'category_not_allow_incident' => addslashes(Html::cleanInputText(__('This category does not allow incident creation', 'searchandcreate'))),
	'category_not_allow_request' => addslashes(Html::cleanInputText(__('This category does not allow request creation', 'searchandcreate'))),
	'nb_tickets' => __('(000 tickets)', 'searchandcreate')
);

echo <<<JS
<script type="text/javascript">

/* global Ext */
Ext.onReady(function () {
	'use strict';
	
	var entitiesData = $entitiesData;
	var categoriesData = $categoriesData;
	var activeEntities = $activeEntities;
	
	Object.keys(entitiesData).forEach(function(id) {
		entitiesData[id] = {
			id : id,
			name : entitiesData[id].name,
			comment : entitiesData[id].comment || '',
			keywords : entitiesData[id].keywords || '',
		}
	});
	
	Object.keys(categoriesData).forEach(function(id) {
		categoriesData[id] = {
			id : id,
			name : categoriesData[id].name,
			comment : categoriesData[id].comment || '',
			keywords : categoriesData[id].keywords || '',
			entities_id : categoriesData[id].entities_id,
			is_incident : categoriesData[id].is_incident === '1',
			is_request : categoriesData[id].is_request === '1',
			is_favorite : categoriesData[id].favorites_id,
			is_favorite_onserver : categoriesData[id].favorites_id,
			ticket_cnt : categoriesData[id].ticket_cnt
		}
	});
					
	
	// peut être 'search' ou 'mostUsed'
	var activeTab;
	
	
	// élements de DOM communs
	var formSaveFavorites = document.querySelector('form[name="$saveFormName"]');
	var zoneSaveFavorites = document.querySelector('#$zoneSaveFavoritesId');
	// élements de DOM pour l'onglet search
	var tbodyResultsSearch, tableCriteria, searchInput, incidentCheckbox, requestCheckbox, scopeRadio, favoritesOnlyCheckbox;
	// élements de DOM pour l'onglet mostused
	var tbodyResultsMu;
	
	/**
	 * 
	 */
	window.sc_onSearchTabLoad = function(ids) {
		document.querySelector('[id*="PluginSearchandcreateSearch$2"]').addEventListener('click', onSearchTabSelect);
		
		tbodyResultsSearch = document.querySelector('#'+ids.tbodyResultsId);
		tableCriteria = document.querySelector('#'+ids.tableCriteriaId);
		
		searchInput = tableCriteria.querySelector('input[name="pattern"]');
		incidentCheckbox = tableCriteria.querySelector('input[name="is_incident"]');
		requestCheckbox = tableCriteria.querySelector('input[name="is_request"]');
		scopeRadio = tableCriteria.querySelector('input[name="entityscope"][value="active"]');
		var scopeRadio2 = tableCriteria.querySelector('input[name="entityscope"][value="all"]');
		favoritesOnlyCheckbox = tableCriteria.querySelector('input[name="only_favorites"]');
		
		Ext.get(searchInput).addListener('keypress', refreshViewForSearchTab, {}, {buffer:300});
		incidentCheckbox.addEventListener('change', refreshViewForSearchTab);
		requestCheckbox.addEventListener('change', refreshViewForSearchTab);
		scopeRadio.addEventListener('change', refreshViewForSearchTab);
		scopeRadio2.addEventListener('change', refreshViewForSearchTab);
		favoritesOnlyCheckbox.addEventListener('change', refreshViewForSearchTab);
		
		onSearchTabSelect();
	}
	
	window.sc_onMostUsedTabLoad = function(ids) {
		document.querySelector('[id*="PluginSearchandcreateSearch$3"]').addEventListener('click', onMostUsedTabSelect);
		
		tbodyResultsMu = document.querySelector('#'+ids.tbodyResultsId);
		//tableOptions = document.querySelector('#'+ids.tableOptionsId);
		
		onMostUsedTabSelect();
	}
	
	function onSearchTabSelect() {
		activeTab = 'search';
		refreshViewForSearchTab();
	}
	
	function onMostUsedTabSelect() {
		activeTab = 'mostUsed';
		refreshView();
	}
	
	function refreshView() {
		if(activeTab === 'search') {
			refreshViewForSearchTab();
		} else if(activeTab === 'mostUsed') {
			refreshViewForMostUsedTab();
		}
	}
	
	function refreshViewForSearchTab() {
		// Prepare data into a regexp
		var search = searchInput.value
			.split(' ')
			.filter(function(word) {return word!=='';})
			.map(function(word){return word.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');})
			.join('|');
		search = search?new RegExp(search, 'ig'):undefined;
		
		// Vide le tableau de résultats
		while (tbodyResultsSearch.firstChild) {
			tbodyResultsSearch.removeChild(tbodyResultsSearch.firstChild);
		}
		
		
		// Calcule le score pour les entités
		Object.keys(entitiesData).forEach(function(id) {
			calculateSearchScore(entitiesData[id], search);
		});
		
		Object.keys(categoriesData)
			.map(function(id) {
				return categoriesData[id];
			})
			
		// Filtre les catégories masquées
			.filter(function(category) {
				return (!incidentCheckbox.checked || category.is_incident) &&
					(!requestCheckbox.checked || category.is_request) &&
					(!favoritesOnlyCheckbox.checked || category.is_favorite) &&
					(!scopeRadio.checked || (typeof activeEntities[category.entities_id] !== 'undefined'));
			})
		
		// Calcule le score & met à jour le DOM
			.map(function(category) {
				calculateSearchScore(category, search);
				category.processedName = '<strong>'+category.processedName+'</strong>';
				refreshHtml(category)
				return category;
			})
			
		// Trie par score décroissant
			.sort(function(a, b) {
				if (a.score>b.score)
					return -1;
				if (a.score<b.score)
					return 1;
				return 0;
			})
			
		// make html
			.forEach(function(category) {
				tbodyResultsSearch.appendChild(category.dom);
			});
	}
	
	function calculateSearchScore(data, search) {
		data.score = 0;
		data.processedName = data.name;
		data.processedComment = data.comment;

		if(search) {
			if(data.entities_id)
				data.score += entitiesData[data.entities_id].score;
			data.processedName = data.name.replace(search, replaceSearchParts(data, 10));
			data.processedComment = data.comment.replace(search, replaceSearchParts(data, 2));
			data.keywords.replace(search, replaceSearchParts(data, 5));
		}
	}
	
	function replaceSearchParts(item, bonus) {
		return function(found) {
			item.score += bonus*found.length;
			return '<span style="font-weight:bold; color:red">'+found+'</span>';
		};
	}
	
	function refreshViewForMostUsedTab() {
		// Vide le tableau de résultats
		while (tbodyResultsMu.firstChild) {
			tbodyResultsMu.removeChild(tbodyResultsMu.firstChild);
		}
		
		// Règle le nom calculée pour les entités
		Object.keys(entitiesData).forEach(function(id) {
			entitiesData[id].processedName = entitiesData[id].name;
		});
		
		Object.keys(categoriesData)
			.map(function(id) {
				return categoriesData[id];
			})
		
		// Calcule le score & met à jour le DOM
			.map(function(category) {
				category.processedName = '<strong>' + category.name + '</strong> $translations[nb_tickets]'.replace('000', category.ticket_cnt);
				category.processedComment = category.comment;
				refreshHtml(category)
				return category;
			})
			
		// Trie par nb de ticket décroissant
			.sort(function(a, b) {
				if (a.ticket_cnt>b.ticket_cnt)
					return -1;
				if (a.ticket_cnt<b.ticket_cnt)
					return 1;
				return 0;
			})
			
		// make html
			.forEach(function(category) {
				tbodyResultsMu.appendChild(category.dom);
			});
	}
	
	
	var favoritesToadd = {};
	var favoritesTodelete = {};
	function toogleFavoriteEventHandler() {
		var categoryId = this.getAttribute('category-id');
		var category = categoriesData[categoryId];
		
		if(category.is_favorite) {
			if(category.is_favorite_onserver) {
				favoritesTodelete[categoryId] = true;
			} else {
				delete favoritesToadd[categoryId];
			}
		} else {
			if(category.is_favorite_onserver) {
				delete favoritesTodelete[categoryId];
			} else {
				favoritesToadd[categoryId] = true;
			}
		}

		category.is_favorite = !category.is_favorite;
		
		refreshHtml(category);

		if(Object.keys(favoritesToadd).length !== 0 || Object.keys(favoritesTodelete).length !== 0) {
			zoneSaveFavorites.removeAttribute('style');
		} else {
			zoneSaveFavorites.setAttribute('style', 'display:none');
		}
	}
	
	formSaveFavorites.addEventListener('submit', prepareFavoriteSubmit);
	function prepareFavoriteSubmit() {
		var formInputs = '';
		Object.keys(favoritesToadd).forEach(function(toadd) {
			formInputs += '<input type="hidden" name="add_fav[]" value="'+toadd+'">';
		});
		Object.keys(favoritesTodelete).forEach(function(toadd) {
			formInputs += '<input type="hidden" name="delete_fav[]" value="'+toadd+'">';
		});
		formSaveFavorites.innerHTML += formInputs;
		return false;
	}
	
	
	
	
	
	/**
	 * Construit ou met à jour l'objet DOM permettant d'afficher la ligne du tableau de recherche associée à une catégorie
	 * @param category la catégorie à afficher
	 * @return rien, fonctionne par effet de bord category.dom contient le résultat
	 */
	function refreshHtml(category) {
		var favImage = category.is_favorite ?
			'<img src="/pics/reset.png" title="$translations[Remove_from_favorites]">' :
			'<img src="/pics/menu_add.png" title="$translations[Add_to_favorites]">' ;
		
		if(typeof category.dom === 'undefined') {
			// création de l'objet DOM
			//TODO ajouter la gestion du profil
			var incidentLink = '$GLPI_CFG[root_doc]/index.php?redirect=plugin_smartredirect_create_1' +
				'e' + category.entities_id +'t1c' + category.id;
			var requestLink = '$GLPI_CFG[root_doc]/index.php?redirect=plugin_smartredirect_create_1' +
				'e' + category.entities_id +'t2c' + category.id;
			
			category.dom = document.createElement('tr');
			category.dom.innerHTML = '<td title="'+category.keywords+'" cs-name="category-name">'+category.processedName+'</td>' +
				'<td title="'+entitiesData[category.entities_id].comment+'" cs-name="entity-name">'+entitiesData[category.entities_id].processedName+'</td>' +
				'<td cs-name="category-comment">'+category.processedComment+'</td>' +
				(category.is_incident ? 
					'<td style="width:5%"><a href="'+incidentLink+'" class="vsubmit">$translations[Incident]</a></td>' :
					'<td title="$translations[category_not_allow_incident]"></td>') +
				(category.is_request ? 
					'<td style="width:5%"><a href="'+requestLink+'" class="vsubmit">$translations[Request]</a></td>' :
					'<td title="$translations[category_not_allow_request]"></td>') +
				'<td style="width:5%; text-align:center"><a class="pointer" category-id="'+category.id+'">'+favImage+'</a></td>';
			category.dom.childNodes[5].firstChild.addEventListener('click', toogleFavoriteEventHandler);
			
		} else {
			// Simple refresh
			category.dom.childNodes[0].innerHTML = category.processedName;
			category.dom.childNodes[1].innerHTML = entitiesData[category.entities_id].processedName;
			category.dom.childNodes[2].innerHTML = category.processedComment;
			category.dom.childNodes[5].firstChild.innerHTML = favImage;
			if(!shouldBePrinted(category)) category.dom.remove();
		}
	}
	
	function shouldBePrinted(category) {
		if(activeTab === 'search' && favoritesOnlyCheckbox && favoritesOnlyCheckbox.checked && !category.is_favorite)
			return false;
		return true;
	}
	
});	


</script>
JS;
?>