<x-layouts::app :title="__('Projects')">
    <main class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 p-4 md:p-8">
        <header class="flex flex-wrap items-center justify-between gap-4">
            <div class="grid gap-1">
                <flux:heading size="xl">{{ __('Projects') }}</flux:heading>
                <flux:text>{{ __('Projects for your current team.') }}</flux:text>
            </div>
            @can('create', [App\Models\Project::class, auth()->user()->currentTeam])
                <flux:button variant="primary" icon="plus" :href="route('projects.create')" wire:navigate>
                    {{ __('New project') }}
                </flux:button>
            @endcan
        </header>

        @if (session('status'))
            <flux:callout variant="success">{{ session('status') }}</flux:callout>
        @endif

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($projects as $project)
                <article class="flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="flex items-start justify-between gap-3">
                        <div class="grid min-w-0 gap-1">
                            <a href="{{ route('projects.show', $project) }}" wire:navigate class="font-semibold hover:underline">
                                {{ $project->name }}
                            </a>
                            <flux:text>{{ Str::headline($project->status) }}</flux:text>
                        </div>
                        <flux:badge>{{ trans_choice(':count task|:count tasks', $project->tasks_count, ['count' => $project->tasks_count]) }}</flux:badge>
                    </div>

                    @if ($project->description)
                        <flux:text class="line-clamp-3">{{ $project->description }}</flux:text>
                    @else
                        <flux:text>{{ __('No description provided.') }}</flux:text>
                    @endif

                    <div class="mt-auto flex items-center justify-between gap-3 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                        <flux:text class="text-sm">
                            {{ __('Created by :name', ['name' => $project->creator?->name ?? __('Former member')]) }}
                        </flux:text>
                        <flux:button size="sm" :href="route('projects.show', $project)" wire:navigate>
                            {{ __('Open') }}
                        </flux:button>
                    </div>
                </article>
            @empty
                <div class="rounded-xl border border-dashed border-zinc-300 px-6 py-12 text-center dark:border-zinc-700 md:col-span-2 xl:col-span-3">
                    <flux:heading>{{ __('No projects yet') }}</flux:heading>
                    <flux:text class="mt-2">{{ __('Create a project to start organizing your team’s work.') }}</flux:text>
                    @can('create', [App\Models\Project::class, auth()->user()->currentTeam])
                        <flux:button class="mt-5" variant="primary" :href="route('projects.create')" wire:navigate>
                            {{ __('Create your first project') }}
                        </flux:button>
                    @endcan
                </div>
            @endforelse
        </section>

        {{ $projects->links() }}
    </main>
</x-layouts::app>
