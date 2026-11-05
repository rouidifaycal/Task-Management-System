<form method="POST" action="{{ $action }}" class="grid gap-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <flux:input
        name="title"
        :label="__('Task title')"
        :value="old('title', $task->title)"
        required
        maxlength="255"
    />
    @error('title')
        <flux:text class="text-red-600">{{ $message }}</flux:text>
    @enderror

    <flux:textarea
        name="description"
        :label="__('Description')"
        rows="5"
    >{{ old('description', $task->description) }}</flux:textarea>
    @error('description')
        <flux:text class="text-red-600">{{ $message }}</flux:text>
    @enderror

    <flux:field>
        <flux:label>{{ __('Status') }}</flux:label>
        <flux:select name="status" required>
            @foreach ($statuses as $status)
                <option value="{{ $status }}" @selected(old('status', $task->status ?? 'todo') === $status)>
                    {{ Str::headline($status) }}
                </option>
            @endforeach
        </flux:select>
        <flux:error name="status" />
    </flux:field>

    <flux:field>
        <flux:label>{{ __('Priority') }}</flux:label>
        <flux:select name="priority" required>
            @foreach ($priorities as $priority)
                <option value="{{ $priority }}" @selected(old('priority', $task->priority ?? 'medium') === $priority)>
                    {{ Str::headline($priority) }}
                </option>
            @endforeach
        </flux:select>
        <flux:error name="priority" />
    </flux:field>

    <flux:field>
        <flux:label>{{ __('Due date') }}</flux:label>
        <flux:input
            name="due_date"
            type="date"
            :value="old('due_date', $task->due_date?->format('Y-m-d'))"
        />
        <flux:error name="due_date" />
    </flux:field>

    <flux:field>
        <flux:label>{{ __('Assigned to') }}</flux:label>
        <flux:select name="assigned_to">
            <option value="">{{ __('Unassigned') }}</option>
            @foreach ($members as $member)
                <option value="{{ $member->id }}" @selected((string) old('assigned_to', $task->assigned_to ?? '') === (string) $member->id)>
                    {{ $member->name }} ({{ $member->email }})
                </option>
            @endforeach
        </flux:select>
        <flux:error name="assigned_to" />
    </flux:field>

    <div class="flex flex-wrap gap-3">
        <flux:button variant="primary" type="submit">{{ $submitLabel }}</flux:button>
        <flux:button :href="$cancelUrl" wire:navigate>{{ __('Cancel') }}</flux:button>
    </div>
</form>
