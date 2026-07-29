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

    // Granular selection
    'select_items_hint' => 'Select the items to restore',
    'no_items_selected' => 'Select at least one database or path to restore.',
    'confirm_databases' => 'Databases to restore',
    'confirm_paths' => 'Paths to restore',

    // Restore target
    'restore_target' => 'Restore Target',
    'target_same_host' => 'Same Host',
    'target_same_host_desc' => 'Restore to the original server',
    'target_remote_host' => 'Remote Host',
    'target_remote_host_desc' => 'Restore to a different server',
    'target_known_host' => 'Known Host',
    'target_known_host_desc' => 'Use credentials from a registered host',
    'known_host_select' => 'Select Host',
    'known_host_required' => 'Select a registered host to restore to.',
    'known_host_summary' => 'Credentials taken from the selected host',

    // Remote config
    'remote_config' => 'Remote Host Configuration',
    'remote_mysql_config' => 'MySQL Connection',
    'remote_postgres_config' => 'PostgreSQL Connection',
    'remote_mongodb_config' => 'MongoDB Connection',
    'remote_filesystem_config' => 'SSH Connection (Filesystem)',
    'remote_host' => 'Host',
    'remote_port' => 'Port',
    'remote_username' => 'Username',
    'remote_password' => 'Password',
    'remote_auth_database' => 'Auth Database',
    'remote_ssh_host' => 'SSH Host',
    'remote_ssh_port' => 'SSH Port',
    'remote_ssh_user' => 'SSH User',
    'remote_ssh_key_path' => 'SSH Key Path',

    // Custom names
    'reset_names' => 'Reset to defaults',
    'custom_name_empty' => 'Target name cannot be empty for selected items.',

    // Override
    'override_existing' => 'Override existing data',
    'override_existing_desc' => 'If a database or directory with the target name already exists, it will be completely replaced.',
    'override_warning_title' => 'Danger - Irreversible Operation',
    'override_warning_1' => 'Existing databases with the same name will be DROPPED and all data will be permanently lost.',
    'override_warning_2' => 'Existing directories will be completely deleted before restoring.',
    'override_warning_3' => 'This operation CANNOT be undone. Make sure you have a backup of the current data.',
    'override_same_name_warning' => 'The original database will be overwritten!',
    'override_confirm_title' => 'CRITICAL WARNING',
    'override_confirm_desc' => 'You have enabled override mode. Existing databases and/or directories with the target names will be PERMANENTLY DELETED before restore. This action is IRREVERSIBLE.',

    // Confirmation additions
    'confirm_target' => 'Restore Target',
    'confirm_target_same' => 'Same Host (Original Server)',
    'confirm_target_remote' => 'Remote Host',
    'confirm_target_known' => 'Known Host (from Source)',
    'confirm_override' => 'Override Mode',
    'confirm_override_yes' => 'YES - Existing data will be replaced',
    'confirm_override_no' => 'No - Safe restore',

    // Validation
    'remote_mysql_required' => 'Please provide MySQL remote host connection details.',
    'remote_postgres_required' => 'Please provide PostgreSQL remote host connection details.',
    'remote_mongodb_required' => 'Please provide MongoDB remote host connection details.',
    'remote_filesystem_required' => 'Please provide SSH connection details for filesystem restore.',

    // Warnings
    'warning_title' => 'Important',
    'warning_db_header' => 'Databases',
    'warning_fs_header' => 'Filesystem',
    'warning_override_tag' => 'Overwrite!',
    'warning_drop_if_exists_tag' => 'Drop if exists',
    'warning_rm_if_exists_tag' => 'Delete if exists',
    'warning_remote_target' => 'The restore will be executed on the configured remote host.',
    'warning_same_target' => 'The restore will be executed on the original source host.',
    'warning_override_active' => '⚠ Override mode is active: existing data with the same target name will be permanently destroyed before restore.',

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
