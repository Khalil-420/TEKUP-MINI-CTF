<?php

/**
 *
 * Provides functionality to select different templates for the standard theme pages <i>album.php</i>, <i>image.php</i>
 * and for Zenpage <i>pages.php</i> and <i>news.php</i>.
 *
 * The additional template files have to be clones of the standard theme pages which must be kept as default ones.
 * The file names of these additional template files must match these patterns and should not include special characters or characters with diacritical marks:
 *
 * Zenphoto gallery items:
 * <ul>
 * <li>For albums: album<var>customname</var>.php</li>
 * <li>For images : image<var>customname</var>.php</li>
 * </ul>
 *
 * "Select album layout" checkbox:
 * If you want to avoid to manually select a specific layout for all images in an album you can check this option
 * on the album's edit page. Then a layout is assigned to the images automatically as well.
 *
 * Example:
 * You select an album layout page named "album_test.php" for an album. If you select that option mentioned above an image layout named "image_test.php"
 * will be used for the direct images of this album if it exists. Otherwise the standard image.php is used or if set an individual image layout page.
 * Selecting this option will not clear already individually set image layouts!
 *
 * Zenpage CMS items:
 * <hr>
 * For Zenpage pages: pages<i>customname</i>.php<br>
 * For Zenpage news articles and news categories: news<i>customname</i>.php.
 *
 * The main news page and the news archive can't be assigned to layout pages.
 *
 * Layout selection inheritance:
 * <hr>
 * The layout selection of a parent album (images see above), page or category is inherited by its sub items on all levels without their own db entry
 * if no specific layout is seleced. News articles don't inherit anything because they don't directly belong to any parent item.
 *
 * @author Malte Müller (acrylian), Stephen Billard (sbillard)
 * @package zpcore\plugins\multiplelayouts
 */
$plugin_is_filter = 5 | FEATURE_PLUGIN;
$plugin_description = gettext("Multiple <em>Theme</em> layouts");
$plugin_author = "Malte Müller (acrylian), Stephen Billard (sbillard)";
$plugin_category = gettext('Misc');

$option_interface = 'multipleLayoutOptions';

zp_register_filter('load_theme_script', 'getLayout');
zp_register_filter('remove_object', 'deleteLayoutSelection');
zp_register_filter('copy_object', 'copyLayoutSelection');
if (getOption('multiple_layouts_albums')) {
	zp_register_filter('edit_album_utilities', 'layoutSelector_album');
	zp_register_filter('save_album_utilities_data', 'saveZenphotoLayoutSelection');
}
if (getOption('multiple_layouts_images')) {
	zp_register_filter('edit_image_utilities', 'layoutSelector');
	zp_register_filter('save_image_utilities_data', 'saveZenphotoLayoutSelection');
}
if (extensionEnabled('zenpage')) {
	if (getOption('multiple_layouts_pages')) {
		zp_register_filter('publish_page_utilities', 'layoutSelector');
		zp_register_filter('new_page', 'saveLayoutSelection');
		zp_register_filter('update_page', 'saveLayoutSelection');
	}
	if (getOption('multiple_layouts_news')) {
		zp_register_filter('publish_article_utilities', 'layoutSelector');
		zp_register_filter('new_article', 'saveLayoutSelection');
		zp_register_filter('update_article', 'saveLayoutSelection');
	}
	if (getOption('multiple_layouts_news_categories')) {
		zp_register_filter('publish_category_utilities', 'layoutSelector');
		zp_register_filter('new_category', 'saveLayoutSelection');
		zp_register_filter('update_category', 'saveLayoutSelection');
	}
}

/**
 * Plugin option handling class
 *
 */
class multipleLayoutOptions {

	function __construct() {
		setOptionDefault('multiple_layouts_images', 0);
		setOptionDefault('multiple_layouts_albums', 0);
		setOptionDefault('multiple_layouts_pages', 1);
		setOptionDefault('multiple_layouts_news', 1);
		setOptionDefault('multiple_layouts_news_categories', 1);
	}

