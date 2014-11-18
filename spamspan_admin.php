<?php

/**
 * @file
 * This module implements the spamspan technique (http://www.spamspan.com ) for hiding email addresses from spambots.
 *
 * Move less frequently used code out of the .module file.
 */

class spamspan_admin {
  protected $configuration_page = 'admin/config/content/spamspan';
  protected $defaults;
  protected $display_name = 'SpamSpan';
  protected $filter;
  protected $help = '<p>The SpamSpan module obfuscates email addresses to help prevent spambots from collecting them. Read the <a href="@url">Spamspan configuration page</a>.</p>';
  protected $form_test_field = 'test';
  protected $test_text = 'My work email is me@example.com and my home email is me@example.org.';

  function __construct() {
    $info = spamspan_filter_info();
    $this->defaults = $info['spamspan']['default settings'];
  }
  function filter_is() {
    return isset($this->filter);
  }
  function filter_set($filter) {
    $this->filter = $filter;
  }
  
  /**
   * Settings callback for spamspan filter
   */
  function filter_settings($form, $form_state, $filter, $format, $defaults, $filters) {
    $filter->settings += $defaults;
  
    // spamspan '@' replacement
    $settings['spamspan_at'] = array(
      '#type' => 'textfield',
      '#title' => t('Replacement for "@"'),
      '#default_value' => $filter->settings['spamspan_at'],
      '#required' => TRUE,
      '#description' => t('Replace "@" with this text when javascript is disabled.'),
    );
    $settings['spamspan_use_graphic'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use a graphical replacement for "@"'),
      '#default_value' => $filter->settings['spamspan_use_graphic'],
      '#description' => t('Replace "@" with a graphical representation when javascript is disabled'
        . ' (and ignore the setting "Replacement for @" above).'),
    );
    $settings['spamspan_dot_enable'] = array(
      '#type' => 'checkbox',
      '#title' => t('Replace dots in email with text'),
      '#default_value' => $filter->settings['spamspan_dot_enable'],
      '#description' => t('Switch on dot replacement.'),
    );
    $settings['spamspan_dot'] = array(
      '#type' => 'textfield',
      '#title' => t('Replacement for "."'),
      '#default_value' => $filter->settings['spamspan_dot'],
      '#required' => TRUE,
      '#description' => t('Replace "." with this text.'),
    );
    $settings['use_form'] = array(
      '#type' => 'fieldset',
      '#title' => t('Use a form instead of a link'),
    );
    $settings['use_form']['spamspan_use_form'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use a form instead of a link'),
      '#default_value' => $filter->settings['spamspan_use_form'],
      '#description' => t('Link to a contact form instad of an email address.'
        . ' The following settings are used only if you select this option.'),
    );
    $settings['use_form']['spamspan_use_url'] = array(
      '#type' => 'textfield',
      '#title' => t('Replacement string for the email address'),
      '#default_value' => $filter->settings['spamspan_use_url'],
      '#required' => TRUE,
      '#description' => t('Replace the email link with this string and substitute the following <br />'
        . '#formname = the webform name,<br />'
        . '#email = the email address as typed,<br />'
        . '#displaytext = text to display for the email address.'),
    );
    $settings['use_form']['spamspan_email_encode'] = array(
      '#type' => 'checkbox',
      '#title' => t('Encode the email address'),
      '#default_value' => $filter->settings['spamspan_email_encode'],
      '#required' => TRUE,
      '#description' => t('Encode the email address using base64 to protect from spammers.'
        . ' Must be enabled for forms because the email address ends up in a URL.'),
    );
    $settings['use_form']['spamspan_email_default'] = array(
      '#type' => 'textfield',
      '#title' => t('Default form'),
      '#default_value' => $filter->settings['spamspan_email_default'],
      '#required' => TRUE,
      '#description' => t('Default form to use if none specified'),
    );
    return $settings;
  }

