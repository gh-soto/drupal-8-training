<?php
/**
 * @file
 * helper.inc
 *
 * Contains helper functions for theme.
 */

use Drupal\Core\Template\Attribute;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Theme\ThemeManager;
use Drupal\Core\Utility\ThemeRegistry;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Url;
use Drupal\Core\Menu\MenuTreeParameters;
use Symfony\Component\Yaml\Yaml;

/**
 * Retrieves a setting for the current theme or for a given theme.
 *
 * This is a wrapper for theme_get_setting(), ensuring to use deprecated
 * setting values instead.
 *
 * @param string $name
 *   The name of the setting to be retrieved.
 * @param string $theme
 *   The name of a given theme; defaults to the currently active theme.
 * @param string $prefix
 *   The prefix used on the $name of the setting, this will be appended with
 *   "_" automatically if set.
 *
 * @return mixed
 *   The value of the requested setting, NULL if the setting does not exist.
 *
 * @see theme_get_setting()
 */
function _mbase_setting($name, $theme = NULL, $prefix = 'mbase') {
  $prefix = !empty($prefix) ? $prefix . '_' : '';
  $setting = theme_get_setting($prefix . $name, $theme);
  return $setting;
}

/**
 * Helper function for adding the necessary classes to a table.
 *
 * @param array|string $classes
 *   The array of classes, passed by reference.
 * @param array|string $variables
 *   The variables of the theme hook, passed by reference.
 */
function _mbase_table_add_classes(&$classes, &$variables) {
  $context = $variables['context'];

  // Generic table class for all tables.
  $classes[] = 'table';

  // Bordered table.
  if (!empty($context['bordered']) || _mbase_setting('table_bordered') || 1) {
    $classes[] = 'table-bordered';
  }

  // Condensed table.
  if (!empty($context['condensed']) || _mbase_setting('table_condensed') || 1) {
    $classes[] = 'table-condensed';
  }

  // Hover rows.
  if (!empty($context['hover']) || _mbase_setting('table_hover') || 1) {
    $classes[] = 'table-hover';
  }

  // Striped rows.
  if (!empty($context['striped']) || _mbase_setting('table_striped') || 1) {
    $classes[] = 'table-striped';
  }

  $variables['responsive'] = !empty($context['responsive']) ? $context['responsive'] : _mbase_setting('table_responsive');
}

/**
 * Helper function for determining whether an element is a button.
 *
 * @param array $element
 *   A renderable element.
 *
 * @return bool
 *   TRUE or FALSE.
 */
function _mbase_is_button(array $element) {
  return !empty($element['#type']) &&
    !empty($element['#value']) && (
      $element['#type'] === 'button' ||
      $element['#type'] === 'submit' ||
      $element['#type'] === 'image_button'
    );
}

/**
 * Helper function for adding colors to button elements.
 *
 * @param array $variables
 *   The form variables of the element, passed by reference.
 */
function _mbase_colorize_button(array &$variables) {
  // Do not add the class if one is already present in the array.
  $button_classes = array(
    'btn-default',
    'btn-primary',
    'btn-success',
    'btn-info',
    'btn-warning',
    'btn-danger',
    'btn-link',
  );
  $class_intersection = array_intersect($button_classes, $variables['attributes']['class']);
  if (empty($class_intersection)) {
    // Get the matched class.
    $class = _mbase_setting('button_colorize') ? _mbase_colorize_text($variables['element']['#value']) : FALSE;
    // If no particular class matched, use the default style.
    if (!$class) {
      $class = 'default';
    }
    $variables['attributes']['class'][] = 'btn-' . $class;
  }
}

/**
 * Helper function for associating Bootstrap classes based on a string's value.
 *
 * @param string $string
 *   The string to match classes against.
 * @param string $default
 *   The default class to return if no match is found.
 *
 * @return string
 *   The Bootstrap class matched against the value of $haystack or $default if
 *   no match could be made.
 */
