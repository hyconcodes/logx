<x-layouts.auth>
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Supervisor Registration')" :description="__('Enter your details below to create your supervisor account')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('supervisor.register') }}" class="flex flex-col gap-6">
            @csrf
            
            <!-- Name -->
            <flux:input
                name="name"
                :label="__('Full Name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                :placeholder="__('Enter your full name')"
                value="{{ old('name') }}"
                :error="$errors->first('name')"
            />

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Email Address')"
                type="email"
                required
                autocomplete="email"
                placeholder="lastname.firstname@bouesti.edu.ng"
                pattern="[a-zA-Z]+\.[a-zA-Z]+@bouesti\.edu\.ng"
                title="Email format: lastname.firstname@bouesti.edu.ng"
                value="{{ old('email') }}"
                :error="$errors->first('email')"
            />

            <!-- Department Selection -->
            <flux:select
                name="department_id"
                :label="__('Department')"
                required
                :error="$errors->first('department_id')"
            >
                <option value="">{{ __('Select your department') }}</option>
                @foreach($departments as $department)
                    <option value="{{ $department->id }}" {{ old('department_id') == $department->id ? 'selected' : '' }}>
                        {{ $department->name }}
                    </option>
                @endforeach
            </flux:select>

            <!-- Password -->
            <flux:input
                name="password"
                :label="__('Password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Password')"
                viewable
                :error="$errors->first('password')"
            />

            <!-- Confirm Password -->
            <flux:input
                name="password_confirmation"
                :label="__('Confirm password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Confirm password')"
                viewable
                :error="$errors->first('password_confirmation')"
            />

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full !bg-green-600 hover:!bg-green-700 !text-white" data-test="register-supervisor-button">
                    {{ __('Create Supervisor Account') }}
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Already have an account?') }}</span>
            <flux:link :href="route('login')">{{ __('Log in') }}</flux:link>
        </div>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Are you a student?') }}</span>
            <flux:link :href="route('student.register')">{{ __('Register as Student') }}</flux:link>
        </div>
    </div>
</x-layouts.auth>