	function getOptionsSupported() {
		$checkboxes = array(
				gettext('Albums') => 'multiple_layouts_albums',
				gettext('Images') => 'multiple_layouts_images'
		);
		if (extensionEnabled('zenpage')) {
			$checkboxes = array_merge($checkboxes, array(
					gettext('Pages') => 'multiple_layouts_pages',
					gettext('News') => 'multiple_layouts_news',
					gettext('News categories') => 'multiple_layouts_news_categories')
			);
		}
		$options = array(
				gettext('Enable multiple layouts for') => array(
						'key' => 'multiple_layouts_allowed',
						'type' => OPTION_TYPE_CHECKBOX_ARRAY,
						'checkboxes' => $checkboxes,
						'desc' => '')
		);
		return $options;
	}

	function handleOption($option, $currentValue) {

	}

}

/**
 * Gets the selected layout page for this item. Returns false if nothing is selected.
 *
 * @param object $obj the object being selected
 * @param string $type For Zenphoto gallery items "multiple_layouts_albums", 'multiple_layouts_albums_images', 'multiple_layouts_images'
 * 										 For Zenpage CMS items , "multiple_layouts_pages", , "multiple_layouts_news" , "multiple_layouts_news_categories"
 * @return string|false
 */
function getSelectedLayout($obj, $type) {
	global $_zp_db;
	if ($obj && $obj->exists) {
		$assignedlayout = $_zp_db->querySingleRow("SELECT * FROM " . $_zp_db->prefix('plugin_storage') . ' WHERE `aux` = ' . $obj->getID() . ' AND `type` = "' . $type . '"');
		if (!$assignedlayout || empty($assignedlayout['data'])) {
			$assignedlayout = checkParentLayouts($obj, $type);
		}
		return $assignedlayout;
	}
	return false;
}

/**
 * Checks if there is a layout inherited from a parent items (album, page or category) and returns it. Returns false otherwise.
 *
 * @param object $obj the object being selected
 * @param string $type For Zenphoto gallery items "multiple_layouts_albums", 'multiple_layouts_albums_images', 'multiple_layouts_images'
 * 										 For Zenpage CMS items , "multiple_layouts_pages", , "multiple_layouts_news" , "multiple_layouts_news_categories"
 * @return array|false
 */
function checkParentLayouts($obj, $type) {
	global $_zp_db;
	$parents = array();
	switch ($type) {
		case 'multiple_layouts_images':
			$type = 'multiple_layouts_albums_images';
			$obj = $obj->getAlbum();
			array_unshift($parents, $obj);
		case 'multiple_layouts_albums':
		case 'multiple_layouts_albums_images':
			while (!is_null($obj = $obj->getParent())) {
				array_unshift($parents, $obj);
			}
			if (count($parents) > 0) {
				$parents = array_reverse($parents); //reverse so we can check the direct parent first.
				foreach ($parents as $parentobj) {
					$parentlayouts = $_zp_db->querySingleRow('SELECT * FROM ' . $_zp_db->prefix('plugin_storage') . ' WHERE `aux`=' . $parentobj->getID() . ' AND `type` = "' . $type . '"');
					if ($parentlayouts && $parentlayouts['data']) {
						return $parentlayouts;
					}
				}
			}
			break;
		case 'multiple_layouts_pages':
		case 'multiple_layouts_news_categories':
			$parents = $obj->getParents();
			if ($parents && count($parents) > 0) {
				$parents = array_reverse($parents); //reverse so we can check the direct parent first.
				foreach ($parents as $parent) {
					if ($type === 'multiple_layouts_pages') {
						$parentobj = new ZenpagePage($parent);
					} else {
						$parentobj = new ZenpageCategory($parent);
					}
					$parentlayouts = $_zp_db->querySingleRow('SELECT * FROM ' . $_zp_db->prefix('plugin_storage') . ' WHERE `aux`=' . $parentobj->getID() . ' AND `type` = "' . $type . '"');
					if ($parentlayouts && $parentlayouts['data']) {
						return $parentlayouts;
					}
				}
			}
			break;
	}
	return false;
}

/**
 * Gets the selected layout page for images if the album option to use the equivalent of their album layout is seleted.
 *
 * @param object $obj the album
 * @return array|false
 */
