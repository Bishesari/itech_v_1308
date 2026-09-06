<flux:navlist.item icon="user-group" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate x-data="{ loading: false }"
                   @click="loading = true">
    <span>{{ __('داشبرد') }}</span>
    <flux:badge color="{{$context->role->color}}" size="sm" class="mr-2">{{$context->role->name}}</flux:badge>
    <flux:icon.loading x-show="loading" class="inline absolute left-2 top-2 size-3.5 text-stone-500"/>
</flux:navlist.item>


<flux:navlist.group :heading="__('اطلاعات پایه')" class="grid" expandable>

</flux:navlist.group>
