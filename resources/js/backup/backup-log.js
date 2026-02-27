// Backup Log component JS
document.addEventListener('livewire:initialized', () => {
    // Auto-close toast notifications after 5 seconds
    Livewire.on('backup-log-detail-opened', () => {
        // Any additional JS behavior for log details
    });
});
