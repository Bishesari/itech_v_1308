<div class="flex flex-col gap-3 mb-7">

    {{-- Header --}}
    <div class="space-y-2 text-center">
        <h1 class="text-xl font-bold text-gray-800 dark:text-gray-200">
            {{ __('انتخاب نقش کاربری') }}
        </h1>

        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('برای ورود، یکی از نقش‌های زیر را انتخاب کنید.') }}
        </p>
    </div>

    {{-- Roles --}}
    @forelse($assignments as $assignment)

        <flux:callout
            wire:click="select({{ $assignment->id }})"
            color="{{ $selectedAssignmentId == $assignment->id
                ? ($assignment->role->color ?? 'indigo')
                : 'zinc' }}"
            class="cursor-pointer transition"
        >

            <flux:callout.heading class="flex items-center justify-between">

                <span class="font-medium">
                    {{ $assignment->role->name }}
                </span>

                @if($assignment->membership)
                    <span class="text-xs font-light">{{__('شعبه : ')}} {{ $assignment->membership->branch->short_name }}</span>
                @endif

            </flux:callout.heading>

        </flux:callout>

    @empty

        <p class="text-center text-gray-500 dark:text-gray-400">
            {{ __('شما هیچ نقش فعالی ندارید.') }}
        </p>

    @endforelse

    {{-- Validation --}}
    @error('selectedAssignmentId')
    <p class="text-center text-sm text-red-500">
        {{ $message }}
    </p>
    @enderror

    {{-- Continue --}}
    @if($selectedAssignmentId)

        <flux:button
            wire:click="confirm"
            variant="primary"
            color="{{ $selectedAssignment?->role->color ?? 'indigo' }}"
            class="relative w-full cursor-pointer py-2 text-sm font-medium"
        >
            <span>{{ __('ادامه با نقش انتخابی') }}</span>
        </flux:button>
    @endif

</div>
