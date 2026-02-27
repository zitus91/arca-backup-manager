// Dashboard component JS
document.addEventListener('livewire:initialized', () => {
    // Dashboard chart and real-time update handlers
    Livewire.on('dashboard-refreshed', () => {
        // Any additional dashboard JS behavior
    });
});
