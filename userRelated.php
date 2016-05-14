<?php

/*
 * This plugin is used to extend the administrator table to add User Profile Picture and Facebook, Twitter, Google+ Integrations.
 *
 * <b>NOTE:</b> you must run setup after enabling or disabling this plugin to cause changes to
 * be made to the database. (Database changes should not be made on an active site.
 * You should close the site when you run setup.) If you disable the plugin all data
 * contained in the fields will be discarded.
 *
 * @author Sachiko Miyamoto (djmonta)
 * @package plugins
 * @subpackage users
 */
// force UTF-8 Ø
$plugin_is_filter = 5 | CLASS_PLUGIN;
$plugin_description = gettext("Add User Profile Picture and Facebook, Twitter, Google+ Integrations.");
$plugin_author = "Sachiko Miyamoto (djmonta)";
$option_interface = 'userRelated';

require_once __DIR__ . '/userRelated/facebook-sdk-v5/autoload.php';

class userRelated {
	function __construct() {
		global $_userRelated;
		$firstTime = extensionEnabled('userRelated') && is_null(getOption('userRelated_addedFields'));
		$me = 'userRelated';
		$newfields = self::fields();
		$previous = getSerializedArray(getOption($me . '_addedFields'));
		$current = $fields = array();
		if (extensionEnabled($me)) { //need to update the database tables.
			foreach ($newfields as $newfield) {
				$current[$newfield['table']][$newfield['name']] = true;
				unset($previous[$newfield['table']][$newfield['name']]);
				switch (strtolower($newfield['type'])) {
					default:
						$dbType = strtoupper($newfield['type']);
						break;
					case 'int':
					case 'varchar':
						$dbType = strtoupper($newfield['type']) . '(' . min(255, $newfield['size']) . ')';
						break;
				}
				$sql = 'ALTER TABLE ' . prefix($newfield['table']) . ' ADD COLUMN `' . $newfield['name'] . '` ' . $dbType;
				if (query($sql, false) && in_array($newfield['table'], array('albums', 'images', 'news', 'news_categories', 'pages')))
					$fields[] = strtolower($newfield['name']);
			}
			setOption($me . '_addedFields', serialize($current));
		} else {
			purgeOption($me . '_addedFields');
		}
		$set_fields = array_flip(explode(',', getOption('search_fields')));
		foreach ($previous as $table => $orpahed) { //drop fields no longer defined
			foreach ($orpahed as $field => $v) {
				unset($set_fields[$field]);
				$sql = 'ALTER TABLE ' . prefix($table) . ' DROP `' . $field . '`';
				query($sql, false);
			}
		}
		$set_fields = array_unique(array_merge($fields, array_flip($set_fields)));
		setOption('search_fields', implode(',', $set_fields));

		if ($firstTime) { //	migrate the custom data user data
			$result = query('SELECT * FROM ' . prefix('administrators') . ' WHERE `valid`!=0');
			if ($result) {
				while ($row = db_fetch_assoc($result)) {
					$custom = getSerializedArray($row['custom_data']);
					if (!empty($custom)) {
						$sql = 'UPDATE ' . prefix('administrators') . ' SET ';
						foreach ($custom as $field => $val) {
							$sql.= '`' . $field . '`=' . db_quote($val) . ',';
						}
						$sql .= '`custom_data`=NULL WHERE `id`=' . $row['id'];
						query($sql);
					}
				}
				db_free_result($result);
			}
		}
	}
	function getOptionsSupported() {
		global $_zp_gallery;
		$options = array(
						gettext('Enable Facebook Integrations') => array('key'    => 'fb_integration', 'type' => OPTION_TYPE_CHECKBOX,
										'desc'   => gettext('Enable or Disable Facebook Integrations'),
										'order'  => 1),
						gettext('Facebook App ID')					 => array('key'		 => 'fb_app_id', 'type'	 => OPTION_TYPE_TEXTBOX,
										'desc'	 => gettext("Facebook App ID"),
										'order'	 => 2),
						gettext('Facebook App Secret')					 => array('key'		 => 'fb_app_secret', 'type'	 => OPTION_TYPE_TEXTBOX,
										'desc'	 => gettext("Facebook App Secret"),
										'order'	 => 3)
		);
		return $options;
	}


	static function fields() {
		return array(
						array('table' => 'administrators', 'name' => 'profile_picture_url', 'desc' => gettext('Profile Picture URL'), 'type' => 'tinytext'),
						array('table' => 'administrators', 'name' => 'fb_id', 'desc' => gettext('Facebook User ID'), 'type' => 'tinytext')
		);
	}

