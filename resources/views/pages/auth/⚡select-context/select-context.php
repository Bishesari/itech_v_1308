<?php

use App\Models\RoleAssignment;
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

    public ?int $selectedAssignmentId = null;

    public ?RoleAssignment $selectedAssignment = null;

    public function mount(CurrentRoleContextService $contextService): void
    {
        $this->assignments = $contextService->available(
            auth()->user()->person,
        );
    }

    public function select(int $assignmentId): void
    {
        $this->selectedAssignmentId = $assignmentId;

        $this->selectedAssignment = $this->assignments
            ->firstWhere('id', $assignmentId);
    }

    public function confirm(CurrentRoleContextService $service): void
    {
        $this->validate([
            'selectedAssignmentId' => ['required', 'integer'],
        ]);

        $service->select(
            auth()->user()->person,
            $this->selectedAssignmentId,
        );

        $this->redirectRoute('dashboard');
    }
};
