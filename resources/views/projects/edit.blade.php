<x-layouts::app :title="__('Edit :name', ['name' => $project->name])">
    <main class="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-6 p-4 md:p-8">
        <div class="grid gap-1">
            <flux:heading size="xl">{{ __('Edit project') }}</flux:heading>
            <flux:text>{{ $project->name }}</flux:text>
        </div>

        <section class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            @include('projects._form', [
                'action' => route('projects.update', $project),
                'method' => 'PUT',
                'submitLabel' => __('Save changes'),
                'cancelUrl' => route('projects.show', $project),
            ])
        </section>
    </main>
</x-layouts::app>
