<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Todo;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class TodoList extends Widget
{
    protected string $view = 'filament.admin.widgets.todo-list';

    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    public string $newTodo = '';

    public function getTodos()
    {
        return Todo::query()
            ->with(['creator', 'completer'])
            ->orderBy('is_done')
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->get();
    }

    public function addTodo(): void
    {
        $description = trim($this->newTodo);

        if ($description === '') {
            return;
        }

        Todo::create([
            'description' => $description,
            'created_by' => Auth::id(),
        ]);

        $this->newTodo = '';
    }

    public function toggleTodo(int $todoId): void
    {
        $todo = Todo::find($todoId);

        if (! $todo) {
            return;
        }

        if ($todo->is_done) {
            $todo->update([
                'is_done' => false,
                'completed_by' => null,
                'completed_at' => null,
            ]);
        } else {
            $todo->update([
                'is_done' => true,
                'completed_by' => Auth::id(),
                'completed_at' => now(),
            ]);
        }
    }

    public function canDeleteTodo(Todo $todo): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return $todo->created_by === $user->id || $user->canViewProfit();
    }

    public function deleteTodo(int $todoId): void
    {
        $todo = Todo::find($todoId);

        if (! $todo || ! $this->canDeleteTodo($todo)) {
            return;
        }

        $todo->delete();
    }
}