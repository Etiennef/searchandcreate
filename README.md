
Créer 400 carégories random

/*
		$category = new ITILCategory();
		for($i=0 ; $i<400 ; $i++) {
			$category->add(array(
				'entities_id' => mt_rand(0, 5),
				'is_recursive' => mt_rand(0, 1),
				'name' => Toolbox::getRandomString(20),
				'comment' => Toolbox::getRandomString(50).' '.Toolbox::getRandomString(50).' '.Toolbox::getRandomString(50).' '.Toolbox::getRandomString(50),
				'itilcategories_id' => mt_rand(0, 10) ? mt_rand(1, $i+1) : 0,
				'users_id' => 0,
				'groups_id' => 0,
				'knowbaseitemcategories_id' => 0,
				'is_helpdeskvisible' => mt_rand(0,1),
				'is_incident' => mt_rand(0,1),
				'is_request' => mt_rand(0,1),
				'is_problem' => 0,
				'tickettemplates_id_demand' => 0,
				'tickettemplates_id_incident' => 0
			));
		}*/
		//DELETE FROM `glpi-0.84.8`.`glpi_itilcategories` WHERE `glpi_itilcategories`.`id` >= 16; ALTER TABLE `glpi_itilcategories` auto_increment = 16;