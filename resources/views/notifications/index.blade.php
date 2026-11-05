<x-layouts::app :title="__('Notifications')">
    <main class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-6 p-4 md:p-8">
        <header class="flex flex-wrap items-center justify-between gap-4">
            <div class="grid gap-1">
                <flux:heading size="xl">{{ __('Notifications') }}</flux:heading>
                <flux:text>{{ __('Updates about work assigned to you.') }}</flux:text>
            </div>

            @if ($unreadCount > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    @method('PATCH')
                    <flux:button type="submit">{{ __('Mark all as read') }}</flux:button>
                </form>
            @endif
        </header>

        @if (session('status'))
            <flux:callout variant="success">{{ session('status') }}</flux:callout>
        @endif

        <section class="grid gap-3">
            @forelse ($notifications as $notification)
                @php($data = $notification->data)
                <article @class([
                    'flex flex-col gap-4 rounded-xl border p-5 sm:flex-row sm:items-center sm:justify-between',
                    'border-blue-300 bg-blue-50 dark:border-blue-900 dark:bg-blue-950/30' => $notification->unread(),
                    'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900' => $notification->read(),
                ])>
                    <div class="grid gap-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <flux:heading>{{ $data['title'] ?? __('Task update') }}</flux:heading>
                            @if ($notification->unread())
                                <flux:badge color="blue">{{ __('Unread') }}</flux:badge>
                            @else
                                <flux:badge>{{ __('Read') }}</flux:badge>
                            @endif
                        </div>
                        <flux:text>
                            {{ __(':task was assigned in :project by :name.', [
                                'task' => $data['task_title'] ?? __('Untitled task'),
                                'project' => $data['project_name'] ?? __('a project'),
                                'name' => $data['assigned_by'] ?? __('a teammate'),
                            ]) }}
                        </flux:text>
                        @if (! empty($data['url']))
                            <a class="mt-1 text-sm font-medium text-blue-700 underline dark:text-blue-300" href="{{ $data['url'] }}">
                                {{ __('Open task') }}
                            </a>
                        @endif
                        <flux:text class="text-xs">{{ $notification->created_at->diffForHumans() }}</flux:text>
                    </div>

                    @if ($notification->unread())
                        <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                            @csrf
                            @method('PATCH')
                            <flux:button type="submit" size="sm">{{ __('Mark as read') }}</flux:button>
                        </form>
                    @endif
                </article>
            @empty
                <div class="rounded-xl border border-dashed border-zinc-300 px-6 py-12 text-center dark:border-zinc-700">
                    <flux:heading>{{ __('You’re all caught up') }}</flux:heading>
                    <flux:text class="mt-2">{{ __('New task assignments will appear here.') }}</flux:text>
                </div>
            @endforelse
        </section>

        {{ $notifications->links() }}
    </main>
</x-layouts::app>
