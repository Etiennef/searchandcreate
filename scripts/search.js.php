<?php 
/**
* @author Etiennef
* Script permettant de rechercher les catégories
*/
?>

<script>
var plugin_searchandcreate = (function() {
	"use strict";
	var entitiesData = <?php echo json_encode(PluginSearchandcreateSearch::getEntitiesData()); ?>;
	var categoriesData = <?php echo json_encode(PluginSearchandcreateSearch::getCategoriesData()); ?>;
	var activeEntities = <?php echo json_encode($_SESSION['glpiactiveentities']); ?>;
	const baseUrl = '/plugins/searchandcreate/';
	const baseId = 'plugin_searchandcreate_';
	const baseNamespace = 'plugin_searchandcreate';
	
	initialize();
	
	return {
		refreshView : refreshView,
		toogleFavorite : toogleFavoriteEventHandler,
	};



	function refreshView() {
		// Prepare data into a regexp
		var search = Ext.get(baseId+'searchField').dom.value
			.split(' ')
			.filter(function(word) {return word!=='';})
			.map(function(word){return word.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');})
			.join('|');
		search = search?new RegExp(search, 'ig'):undefined;

		// Calulate score for entities
		Object.keys(entitiesData)
			.forEach(function(id) {
				entitiesData[id].score = 0;
				if(search) {
					entitiesData[id].processedName = entitiesData[id].name.replace(search, replaceSearchParts(entitiesData[id], 10));
					entitiesData[id].comment.replace(search, replaceSearchParts(entitiesData[id], 2));
					entitiesData[id].keywords.replace(search, replaceSearchParts(entitiesData[id], 5));
				} else {
					entitiesData[id].processedName = entitiesData[id].name;
				}
			});

		var isIncident = Ext.get(baseId+'type_isIncident').dom.checked;
		var isRequest = Ext.get(baseId+'type_isRequest').dom.checked;
		var searchScopeOnlyActive = Ext.get(baseId+'entityscope_active').dom.checked;
		var searchScopeOnlyFavorites = Ext.get(baseId+'onlyfavorites').dom.checked;
		
		Ext.get(baseId+'tbody').update();
		
		Object.keys(categoriesData)
			.map(function(id) {
				return categoriesData[id];
			})
			
		// consider only categories in scope
			.filter(function(category) {
				return (!isIncident || category.is_incident) &&
					(!isRequest || category.is_request) &&
					(!searchScopeOnlyFavorites || category.isFavorite) &&
					(!searchScopeOnlyActive || activeEntities.indexOf(category.entities_id) !== -1);
			})
		
		// Calculate score & displayed info
			.map(function(category) {
				makeHtml(category, search);
				return category;
			})
			
		// sort by desc score
			.sort(function(a, b) {
				if (a.score>b.score)
					return -1;
				if (a.score<b.score)
					return 1;
				return 0;
			})
		
		// add rows in DOM
			.forEach(function(category) {
	            Ext.DomHelper.append(baseId+'tbody', category.html);
			});
	}

	
	/*--------------------------------------------------------------------------------------------------------------
	 * Fonctions intermédiaires
	-------------------------------------------------------------------------------------------------------------- */
	function initialize() {
		entitiesData = prepareRawData(entitiesData, 'id', function(entity) {
			entity.comment = escapeHtml(entity.comment) || '';
			entity.keywords = escapeHtml(entity.keywords) || '';
			return entity;
		});
		
		categoriesData = prepareRawData(categoriesData, 'id', function(category) {
			category.comment = escapeHtml(category.comment) || '';
			category.keywords = escapeHtml(category.keywords) || '';
			category.is_incident = category.is_incident === '1';
			category.is_request = category.is_request === '1';
			category.isFavorite = category.favorites_id !== null;
			return category;
		});

		if(!Array.isArray(activeEntities)) {
			activeEntities = Object.keys(activeEntities);
		}
		
		console.log(activeEntities);

		Ext.onReady(function() {
			Ext.get(baseId+'searchField').addListener('keypress', refreshView, {}, {buffer:300});
			refreshView();
		});
	}

	function replaceSearchParts(item, bonus) {
		return function(found) {
			item.score += bonus*found.length;
			return '<span style="font-weight:bold; color:red">'+found+'</span>';
		};
	}

	function toogleFavoriteEventHandler(categoryId) {
		var category = categoriesData[categoryId];
		console.log(category);

		//Hide button until server respond
		Ext.get(baseId+'toogleFavorite_'+categoryId).setVisible(false);
		
		if(category.isFavorite) {
			Ext.Ajax.request({
				url : baseUrl+'ajax/favorites.php',
				params : {
					action : 'delete',
					id : category.favorites_id
				},
				success : function() {
					category.isFavorite = false;
					category.favorites_id = null;
					refreshView();
				},
			});
		} else {
			Ext.Ajax.request({
				url : baseUrl+'ajax/favorites.php',
				params : {
					action : 'add',
					itilcategories_id : category.id,
				},
				success : function(res) {
					category.isFavorite = true;
					category.favorites_id = JSON.parse(res.responseText);
					refreshView();
				},
			});
		}

	}

	
	
	/*--------------------------------------------------------------------------------------------------------------
	 * Usines à HTML
	-------------------------------------------------------------------------------------------------------------- */

	/**
	 * Renvoie le code HTML permettant d'afficher la ligne du tableau de recherche associée à une catégorie
	 * @param category la catégorie à afficher
	 * @param search l'expression régulière utilisée pour la recherche
	 * @param type le type de ticket qu'on veut créer (1 pour incident, 2 pour demande)
	 * @return rien, fonctionne par effet de bord category.searchHtml contient le résultat
	 */
	 function makeHtml(category, search) {
		category.score = 0;
		var name = category.name;
		var comment = category.comment;
		var keywords = category.keywords;

		if(search) {
			name = category.name.replace(search, replaceSearchParts(category, 10));
			comment = category.comment.replace(search, replaceSearchParts(category, 2));
			keywords = category.keywords.replace(search, replaceSearchParts(category, 5));
			category.score += entitiesData[category.entities_id].score;
		}
		
		category.html = 
			'<tr class="tab_bg_1">' + 
			'<td title="'+category.keywords+'"><strong>'+name+'</strong> (score='+category.score+')</td>' + 
			'<td title="'+entitiesData[category.entities_id].comment+'">'+entitiesData[category.entities_id].processedName+'</td>' + 
			'<td>'+comment+'</td>' + 
			makeCreateButtonCell(category, 1) +
			makeCreateButtonCell(category, 2) +
			'<td style="width:5%">'+makeFavoriteButtonHtml(category)+'</td>' + 
			'</tr>';
	}

	/**
	 * Renvoie le code html pour afficher le bouton qui permet d'ajouter/retirer un favori.
	 * @param category id de la catégorie à ajouter/retirer des favoris
	 * @return string code html à utiliser
	 */
	function makeFavoriteButtonHtml(category) {
		var text = category.isFavorite ? 
				'<?php echo __('Remove from favorites', 'searchandcreate')?>' : 
				'<?php echo __('Add to favorites', 'searchandcreate')?>';

		return '<a onclick="'+baseNamespace+'.toogleFavorite('+category.id+')" class="vsubmit" id="'+baseId+'toogleFavorite_'+category.id+'">'+text+'</a>';
	}
	
	/**
	 * Renvoie le code html pour afficher le bouton qui redirige vers la création d'un ticket.
	 * @param category objet catégory vers laquelle créer un boutton
	 * @param type type à préselectionner (1 pour incident, 2 pour demande, peut être au choix une string ou un integer)
	 * @return string code html à utiliser
	 */
	function makeCreateButtonCell(category, type) {
		if((type === '1' || type === 1)) {
			if(category.is_incident) {
				return '<td style="width:5%"><a href="'+baseUrl+'front/createredir.form.php?id=e'+category.entities_id+'t1c'+category.id+'" class="vsubmit">'+
					'<?php echo __('Incident')?></a></td>';
			} else {
				return '<td title="<?php echo __('This category does not allow incident creation', 'searchandcreate')?>"></td>';
			}
		} else if((type === '2' || type === 2)) {
			if(category.is_request) {
				return '<td style="width:5%"><a href="'+baseUrl+'front/createredir.form.php?id=e'+category.entities_id+'t2c'+category.id+'" class="vsubmit">'+
					'<?php echo __('Request')?></a></td>';
			} else {
				return '<td title="<?php echo __('This category does not allow request creation', 'searchandcreate')?>"></td>';
			}
		}
		
	}


	/*--------------------------------------------------------------------------------------------------------------
	 * Mini-fonctions utilitaires sans lien fort avec GLPI
	-------------------------------------------------------------------------------------------------------------- */
	
	/**
	 * Transforme un tableau ou un objet en objet hasmap. Peut appliquer une transformation en même temps.
	 * Très utile pour initialiser les données reçues du serveur : on ne sait pas s'il s'agit d'un objet ou d'un tableau, et il est nécessaire de procéder à des initialisations.
	 * 
	 * @param rawData : tableau ou objet à transformer
	 * @param id : le nom du champ des objets qui servira de clé (par défaut, 'id')
	 * @param transform : fonction de transformation (par défaut, identité)
	 * 		@param raw l'objet tel que reçu
	 * 		@return l'objet transformé
	 * @return l'objet indexé comme convenu
	 */
	function prepareRawData(rawData, id, transform) {
		if(!transform) {
			transform = function(rawData){return rawData;};
		}
		if(!id) id = 'id';
	
		if(Array.isArray(rawData)) {
			return rawData.reduce(function(o, v, i) {
				o[v[id]] = transform(v);
				return o;
			}, {});
		} else {
			return Object.keys(rawData).reduce(function(o, key, i) {
				o[rawData[key][id]] = transform(rawData[key]);
				return o;
			}, {});
		}
	}

	function escapeHtml(str) {
		var div = document.createElement('div');
		div.appendChild(document.createTextNode(str));
		return div.innerHTML;
	};
	
})();





</script>