function _mbase_colorize_text($string, $default = '') {
  static $texts;
  if (!isset($texts)) {
    $filedata = _mbase_load_file_data();
    $texts = $filedata['textcolor'];
    // Allow sub-themes to alter this array of patterns.
    \Drupal::moduleHandler()->alter('mbase_colorize_text', $texts);
  }
  // Iterate over the array.
  foreach ($texts as $pattern => $strings) {
    foreach ($strings as $class => $values) {
      foreach ($values as $value) {
        switch ($pattern) {
          case 'matches':
            if ($string === $value) {
              return $class;
            }
            break;

          case 'contains':
            if (strpos(Unicode::strtolower($string), Unicode::strtolower($value)) !== FALSE) {
              return $class;
            }
            break;
        }
      }
    }
  }
  return $default;
}

/**
 * Helper function for adding icons to button elements.
 *
 * @param array $element
 *   The form element, passed by reference.
 */
function _mbase_iconize_button(array &$element) {
  if (_mbase_setting('button_iconize') && _mbase_is_button($element) && ($icon = _mbase_iconize_text($element['#value']))) {
    $element['#icon'] = $icon;
  }
}

/**
 * Helper function for associating Bootstrap icons with text.
 *
 * @param string $string
 *   The string to match classes against.
 * @param string $default
 *   The default icon to return if no match is found.
 *
 * @return string
 *   The Bootstrap icon matched against the value of $haystack or $default if
 *   no match could be made.
 */
function _mbase_iconize_text($string, $default = '') {
  static $texts;
  if (!isset($texts)) {
    $filedata = _mbase_load_file_data();
    $texts = $filedata['texticons'];
    // @todo allow sub-themes to alter this array of patterns.
    \Drupal::moduleHandler()->alter('mbase_iconize_text', $texts);
  }

  // Iterate over the array.
  foreach ($texts as $pattern => $strings) {
    if (!empty($strings)) {
      foreach ($strings as $icon => $values) {
        foreach ($values as $value) {
          switch ($pattern) {
            case 'matches':
              if ($string === $value) {
                return _mbase_icon($icon);
              }
              break;

            case 'contains':
              if (strpos(Unicode::strtolower($string), Unicode::strtolower($value)) !== FALSE) {
                return _mbase_icon($icon);
              }
              break;
          }
        }
      }
    }
  }
  // Return a default icon if nothing was matched.
  return _mbase_icon($default);
}

/**
 * Helper function to prerender input fields.
 */
function _mbase_prerender_input(&$variables) {
  $element = $variables['element'];
  $type = $element['#type'];

  // Only add the "form-control" class for specific element input types.
  $types = array(
    // Core.
    'password',
    'password_confirm',
    'select',
    'textfield',
    'machine_name',
    'entity_autocomplete',
    // HTML5.
    'email',
    // Webform module.
    'webform_email',
    'webform_number',
    // Elements module.
    'date',
    'color',
    'email',
    'number',
    'range',
    'search',
    'tel',
    'url',
  );

  if (!empty($type) && (in_array($type, $types) || ($type === 'file' && empty($element['#managed_file'])))) {
    $variables['attributes']['class'][] = 'form-control';
  }
  return $variables;
}

/**
 * Helper function for returning a Icon.
 *
 * @param string $name
 *   The icon name, minus the "glyphicon-" or "fa-" prefix.
 * @param string $icon_provider
 *   (Optional) Icon provider class prefix name, "glyphicon" or "fa".
 * @param string $default
 *   (Optional) The default value to return.
 *
 * @return string
 *   The HTML markup containing the icon defined by $name, $default value if
 *   icon does not exist or returns empty output for whatever reason.
 */
