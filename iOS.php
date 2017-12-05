<?php
/*
 * Add Zenphoto for iOS required functions
 *
 * @author Sachiko Miyamoto
 * @package plugins
 */
$plugin_description = gettext('Add Zenphoto for iOS required functions');
$plugin_author = 'Sachiko Miyamoto (djmonta)';
$option_interface = 'iOS';

class iOS
{
    public function __construct()
    {
        setOptionDefault('iOS_width', '300');
        setOptionDefault('iOS_height', '300');
        setOptionDefault('iOS_cropw', '300');
        setOptionDefault('iOS_croph', '300');
        setOptionDefault('iOS_update', 1);
        setOptionDefault('iOS_phperror', 0);
        if (class_exists('cacheManager')) {
            cacheManager::deleteThemeCacheSizes('iOS');
            // cacheManager::addThemeCacheSize('iOS', NULL, getOption('iOS_width'), getOption('iOS_height'), getOption('iOS_cropw'), getOption('iOS_croph'), NULL, NULL, true, NULL, NULL, NULL);
            cacheManager::addThemeCacheSize('iOS', getOption('iOS_width'), null, null, getOption('iOS_cropw'), getOption('iOS_croph'), null, null, null, null, null, null);
        }
    }

    public function getOptionsSupported()
    {
        global $_zp_gallery;
        $options = [
                        gettext('Width')					 => ['key'		       => 'iOS_width', 'type'	 => OPTION_TYPE_TEXTBOX,
                                        'desc'	                 => gettext('Width of the thumb.'),
                                        'order'	                => 1, ],
                        gettext('Height')					 => ['key'		      => 'iOS_height', 'type'	 => OPTION_TYPE_TEXTBOX,
                                        'desc'	                 => gettext('Height of the thumb.'),
                                        'order'	                => 2, ],
                        gettext('Crop width')			 => ['key'		    => 'iOS_cropw', 'type'	 => OPTION_TYPE_TEXTBOX,
                                        'desc'	                 => '',
                                        'order'	                => 3, ],
                        gettext('Crop height')		 => ['key'		    => 'iOS_croph', 'type'	 => OPTION_TYPE_TEXTBOX,
                                        'desc'	                 => '',
                                        'order'	                => 4, ],
                        gettext('Updated PHP RPC') => ['key'    => 'iOS_update', 'type' => OPTION_TYPE_CHECKBOX,
                                        'desc'                  => gettext('Enable or Disable the updating of the RPC file'),
                                        'order'                 => 5, ],
                        gettext('Display Errors')  => ['key'    => 'iOS_phperror', 'type' => OPTION_TYPE_CHECKBOX,
                                        'desc'                  => gettext('Display or Hide PHP Error'),
                                        'order'                 => 6, ],
        ];

        return $options;
    }
}