function checkLayoutUseForImages($obj) {
	global $_zp_db;
	$albumimagelayout = $_zp_db->querySingleRow("SELECT id, `data` FROM " . $_zp_db->prefix('plugin_storage') . ' WHERE `aux` = ' . $obj->getID() . ' AND `type` = "multiple_layouts_albums_images"');
	if ($albumimagelayout) {
		return $albumimagelayout;
	} else {
		$parents = array();
		while (!is_null($obj = $obj->getParent())) {
			array_unshift($parents, $obj);
		}
		if (count($parents) > 0) {
			$parents = array_reverse($parents);
			foreach ($parents as $parent) {
				$parentimagelayouts = $_zp_db->queryFullArray('SELECT id, `data` FROM ' . $_zp_db->prefix('plugin_storage') . ' WHERE `aux`=' . $parent->getID() . ' AND `type` = "multiple_layouts_albums_images"');
				if ($parentimagelayouts && $parentimagelayouts['data']) {
					return $parentimagelayouts;
				}
			}
		}
	}
	return false;
}

/**
 * returns the layout selector for an object.
 *
 * @param $string $html
 * @param object $obj
 * @return string
 */
function layoutSelector($html, $obj, $prefix = '') {
	$type = 'multiple_layouts_' . $obj->table;
	if (getOption($type)) {
		$html .= getLayoutSelector($obj, $type, '<hr /><p>' . gettext('Select layout:') . '</p>', $prefix);
	}
	return $html;
}

/**
 * returns the layout selectors for an album.
 *
 * @param $string $html
 * @param object $obj
 * @parem string $prefix
 * @return string
 */
function layoutSelector_album($html, $obj, $prefix = '') {
	if (getOption('multiple_layouts_albums')) {
		$albumhtml = getLayoutSelector($obj, 'multiple_layouts_albums', '<hr /><p>' . gettext('Select album layout:') . '</p>', $prefix);
		$imagehtml = getLayoutSelector($obj, 'multiple_layouts_albums_images', '<p>' . gettext('Select default album image layout:') . '</p>', $prefix, true);
		if (!$obj->isDynamic() && strpos($imagehtml, '<p class="no_extra">') === false) {
			$imagehtml .= '<br /><input type="checkbox" id="layout_selector_resetimagelayouts" name="layout_selector_resetimagelayouts" /><label for="layout_selector_resetimagelayouts">' . gettext('Reset individual image layouts') . '</label>';
		}
		return $html . $albumhtml . $imagehtml;
	}
	return $html;
}

/**
 * Worker function for creating layout selectors. Returns the HTML
 *
 * @param object $obj
 * @param string $type
 * @param string $text
 * @param string $prefix Default empty
 * @param bool $secondary Default false
 * @return string
 */
