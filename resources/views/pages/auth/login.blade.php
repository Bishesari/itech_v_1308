<x-layouts::auth :title="__('ورود')">
    <div class="flex flex-col gap-6 pb-5">
        <x-auth-header :title="__('فرم ورود به حساب')"
                       :description="__('اطلاعات حساب خود را جهت ورود وارد کنید.')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

{{--        <x-passkey-verify />--}}

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6" autocomplete="off"
              x-data="{ loading: false }"
              @submit="loading = true"
        >
            @csrf

            {{-- نام کاربری --}}
            <flux:field>
                <flux:label badge="به حروف بزرگ و کوچک حساس نیست." class="text-xs font-light!">{{ __('نام کاربری') }}</flux:label>

                <flux:input name="username" :value="old('username')" type="text" inputmode="text" required autofocus
                            input:class="text-center py-6 font-semibold text-base!" maxlength="25" dir="ltr"
                />
                <flux:error name="username" class="-mt-2! text-xs font-light!"/>
            </flux:field>

            {{-- کلمه عبور --}}
            <flux:field class="relative">
                <flux:label class="text-xs font-light!">{{ __('کلمه عبور') }}</flux:label>

                <flux:input name="password" type="password" inputmode="text" required viewable
                            input:class="text-center py-6 font-semibold text-base!" maxlength="30" dir="ltr"
                />
                @if (Route::has('password.request'))
                    <flux:link class="absolute top-0 text-sm inset-e-0" :href="route('password.request')" wire:navigate>
                        {{ __('بازیابی کلمه عبور') }}
                    </flux:link>
                @endif
            </flux:field>

            <!-- Remember Me -->
            <flux:checkbox name="remember" :label="__('بخاطرسپاری')" :checked="old('remember')" class="cursor-pointer"/>

            {{-- Submit --}}
            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full cursor-pointer"
                             data-test="login-button" color="violet" x-bind:disabled="loading" :loading="false">
                    <span x-show="!loading">
                        {{ __('ورود') }}
                    </span>
                    <span x-show="loading" class="flex items-center justify-center gap-2">
                        <span>{{ __('منتظر بمانید، در حال پردازش  ... !') }}</span>
                        <flux:icon.loading class="size-5 animate-spin" />
                    </span>
                </flux:button>
            </div>

        </form>



        <div class="space-x-1 text-sm text-center rtl:space-x-reverse text-zinc-600 dark:text-zinc-400">
            <span>{{ __('هنوز ثبت نام نکرده اید؟') }}</span>

            <flux:link :href="route('register')" wire:navigate x-data="{ loading: false }" class="mr-2"
                       @click="loading = true">
                <span x-show="!loading">{{ __('ثبت نام کنید.') }} </span>
                <flux:icon.loading x-show="loading" class="inline size-4 text-yellow-500 mr-10"/>
            </flux:link>
        </div>
    </div>
</x-layouts::auth>
