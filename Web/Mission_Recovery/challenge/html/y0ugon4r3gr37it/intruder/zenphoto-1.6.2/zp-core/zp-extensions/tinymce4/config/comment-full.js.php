<?php
/**
 * The configuration functions for TinyMCE 4.x.
 *
 * Comment form plugin default light configuration
 */
 global $_zp_rtl_css;
?>
<script src="<?php echo WEBPATH . "/" . ZENFOLDER . "/" . PLUGIN_FOLDER; ?>/tinymce4/tinymce.min.js"></script>
<script>
	tinymce.init({
		selector: "textarea.textarea_inputbox,textarea.texteditor_comments",
		language: "<?php echo $locale; ?>",
		entity_encoding: '<?php echo getOption('tinymce4_entityencoding'); ?>',
		<?php if(!empty(trim(strval(getOption('tinymce4_entities'))))) { ?>
			entities: '<?php echo getOption('tinymce4_entities'); ?>',
		<?php } ?>	
		directionality: "<?php echo $_zp_rtl_css ? 'rtl' : 'ltr'; ?>",
		menubar: false,
		relative_urls: false,
		plugins: [
			"advlist autolink lists link image charmap print preview hr anchor pagebreak",
			"searchreplace visualblocks code directionality",
			"insertdatetime media table contextmenu",
			"emoticons paste"
		],
		statusbar: false,
		content_css: "<?php echo getPlugin('tinymce4/config/content.css', true, FULLWEBPATH); ?>",
		toolbar: "undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | preview | forecolor backcolor emoticons | code | ltr rtl"
	});
</script>
