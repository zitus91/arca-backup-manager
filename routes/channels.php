<?php

use Illuminate\Support\Facades\Broadcast;

// Public channel for backup job updates
// No auth needed - admin area only
Broadcast::channel('backup-jobs', function () {
    return true;
});
