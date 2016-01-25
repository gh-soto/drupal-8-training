<?php
/**
 * @file
 * theme-settings.php
 *
 * Provides theme settings for Bootstrap based themes when admin theme is not.
 *
 * @see ./includes/settings.inc
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;
use GuzzleHttp\Exception\RequestException;


/**
 * Include common Bootstrap functions.
 */
include_once dirname(__FILE__) . '/includes/helper.inc';

/**
 * Implements hook_form_FORM_ID_alter().
 */
function mbase_form_system_theme_settings_alter(&$form, FormStateInterface $form_state, $form_id = NULL) {
  // Do not add Bootstrap specific settings to non-bootstrap based themes,
  // including a work-around for a core bug affecting admin themes.
  // @see https://drupal.org/node/943212
  $args = $form_state->getBuildInfo()['args'];
  // Do not add Bootstrap specific settings to non-bootstrap based themes.
  $theme = !empty($args[0]) ? $args[0] : FALSE;
  if (isset($form_id) || $theme === FALSE || !in_array('mbase', _mbase_get_base_themes($theme, TRUE))) {
    return;
  }

  $form['mbase'] = array(
    '#type' => 'vertical_tabs',
    '#attached' => array(
      'library'  => array('mbase/adminscript'),
    ),
    '#prefix' => '<h2><small>' . t('Modern Base theme Settings') . '</small></h2>',
    '#weight' => -10,
  );

  // General.
  $form['general'] = array(
    '#type' => 'details',
    '#title' => t('General'),
    '#group' => 'mbase',
  );

  // Container.
  $form['general']['container'] = array(
    '#type' => 'fieldset',
    '#title' => t('Container'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  $form['general']['container']['mbase_fluid_container'] = array(
    '#type' => 'checkbox',
    '#title' => t('Fluid container'),
    '#default_value' => _mbase_setting('fluid_container', $theme),
    '#description' => t('Use <code>.container-fluid</code> class. See <a href="http://getbootstrap.com/css/#grid-example-fluid">Fluid container</a>'),
  );

  // Buttons.
  $form['general']['buttons'] = array(
    '#type' => 'details',
    '#title' => t('Buttons'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  $form['general']['buttons']['mbase_button_size'] = array(
    '#type' => 'select',
    '#title' => t('Default button size'),
    '#default_value' => _mbase_setting('button_size', $theme),
    '#empty_option' => t('Normal'),
    '#options' => array(
      'btn-xs' => t('Extra Small'),
      'btn-sm' => t('Small'),
      'btn-lg' => t('Large'),
    ),
  );
  $form['general']['buttons']['mbase_button_colorize'] = array(
    '#type' => 'checkbox',
    '#title' => t('Colorize Buttons'),
    '#default_value' => _mbase_setting('button_colorize', $theme),
    '#description' => t('Adds classes to buttons based on their text value. See: <a href="!bootstrap_url" target="_blank">Buttons</a> and <a href="!api_url" target="_blank">hook_mbase_colorize_text_alter()</a>', array(
      '!bootstrap_url' => 'http://getbootstrap.com/css/#buttons',
      '!api_url' => 'http://drupalcode.org/project/bootstrap.git/blob/refs/heads/7.x-3.x:/bootstrap.api.php#l13',
    )),
  );
  $form['general']['buttons']['mbase_button_iconize'] = array(
    '#type' => 'checkbox',
    '#title' => t('Iconize Buttons'),
    '#default_value' => _mbase_setting('button_iconize', $theme),
    '#description' => t('Adds icons to buttons based on the text value. See: <a href="!api_url" target="_blank">hook_mbase_iconize_text_alter()</a>', array(
      '!api_url' => 'http://drupalcode.org/project/bootstrap.git/blob/refs/heads/7.x-3.x:/bootstrap.api.php#l37',
    )),
  );

  // Forms.
  $form['general']['forms'] = array(
    '#type' => 'details',
    '#title' => t('Forms'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  $form['general']['forms']['mbase_forms_required_has_error'] = array(
    '#type' => 'checkbox',
    '#title' => t('Make required elements display as an error'),
    '#default_value' => _mbase_setting('forms_required_has_error', $theme),
    '#description' => t('If an element in a form is required, enabling this will always display the element with a <code>.has-error</code> class. This turns the element red and helps in usability for determining which form elements are required to submit the form.  This feature compliments the "JavaScript > Forms > Automatically remove error classes when values have been entered" feature.'),
  );

  // Images.
  $form['general']['images'] = array(
    '#type' => 'details',
    '#title' => t('Images'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  $form['general']['images']['mbase_image_shape'] = array(
    '#type' => 'select',
    '#title' => t('Default image shape'),
    '#description' => t('Add classes to an <code>&lt;img&gt;</code> element to easily style images in any project. Note: Internet Explorer 8 lacks support for rounded corners. See: <a href="!bootstrap_url" target="_blank">Image Shapes</a>', array(
      '!bootstrap_url' => 'http://getbootstrap.com/css/#images-shapes',
    )),
    '#default_value' => _mbase_setting('image_shape', $theme),
    '#empty_option' => t('None'),
    '#options' => array(
      'img-rounded' => t('Rounded'),
      'img-circle' => t('Circle'),
      'img-thumbnail' => t('Thumbnail'),
    ),
  );
  $form['general']['images']['mbase_image_responsive'] = array(
    '#type' => 'checkbox',
    '#title' => t('Responsive Images'),
    '#default_value' => _mbase_setting('image_responsive', $theme),
    '#description' => t('Images in Bootstrap 3 can be made responsive-friendly via the addition of the <code>.img-responsive</code> class. This applies <code>max-width: 100%;</code> and <code>height: auto;</code> to the image so that it scales nicely to the parent element.'),
  );

  // Tables.
  $form['general']['tables'] = array(
    '#type' => 'details',
    '#title' => t('Tables'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  $form['general']['tables']['mbase_table_bordered'] = array(
    '#type' => 'checkbox',
    '#title' => t('Bordered table'),
    '#default_value' => _mbase_setting('table_bordered', $theme),
    '#description' => t('Add borders on all sides of the table and cells.'),
  );
  $form['general']['tables']['mbase_table_condensed'] = array(
    '#type' => 'checkbox',
    '#title' => t('Condensed table'),
    '#default_value' => _mbase_setting('table_condensed', $theme),
    '#description' => t('Make tables more compact by cutting cell padding in half.'),
  );
  $form['general']['tables']['mbase_table_hover'] = array(
    '#type' => 'checkbox',
    '#title' => t('Hover rows'),
    '#default_value' => _mbase_setting('table_hover', $theme),
    '#description' => t('Enable a hover state on table rows.'),
  );
  $form['general']['tables']['table_striped'] = array(
    '#type' => 'checkbox',
    '#title' => t('Striped rows'),
    '#default_value' => _mbase_setting('table_striped', $theme),
    '#description' => t('Add zebra-striping to any table row within the <code>&lt;tbody&gt;</code>. <strong>Note:</strong> Striped tables are styled via the <code>:nth-child</code> CSS selector, which is not available in Internet Explorer 8.'),
  );
  $form['general']['tables']['mbase_table_responsive'] = array(
    '#type' => 'checkbox',
    '#title' => t('Responsive tables'),
    '#default_value' => _mbase_setting('table_responsive', $theme),
    '#description' => t('Makes tables responsive by wrapping them in <code>.table-responsive</code> to make them scroll horizontally up to small devices (under 768px). When viewing on anything larger than 768px wide, you will not see any difference in these tables.'),
  );

  // Components.
  $form['components'] = array(
    '#type' => 'details',
    '#title' => t('Components'),
    '#group' => 'mbase',
  );

  // Breadcrumbs.
  $form['components']['breadcrumbs'] = array(
    '#type' => 'details',
    '#title' => t('Breadcrumbs'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  $form['components']['breadcrumbs']['mbase_breadcrumb'] = array(
    '#type' => 'select',
    '#title' => t('Breadcrumb visibility'),
    '#default_value' => _mbase_setting('breadcrumb', $theme),
    '#options' => array(
      0 => t('Hidden'),
      1 => t('Visible'),
      2 => t('Only in admin areas'),
    ),
  );
  $form['components']['breadcrumbs']['mbase_breadcrumb_home'] = array(
    '#type' => 'checkbox',
    '#title' => t('Show "Home" breadcrumb link'),
    '#default_value' => _mbase_setting('breadcrumb_home', $theme),
    '#description' => t('If your site has a module dedicated to handling breadcrumbs already, ensure this setting is enabled.'),
    '#states' => array(
      'invisible' => array(
        ':input[name="mbase_breadcrumb"]' => array('value' => 0),
      ),
    ),
  );
  $form['components']['breadcrumbs']['mbase_breadcrumb_title'] = array(
    '#type' => 'checkbox',
    '#title' => t('Show current page title at end'),
    '#default_value' => _mbase_setting('breadcrumb_title', $theme),
    '#description' => t('If your site has a module dedicated to handling breadcrumbs already, ensure this setting is disabled.'),
    '#states' => array(
      'invisible' => array(
        ':input[name="mbase_breadcrumb"]' => array('value' => 0),
      ),
    ),
  );
  $form['components']['breadcrumbs']['mbase_breadcrumb_text'] = array(
    '#type' => 'textfield',
    '#title' => t('Prefix text'),
    '#description' => t('Enter the prefix text for breadcrumbs.'),
    '#default_value' => _mbase_setting('breadcrumb_text', $theme),
  );

  // Navbar.
  $form['components']['navbar'] = array(
    '#type' => 'details',
    '#title' => t('Navbar'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  $form['components']['navbar']['mbase_navbar_position'] = array(
    '#type' => 'select',
    '#title' => t('Navbar Position'),
    '#description' => t('Select your Navbar position.'),
    '#default_value' => _mbase_setting('navbar_position', $theme),
    '#options' => array(
      'default' => t('Default'),
      'static-top' => t('Static Top'),
      'fixed-top' => t('Fixed Top'),
      'fixed-bottom' => t('Fixed Bottom'),
    ),
  );
  $form['components']['navbar']['mbase_navbar_inverse'] = array(
    '#type' => 'checkbox',
    '#title' => t('Inverse navbar style'),
    '#description' => t('Select if you want the inverse navbar style.'),
    '#default_value' => _mbase_setting('navbar_inverse', $theme),
  );

  // Region wells.
  $wells = array(
    '' => t('None'),
    'well' => t('.well (normal)'),
    'well well-sm' => t('.well-sm (small)'),
    'well well-lg' => t('.well-lg (large)'),
  );
  $form['components']['region_wells'] = array(
    '#type' => 'details',
    '#title' => t('Region wells'),
    '#description' => t('Enable the <code>.well</code>, <code>.well-sm</code> or <code>.well-lg</code> classes for specified regions. See: documentation on !wells.', array(
      '!wells' => \Drupal::l(t('Bootstrap Wells'), Url::fromUri('http://getbootstrap.com/components/#wells')),
    )),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  // Get defined regions.
  $regions = system_region_list('mbase');
  foreach ($regions as $name => $title) {
    $form['components']['region_wells']['mbase_region_well-' . $name] = array(
      '#title' => $title,
      '#type' => 'select',
      '#attributes' => array(
        'class' => array('input-sm'),
      ),
      '#options' => $wells,
      '#default_value' => _mbase_setting('region_well-' . $name, $theme),
    );
  }

  // Region visibility.
  $breakpoints = array(
    'xs' => t('Extra small devices (Phones)'),
    'sm' => t('Small devices (Tablets)'),
    'md' => t('Medium devices (Desktops)'),
    'lg' => t('Large devices (Desktops)'),
  );
  $form['components']['region_visibility'] = array(
    '#type' => 'details',
    '#title' => t('Region Visibility'),
    '#description' => t('Choose the visibility per breakpoints per region. See: documentation on !responsive.', array(
  '!responsive' => \Drupal::l(t('Bootstrap Responsive utilities'), Url::fromUri('http://getbootstrap.com/css/#responsive-utilities')),
    )),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  foreach ($regions as $name => $title) {
    $form['components']['region_visibility'][$name] = array(
      '#type' => 'details',
      '#title' => $name,
      '#collapsible' => FALSE,
    );
    foreach ($breakpoints as $devicekey => $devicename) {
      $visibility_options = array(
        'visible-' . $devicekey . '-block' => t('Display as block'),
        'visible-' . $devicekey . '-inline' => t('Display as inline'),
        'visible-' . $devicekey . '-inline-block' => t('Display as inline block'),
        'hidden-' . $devicekey => t('Hide from display'),
      );
      $form['components']['region_visibility'][$name]['mbase_region_visibility-' . $name . '-' . $devicekey] = array(
        '#title' => $devicename,
        '#type' => 'select',
        '#attributes' => array(
          'class' => array('input-sm'),
        ),
        '#options' => $visibility_options,
        '#default_value' => _mbase_setting('region_visibility-' . $name . '-' . $devicekey, $theme),
      );
    }
  }

  // JavaScript settings.
  $form['javascript'] = array(
    '#type' => 'details',
    '#title' => t('JavaScript'),
    '#group' => 'mbase',
  );

  // Anchors.
  $form['javascript']['anchors'] = array(
    '#type' => 'details',
    '#title' => t('Anchors'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  $form['javascript']['anchors']['mbase_anchors_fix'] = array(
    '#type' => 'checkbox',
    '#title' => t('Fix anchor positions'),
    '#default_value' => _mbase_setting('anchors_fix', $theme),
    '#description' => t('Ensures anchors are correctly positioned only when there is margin or padding detected on the BODY element. This is useful when fixed navbar or administration menus are used.'),
  );
  $form['javascript']['anchors']['mbase_anchors_smooth_scrolling'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable smooth scrolling'),
    '#default_value' => _mbase_setting('anchors_smooth_scrolling', $theme),
    '#description' => t('Animates page by scrolling to an anchor link target smoothly when clicked.'),
    '#states' => array(
      'invisible' => array(
        ':input[name="mbase_anchors_fix"]' => array('checked' => FALSE),
      ),
    ),
  );

  // Forms.
  $form['javascript']['forms'] = array(
    '#type' => 'details',
    '#title' => t('Forms'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  $form['javascript']['forms']['mbase_forms_has_error_value_toggle'] = array(
    '#type' => 'checkbox',
    '#title' => t('Automatically remove error classes when values have been entered'),
    '#default_value' => _mbase_setting('forms_has_error_value_toggle', $theme),
    '#description' => t('If an element has a <code>.has-error</code> class attached to it, enabling this will automatically remove that class when a value is entered. This feature compliments the "General > Forms > Make required elements display as an error" feature.'),
  );
  // Other settings.
  $form['javascript']['others'] = array(
    '#type' => 'details',
    '#title' => t('Other Settings'),
    '#description' => t("Other Bootstrap Js settings"),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );
  $form['javascript']['others']['mbase_alert_dismissible'] = array(
    '#type' => 'checkbox',
    '#title' => t('Dismissible Alert'),
    '#description' => t("Makes the alert messages dismissable."),
    '#default_value' => _mbase_setting('alert_dismissible', $theme),
  );
  // Advanced settings.
  $form['advanced'] = array(
    '#type' => 'details',
    '#title' => t('Advanced'),
    '#group' => 'mbase',
  );
  $form['advanced']['mbase_bsflatit'] = array(
    '#type' => 'checkbox',
    '#title' => t('Bootstrap Flat.it'),
    '#description' => t('Select to Remove all border radiuses to zero. Using <a href="@flatitlink" target = "_blank">Bootstrap Rate.it</a> CSS.', array('@flatitlink' => 'https://github.com/cmsbots/bsflat.it')),
    '#default_value' => _mbase_setting('bsflatit', $theme),
  );
  $form['advanced']['mbase_animatecss'] = array(
    '#type' => 'checkbox',
    '#title' => t('Add animate.css'),
    '#description' => t('Select to add CSS3 animations to site. Using <a href="@animation" target = "_blank">Animate.css</a> CSS.', array('@animation' => 'https://github.com/daneden/animate.css')),
    '#default_value' => _mbase_setting('animatecss', $theme),
  );
  $form['advanced']['mbase_fontawesome'] = array(
    '#type' => 'checkbox',
    '#title' => t('Add Font awesome'),
    '#description' => t('Add fontawesome icons, it will override the default glyphicon icons comes with Bootstrap. Make sure you added right CDN file for fontawesome to work.'),
    '#default_value' => _mbase_setting('fontawesome', $theme),
  );

  $theme_info = \Drupal::service('theme_handler')->listInfo();
  $default_cdn_css = trim(_mbase_setting('addbscsscdn', $theme));
  $default_cdn_css = $default_cdn_css ? $default_cdn_css : $theme_info['mbase']->info['bs-cdn-css'];
  $form['advanced']['mbase_addbscsscdn'] = array(
    '#type' => 'textfield',
    '#title' => t('Add Bootstrap CDN CSS file.'),
    '#description' => t("Add Bootstrap CSS CDN file. Enter Fully qualified URL."),
    '#default_value' => $default_cdn_css,
    '#required' => TRUE,
  );

  $default_cdn_js = trim(_mbase_setting('addbsjsscdn', $theme));
  $default_cdn_js = $default_cdn_js ? $default_cdn_js : $theme_info['mbase']->info['bs-cdn-css'];
  $form['advanced']['mbase_addbsjsscdn'] = array(
    '#type' => 'textfield',
    '#title' => t('Add Bootstrap CDN Javascript file.'),
    '#description' => t("Add Bootstrap Javascript CDN file. Enter Fully qualified URL."),
    '#default_value' => $default_cdn_js,
    '#required' => TRUE,
  );

  $form['advanced']['mbase_include_cdn_css'] = array(
    '#type' => 'textarea',
    '#title' => t('Add CSS files from CDN'),
    '#description' => t("Add one css file per line. It must be full URL."),
    '#default_value' => _mbase_setting('include_cdn_css', $theme),
  );
  $form['advanced']['mbase_include_cdn_js'] = array(
    '#type' => 'textarea',
    '#title' => t('Add JS files from CDN'),
    '#description' => t("Add one Javascript file per line. It must be full URL."),
    '#default_value' => _mbase_setting('include_cdn_js', $theme),
  );
}
