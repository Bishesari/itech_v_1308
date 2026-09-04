<?php

use App\Services\Authorization\CurrentRoleContextService;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new
#[Layout('layouts::auth')]
#[Title('انتخاب نقش')]
class extends Component
{
    public Collection $assignments;

    public function mount(CurrentRoleContextService $contextService): void
    {
        $this->assignments = $contextService->available(
            auth()->user()->person,
        );
    }

    public function select(
        int $roleAssignmentId,
        CurrentRoleContextService $contextService,
    ) {
        $contextService->select(
            auth()->user()->person,
            $roleAssignmentId,
        );

        $this->redirectRoute('dashboard');
    }
};
