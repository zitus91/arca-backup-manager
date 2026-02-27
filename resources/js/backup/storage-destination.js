// Storage Destination component JS
document.addEventListener('livewire:initialized', () => {
    Livewire.on('destination-saved', () => {
        // Refresh parent component after save
        Livewire.dispatch('$refresh');
    });
});
