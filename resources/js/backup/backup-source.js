// Backup Source component JS
document.addEventListener('livewire:initialized', () => {
    Livewire.on('source-saved', () => {
        Livewire.dispatch('$refresh');
    });
});
