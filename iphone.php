<?php
/*
 * Add Zenphoto for iOS required functions
 *
 * @author Sachiko Miyamoto
 * @package plugins
 */
$plugin_description = gettext("Add thumbnail size to cache manager use for iPhone app");
$plugin_author = "Sachiko Miyamoto (djmonta)";
$option_interface = 'iphone';

class iphone {
	function __construct() {
			setOptionDefault('iphone_width', '300');
			setOptionDefault('iphone_height', '300');
			setOptionDefault('iphone_cropw', '300');
			setOptionDefault('iphone_croph', '300');
			setOptionDefault('iphone_update', 1);
			setOptionDefault('iphone_phperror', 0);
			if (class_exists('cacheManager')) {
			cacheManager::deleteThemeCacheSizes('iphone');
				// cacheManager::addThemeCacheSize('iphone', NULL, getOption('iphone_width'), getOption('iphone_height'), getOption('iphone_cropw'), getOption('iphone_croph'), NULL, NULL, true, NULL, NULL, NULL);
				cacheManager::addThemeCacheSize('iphone', getOption('iphone_width'), NULL, NULL, getOption('iphone_cropw'), getOption('iphone_croph'), NULL, NULL, true, NULL, NULL, NULL);
			}
	}

	function getOptionsSupported() {
		global $_zp_gallery;
		$options = array(
						gettext('Width')					 => array('key'		 => 'iphone_width', 'type'	 => OPTION_TYPE_TEXTBOX,
										'desc'	 => gettext("Width of the thumb."),
										'order'	 => 1),
						gettext('Height')					 => array('key'		 => 'iphone_height', 'type'	 => OPTION_TYPE_TEXTBOX,
										'desc'	 => gettext("Height of the thumb."),
										'order'	 => 2),
						gettext('Crop width')			 => array('key'		 => 'iphone_cropw', 'type'	 => OPTION_TYPE_TEXTBOX,
										'desc'	 => "",
										'order'	 => 3),
						gettext('Crop height')		 => array('key'		 => 'iphone_croph', 'type'	 => OPTION_TYPE_TEXTBOX,
										'desc'	 => "",
										'order'	 => 4),
						gettext('Updated PHP RPC') => array('key'    => 'iphone_update', 'type' =>
							OPTION_TYPE_CHECKBOX,
										'desc'   => gettext('Enable or Disable the updating of the RPC file'),
										'order'  => 5),
						gettext('Display Errors')  => array('key'    => 'iphone_phperror', 'type' =>
							OPTION_TYPE_CHECKBOX,
										'desc'   => gettext("Display or Hide PHP Error"),
										'order'  => 6)
		);
		return $options;
	}
}

?>