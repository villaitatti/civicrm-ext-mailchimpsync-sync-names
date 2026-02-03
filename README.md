# Mailchimp Sync Names

Populate Mailchimp merge fields `FNAME` and `LNAME` for subscribed members when the Mailchimpsync data-sync runs with `with_data=1`.

## Install (CiviCRM Standalone)

1. Copy the `mailchimpsync_names` folder into your CiviCRM extensions directory
   (check **Administer → System Settings → Directories → Extensions** for the path;
   commonly `web/sites/default/ext/`).
2. In CiviCRM, go to **Administer → System Settings → Extensions**.
3. Find **Mailchimp Sync Names** and click **Install**.

## How to test

Run the Mailchimpsync scheduled job with `with_data=1`:

- Scheduled Jobs UI: enable or run the Mailchimpsync job with the `with_data=1` parameter.
- API: `Mailchimpsync.fetchandreconcile` with `with_data=1`.

## How to verify

1. In Mailchimp, open a subscribed member and confirm `FNAME` and `LNAME` are populated.
2. In CiviCRM DB, check the cache row data:

```
SELECT id, civicrm_contact_id, civicrm_data
FROM civicrm_mailchimpsync_cache
WHERE civicrm_data LIKE '%mcs_name_sync%';
```

The `civicrm_data` field is a serialized PHP array. This extension stores:

```
$data['mcs_name_sync'] = [
  'previous' => ['FNAME' => '...', 'LNAME' => '...'],
  'mailchimp_updates' => ['merge_fields' => ['FNAME' => '...', 'LNAME' => '...']],
];
```

## Operator guide

- Runs only when the Mailchimpsync job is executed with `with_data=1`.
- Only updates members with Mailchimp status `subscribed`.
- Only sends `merge_fields.FNAME` and `merge_fields.LNAME`.
- Populates existing subscribers over time as they are processed by the `with_data=1` run.
- Does not blank Mailchimp names when both CiviCRM first and last name are empty;
  if names are removed in CiviCRM, Mailchimp retains its prior values.
- Skips updates when the cache row is missing a Mailchimp member ID or when the
  contact's primary email does not match the cache row email (defensive safety).