  /**
   * Responds to hook_help().
   */
  function help($path, $arg) {
    switch ($path) {
      case 'admin/help#spamspan':
        return t($this->help, array('@url' => $this->configuration_page));
    }
  }

  /**
   * @function
   * Generic logging function. Used mainly for development.
   */
  function log($message, $variables = array()) {
    watchdog($this->display_name, $message, $variables);
  }
  /**
   * Responds to hook_menu().
   */
  function menu() {
    $items[$this->configuration_page] = array(
      'title' => 'Spamspan',
      'description' => 'Experiment with the Spamspan function.',
      'page callback' => 'drupal_get_form',
      'page arguments' => array('spamspan_admin_page'),
      'access arguments' => array('administer filters'),
    );
    return $items;
  }

  /**
   * A helper function for the callbacks
   *
   * Replace an email addresses which has been found with the appropriate
   * <span> tags
   *
   * @param $name
   *  The user name
   * @param $domain
   *  The email domain
   * @param $contents
   *  The contents of any <a> tag
   * @param $headers
   *  The email headers extracted from a mailto: URL
   * @param $vars
   *  Optional parameters to be implemented later.
   * @param $settings
   *  Provide specific settings. They replace anything set through filter_set().
   * @return
   *  The span with which to replace the email address
   */
  function output($name, $domain, $contents = '', $headers = '', $vars = '', $settings = NULL) {
    if ($settings === NULL) {
      $settings = $this->defaults;
      if ($this->filter_is()) {
        $settings = $this->filter->settings;
      }
    }
    $at = $settings['spamspan_at'];
    if ($settings['spamspan_use_graphic']) {
      $at = '<img class="spam-span-image" alt="at" width="10" src="' . base_path() . drupal_get_path('module', 'spamspan') . '/image.gif" />';
    }
    
    // New processing for forms.
    if (isset($settings['spamspan_use_form']) and $settings['spamspan_use_form']) {
      if ($settings['spamspan_email_encode']) {
        $email = base64_encode($name . '@' . $domain) . '&en=1';
      }
      else {
        $email = $email = $name . '@' . $domain;
      }
      $var = strip_tags($var);
      if (strlen($var)) {
        $vars = explode('|', $var . ' ');
      }
      if (!isset($vars[0]) or !strlen($vars[0])) {
        $vars[0] = $settings['spamspan_email_default'];
      }
      $output = str_replace('#formname', $vars[0], $settings['spamspan_use_url']);
    }
    else {
      if (isset($settings['spamspan_dot_enable']) and $settings['spamspan_dot_enable']) {
        // Replace .'s in the address with [dot]
        $name = str_replace('.', $settings['spamspan_dot'], $name);
        $domain = str_replace('.', $settings['spamspan_dot'], $domain);
      }
      $output = '<span class="u">' . $name . '</span>' . $at . '<span class="d">' . $domain . '</span>';
    }
  
    // if there are headers, include them as eg (subject: xxx, cc: zzz)
    if (isset($headers) and $headers) {
      $temp_headers = array();
      foreach ($headers as $value) {
        //replace the = in the headers arrays by ": " to look nicer
        $temp_headers[] = str_replace('=', ': ', $value);
      }
      $output .= '<span class="h"> (' . check_plain(implode(', ', $temp_headers)) . ') </span>';
    }
    // if there are tag contents, include them, between round brackets, unless
    // the contents are an email address.  In that case, we can ignore them.  This
    // is also a good idea because otherwise the tag contents are themselves
    // converted into a spamspan, with undesirable consequences - see bug #305464.
    // NB problems may still be caused by edge cases, eg if the tag contents are
    // "blah blah email@example.com ..."
    if (isset($contents) and $contents and !(preg_match('!^' . SPAMSPAN_EMAIL . '$!ix', $contents))) {
      $output .= '<span class="t"> (' . $contents . ')</span>';
    }
    $output = '<span class="spamspan">' . $output . '</span>';
    // remove anything except certain inline elements, just in case.  NB nested
    // <a> elements are illegal.  <img> needs to be here to allow for graphic
    // @
    $output = filter_xss($output, $allowed_tags = array('em', 'strong', 'cite', 'b', 'i', 'code', 'span', 'img'));
    return $output;
  }
  
