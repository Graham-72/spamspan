<?php

/**
 * @file
 * This module implements the spamspan technique (http://www.spamspan.com ) for hiding email addresses from spambots.
 *
 * Move less frequently used code out of the .module file.
  */

class spamspan_admin {
  protected $display_name = 'SpamSpan';
  protected $filter;
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
      '#description' => t('Replace "@" with a graphical representation when javascript is disabled (and ignore the setting "Replacement for @" above).'),
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
    return $settings;
  }

  /**
   * @function
   * Generic logging function. Used mainly for development.
   */
  function log($message, $variables = array()) {
    watchdog($this->display_name, $message, $variables);
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
   * @return
   *  The span with which to replace the email address
   */
  function output($name, $domain, $contents, $headers, $settings = NULL) {
    if ($settings === NULL) {
      $settings = array();
      if ($this->filter_is()) {
        $settings = $this->filter->settings;
      }
    }
    // Replace .'s in the address with [dot]
    $user_name = str_replace(".", " [dot] ", $name);
    $domain = str_replace(".", " [dot] ", $domain);
    $at = $settings['spamspan_use_graphic'] ? '<img alt="at" width="10" src="' . base_path() . drupal_get_path("module", "spamspan") . "/image.gif" . '" />' : $settings['spamspan_at'];
  
  
    $output = '<span class="spamspan"><span class="u">' . $user_name . '</span>' . $at . '<span class="d">' . $domain . '</span>';
  
    // if there are headers, include them as eg (subject: xxx, cc: zzz)
    if (isset($headers) and $headers) {
      foreach ($headers as $value) {
        //replace the = in the headers arrays by ": " to look nicer
        $temp_headers[] = str_replace("=", ": ", $value);
      }
      $output .= '<span class="h"> (' . check_plain(implode(', ', $temp_headers)) . ') </span>';
    }
    // if there are tag contents, include them, between round brackets, unless
    // the contents are an email address.  In that case, we can ignore them.  This
    // is also a good idea because otherise the tag contents are themselves
    // converted into a spamspan, with undesirable consequences - see bug #305464.
    // NB problems may still be caused by edge cases, eg if the tag contents are
    // "blah blah email@example.com ..."
    if (isset($contents) and $contents and   !(preg_match("!^" . SPAMSPAN_EMAIL . "$!ix", $contents))) {
      $output .= '<span class="t"> (' . $contents . ')</span>';
    }
    $output .= "</span>";
    // remove anything except certain inline elements, just in case.  NB nested
    // <a> elements are illegal.  <img> needs to be here to allow for graphic
    // @
    $output = filter_xss($output, $allowed_tags = array('em', 'strong', 'cite', 'b', 'i', 'code', 'span', 'img'));
    return $output;
  }
   
}