function _mbase_icon($name, $icon_provider = 'glyphicon', $default = NULL) {
  $output = NULL;
  if (_mbase_setting('fontawesome')) {
    $icon_provider = 'fa';
  }
  $filedata = _mbase_load_file_data();
  $icons = array();
  foreach ($filedata['icons'] as $key => $icon) {
    $icons[$icon[$icon_provider]] = $key;
  }

  if (in_array($name, $icons)) {
    // Attempt to use the Icon API module, if enabled and it generates output.
    if (\Drupal::moduleHandler()->moduleExists('icon')) {
      $icon = array(
        '#type' => 'icon',
        '#bundle' => 'bootstrap',
        '#icon' => $icon_provider . '-' . $name,
      );
    }
    else {
      $icon = array(
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => '',
        '#attributes' => array(
          'class' => array('icon', $icon_provider, array_search($name, $icons)),
          'aria-hidden' => 'true',
        ),
      );
    }

    $output = \Drupal::service('renderer')->render($icon);
  }
  return empty($output) && isset($default) ? $default : $output;
}

/**
 * Helper function for retrieving an element attributes array.
 *
 * @param array $element
 *   The individual renderable array element. It is possible to also pass the
 *   $variables parameter in [pre]process functions and it will logically
 *   determine the correct path to that particular theme hook's attribute array.
 *   Passed by reference.
 * @param string $property
 *   Determines which attributes array to retrieve. By default, this is the
 *   normal attributes, but can be "wrapper_attributes" or
 *   "input_group_attributes".
 *
 * @return array
 *   The attributes array. Passed by reference.
 */
function &_mbase_get_attributes(array &$element, $property = 'attributes') {
  // Attempt to retrieve a renderable element attributes first.
  if (
    isset($element['#type']) ||
    isset($element['#theme']) ||
    isset($element['#pre_render']) ||
    isset($element['#markup']) ||
    isset($element['#theme_wrappers']) ||
    isset($element["#$property"])
  ) {
    if (!isset($element["#$property"])) {
      $element["#$property"] = array();
    }
    return $element["#$property"];
  }
  // Treat $element as if it were a [pre]process function $variables parameter
  // and look for a renderable "element".
  elseif (isset($element['element'])) {
    if (!isset($element['element']["#$property"])) {
      $element['element']["#$property"] = array();
    }
    return $element['element']["#$property"];
  }

  // If all else fails, create (if needed) a default "attributes" array. This
  // will, at the very least, either work or cause an error that can be tracked.
  if (!isset($element[$property])) {
    $element[$property] = array();
  }

  return $element[$property];
}

/**
 * Helper function for retrieving an element classes array.
 *
 * @param array $element
 *   The individual renderable array element. It is possible to also pass the
 *   $variables parameter in [pre]process functions and it will logically
 *   determine the correct path to that particular theme hook's classes array.
 *   Passed by reference.
 * @param string $property
 *   Determines which attributes array to retrieve. By default, this is the
 *   normal attributes, but can be "wrapper_attributes" or
 *   "input_group_attributes".
 *
 * @return array
 *   The classes array. Passed by reference.
 */
function &_mbase_get_classes(array &$element, $property = 'attributes') {
  $attributes = &_mbase_get_attributes($element, $property);

  if (!isset($attributes['class'])) {
    $attributes['class'] = array();
  }
  // Contrib modules have a very bad habit of frequently adding classes as
  // strings, convert them to a proper array.
  // @see https://www.drupal.org/node/2269653
  elseif (!is_array($attributes['class'])) {
    $attributes['class'] = explode(' ', $attributes['class']);
  }

  // Ensure classes are not duplicated.
  $attributes['class'] = array_unique($attributes['class']);
  return $attributes['class'];
}

/**
 * Helper function for adding a class to an element.
 *
 * @param string|array $class
 *   An individual class or an array of classes to add.
 * @param array $element
 *   The individual renderable array element. It is possible to also pass the
 *   $variables parameter in [pre]process functions and it will logically
 *   determine the correct path to that particular theme hook's classes array.
 *   Passed by reference.
 * @param string $property
 *   Determines which attributes array to retrieve. By default, this is the
 *   normal attributes, but can be "wrapper_attributes" or
 *   "input_group_attributes".
 */
