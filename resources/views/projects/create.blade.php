<x-layouts::app :title="__('New project')">
    <main class="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-6 p-4 md:p-8">
        <div class="grid gap-1">
            <flux:heading size="xl">{{ __('Create a project') }}</flux:heading>
            <flux:text>{{ __('Give your team a clear place to organize related work.') }}</flux:text>
        </div>

        <section class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            @include('projects._form', [
                'action' => route('projects.store'),
                'method' => 'POST',
                'submitLabel' => __('Create project'),
                'cancelUrl' => route('projects.index'),
            ])
        </section>
    </main>
</x-layouts::app>
