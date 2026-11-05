<x-layouts::app :title="__('New task')">
    <main class="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-6 p-4 md:p-8">
        <div class="grid gap-1">
            <flux:breadcrumbs>
                <flux:breadcrumbs.item :href="route('projects.index')" wire:navigate>{{ __('Projects') }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item :href="route('projects.show', $project)" wire:navigate>{{ $project->name }}</flux:breadcrumbs.item>
                <flux:breadcrumbs.item>{{ __('New task') }}</flux:breadcrumbs.item>
            </flux:breadcrumbs>
            <flux:heading size="xl">{{ __('Create a task') }}</flux:heading>
        </div>

        <section class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            @include('tasks._form', [
                'action' => route('projects.tasks.store', $project),
                'method' => 'POST',
                'submitLabel' => __('Create task'),
                'cancelUrl' => route('projects.tasks.index', $project),
            ])
        </section>
    </main>
</x-layouts::app>
