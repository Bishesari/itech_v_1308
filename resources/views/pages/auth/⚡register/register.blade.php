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
                input:class="text-center py-6 font-semibold text-base!" maxlength="40"
            />

            <flux:error name="first_name_fa" class="-mt-2! text-xs"/>
        </flux:field>

        {{-- نام خانوادگی --}}
        <flux:field>
            <flux:label badge="فارسی" class="text-xs font-light!">{{ __('نام خانوادگی') }}</flux:label>

            <flux:input wire:model="last_name_fa" type="text" inputmode="text" required
                input:class="text-center py-6 font-semibold text-base!" maxlength="50"
            />

            <flux:error name="last_name_fa" class="-mt-2! text-xs"/>
        </flux:field>

        {{-- نوع تابعیت --}}
        <flux:radio.group x-model="nationalityType" variant="cards" class="max-sm:flex-col">
            @foreach (\App\Enums\NationalityType::cases() as $type)
                <flux:radio  value="{{ $type->value }}" label="{{ $type->label() }}" class="cursor-pointer"/>
            @endforeach
        </flux:radio.group>

        <flux:error name="nationality_type" class="-mt-5!"/>

        {{-- شناسه --}}
        <flux:field>
            <flux:label class="text-xs font-light!"><span x-text="identityLabel"></span></flux:label>

            <flux:input wire:model="identity" type="text" inputmode="numeric" dir="ltr" required
                        x-bind:maxlength="identityMaxLength"
                        input:class="text-center pt-6.5 pb-5.5 tracking-widest font-semibold text-lg!"
            />

            <flux:error name="identity" class="-mt-2! text-xs"/>
        </flux:field>


        {{-- موبایل --}}
        <flux:field>
            <flux:label badge="پیامک به این شماره ارسال می گردد." class="text-xs font-light!">{{ __('شماره موبایل') }}</flux:label>

            <flux:input wire:model="mobile" type="text" inputmode="numeric" dir="ltr" maxlength="11" required
                input:class="text-center pt-6.5 pb-5.5 tracking-widest font-semibold text-lg!"
            />

            <flux:error name="mobile" class="-mt-2! text-xs"/>
        </flux:field>


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






















    {{-- OTP Verification Modal --}}
    <flux:modal
        name="verify-otp111"
        class="md:w-96"
        :dismissible="false"
        focusable
    >
        <div
            x-data="{
            resendAvailableAt: $wire.entangle('resendAvailableAt'),
            remaining: 0,
            timer: null,

            init() {
                this.startCooldown();

                this.$watch('resendAvailableAt', () => {
                    this.startCooldown();
                });
            },

            startCooldown() {
                this.updateRemaining();

                this.stopTimer();

                if (this.remaining > 0) {
                    this.timer = setInterval(() => {
                        this.updateRemaining();
                    }, 1000);
                }
            },

            updateRemaining() {
                if (!this.resendAvailableAt) {
                    this.remaining = 0;
                    return;
                }

                const target = new Date(this.resendAvailableAt).getTime();
                const now = Date.now();

                this.remaining = Math.max(
                    0,
                    Math.ceil((target - now) / 1000)
                );

                if (this.remaining === 0) {
                    this.stopTimer();
                }
            },

            stopTimer() {
                if (this.timer !== null) {
                    clearInterval(this.timer);
                    this.timer = null;
                }
            },

            get canResend() {
                return this.remaining === 0;
            },

            formatRemaining() {
                const minutes = Math.floor(this.remaining / 60);
                const seconds = this.remaining % 60;

                return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            },
        }"
            x-init="init()"
            class="space-y-6"
        >

            <form wire:submit="verifyOtp" class="space-y-6">

                {{-- Header --}}
                <div class="max-w-72 mx-auto space-y-2">
                    <flux:heading size="lg" class="text-center">
                        {{ __('تأیید شماره موبایل') }}
                    </flux:heading>

                    <flux:text class="text-center">
                        {{ __('کد تأیید ارسال‌شده به شماره موبایل خود را وارد کنید.') }}
                    </flux:text>
                </div>


                {{-- OTP --}}
                <flux:otp
                    wire:model="otp"
                    id="otp-input-wrapper"
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
                <flux:error
                    name="otp"
                    class="-mt-2! text-xs"
                />


                {{-- Verify --}}
                <flux:button
                    type="submit"
                    variant="primary"
                    color="teal"
                    class="w-full cursor-pointer"
                    wire:loading.attr="disabled"
                    wire:target="verifyOtp"
                >
                <span
                    wire:loading.remove
                    wire:target="verifyOtp"
                >
                    {{ __('تأیید و ادامه') }}
                </span>

                    <span
                        wire:loading
                        wire:target="verifyOtp"
                    >
                    {{ __('در حال بررسی...') }}
                </span>
                </flux:button>

            </form>


            {{-- Resend --}}
            <div class="text-center">

                <button
                    type="button"
                    class="text-sm cursor-pointer disabled:cursor-not-allowed disabled:opacity-50"
                    wire:click="resendOtp"
                    wire:loading.attr="disabled"
                    wire:target="resendOtp"
                    :disabled="!canResend"
                >
                <span
                    wire:loading.remove
                    wire:target="resendOtp"
                >
                    <template x-if="!canResend">
                        <span>
                            {{ __('ارسال مجدد') }}
                            (<span x-text="formatRemaining()"></span>)
                        </span>
                    </template>

                    <template x-if="canResend">
                        <span>
                            {{ __('ارسال مجدد کد') }}
                        </span>
                    </template>
                </span>

                    <span
                        wire:loading
                        wire:target="resendOtp"
                    >
                    {{ __('در حال ارسال...') }}
                </span>
                </button>

            </div>

        </div>
    </flux:modal>

</div>