  /**
   * Return the admin page.
   * External text should be checked: = array('#markup' => check_plain($format->name));
   */
  function page($form, &$form_state) {
    $test_text = $this->test_text;
    if(isset($form_state['storage'][$this->form_test_field])) {
      $test_text = $form_state['storage'][$this->form_test_field];
    }
    $default_list = array();
    foreach ($this->defaults as $name => $value) {
      if ($value === TRUE) { $value = 'TRUE'; }
      elseif ($value === FALSE) { $value = 'FALSE'; }
      $default_list[] = $name . ': <strong>' . htmlentities($value) . '</strong>';
    }
    $form['configure'] = array('#markup' => t(
      '<p>The @dn module obfuscates email addresses to help prevent spambots from collecting them.'
      . ' It will produce clickable links if JavaScript is enabled,'
      . ' and will show the email address as <code>example [at] example [dot] com</code> if the browser does not support JavaScript.</p>'
      . '<p>To configure the module:<ol>'
      . '<li>Read the list of text formats at <a href="/admin/config/content/formats">Text formats</a>.</li>'
      . '<li>Select <strong>configure</strong> for the format requiring email addresses.</li>'
      . '<li>In <strong>Enable filters</strong>, select <em>@dn email address encoding filter</em>.</li>'
      . '<li>In <strong>Filter processing order </strong>, move @dn below <em>Convert line breaks into HTML</em> and above <em>Convert URLs into links</em>.</li>'
      . '<li>If you use the <strong>Limit allowed HTML tags</strong> filter, make sure that <span> is one of the allowed tags.</li>'
      . '<li>Select <strong>@dn email address encoding filter</strong> to configure @dn for the text format.</li>'
      . '<li>Select <strong>Save configuration</strong> to save your changes.</li>'
      . '</ol></p>'
      . '<h2>Defaults</h2>'
      . '<p>The following defaults are used for new filters and for spamspan() when there is no filter specified.</p>'
      . '<ul><li>' . implode('</li><li>', $default_list) . '</li></ul>'
      . '<h2>Test spamspan()</h2>'
      . '<p>Test the @dn <code>spamspan()</code> function using the following <strong>Test text</strong> field.'
      . ' Enter text containing an email address then hit the Test button. We set up a default example to get you started.</p>',
      array('@dn' => $this->display_name)
    ));
    $form[$this->form_test_field] = array(
      '#type' => 'textfield',
      '#title' => t('Test text'),
      '#size' => 80,
      '#maxlength' => 200,
      '#default_value' => $test_text,
    );
    $test_result = spamspan($test_text);
    $form['test_js'] = array('#markup' => '<p>The result passed through spamspan()'
      . ' and processed by Javasript:</p><div style="background-color: #ccffcc;">' . $test_result . '</div>');
    $form['test_result'] = array('#markup' => '<p>The result passed through spamspan() but not processed by Javascript:</p>'
      . '<div style="background-color: #ccccff;">'
      . str_replace('class="spamspan"', '', $test_result)
      . '</div>');
    $form['test_as_html'] = array('#markup' => '<p>The HTML in the result:</p>'
      . '<div style="background-color: #ffcccc;">' . nl2br(htmlentities($test_result)) . '</div>');
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('Test'));
    return $form;
  }
  /**
   * .
   */
  function page_submit($form, &$form_state) {
    // Store the submitted value in $form_state['storage']:
    $form_state['storage'][$this->form_test_field] = $form_state['values'][$this->form_test_field];
    // Force the form to rebuild to save the stored value.
    $form_state['rebuild'] = TRUE;
  }
}