function getLayoutSelector($obj, $type, $text, $prefix = '', $secondary = false) {
	global $_zp_gallery, $_zp_db;
	$selectdefault = '';
	$selected = '';
	$files = array();
	$list = array();
	$getlayout = '';
	$table = $obj->table;
	$path = SERVERPATH . '/' . THEMEFOLDER . '/' . $_zp_gallery->getCurrentTheme() . '/';
	$defaultlayout = '';
	$defaulttext = gettext('default');
	switch ($table) {
		case 'albums':
			if ($secondary) { //	the selector for the image default of the album
				$filesmask = 'image';
			} else {
				$filesmask = 'album';
			};
			$child = $obj->getParentID();
			$defaulttext = gettext('inherited');
			break;
		case 'images':
			$filesmask = 'image';
			$album = $obj->album;
			$child = $album->getID();
			$defaulttext = gettext('album default');
			break;
		case 'pages':
			$filesmask = 'pages';
			$child = $obj->getParentID();
			$defaulttext = gettext('inherited');
			break;
		case 'news':
			$child = false;
			$categories = $obj->getCategories();
			if ($categories) {
				foreach ($categories as $cat) {
					$cat = new ZenpageCategory($cat['titlelink']);
					$getlayout = getSelectedLayout($cat, 'multiple_layouts_news_categories');
					if ($getlayout && $getlayout['data']) { //	in at least one news category with an alternate page
						$defaulttext = gettext('inherited');
						$defaultlayout = gettext('from category');
						break;
					}
				}
			}
			$filesmask = 'news';
			break;
		case 'news_categories':
			$child = $obj->getParentID();
			$defaulttext = gettext('inherited');
			$filesmask = 'news';
			break;
	}
	$curdir = getcwd();
	chdir($path);
	$files = safe_glob($filesmask . '*.php');
	chdir($curdir);

	if ($child) {
		$defaultlayout = checkParentLayouts($obj, $type);
		if($defaultlayout) {
			$defaultlayout = $defaultlayout['data'];
		}
	}
	if ($defaultlayout) {
		$defaultlayout = stripSuffix($defaultlayout);
	} else {
		$defaultlayout = $filesmask;
	}

	if ($obj->transient) {
		$getlayout = false;
	} else {
		$getlayout = $_zp_db->querySingleRow("SELECT * FROM " . $_zp_db->prefix('plugin_storage') . ' WHERE `aux` = ' . $obj->getID() . ' AND `type` = "' . $type . '"');
	}
	if (!$child && ($key = array_search($filesmask . '.php', $files)) !== false) {
		unset($files[$key]);
	}
	foreach ($files as $file) {
		$file = filesystemToInternal($file);
		$list[stripSuffix($file)] = $file;
	}
	ksort($list);

	$html = $text;
	if (count($files) != 0) {
		$html .= '<select id="' . $type . $prefix . '" name="' . $prefix . $type . '">' . "\n";
		if (is_array($getlayout)) {
			$selectedlayout = $getlayout['data'];
		} else {
			$selectedlayout = '';
		}
		$html .= '<option value=""' . ($selectedlayout == '' ? ' selected="selected"' : '') . ' style="background-color:LightGray" >*' . $defaulttext . '* (' . $defaultlayout . ')</option>' . "\n";
		foreach ($list as $display => $file) {
			$html .= '<option value="' . html_encode($file) . '"' . ($selectedlayout == $file ? ' selected="selected"' : '') . '>' . $display . '</option>' . "\n";
		}
		$html .= '</select>' . "\n";
	} else {
		$html = '<p class="no_extra">' . sprintf(gettext('No extra <em>%s</em> theme pages available'), $filesmask) . '</p>' . "\n";
	}
	return $html;
}

/**
 * Gets the select layout page and returns it to the load_theme_script filter
 *
 * @param string $path Path of the layout file
 * @return string
 */
function getLayout($path) {
	global $_zp_gallery, $_zp_gallery_page, $_zp_current_image, $_zp_current_album, $_zp_current_zenpage_page, $_zp_current_zenpage_news, $_zp_current_category, $_zp_current_search;
	if ($path) {
		$themepath = THEMEFOLDER . '/' . $_zp_gallery->getCurrentTheme() . '/';
		$getlayout = false;
		switch ($_zp_gallery_page) {
			case 'album.php':
				if (getOption('multiple_layouts_albums')) {
					$getlayout = getSelectedLayout($_zp_current_album, 'multiple_layouts_albums');
				}
				break;
			case 'image.php':
				if (getOption('multiple_layouts_images')) {
					$getlayout = getSelectedLayout($_zp_current_image, 'multiple_layouts_images');
					if (in_context(ZP_SEARCH_LINKED) && !in_context(ZP_ALBUM_LINKED)) {
						if (!$album = $_zp_current_search->getDynamicAlbum()) {
							$album = $_zp_current_album;
						}
					} else {
						$album = $_zp_current_album;
					}
					if ($album && !$getlayout) {
						$getlayout = checkLayoutUseForImages($album);
					}
				}
				break;
			case 'pages.php':
				if (getOption('multiple_layouts_pages')) {
					$getlayout = getSelectedLayout($_zp_current_zenpage_page, 'multiple_layouts_pages');
				}
				break;
			case 'news.php':
				if (getOption('multiple_layouts_news_categories') && in_context(ZP_ZENPAGE_NEWS_CATEGORY)) {
					$getlayout = getSelectedLayout($_zp_current_category, 'multiple_layouts_news_categories');
				} elseif (getOption('multiple_layouts_news') && in_context(ZP_ZENPAGE_SINGLE)) {
					$getlayout = getSelectedLayout($_zp_current_zenpage_news, 'multiple_layouts_news');
				}
				break;
		}
		if ($getlayout && $getlayout['data'] && file_exists(internalToFilesystem(SERVERPATH . '/' . $themepath . $getlayout['data']))) {
			return $themepath . $getlayout['data'];
		}
	}
	return $path;
}

