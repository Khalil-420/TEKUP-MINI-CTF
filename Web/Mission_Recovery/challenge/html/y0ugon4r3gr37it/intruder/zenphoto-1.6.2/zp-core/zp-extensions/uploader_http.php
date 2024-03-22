<?php

/**
 *
 * This plugin provides an HTTP based image upload handler for the <i>upload/images</i> admin tab.
 *
 * @author Stephen Billard (sbillard)
 * @package zpcore\plugins\uploaderhttp
 *
 */
$plugin_is_filter = 5 | ADMIN_PLUGIN;
$plugin_description = gettext('<em>http</em> image upload handler.');
$plugin_author = 'Stephen Billard (sbillard)';
$plugin_category = gettext('Uploader');

if (OFFSET_PATH == 2)
	setoptiondefault('zp_plugin_uploader_http', $plugin_is_filter);

if (zp_loggedin(UPLOAD_RIGHTS)) {
	zp_register_filter('upload_handlers', 'httpUploadHandler');
	zp_register_filter('admin_tabs', 'httpUploadHandler_admin_tabs', 10);
}

function httpUploadHandler($uploadHandlers) {
	$uploadHandlers['http'] = SERVERPATH . '/' . ZENFOLDER . '/' . PLUGIN_FOLDER . '/uploader_http';
	return $uploadHandlers;
}

function httpUploadHandler_admin_tabs($tabs) {
	$me = sprintf(gettext('images (%s)'), 'http');
	$mylink = FULLWEBPATH . "/" . ZENFOLDER . '/admin-upload.php?page=upload&tab=http&type=' . gettext('images');
	if (is_null($tabs['upload'])) {
		$tabs['upload'] = array(
				'text' => gettext("upload"),
				'link' => FULLWEBPATH . "/" . ZENFOLDER . '/admin-upload.php',
				'subtabs' => NULL);
	}
	$tabs['upload']['subtabs'][$me] = $mylink;
	if (zp_getcookie('zpcms_admin_uploadtype') == 'http')
		$tabs['upload']['link'] = $mylink;
	return $tabs;
}