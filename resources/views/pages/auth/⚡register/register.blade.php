<div class="flex flex-col gap-6"
     x-data="{nationalityType: $wire.entangle('nationality_type'), get identityLabel() {
            return this.nationalityType == 1
                ? 'کد ملی'
                : 'شناسه اختصاصی';
        },
        get identityMaxLength() {
            return this.nationalityType == 1 ? 10 : 20;
        }
    }"
>
    <x-auth-header :title="__('ثبت نام')" :description="__('اطلاعات خواسته شده را کامل کنید.')"/>

    <x-auth-session-status class="text-center" :status="session('status')"/>
    <form wire:submit="continueRegister" class="flex flex-col gap-6" autocomplete="off">

        {{-- نام --}}
        <flux:field>
            <flux:label badge="فارسی" class="text-xs font-light!">{{ __('نام') }}</flux:label>

            <flux:input wire:model="first_name_fa" type="text" inputmode="text" required autofocus
                input:class="text-center py-6 font-semibold text-base!" maxlength="30"
            />

            <flux:error name="first_name_fa" class="-mt-2! text-xs font-light!"/>
        </flux:field>

        {{-- نام خانوادگی --}}
        <flux:field>
            <flux:label badge="فارسی" class="text-xs font-light!">{{ __('نام خانوادگی') }}</flux:label>

            <flux:input wire:model="last_name_fa" type="text" inputmode="text" required
                input:class="text-center py-6 font-semibold text-base!" maxlength="40"
            />

            <flux:error name="last_name_fa" class="-mt-2! text-xs font-light!"/>
        </flux:field>

        {{-- نوع تابعیت --}}
        <flux:radio.group x-model="nationalityType" variant="cards" class="max-sm:flex-col">
            @foreach (\App\Enums\NationalityType::cases() as $type)
                <flux:radio  value="{{ $type->value }}" label="{{ $type->label() }}" class="cursor-pointer"/>
            @endforeach
        </flux:radio.group>

        <flux:error name="nationality_type" class="-mt-5! text-xs font-light!"/>

        {{-- شناسه --}}
        <flux:field>
            <flux:label class="text-xs font-light!"><span x-text="identityLabel"></span></flux:label>

            <flux:input wire:model="identity" type="text" inputmode="numeric" dir="ltr" required
                        x-bind:maxlength="identityMaxLength"
                        input:class="text-center pt-6.5 pb-5.5 tracking-widest font-semibold text-lg!"
            />

            <flux:error name="identity" class="-mt-2! text-xs font-light!"/>
        </flux:field>


        {{-- موبایل --}}
        <flux:field>
            <flux:label badge="کد پیامکی به این شماره ارسال می گردد." class="text-xs font-light!">{{ __('شماره موبایل') }}</flux:label>

            <flux:input wire:model="mobile" type="text" inputmode="numeric" dir="ltr" maxlength="11" required
                input:class="text-center pt-6.5 pb-5.5 tracking-widest font-semibold text-lg!"
            />

            <flux:error name="mobile" class="-mt-2! text-xs font-light!"/>
        </flux:field>


        <flux:error
            name="verification"
            class="text-center text-xs font-light!"
        />

        {{-- Submit --}}
        <div class="flex items-center justify-end">
            <flux:button
                type="submit"
                variant="primary"
                color="teal"
                class="w-full cursor-pointer"
                data-test="register-user-button"
            >
                {{ __('ادامه ثبت نام') }}
            </flux:button>
        </div>
    </form>


    {{-- Login navigation --}}
    <div
        x-data="{ navigating: false }"
        x-on:livewire:navigate.window="navigating = true"
        x-on:livewire:navigated.window="navigating = false"
        x-on:livewire:navigate-error.window="navigating = false"
        class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400"
    >
        <span>{{ __('حساب کاربری داشته اید؟') }}</span>

        <flux:link :href="route('login')" wire:navigate x-data="{ loading: false }" class="mr-2"
                           @click="loading = true">
            <span x-show="!loading">{{ __('وارد شوید.') }} </span>
            <flux:icon.loading x-show="loading" class="inline size-4 text-yellow-500 mr-10"/>
        </flux:link>
    </div>

    {{-------------------------- OTP VERIFY Modal --------------------------}}

    <flux:modal
        name="verify-otp"
        class="md:w-96"
        :dismissible="false"
        focusable
    >
        <form
            wire:submit="verify_otp"
            class="space-y-8"
            autocomplete="off"
        >

            <div class="max-w-72 mx-auto space-y-2">
                <flux:heading size="lg" class="text-center">
                    {{ __('تایید کد پیامکی') }}
                </flux:heading>

                <flux:text class="text-center">
                    {{ __('کد پیامک شده را وارد کنید.') }}
                </flux:text>
            </div>

            <flux:otp
                wire:model="otp"
                id="otp-input-wrapper"
                submit="auto"
                :error:icon="false"
                error:class="text-center"
                class="mx-auto"
                dir="ltr"
            >
                <flux:otp.input autofocus />
                <flux:otp.input />
                <flux:otp.input />

                <flux:otp.separator />

                <flux:otp.input />
                <flux:otp.input />
                <flux:otp.input />
            </flux:otp>

            <div
                x-data="{
                expiresAt: $wire.entangle('otp_expires_at'),

                remaining: 0,

                timer: null,

                start() {
                    this.update();

                    clearInterval(this.timer);

                    this.timer = setInterval(() => {
                        this.update();
                    }, 1000);
                },

                update() {
                    if (!this.expiresAt) {
                        this.remaining = 0;
                        return;
                    }

                    this.remaining = Math.max(
                        0,
                        Math.ceil(
                            (
                                new Date(this.expiresAt).getTime()
                                - Date.now()
                            ) / 1000
                        )
                    );

                    if (this.remaining === 0) {
                        clearInterval(this.timer);
                    }
                },

                get minutes() {
                    return String(
                        Math.floor(this.remaining / 60)
                    ).padStart(2, '0');
                },

                get seconds() {
                    return String(
                        this.remaining % 60
                    ).padStart(2, '0');
                }
            }"
                x-init="$watch('expiresAt', () => start())"
            >
                <div class="space-y-4">
                    {{-- Countdown --}}
                    <template x-if="remaining > 0">
                        <flux:button
                            type="button"
                            class="w-full"
                            disabled
                        >
                        <span
                            dir="ltr"
                            class="tabular-nums"
                            x-text="`${minutes} : ${seconds}`"></span>
                            {{ __(' تا ارسال مجدد') }}
                        </flux:button>
                    </template>

                    {{-- Resend --}}
                    <template x-if="remaining === 0">
                        <flux:button
                            type="button"
                            wire:click="resendOtp"
                            variant="primary"
                            color="teal"
                            class="w-full cursor-pointer"
                        >
                            {{ __('ارسال مجدد کد') }}
                        </flux:button>
                    </template>

                    <flux:error
                        name="otp"
                        class="text-center text-xs font-light!"
                    />
                </div>
            </div>
        </form>
    </flux:modal>
</div>
