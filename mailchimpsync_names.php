<?php

require_once 'mailchimpsync_names.civix.php';

use CRM_MailchimpsyncNames_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config
 */
function mailchimpsync_names_civicrm_config(&$config) {
  _mailchimpsync_names_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function mailchimpsync_names_civicrm_install() {
  _mailchimpsync_names_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function mailchimpsync_names_civicrm_enable() {
  _mailchimpsync_names_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_container.
 *
 * Register Mailchimpsync data-sync listeners for name merge fields.
 *
 * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
 */
function mailchimpsync_names_civicrm_container($container) {
  $container->findDefinition('dispatcher')
    ->addMethodCall('addListener', [
      'hook_mailchimpsync_data_updates_check_pre',
      'mailchimpsync_names__name_sync_pre',
    ])
    ->addMethodCall('addListener', [
      'hook_mailchimpsync_data_updates_check',
      'mailchimpsync_names__name_sync',
    ]);
}

/**
 * Bulk-load contact names and primary email for cache entries.
 *
 * Implements hook_mailchimpsync_data_updates_check_pre.
 *
 * @param Civi\Core\Event\GenericHookEvent $event
 */
function mailchimpsync_names__name_sync_pre($event) {
  if (empty($event->cache_entry_ids)) {
    return;
  }

  $ids = implode(',', array_map('intval', $event->cache_entry_ids));
  $contact_ids = CRM_Core_DAO::executeQuery(
    "SELECT DISTINCT civicrm_contact_id
     FROM civicrm_mailchimpsync_cache
     WHERE id IN ($ids) AND civicrm_contact_id IS NOT NULL"
  )->fetchMap('civicrm_contact_id', 'civicrm_contact_id');

  if (empty($contact_ids)) {
    return;
  }

  $contact_id_list = implode(',', array_map('intval', $contact_ids));
  $dao = CRM_Core_DAO::executeQuery(
    "SELECT c.id AS contact_id, c.first_name, c.last_name, e.email AS primary_email
     FROM civicrm_contact c
     LEFT JOIN civicrm_email e ON e.contact_id = c.id AND e.is_primary = 1
     WHERE c.id IN ($contact_id_list)"
  );

  $contacts = [];
  while ($dao->fetch()) {
    $contacts[(int) $dao->contact_id] = [
      'first_name' => $dao->first_name,
      'last_name' => $dao->last_name,
      'primary_email' => $dao->primary_email,
    ];
  }

  if (!isset($event->pre_data['mcs_name_sync'])) {
    $event->pre_data['mcs_name_sync'] = [];
  }
  $event->pre_data['mcs_name_sync']['contacts'] = $contacts;
}

/**
 * Add name merge field updates.
 *
 * Implements hook_mailchimpsync_data_updates_check.
 *
 * @param Civi\Core\Event\GenericHookEvent $event
 */
function mailchimpsync_names__name_sync($event) {
  $cache_entry = $event->cache_entry;
  $cache_id = (int) $cache_entry->id;
  $contact_id = (int) $cache_entry->civicrm_contact_id;
  $list_id = (string) $cache_entry->mailchimp_list_id;

  if ($cache_entry->mailchimp_status !== 'subscribed') {
    return;
  }

  if (empty($cache_entry->mailchimp_member_id)) {
    return;
  }

  $contact = $event->pre_data['mcs_name_sync']['contacts'][$contact_id] ?? NULL;
  if (!$contact) {
    return;
  }

  $first_name = isset($contact['first_name']) ? trim((string) $contact['first_name']) : '';
  $last_name = isset($contact['last_name']) ? trim((string) $contact['last_name']) : '';
  $has_name = ($first_name !== '' || $last_name !== '');

  if (!$has_name) {
    return;
  }

  $primary_email = isset($contact['primary_email']) ? trim((string) $contact['primary_email']) : '';
  $mailchimp_email = isset($cache_entry->mailchimp_email) ? trim((string) $cache_entry->mailchimp_email) : '';
  if ($primary_email !== '' && strcasecmp($primary_email, $mailchimp_email) !== 0) {
    return;
  }

  $data = mailchimpsync_names__safe_unserialize($cache_entry->civicrm_data);
  $our_data = $data['mcs_name_sync'] ?? [];
  if (!is_array($our_data)) {
    $our_data = [];
  }

  $previous = is_array($our_data['previous'] ?? NULL) ? $our_data['previous'] : [];
  $previous_fname = (string) ($previous['FNAME'] ?? '');
  $previous_lname = (string) ($previous['LNAME'] ?? '');

  $new = [
    'FNAME' => $first_name,
    'LNAME' => $last_name,
  ];

  $changed_fields = [];
  if ($new['FNAME'] !== $previous_fname) {
    $changed_fields[] = 'FNAME';
  }
  if ($new['LNAME'] !== $previous_lname) {
    $changed_fields[] = 'LNAME';
  }

  if (empty($changed_fields)) {
    return;
  }

  // Mailchimpsync merges civicrm_data[*]['mailchimp_updates'] into the member
  // update payload in CRM_Mailchimpsync_Audience::reconcileExtraData.
  // Store previous values and member updates under our namespace key.
  $our_data['previous'] = $new;
  $our_data['mailchimp_updates'] = [
    'merge_fields' => $new,
  ];
  $data['mcs_name_sync'] = $our_data;

  $cache_entry->civicrm_data = serialize($data);
  if ($cache_entry->sync_status !== 'todo') {
    $cache_entry->sync_status = 'todo';
  }

  $event->needs_saving = TRUE;

  Civi::log()->debug('MailchimpsyncNames: queued merge field updates', [
    'cache_id' => $cache_id,
    'civicrm_contact_id' => $contact_id,
    'list_id' => $list_id,
    'changed_fields' => $changed_fields,
  ]);
}

/**
 * Safely unserialize civicrm_data and return an array.
 *
 * @param string|null $value
 * @return array
 */
function mailchimpsync_names__safe_unserialize($value): array {
  if (empty($value) || !is_string($value)) {
    return [];
  }
  $data = @unserialize($value, ['allowed_classes' => FALSE]);
  return is_array($data) ? $data : [];
}