	static function adminSave($updated, $userobj, $i, $alter) {
		$fields = self::fields();
		if ($userobj->getValid()) {
			foreach ($fields as $field) {
				if (isset($_FILES[$field['name'] . '_' . $i])) {
					if ($field['name'] == 'profile_picture_url') {
						$path = self::saveImage($_FILES[$field['name'] . '_' . $i]);
						$newdata = str_replace(SERVERPATH, FULLWEBPATH, $path);
						$olddata = $userobj->get($field['name']);
						$userobj->set($field['name'], $newdata);
						if ($olddata != $newdata) {
							$updated = true;
						}
					}
				}
				//  elseif (isset($_POST[$field['name'] . '_' . $i])) {
				// 	if ($field['table'] == 'administrators') {
				// 		$olddata = $userobj->get($field['name']);
				// 		$userobj->set($field['name'], $newdata = $_POST[$field['name'] . '_' . $i]);
				// 		if ($olddata != $newdata) {
				// 			$updated = true;
				// 		}
				// 	}
				// }
			}
		}
		return $updated;
	}

	static function adminTabs($tabs) {
		if (isset($tabs['users']['subtabs'])) {
			$subtabs = $tabs['users']['subtabs'];
		} else {
			$subtabs = array();
		}
		$subtabs[gettext('users')] = 'admin-users.php?page=users&tab=users';
		$subtabs[gettext('profile')] = '../' . USER_PLUGIN_FOLDER . '/userRelated/userRelatedTab.php?page=users&tab=profile';
		$tabs['users'] = array('text'		 => gettext("users"),
						'link'		 => WEBPATH . "/" . ZENFOLDER . '/admin-users.php?page=users&tab=users',
						'subtabs'	 => $subtabs,
						'default'	 => 'users');
		return $tabs;
	}
	/**
	 * registers filters for handling display and edit of objects as appropriate
	 */
	static function register() {
		$items = array();
		$fields = self::fields();
		foreach ($fields as $field) {
			$items[$field['table']] = true;
		}

		if (isset($items['administrators'])) {
			zp_register_filter("save_admin_custom_data", "userRelated::adminSave");
		// 	zp_register_filter("edit_admin_custom_data", "userRelated::adminEdit");
		// 	zp_register_filter("admin_head", "userRelated::adminHead");
			zp_register_filter("admin_tabs", "userRelated::adminTabs");
		}
		if (!getOption("userRelated_addedFields")) {
			zp_register_filter("admin_note", "userRelated::adminNotice");
		}
	}

	static function adminNotice($tab, $subtab) {
		echo '<p class="notebox">' . sprintf(gettext('You will need to run <a href="%1$s">setup</a> to update the database with the custom fields defined by the <em>%2$s</em> plugin.'), FULLWEBPATH . '/' . ZENFOLDER . '/setup.php', 'userRelated') . '</p>';
	}

	static function getCustomData($obj) {
		$result = array();
		$fields = self::fields();
		foreach ($fields as $element) {
			if ($element['table'] == $obj->table) {
				$result[$element['name']] = $obj->get($element['name']);
			}
		}
		return $result;
	}
	static function setCustomData($obj, $values) {
		foreach ($values as $field => $value) {
			$obj->set($field, $value);
		}
	}

	static function facebookLoginUrl() {
		if (getOption('fb_integration')) {
			$fb = new Facebook\Facebook([
			  'app_id' => getOption('fb_app_id'),
			  'app_secret' => getOption('fb_app_secret'),
			  'default_graph_version' => 'v2.5',
			]);
			$helper = $fb->getRedirectLoginHelper();
			$permissions = ['email']; // optional
			$loginUrl = $helper->getLoginUrl(FULLWEBPATH . '/' . USER_PLUGIN_FOLDER . '/userRelated/login-callback.php', $permissions);

			return $loginUrl;
		}
	}
	static function facebook($accessToken) {
		if (getOption('fb_integration')) {
			$fb = new Facebook\Facebook([
			  'app_id' => getOption('fb_app_id'),
			  'app_secret' => getOption('fb_app_secret'),
			  'default_graph_version' => 'v2.5',
			]);
			if($accessToken) {
				$oAuth2Client = $fb->getOAuth2Client();
				$longLivedAccessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
				$fb->setDefaultAccessToken($accessToken);
				try {
				  $response = $fb->get('/me');
				  $plainOldArray = $response->getDecodedBody();
				} catch(Facebook\Exceptions\FacebookResponseException $e) {
				  // When Graph returns an error
				  debugLog('Graph returned an error: ' . $e->getMessage());
				  exit;
				} catch(Facebook\Exceptions\FacebookSDKException $e) {
				  // When validation fails or other local issues
				  debugLog('Facebook SDK returned an error: ' . $e->getMessage());
				  exit;
				}

			}
			return $plainOldArray;
		}
	}

}

if (OFFSET_PATH == 2) { // setup call: add the fields into the database
	setOptionDefault('zp_plugin_userRelated', $plugin_is_filter);
	new userRelated;
} else {
	userRelated::register();
}

?>