/**
 * Saves the layout page assignment via filter on the backend
 *
 * @param string $message Message (not used)
 * @param object $obj Object of the item to assign the layout
 * @return string
 */
function saveLayoutSelection($message, $obj) {
	global $_zp_db;
	$selectedlayout = '';
	$type = 'multiple_layouts_' . $obj->table;
	if (isset($_POST[$type])) {
		$selectedlayout = sanitize($_POST[$type]);
		$table = $obj->table;
		$exists = $_zp_db->querySingleRow("SELECT * FROM " . $_zp_db->prefix('plugin_storage') . ' WHERE `aux` = ' . $obj->getID() . ' AND `type` = "' . $type . '"');
		if ($selectedlayout) { // not default
			if ($exists) {
				$query = $_zp_db->query('UPDATE ' . $_zp_db->prefix('plugin_storage') . ' SET `aux`=' . $obj->getID() . ', `data`=' . $_zp_db->quote($selectedlayout) . ' WHERE `id`=' . $exists['id']);
			} else {
				$query = $_zp_db->query('INSERT INTO ' . $_zp_db->prefix('plugin_storage') . ' (type,aux,data) VALUES (' . $_zp_db->quote($type) . ', ' . $obj->getID() . ', ' . $_zp_db->quote($selectedlayout) . ')');
			}
		} else {
			if ($exists) { //	got to get rid of the record
				$query = $_zp_db->query('DELETE FROM ' . $_zp_db->prefix('plugin_storage') . ' WHERE `id`=' . $exists['id']);
			} else {
				$query = true; //	no harm, no foul
			}
		}
		if (!$query) {
			$message .= '<p class="errorbox">' . sprintf(gettext('Query failure: %s'), $_zp_db->getError()) . '</p>';
		}
	}
	return $message;
}

/**
 * Saves the layout page assignment via filter on the backend for images and albums
 *
 * @param object $obj Object of the item to assign the layout
 * @param string $prefix
 * @return string
 */
function saveZenphotoLayoutSelection($obj, $prefix) {
	global $_zp_db;
	$cssIDappend = '';
	$selectedlayout = '';
	$titlelink = '';
	$table = $obj->table;
	$type = 'multiple_layouts_' . $table;
	if (isset($_POST[$prefix . $type]) || isset($_POST[$prefix . 'multiple_layouts_albums_images'])) {
		if (isset($_POST[$prefix . $type])) {
			$selectedlayout = sanitize($_POST[$prefix . $type]);
		} else if (isset($_POST[$prefix . 'multiple_layouts_albums_images'])) {
			$selectedlayout = sanitize($_POST[$prefix . 'multiple_layouts_albums_images']);
		}
		$exists = $_zp_db->querySingleRow("SELECT * FROM " . $_zp_db->prefix('plugin_storage') . ' WHERE `aux` = ' . $obj->getID() . ' AND `type` = "' . $type . '"');
		if ($selectedlayout) { // not default
			if ($exists) {
				$query = $_zp_db->query('UPDATE ' . $_zp_db->prefix('plugin_storage') . ' SET `aux`=' . $obj->getID() . ', `data`=' . $_zp_db->quote($selectedlayout) . ' WHERE `id`=' . $exists['id']);
			} else {
				$query = $_zp_db->query('INSERT INTO ' . $_zp_db->prefix('plugin_storage') . ' (type,aux,data) VALUES ("' . $type . '", ' . $obj->getID() . ', ' . $_zp_db->quote($selectedlayout) . ')');
			}
		} else {
			if ($exists) { //	got to get rid of the record
				$query = $_zp_db->query('DELETE FROM ' . $_zp_db->prefix('plugin_storage') . ' WHERE `id`=' . $exists['id']);
			} else {
				$query = true; //	no harm, no foul
			}
		}
		if ($table == 'albums') { //	deal with the default images selection, clear image selections
			if (isset($_POST['layout_selector_resetimagelayouts'])) {
				$result = $_zp_db->queryFullArray('SELECT `id` FROM ' . $_zp_db->prefix('images') . ' WHERE `albumid`=' . $obj->getID());
				if ($result) {
					$imagelist = '';
					foreach ($result as $row) {
						$imagelist .= '`aux`=' . $row['id'] . ' OR ';
					}
					$query = $_zp_db->query($sql = 'DELETE FROM ' . $_zp_db->prefix('plugin_storage') . ' WHERE `type`="multiple_layouts_images" AND (' . substr($imagelist, 0, -4) . ')', false);
				}
			}
			$exists = $_zp_db->querySingleRow("SELECT * FROM " . $_zp_db->prefix('plugin_storage') . ' WHERE `aux` = ' . $obj->getID() . ' AND `type` = "multiple_layouts_albums_images"');
			$selectedlayout = isset($_POST[$prefix . 'multiple_layouts_albums_images']) ? sanitize($_POST[$prefix . 'multiple_layouts_albums_images']) : NULL;
			if ($selectedlayout) { // not default
				if ($exists) {
					$query = $_zp_db->query('UPDATE ' . $_zp_db->prefix('plugin_storage') . ' SET `aux`=' . $obj->getID() . ', `data`=' . $_zp_db->quote($selectedlayout) . ' WHERE `id`=' . $exists['id']);
				} else {
					$query = $_zp_db->query('INSERT INTO ' . $_zp_db->prefix('plugin_storage') . ' (type,aux,data) VALUES ("multiple_layouts_albums_images", ' . $obj->getID() . ', ' . $_zp_db->quote($selectedlayout) . ')');
				}
			} else {
				if ($exists) { //	got to get rid of the record
					$query = $_zp_db->query('DELETE FROM ' . $_zp_db->prefix('plugin_storage') . ' WHERE `id`=' . $exists['id']);
				} else {
					$query = true; //	no harm, no foul
				}
			}
		}
	}
	return $obj;
}

