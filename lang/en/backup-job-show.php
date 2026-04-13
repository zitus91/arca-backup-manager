<?php

return [
    // Page
    'title'              => 'Job Overview',
    'subtitle'           => 'Statistics, logs and restores for this backup job.',
    'back'               => 'Back to Jobs',
    'edit'               => 'Edit Job',
    'run_now'            => 'Run Now',
    'dispatched'         => 'Backup job dispatched successfully.',
    'job_saved'          => 'Job saved successfully.',

    // Status badge
    'active'             => 'Active',
    'inactive'           => 'Inactive',

    // Stat cards
    'stat_total'         => 'Total Backups',
    'stat_success'       => 'Successful',
    'stat_failed'        => 'Failed',
    'stat_storage'       => 'Total Storage',
    'stat_success_rate'  => 'Success Rate (30d)',
    'stat_avg_duration'  => 'Avg. Duration (30d)',
    'stat_restores'      => 'Total Restores',
    'stat_restores_ok'   => 'Successful Restores',
    'no_data'            => 'N/A',

    // Job details card
    'details'            => 'Job Details',
    'detail_source'      => 'Source',
    'detail_destination' => 'Destination',
    'detail_schedule'    => 'Schedule',
    'detail_type'        => 'Backup Type',
    'detail_retention'   => 'Retention',
    'detail_retention_n' => ':count backups',
    'detail_last_run'    => 'Last Run',
    'detail_next_run'    => 'Next Run',
    'detail_never'       => 'Never',

    // Schedule types
    'schedule_manual'    => 'Manual',
    'schedule_hourly'    => 'Hourly',
    'schedule_daily'     => 'Daily',
    'schedule_weekly'    => 'Weekly',
    'schedule_monthly'   => 'Monthly',
    'schedule_cron'      => 'Custom (Cron)',

    // Backup type
    'type_full'          => 'Full',
    'type_incremental'   => 'Incremental',

    // Tabs
    'tab_backups'        => 'Backup Logs',
    'tab_restores'       => 'Restore History',

    // Backup logs table
    'col_started'        => 'Started',
    'col_status'         => 'Status',
    'col_type'           => 'Type',
    'col_size'           => 'Size',
    'col_duration'       => 'Duration',
    'col_actions'        => 'Actions',
    'col_user'           => 'User',
    'col_restore_type'   => 'Restore Type',
    'col_target'         => 'Target',
    'col_backup_ref'     => 'Backup Ref',

    // Status labels
    'status_pending'     => 'Pending',
    'status_running'     => 'Running',
    'status_success'     => 'Success',
    'status_failed'      => 'Failed',
    'status_partial'     => 'Partial',
    'status_cancelled'   => 'Cancelled',
    'status_completed'   => 'Completed',

    // Actions
    'action_detail'      => 'View Details',
    'action_download'    => 'Download',
    'action_restore'     => 'Restore',
    'action_lock'        => 'Lock (preserve from retention)',
    'action_unlock'      => 'Unlock (allow retention deletion)',
    'action_view_log'    => 'View Logs',
    'action_view_restore'=> 'View Restores',

    // Lock status
    'locked_badge'       => 'Locked',
    'locked_by'          => 'Locked by :name on :date',

    // Empty states
    'empty_backups'      => 'No backup logs yet for this job.',
    'empty_restores'     => 'No restores have been performed for this job.',

    // Chart
    'chart_title'        => 'Last 14 Days Activity',
    'chart_success'      => 'Success',
    'chart_failed'       => 'Failed',

    // Locked backups
    'locked_backups_title'   => 'Pinned Backups',
    'locked_backups_empty'   => 'No pinned backups for this job.',
    'lock_chain_locked'      => ':count backup(s) in the chain locked.',
    'lock_chain_unlocked'    => ':count backup(s) in the chain unlocked.',
];
