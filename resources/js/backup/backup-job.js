// Backup Job component JS
document.addEventListener('livewire:initialized', () => {
    Livewire.on('job-saved', () => {
        Livewire.dispatch('$refresh');
    });
});
