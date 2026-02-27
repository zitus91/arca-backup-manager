<?php

return [
    'title' => 'Restore',
    'subtitle' => 'Browse available backups and restore databases or files with "_restored" suffix.',

    // Tabs & sections
    'history' => 'Restore History',
    'available_backups' => 'Available Backups',

    // Filters
    'filter_job' => 'Backup Job',
    'filter_date_from' => 'From',
    'filter_date_to' => 'To',
    'all' => 'All',

    // Table columns
    'col_job' => 'Job',
    'col_source' => 'Source',
    'col_destination' => 'Destination',
    'col_types' => 'Types',
    'col_date' => 'Backup Date',
    'col_file' => 'File',
    'col_size' => 'Size',
    'col_actions' => 'Actions',
    'col_backup' => 'Backup',
    'col_type' => 'Restore Type',
    'col_status' => 'Status',
    'col_restored_to' => 'Restored To',
    'col_started' => 'Started',
    'col_duration' => 'Duration',
    'col_user' => 'User',

    // Restore types
    'type_full' => 'Full',
    'type_db_only' => 'Database Only',
    'type_files_only' => 'Files Only',
    'type_full_desc' => 'Restore DB and files',
    'type_db_only_desc' => 'Restore only databases',
    'type_files_only_desc' => 'Restore only files',

    // Status
    'status_pending' => 'Pending',
    'status_running' => 'Running',
    'status_success' => 'Success',
    'status_failed' => 'Failed',

    // Buttons
    'restore_btn' => 'Restore',
    'cancel' => 'Cancel',
    'continue' => 'Continue',
    'back' => 'Back',
    'execute' => 'Execute Restore',
    'close' => 'Close',
    'detail' => 'Details',

    // Modal
    'modal_title' => 'Restore Backup',
    'info_job' => 'Backup Job',
    'info_source' => 'Source',
    'info_date' => 'Backup Date',
    'info_size' => 'File Size',
    'backup_contains' => 'Backup Contents',
    'select_type' => 'What do you want to restore?',

    // Warnings
    'warning_title' => 'Important',
    'warning_desc' => 'Databases will be restored with "_restored_TIMESTAMP" suffix (e.g. mydb → mydb_restored_20260227_143000). Files will be restored to the original path with the same suffix (e.g. /data/app → /data/app_restored_20260227_143000).',

    // Confirmation
    'confirm_title' => 'Confirm Restore',
    'confirm_desc' => 'Are you sure you want to proceed with the restore? This operation cannot be undone.',

    // Detail modal
    'detail_title' => 'Restore Details',
    'detail_finished' => 'Finished',
    'detail_db' => 'Restored Database',
    'detail_path' => 'Restored Path',
    'detail_error' => 'Error',
    'detail_meta' => 'Metadata',

    // Messages
    'restore_started' => 'Restore started. You will be notified when it completes.',
    'backup_not_available' => 'This backup is not available for restore.',
    'no_backups' => 'No successful backups available for restore.',

    // Toast
    'toast_started' => 'Restore in progress...',
    'toast_success' => 'Restore completed successfully!',
    'toast_failed' => 'Restore failed.',
];