/**
 * processes object removals if they have been assigned layouts
 *
 * @param bool $allow we just return this since we have no need to abort the remove
 * @param object $obj the object being removed
 * @return bool
 */
function deleteLayoutSelection($allow, $obj) {
	global $_zp_db;
	$type = 'multiple_layouts_' . $obj->table;
	if (getOption($type)) {
		$query = $_zp_db->query('DELETE FROM ' . $_zp_db->prefix('plugin_storage') . ' WHERE `aux` = ' . $obj->getID() . ' AND type = "' . $type . '"', false);
		if (AlbumBase::isAlbumClass($obj)) {
			$result = $_zp_db->querySingleRow('DELETE FROM ' . $_zp_db->prefix('plugin_storage') . ' WHERE `aux` = ' . $obj->getID() . ' AND type = "multiple_layouts_albums_images"', false);
		}
	}
	return $allow;
}

/**
 * Copies the layout selection
 * 
 * @param int $newid ID of the item to copy to
 * @param obj $obj Object of the  original item
 * return int
 */
function copyLayoutSelection($newid, $obj) {
	global $_zp_db;
	$type = 'multiple_layouts_' . $obj->table;
	if (getOption($type)) {
		$result = $_zp_db->querySingleRow('SELECT * FROM ' . $_zp_db->prefix('plugin_storage') . ' WHERE `aux` = ' . $obj->getID() . ' AND type = "' . $type . '"', false);
		if ($result) {
			$query = $_zp_db->query('INSERT INTO ' . $_zp_db->prefix('plugin_storage') . ' (type,aux,data) VALUES ("' . $result['type'] . '", ' . $newid . ', ' . $_zp_db->quote($result['data']) . ')');
		}
		if (AlbumBase::isAlbumClass($obj)) {
			$result = $_zp_db->querySingleRow('SELECT * FROM ' . $_zp_db->prefix('plugin_storage') . ' WHERE `aux` = ' . $obj->getID() . ' AND type = "multiple_layouts_albums_images"', false);
			if ($result) {
				$query = $_zp_db->query('INSERT INTO ' . $_zp_db->prefix('plugin_storage') . ' (type,aux,data) VALUES ("multiple_layouts_albums_images", ' . $newid . ', ' . $_zp_db->quote($result['data']) . ')');
			}
		}
	}
	return $newid;
}