<x-layouts::app :title="__('Tasks - :name', ['name' => $project->name])">
    <main class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 md:p-8">
        <header class="flex flex-wrap items-center justify-between gap-4">
            <div class="grid gap-2">
                <flux:breadcrumbs>
                    <flux:breadcrumbs.item :href="route('projects.index')" wire:navigate>{{ __('Projects') }}</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item :href="route('projects.show', $project)" wire:navigate>{{ $project->name }}</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item>{{ __('Tasks') }}</flux:breadcrumbs.item>
                </flux:breadcrumbs>
                <flux:heading size="xl">{{ __('Tasks') }}</flux:heading>
            </div>
            <flux:button variant="primary" icon="plus" :href="route('projects.tasks.create', $project)" wire:navigate>
                {{ __('New task') }}
            </flux:button>
        </header>

        @if (session('status'))
            <flux:callout variant="success">{{ session('status') }}</flux:callout>
        @endif

        <section class="grid gap-3">
            @forelse ($tasks as $task)
                <article class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="grid min-w-0 gap-1">
                        <a href="{{ route('projects.tasks.show', [$project, $task]) }}" wire:navigate class="font-semibold hover:underline">
                            {{ $task->title }}
                        </a>
                        <flux:text>{{ $task->assignedTo?->name ?? __('Unassigned') }}</flux:text>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <flux:badge>{{ Str::headline($task->status) }}</flux:badge>
                        <flux:badge>{{ Str::headline($task->priority) }}</flux:badge>
                        @if ($task->due_date)
                            <flux:text>{{ $task->due_date->toFormattedDateString() }}</flux:text>
                        @endif
                    </div>
                </article>
            @empty
                <div class="rounded-xl border border-dashed border-zinc-300 px-6 py-12 text-center dark:border-zinc-700">
                    <flux:heading>{{ __('No tasks yet') }}</flux:heading>
                    <flux:text class="mt-2">{{ __('Add the first task to this project.') }}</flux:text>
                    <flux:button class="mt-5" variant="primary" :href="route('projects.tasks.create', $project)" wire:navigate>
                        {{ __('Create a task') }}
                    </flux:button>
                </div>
            @endforelse
        </section>

        {{ $tasks->links() }}
    </main>
</x-layouts::app>
