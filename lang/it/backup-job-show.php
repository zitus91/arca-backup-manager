<?php

return [
    // Page
    'title'              => 'Riepilogo Job',
    'subtitle'           => 'Statistiche, log e ripristini per questo job di backup.',
    'back'               => 'Torna ai Job',
    'edit'               => 'Modifica Job',
    'run_now'            => 'Esegui Ora',
    'dispatched'         => 'Job di backup avviato con successo.',
    'job_saved'          => 'Job salvato con successo.',

    // Status badge
    'active'             => 'Attivo',
    'inactive'           => 'Inattivo',

    // Stat cards
    'stat_total'         => 'Backup Totali',
    'stat_success'       => 'Completati',
    'stat_failed'        => 'Falliti',
    'stat_storage'       => 'Storage Totale',
    'stat_success_rate'  => 'Tasso Successo (30g)',
    'stat_avg_duration'  => 'Durata Media (30g)',
    'stat_restores'      => 'Ripristini Totali',
    'stat_restores_ok'   => 'Ripristini Riusciti',
    'no_data'            => 'N/D',

    // Job details card
    'details'            => 'Dettagli Job',
    'detail_source'      => 'Sorgente',
    'detail_destination' => 'Destinazione',
    'detail_schedule'    => 'Pianificazione',
    'detail_type'        => 'Tipo Backup',
    'detail_retention'   => 'Retention',
    'detail_retention_n' => ':count backup',
    'detail_last_run'    => 'Ultima Esecuzione',
    'detail_next_run'    => 'Prossima Esecuzione',
    'detail_never'       => 'Mai',

    // Schedule types
    'schedule_manual'    => 'Manuale',
    'schedule_hourly'    => 'Ogni ora',
    'schedule_daily'     => 'Giornaliero',
    'schedule_weekly'    => 'Settimanale',
    'schedule_monthly'   => 'Mensile',
    'schedule_cron'      => 'Personalizzato (Cron)',

    // Backup type
    'type_full'          => 'Completo',
    'type_incremental'   => 'Incrementale',

    // Tabs
    'tab_backups'        => 'Log Backup',
    'tab_restores'       => 'Cronologia Ripristini',

    // Backup logs table
    'col_started'        => 'Avviato',
    'col_status'         => 'Stato',
    'col_type'           => 'Tipo',
    'col_size'           => 'Dimensione',
    'col_duration'       => 'Durata',
    'col_actions'        => 'Azioni',
    'col_user'           => 'Utente',
    'col_restore_type'   => 'Tipo Ripristino',
    'col_target'         => 'Destinazione',
    'col_backup_ref'     => 'Rif. Backup',

    // Status labels
    'status_pending'     => 'In attesa',
    'status_running'     => 'In esecuzione',
    'status_success'     => 'Successo',
    'status_failed'      => 'Fallito',
    'status_partial'     => 'Parziale',
    'status_cancelled'   => 'Annullato',
    'status_completed'   => 'Completato',

    // Actions
    'action_detail'      => 'Vedi Dettagli',
    'action_download'    => 'Scarica',
    'action_restore'     => 'Ripristina',
    'action_lock'        => 'Blocca (escludi dalla retention)',
    'action_unlock'      => 'Sblocca (permetti cancellazione retention)',
    'action_view_log'    => 'Vedi Log',
    'action_view_restore'=> 'Vedi Ripristini',

    // Lock status
    'locked_badge'       => 'Bloccato',
    'locked_by'          => 'Bloccato da :name il :date',

    // Empty states
    'empty_backups'      => 'Nessun log di backup per questo job.',
    'empty_restores'     => 'Nessun ripristino effettuato per questo job.',

    // Chart
    'chart_title'        => 'Attività Ultimi 14 Giorni',
    'chart_success'      => 'Successo',
    'chart_failed'       => 'Falliti',

    // Locked backups
    'locked_backups_title'   => 'Backup Fissati',
    'locked_backups_empty'   => 'Nessun backup fissato per questo job.',
    'lock_chain_locked'      => ':count backup nella catena bloccati.',
    'lock_chain_unlocked'    => ':count backup nella catena sbloccati.',
];
