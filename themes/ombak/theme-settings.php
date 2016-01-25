<?php
/**
 * @file
 * theme-settings.php
 *
 * Provides theme settings for Ombak themes.
 */

/**
 * Implements hook_form_FORM_ID_alter().
 */
function ombak_form_system_theme_settings_alter(&$form, $form_state) {
  $module_handler = \Drupal::service('module_handler');

  if ($module_handler->moduleExists('search')) {
    $form['theme_settings']['toggle_top_bar_search'] = array(
      '#type' => 'checkbox',
      '#title' => t('Top bar search form'),
      '#default_value' => theme_get_setting('features.top_bar_search'),
    );
  }
}
