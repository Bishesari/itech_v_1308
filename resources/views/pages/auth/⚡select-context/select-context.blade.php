<div class="mx-auto w-full max-w-lg">

    <div class="mb-6 text-center">
        <flux:heading size="xl">
            انتخاب نقش
        </flux:heading>

        <flux:text class="mt-2">
            برای ورود، نقش و شعبه موردنظر خود را انتخاب کنید.
        </flux:text>
    </div>

    <div class="flex flex-col gap-3">
        @foreach ($assignments as $assignment)
            <button
                type="button"
                wire:click="select({{ $assignment->id }})"
                wire:loading.attr="disabled"
                wire:target="select({{ $assignment->id }})"
                class="w-full text-start"
            >
                <flux:card class="transition hover:ring-2 hover:ring-primary">
                    <div class="flex items-center justify-between gap-4">

                        <div>
                            <flux:heading size="sm">
                                {{ $assignment->role->name }}
                            </flux:heading>

                            @if ($assignment->membership?->branch)
                                <flux:text size="sm" class="mt-1">
                                    {{ $assignment->membership->branch->short_name }}
                                </flux:text>
                            @else
                                <flux:text size="sm" class="mt-1">
                                    سطح آموزشگاه
                                </flux:text>
                            @endif
                        </div>

                        <flux:icon.chevron-left class="size-5" />
                    </div>
                </flux:card>
            </button>
        @endforeach
    </div>

</div>
