<?php
// force UTF-8 Ø
if (!defined('WEBPATH'))
	die();
if (function_exists('printSlideShow')) {
?>
<!DOCTYPE html>
<html<?php printLangAttribute(); ?>>
	<head>
		<meta charset="<?php echo LOCAL_CHARSET; ?>">
		<?php zp_apply_filter('theme_head'); ?>
		<?php printHeadTitle(); ?>
		<link rel="stylesheet" href="<?php echo $_zp_themeroot; ?>/style.css" />
	</head>
	<body>
			<?php zp_apply_filter('theme_body_open'); ?>
			<div id="slideshowpage">
				<?php printSlideShow(true, true); ?>
			</div>
			<?php zp_apply_filter('theme_body_close'); ?>
		</body>
</html>
<?php
} else {
	include(SERVERPATH . '/' . ZENFOLDER . '/404.php');
}
?>