function _mbase_add_class($class, array &$element, $property = 'attributes') {
  // Retrieve the element's classes.
  $classes = &_mbase_get_classes($element, $property);

  // Convert the class to an array.
  if (!is_array($class)) {
    $class = array($class);
  }

  // Iterate over all classes to add.
  foreach ($class as $_class) {
    // Ensure the class to add does not yet already exist.
    if (!in_array($_class, $classes)) {
      $classes[] = $_class;
    }
  }
}

/**
 * Helper function for removing a class from an element.
 *
 * @param string|array $class
 *   An individual class or an array of classes to remove.
 * @param array $element
 *   The individual renderable array element. It is possible to also pass the
 *   $variables parameter in [pre]process functions and it will logically
 *   determine the correct path to that particular theme hook's classes array.
 *   Passed by reference.
 * @param string $property
 *   Determines which attributes array to retrieve. By default, this is the
 *   normal attributes, but can be "wrapper_attributes" or
 *   "input_group_attributes".
 */
function _mbase_remove_class($class, array &$element, $property = 'attributes') {
  // Retrieve the element's classes.
  $classes = &_mbase_get_classes($element, $property);

  // Convert the class to an array.
  if (!is_array($class)) {
    $class = array($class);
  }

  // Iterate over all classes to add.
  foreach ($class as $_class) {
    $key = array_search($_class, $classes);
    if ($key !== FALSE) {
      unset($classes[$key]);
    }
  }
}

/**
 * Helper function for returning whether a theme is Mbase based.
 *
 * @param string $theme_key
 *   The machine name of the theme to check, if not set the active theme name
 *   will be used.
 * @param bool $include_theme_key
 *   Whether to append the returned list with $theme_key.
 *
 * @return array
 *   An indexed array of base themes.
 */
function _mbase_get_base_themes($theme_key, $include_theme_key = FALSE) {
  static $base_themes;
  if (!isset($base_themes)) {
    $theme_lists = \Drupal::service('theme_handler')->listInfo();
    $base_themes = array();
    if (isset($theme_lists[$theme_key]->base_themes)) {
      foreach ($theme_lists[$theme_key]->base_themes as $key => $name) {
        $base_themes[] = $key;
      }
    }
  }
  if ($include_theme_key) {
    $base_themes[] = $theme_key;
  }
  return $base_themes;
}

/**
 * Helper function that wraps file_scan_directory().
 *
 * Finds all files that match a given mask in a given directory and then caches
 * the results. A general site cache clear will force new scans to be initiated
 * for already cached directories.
 *
 * @param string $dir
 *   The base directory or URI to scan, without trailing slash.
 * @param string $mask
 *   The preg_match() regular expression of the files to find.
 * @param array $options
 *   Additional options to pass to file_scan_directory().
 *
 * @return array
 *   An associative array (keyed on the chosen key) of objects with 'uri',
 *   'filename', and 'name' members corresponding to the matching files.
 *
 * @see file_scan_directory()
 */
function _mbase_file_scan_directory($dir, $mask, array $options = array()) {
  // Retrieve cached data.
  $cid = 'theme_registry:mbase:files';
  $files = array();
  if ($cached = \Drupal::cache()->get($cid)) {
    $files = $cached->data;
  }
  // Generate a unique hash for all parameters passed as a change in any of
  // them would return different results.
  $hash = Crypt::hashBase64(serialize(func_get_args()));
  if (!isset($files[$hash])) {
    $files[$hash] = file_scan_directory($dir, $mask, $options);
    \Drupal::cache()->set($cid, $files);
  }
  return $files[$hash];
}

/**
 * Merges the cached element information into the runtime array.
 *
 * @param array $info
 *   The element info array to merge data into.
 * @param array $cached
 *   The cached element info data array to merge from.
 *
 * @return array
 *   The altered element info array.
 */
