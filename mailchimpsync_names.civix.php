<?php

// AUTO-GENERATED FILE -- Civix may overwrite any changes made to this file

use CRM_MailchimpsyncNames_ExtensionUtil as E;

/**
 * (Delegated) Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config
 */
function _mailchimpsync_names_civix_civicrm_config($config = NULL) {
  static $configured = FALSE;
  if ($configured) {
    return;
  }
  $configured = TRUE;

  $extRoot = __DIR__ . DIRECTORY_SEPARATOR;
  $include_path = $extRoot . PATH_SEPARATOR . get_include_path();
  set_include_path($include_path);
  // Based on <compatibility>, this does not currently require mixin/polyfill.php.
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function _mailchimpsync_names_civix_civicrm_install() {
  _mailchimpsync_names_civix_civicrm_config();
}

/**
 * (Delegated) Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function _mailchimpsync_names_civix_civicrm_enable(): void {
  _mailchimpsync_names_civix_civicrm_config();
}

/**
 * (Delegated) Implements hook_civicrm_disable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_disable
 */
function _mailchimpsync_names_civix_civicrm_disable(): void {
  // No-op.
}

/**
 * (Delegated) Implements hook_civicrm_uninstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_uninstall
 */
function _mailchimpsync_names_civix_civicrm_uninstall(): void {
  // No-op.
}

/**
 * (Delegated) Implements hook_civicrm_managed().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
 */
function _mailchimpsync_names_civix_civicrm_managed(&$entities): void {
  // No managed entities.
}

/**
 * (Delegated) Implements hook_civicrm_entityTypes().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 */
function _mailchimpsync_names_civix_civicrm_entityTypes(&$entityTypes): void {
  // No custom entity types.
}
