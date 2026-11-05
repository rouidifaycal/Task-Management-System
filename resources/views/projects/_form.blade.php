<form method="POST" action="{{ $action }}" class="grid gap-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <flux:input
        name="name"
        :label="__('Project name')"
        :value="old('name', $project->name)"
        required
        maxlength="255"
    />
    @error('name')
        <flux:text class="text-red-600">{{ $message }}</flux:text>
    @enderror

    <flux:textarea
        name="description"
        :label="__('Description')"
        rows="5"
    >{{ old('description', $project->description) }}</flux:textarea>
    @error('description')
        <flux:text class="text-red-600">{{ $message }}</flux:text>
    @enderror

    <flux:field>
        <flux:label>{{ __('Status') }}</flux:label>
        <flux:select name="status" required>
            @foreach ($statuses as $status)
                <option value="{{ $status }}" @selected(old('status', $project->status ?? 'planning') === $status)>
                    {{ Str::headline($status) }}
                </option>
            @endforeach
        </flux:select>
        <flux:error name="status" />
    </flux:field>

    <div class="flex flex-wrap gap-3">
        <flux:button variant="primary" type="submit">{{ $submitLabel }}</flux:button>
        <flux:button :href="$cancelUrl" wire:navigate>{{ __('Cancel') }}</flux:button>
    </div>
</form>
