<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            To-Do List
        </x-slot>

        <x-slot name="description">
            Shared with everyone. Anyone can add or check off a task; only the creator or an Admin can remove one.
        </x-slot>

        <form wire:submit="addTodo" style="display: flex; gap: 8px; margin-bottom: 16px;">
            <div style="flex: 1;">
                <input
                    type="text"
                    wire:model="newTodo"
                    placeholder="Add a new task..."
                    style="width: 100%; padding: 8px 12px; border-radius: 8px; border: 1px solid rgba(0,0,0,0.15); background: transparent;"
                />
            </div>
            <x-filament::button type="submit" icon="heroicon-o-plus">
                Add
            </x-filament::button>
        </form>

        <div style="display: flex; flex-direction: column; gap: 6px;">
            @forelse($this->getTodos() as $todo)
                <div
                    wire:key="todo-{{ $todo->id }}"
                    style="display: flex; align-items: center; gap: 10px; padding: 8px 10px; border-radius: 8px; background: rgba(0,0,0,0.03);"
                >
                    <input
                        type="checkbox"
                        wire:click="toggleTodo({{ $todo->id }})"
                        @checked($todo->is_done)
                        style="width: 18px; height: 18px; flex-shrink: 0;"
                    />

                    <div style="flex: 1;">
                        <div style="{{ $todo->is_done ? 'text-decoration: line-through; opacity: 0.55;' : '' }}">
                            {{ $todo->description }}
                        </div>
                        <div style="font-size: 11px; opacity: 0.6; margin-top: 2px;">
                            @if($todo->is_done)
                                Done by {{ $todo->completer?->name ?? '—' }} · {{ $todo->completed_at?->diffForHumans() }}
                            @else
                                Added by {{ $todo->creator?->name ?? '—' }} · {{ $todo->created_at->diffForHumans() }}
                            @endif
                        </div>
                    </div>

                    @if($this->canDeleteTodo($todo))
                        <button
                            type="button"
                            wire:click="deleteTodo({{ $todo->id }})"
                            wire:confirm="Remove this task?"
                            style="opacity: 0.5; padding: 4px; border-radius: 6px; flex-shrink: 0;"
                            title="Remove"
                        >
                            <x-filament::icon icon="heroicon-o-trash" style="width: 16px; height: 16px;" />
                        </button>
                    @endif
                </div>
            @empty
                <div style="text-align: center; padding: 24px 0; opacity: 0.6; font-size: 14px;">
                    No tasks yet. Add one above.
                </div>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>