<x-layouts::app :title="__('Dashboard')">
    <livewire:pages::teams.pending-invitations-modal />

    <main class="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-8 p-4 md:p-8">
        <header class="flex flex-col gap-2">
            <flux:heading size="xl">{{ __('Welcome back, :name', ['name' => auth()->user()->name]) }}</flux:heading>
            <flux:text>{{ __('Here is an overview of your project workspace.') }}</flux:text>
        </header>

        <section aria-label="{{ __('Workspace overview') }}" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <a href="{{ route('projects.index') }}" wire:navigate class="rounded-xl border border-zinc-200 bg-white p-5 transition hover:border-blue-400 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text>{{ __('Projects') }}</flux:text>
                <flux:heading size="xl" class="mt-2">{{ $projectCount }}</flux:heading>
                <flux:text class="mt-1">{{ __('Projects in your workspace') }}</flux:text>
            </a>
            <article class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text>{{ __('Open tasks') }}</flux:text>
                <flux:heading size="xl" class="mt-2">{{ $openTaskCount }}</flux:heading>
                <flux:text class="mt-1">{{ __('Tasks waiting for your attention') }}</flux:text>
            </article>
            <article class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text>{{ __('Completed tasks') }}</flux:text>
                <flux:heading size="xl" class="mt-2">{{ $completedTaskCount }}</flux:heading>
                <flux:text class="mt-1">{{ __('Tasks completed so far') }}</flux:text>
            </article>
        </section>

        <section class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(16rem,1fr)]">
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">{{ __('Your projects') }}</flux:heading>
                @forelse ($projects as $project)
                    <a href="{{ route('projects.show', $project) }}" wire:navigate class="mt-4 flex items-center justify-between gap-4 rounded-lg border border-zinc-200 p-4 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">
                        <div class="min-w-0">
                            <flux:heading class="truncate">{{ $project->name }}</flux:heading>
                            <flux:text>{{ Str::headline($project->status) }}</flux:text>
                        </div>
                        <flux:badge>{{ trans_choice(':count task|:count tasks', $project->tasks_count, ['count' => $project->tasks_count]) }}</flux:badge>
                    </a>
                @empty
                    <div class="mt-6 rounded-lg border border-dashed border-zinc-300 px-5 py-10 text-center dark:border-zinc-700">
                        <flux:heading>{{ __('No projects yet') }}</flux:heading>
                        <flux:text class="mx-auto mt-2 max-w-md">
                            {{ __('Create a project to organize your team’s work.') }}
                        </flux:text>
                        <flux:button class="mt-4" variant="primary" :href="route('projects.create')" wire:navigate>
                            {{ __('Create your first project') }}
                        </flux:button>
                    </div>
                @endforelse
            </div>

            <aside class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg">{{ __('Getting started') }}</flux:heading>
                <flux:text class="mt-3">
                    {{ __('Your workspace is ready. Organize work into projects and tasks, then assign tasks to your teammates.') }}
                </flux:text>
                <flux:button class="mt-5" variant="primary" :href="route('projects.create')" wire:navigate>
                    {{ __('Create a project') }}
                </flux:button>
            </aside>
        </section>
    </main>
</x-layouts::app>
