<x-layouts::app :title="$project->name">
    <main class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 md:p-8">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="grid gap-2">
                <flux:breadcrumbs>
                    <flux:breadcrumbs.item :href="route('projects.index')" wire:navigate>{{ __('Projects') }}</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item>{{ $project->name }}</flux:breadcrumbs.item>
                </flux:breadcrumbs>
                <flux:heading size="xl">{{ $project->name }}</flux:heading>
                <flux:badge>{{ Str::headline($project->status) }}</flux:badge>
            </div>
            <div class="flex flex-wrap gap-2">
                @can('create', [App\Models\Task::class, $project])
                    <flux:button variant="primary" icon="plus" :href="route('projects.tasks.create', $project)" wire:navigate>
                        {{ __('New task') }}
                    </flux:button>
                @endcan
                @can('update', $project)
                    <flux:button :href="route('projects.edit', $project)" wire:navigate>
                        {{ __('Edit project') }}
                    </flux:button>
                @endcan
                @can('delete', $project)
                    <form method="POST" action="{{ route('projects.destroy', $project) }}" onsubmit="return confirm('Delete this project and all its tasks?')">
                        @csrf
                        @method('DELETE')
                        <flux:button variant="danger" type="submit">{{ __('Delete') }}</flux:button>
                    </form>
                @endcan
            </div>
        </div>

        @if (session('status'))
            <flux:callout variant="success">{{ session('status') }}</flux:callout>
        @endif

        <section class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(16rem,1fr)]">
            <div class="grid content-start gap-6">
                <article class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:heading size="lg">{{ __('About this project') }}</flux:heading>
                    <flux:text class="mt-3 whitespace-pre-line">{{ $project->description ?: __('No description provided.') }}</flux:text>
                </article>

                <article class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <flux:heading size="lg">{{ __('Recent tasks') }}</flux:heading>
                        <flux:button size="sm" :href="route('projects.tasks.index', $project)" wire:navigate>
                            {{ __('All tasks') }}
                        </flux:button>
                    </div>

                    <div class="mt-4 grid gap-3">
                        @forelse ($project->tasks as $task)
                            <a href="{{ route('projects.tasks.show', [$project, $task]) }}" wire:navigate class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-zinc-200 p-4 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">
                                <span class="font-medium">{{ $task->title }}</span>
                                <span class="flex items-center gap-2">
                                    <flux:badge>{{ Str::headline($task->status) }}</flux:badge>
                                    <flux:text>{{ $task->assignedTo?->name ?? __('Unassigned') }}</flux:text>
                                </span>
                            </a>
                        @empty
                            <flux:text class="py-6 text-center">{{ __('No tasks in this project yet.') }}</flux:text>
                        @endforelse
                    </div>
                </article>
            </div>

            <aside class="grid content-start gap-4 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">{{ __('Project details') }}</flux:heading>
                <div class="grid gap-1">
                    <flux:text>{{ __('Created by') }}</flux:text>
                    <flux:text>{{ $project->creator?->name ?? __('Former member') }}</flux:text>
                </div>
                <div class="grid gap-1">
                    <flux:text>{{ __('Tasks') }}</flux:text>
                    <flux:text>{{ $project->tasks_count }}</flux:text>
                </div>
                <div class="grid gap-1">
                    <flux:text>{{ __('Created') }}</flux:text>
                    <flux:text>{{ $project->created_at->toFormattedDateString() }}</flux:text>
                </div>
            </aside>
        </section>
    </main>
</x-layouts::app>
