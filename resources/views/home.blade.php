<x-layouts::auth.simple :title="__('Welcome to TaskFlow')">
    <main class="flex flex-col gap-6 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 sm:p-8">
        <div class="flex flex-col gap-3">
            <flux:badge color="blue" class="w-fit">{{ __('Project management') }}</flux:badge>
            <flux:heading size="xl">{{ __('Bring your team and work together') }}</flux:heading>
            <flux:text>{{ __('TaskFlow helps teams organize projects, assign tasks, and keep progress visible.') }}</flux:text>
        </div>

        @auth
            <flux:button variant="primary" :href="route('dashboard')" wire:navigate>
                {{ __('Open your dashboard') }}
            </flux:button>
        @else
            <div class="grid gap-3 sm:grid-cols-2">
                <flux:button variant="primary" :href="route('login')" wire:navigate>
                    {{ __('Log in') }}
                </flux:button>
                <flux:button :href="route('register')" wire:navigate>
                    {{ __('Create an account') }}
                </flux:button>
            </div>
        @endauth
    </main>
</x-layouts::auth.simple>