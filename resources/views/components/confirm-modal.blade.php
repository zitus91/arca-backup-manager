@props([
    'show'          => false,
    'title'         => __('ui.confirm_delete_title'),
    'message'       => '',
    'confirmLabel'  => __('ui.confirm_delete'),
    'cancelLabel'   => __('ui.cancel'),
    'confirmAction' => 'deleteConfirmed',
    'cancelAction'  => 'cancelDelete',
])

@if ($show)
    <div class="modal modal-open z-[200]">
        <div class="modal-box max-w-sm bg-base-100 border border-base-content/10 rounded-2xl p-0 shadow-2xl">

            {{-- Icon + Content --}}
            <div class="px-8 pt-8 pb-6 text-center">
                <div class="w-16 h-16 mx-auto rounded-2xl bg-error/10 flex items-center justify-center mb-5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-error" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold mb-2">{{ $title }}</h3>
                @if ($message)
                    <p class="text-sm text-base-content/55 leading-relaxed">{{ $message }}</p>
                @endif
            </div>

            {{-- Actions --}}
            <div class="flex gap-3 px-6 pb-6">
                <button
                    type="button"
                    wire:click="{{ $cancelAction }}"
                    wire:loading.attr="disabled"
                    wire:target="{{ $confirmAction }}"
                    class="btn btn-ghost btn-sm flex-1 rounded-xl"
                >
                    {{ $cancelLabel }}
                </button>
                <button
                    type="button"
                    wire:click="{{ $confirmAction }}"
                    wire:loading.attr="disabled"
                    wire:target="{{ $confirmAction }}"
                    class="btn btn-error btn-sm flex-1 rounded-xl"
                >
                    <span wire:loading wire:target="{{ $confirmAction }}" class="loading loading-spinner loading-xs"></span>
                    <span wire:loading.remove wire:target="{{ $confirmAction }}">{{ $confirmLabel }}</span>
                </button>
            </div>
        </div>

        {{-- Backdrop --}}
        <div
            class="modal-backdrop bg-black/60 backdrop-blur-sm"
            wire:click="{{ $cancelAction }}"
        ></div>
    </div>
@endif
