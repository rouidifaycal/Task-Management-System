<x-layouts::app :title="$task->title">
    <main class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6 p-4 md:p-8">
        <header class="flex flex-wrap items-start justify-between gap-4">
            <div class="grid gap-2">
                <flux:breadcrumbs>
                    <flux:breadcrumbs.item :href="route('projects.index')" wire:navigate>{{ __('Projects') }}</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item :href="route('projects.show', $project)" wire:navigate>{{ $project->name }}</flux:breadcrumbs.item>
                    <flux:breadcrumbs.item>{{ $task->title }}</flux:breadcrumbs.item>
                </flux:breadcrumbs>
                <flux:heading size="xl">{{ $task->title }}</flux:heading>
                <div class="flex flex-wrap gap-2">
                    <flux:badge>{{ Str::headline($task->status) }}</flux:badge>
                    <flux:badge>{{ Str::headline($task->priority) }}</flux:badge>
                </div>
            </div>
            <div class="flex gap-2">
                <flux:button :href="route('projects.tasks.edit', [$project, $task])" wire:navigate>
                    {{ __('Edit task') }}
                </flux:button>
                <form method="POST" action="{{ route('projects.tasks.destroy', [$project, $task]) }}" onsubmit="return confirm('Delete this task and its comments?')">
                    @csrf
                    @method('DELETE')
                    <flux:button variant="danger" type="submit">{{ __('Delete') }}</flux:button>
                </form>
            </div>
        </header>

        @if (session('status'))
            <flux:callout variant="success">{{ session('status') }}</flux:callout>
        @endif

        <section class="grid gap-6 md:grid-cols-[minmax(0,2fr)_minmax(14rem,1fr)]">
            <article class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">{{ __('Description') }}</flux:heading>
                <flux:text class="mt-3 whitespace-pre-line">{{ $task->description ?: __('No description provided.') }}</flux:text>
            </article>

            <aside class="grid content-start gap-4 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">{{ __('Task details') }}</flux:heading>
                <div class="grid gap-1">
                    <flux:text>{{ __('Assigned to') }}</flux:text>
                    <flux:text>{{ $task->assignedTo?->name ?? __('Unassigned') }}</flux:text>
                </div>
                <div class="grid gap-1">
                    <flux:text>{{ __('Due date') }}</flux:text>
                    <flux:text>{{ $task->due_date?->toFormattedDateString() ?? __('Not set') }}</flux:text>
                </div>
                <div class="grid gap-1">
                    <flux:text>{{ __('Created by') }}</flux:text>
                    <flux:text>{{ $task->creator?->name ?? __('Former member') }}</flux:text>
                </div>
            </aside>
        </section>

        <section class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:heading size="lg">{{ __('Comments') }}</flux:heading>
            @forelse ($task->comments as $comment)
                <article class="mt-4 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                    <div class="flex flex-wrap justify-between gap-2">
                        <flux:text class="font-medium">{{ $comment->user?->name ?? __('Former member') }}</flux:text>
                        <flux:text>{{ $comment->created_at->toFormattedDateString() }}</flux:text>
                    </div>
                    <flux:text class="mt-2 whitespace-pre-line">{{ $comment->content }}</flux:text>
                </article>
            @empty
                <flux:text class="mt-3">{{ __('No comments yet.') }}</flux:text>
            @endforelse
        </section>
    </main>
</x-layouts::app>