function _mbase_element_info_array_merge(array $info, array $cached) {
  foreach ($cached as $type => $element) {
    $replacement_data = isset($element['#bootstrap_replace']) ? $element['#bootstrap_replace'] : array();
    unset($element['#bootstrap_replace']);
    foreach ($element as $property => $data) {
      if (is_array($data)) {
        if (!isset($info[$type][$property])) {
          $info[$type][$property] = array();
        }
        // Append the values if not already in the array.
        foreach ($data as $key => $value) {
          if (!in_array($value, $info[$type][$property])) {
            $info[$type][$property][] = $value;
          }
        }
      }
      // Create the property, if not already set.
      elseif (!isset($info[$type][$property])) {
        $info[$type][$property] = $data;
      }
    }
    // Replace data, if necessary.
    foreach ($replacement_data as $property => $data) {
      if (is_array($data)) {
        foreach ($data as $needle => $replacement) {
          if (!empty($info[$type][$property]) && ($key = array_search($needle, $info[$type][$property])) !== FALSE) {
            $info[$type][$property][$key] = $replacement;
          }
        }
      }
      // Replace the property with the new data.
      else {
        $info[$type][$property] = $data;
      }
    }
  }

  // Return the altered element info array.
  return $info;
}

/**
 * Helper funtion to create CSS array.
 */
function _mbase_css_alter_array_build($link, $weight, $type = 'external') {
  $css = array(
    'data' => $link,
    'type' => $type,
    'every_page' => TRUE,
    'media' => 'all',
    'preprocess' => FALSE,
    'group' => CSS_AGGREGATE_DEFAULT,
    'browsers' => array('IE' => TRUE, '!IE' => TRUE),
    'weight' => $weight,
  );
  return $css;
}

/**
 * Helper funtion to create JS array.
 */
function _mbase_js_alter_array_build($link, $weight, $type = 'external') {
  $js = drupal_js_defaults($link);
  $js['type'] = $type;
  $js['every_page'] = TRUE;
  $js['scope'] = 'footer';
  $js['weight'] = $weight;
  return $js;
}

/**
 * Helper funtion to create JSON from animate.yml.
 */
function _mbase_animate_data() {
  $animate_json = array();
  static $drupal_static_fast;
  if (!isset($drupal_static_fast)) {
    $drupal_static_fast = &drupal_static(__FUNCTION__);
  }
  // Get the static data.
  $data = &$drupal_static_fast;
  if (!isset($data)) {
    $theme_key = \Drupal::theme()->getActiveTheme()->getName();
    // Retrieve cached data.
    $cid = 'theme_registry:' . $theme_key . ':yml_animate';
    if (($cache = \Drupal::cache()->get($cid)) && !empty($cache->data)) {
      $data = $cache->data;
    }
    else {
      $base_themes = array_reverse(_mbase_get_base_themes($theme_key));
      foreach ($base_themes as $base_theme) {
        $animate_file = drupal_get_path('theme', $base_theme) . '/config/optional/animate.yml';
        if (file_exists($animate_file)) {
          $data = Yaml::parse(file_get_contents($animate_file));
          // Store our retrived data in the cache.
          \Drupal::cache()->set($cid, $data);
          break;
        }
      }
    }
  }
  return $data;
}

/**
 * Helper function to load data from YML file if not in cache.
 */
function _mbase_load_file_data() {
  // Use the advanced drupal_static pattern, since this is called very often.
  static $drupal_static_fast;
  if (!isset($drupal_static_fast)) {
    $drupal_static_fast = &drupal_static(__FUNCTION__);
  }
  // Get our static data.
  $data = &$drupal_static_fast;
  $data_types = array('icons', 'textcolor', 'texticons');
  foreach ($data_types as $type) {
    if (!isset($data[$type])) {
      // Retrieve cached data.
      $cid = 'theme_registry:mbase:yml_' . $type;
      if (($cache = \Drupal::cache()->get($cid)) && !empty($cache->data)) {
        $data[$type] = $cache->data;
      }
      else {
        // Load the data from YML file.
        $data_file = drupal_get_path('theme', 'mbase') . '/config/optional/' . $type . '.yml';
        $data[$type] = Yaml::parse(file_get_contents($data_file));
        // Store our retrived data in the cache.
        \Drupal::cache()->set($cid, $data[$type]);
      }
    }
  }
  return $data;
}
