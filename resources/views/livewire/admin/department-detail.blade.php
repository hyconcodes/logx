<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

new class extends Component {
    use WithPagination;

    public $departmentId;
    public $department;
    public $selectedStudents = [];
    public $selectedSupervisor = null;
    public $searchStudents = '';
    public $searchSupervisors = '';
    public $showBulkAssignModal = false;

    public function mount($id)
    {
        $this->departmentId = $id;
        $this->department = Department::with(['students', 'supervisors'])->findOrFail($id);
    }

    public function with()
    {
        $studentsQuery = $this->department->students()
            ->when($this->searchStudents, function (Builder $query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->searchStudents . '%')
                      ->orWhere('email', 'like', '%' . $this->searchStudents . '%')
                      ->orWhere('matric_no', 'like', '%' . $this->searchStudents . '%');
                });
            });

        $supervisorsQuery = $this->department->supervisors()
            ->when($this->searchSupervisors, function (Builder $query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->searchSupervisors . '%')
                      ->orWhere('email', 'like', '%' . $this->searchSupervisors . '%');
                });
            });

        return [
            'students' => $studentsQuery->paginate(10, ['*'], 'studentsPage'),
            'supervisors' => $supervisorsQuery->paginate(10, ['*'], 'supervisorsPage'),
            'allSupervisors' => $this->department->supervisors()->get(),
            'studentsWithoutSupervisor' => $this->department->students()->whereNull('supervisor_id')->count(),
            'totalStudents' => $this->department->students()->count(),
            'totalSupervisors' => $this->department->supervisors()->count(),
        ];
    }

    public function toggleStudentSelection($studentId)
    {
        if (in_array($studentId, $this->selectedStudents)) {
            $this->selectedStudents = array_diff($this->selectedStudents, [$studentId]);
        } else {
            $this->selectedStudents[] = $studentId;
        }
    }

    public function selectAllStudents()
    {
        $studentIds = $this->department->students()
            ->when($this->searchStudents, function (Builder $query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->searchStudents . '%')
                      ->orWhere('email', 'like', '%' . $this->searchStudents . '%')
                      ->orWhere('matric_no', 'like', '%' . $this->searchStudents . '%');
                });
            })
            ->pluck('id')
            ->toArray();

        $this->selectedStudents = $studentIds;
    }

    public function deselectAllStudents()
    {
        $this->selectedStudents = [];
    }

    public function openBulkAssignModal()
    {
        if (empty($this->selectedStudents)) {
            session()->flash('error', 'Please select at least one student.');
            return;
        }
        $this->showBulkAssignModal = true;
    }

    public function closeBulkAssignModal()
    {
        $this->showBulkAssignModal = false;
        $this->selectedSupervisor = null;
    }

    public function bulkAssignSupervisor()
    {
        $this->validate([
            'selectedSupervisor' => 'required|exists:users,id',
        ]);

        if (empty($this->selectedStudents)) {
            session()->flash('error', 'No students selected.');
            return;
        }

        // Check if supervisor exists and belongs to this department
        $supervisor = User::where('id', $this->selectedSupervisor)
            ->where('department_id', $this->departmentId)
            ->whereHas('roles', function ($query) {
                $query->where('name', 'supervisor');
            })
            ->first();

        if (!$supervisor) {
            session()->flash('error', 'Invalid supervisor selected.');
            return;
        }

        // Update selected students
        User::whereIn('id', $this->selectedStudents)
            ->where('department_id', $this->departmentId)
            ->update(['supervisor_id' => $this->selectedSupervisor]);

        session()->flash('success', 'Successfully assigned supervisor to ' . count($this->selectedStudents) . ' students.');
        
        $this->selectedStudents = [];
        $this->selectedSupervisor = null;
        $this->showBulkAssignModal = false;
    }

    public function removeSupervisorFromStudent($studentId)
    {
        User::where('id', $studentId)
            ->where('department_id', $this->departmentId)
            ->update(['supervisor_id' => null]);

        session()->flash('success', 'Supervisor removed from student.');
    }

    public function updatedSearchStudents()
    {
        $this->resetPage('studentsPage');
        $this->selectedStudents = [];
    }

    public function updatedSearchSupervisors()
    {
        $this->resetPage('supervisorsPage');
    }
}; ?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $department->name }}</h1>
            <p class="text-gray-600">{{ $department->description ?: 'No description available' }}</p>
        </div>
        <div class="flex items-center space-x-4">
            <a href="{{ route('admin.departments') }}" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to Departments
            </a>
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-md">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md">
            {{ session('error') }}
        </div>
    @endif

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Students</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $totalStudents }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Supervisors</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $totalSupervisors }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="p-2 bg-yellow-100 rounded-lg">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 15.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Unassigned Students</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $studentsWithoutSupervisor }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="p-2 bg-indigo-100 rounded-lg">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Department Status</p>
                    <p class="text-lg font-bold {{ $department->status ? 'text-green-600' : 'text-red-600' }}">
                        {{ $department->status ? 'Active' : 'Inactive' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Students Section -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Students</h3>
                <div class="flex items-center space-x-4">
                    @if(count($selectedStudents) > 0)
                        <span class="text-sm text-gray-600">{{ count($selectedStudents) }} selected</span>
                        <button wire:click="openBulkAssignModal" 
                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Assign Supervisor
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="p-6">
            <!-- Search and Bulk Actions -->
            <div class="flex flex-col sm:flex-row gap-4 mb-6">
                <div class="flex-1">
                    <input wire:model.live="searchStudents" 
                           type="text" 
                           placeholder="Search students..." 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div class="flex space-x-2">
                    <button wire:click="selectAllStudents" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        Select All
                    </button>
                    <button wire:click="deselectAllStudents" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        Deselect All
                    </button>
                </div>
            </div>

            <!-- Students Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <input type="checkbox" 
                                       wire:click="selectAllStudents"
                                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Student
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Matric No
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Supervisor
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($students as $student)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="checkbox" 
                                           wire:click="toggleStudentSelection({{ $student->id }})"
                                           @if(in_array($student->id, $selectedStudents)) checked @endif
                                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-gray-100 flex items-center justify-center">
                                                <span class="text-sm font-medium text-gray-600">
                                                    {{ strtoupper(substr($student->name, 0, 2)) }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">{{ $student->name }}</div>
                                            <div class="text-sm text-gray-500">{{ $student->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $student->matric_no }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($student->supervisor)
                                        <div class="text-sm text-gray-900">{{ $student->supervisor->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $student->supervisor->email }}</div>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            Unassigned
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    @if($student->supervisor)
                                        <button wire:click="removeSupervisorFromStudent({{ $student->id }})"
                                                wire:confirm="Are you sure you want to remove the supervisor from this student?"
                                                class="text-red-600 hover:text-red-900">
                                            Remove Supervisor
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                    No students found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Students Pagination -->
            <div class="mt-6">
                {{ $students->links() }}
            </div>
        </div>
    </div>

    <!-- Supervisors Section -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Supervisors</h3>
        </div>

        <div class="p-6">
            <!-- Search -->
            <div class="mb-6">
                <input wire:model.live="searchSupervisors" 
                       type="text" 
                       placeholder="Search supervisors..." 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <!-- Supervisors Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Supervisor
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Students Assigned
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($supervisors as $supervisor)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                                                <span class="text-sm font-medium text-green-600">
                                                    {{ strtoupper(substr($supervisor->name, 0, 2)) }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">{{ $supervisor->name }}</div>
                                            <div class="text-sm text-gray-500">{{ $supervisor->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $supervisor->students()->count() }} students
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($supervisor->status)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            Inactive
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-4 text-center text-gray-500">
                                    No supervisors found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Supervisors Pagination -->
            <div class="mt-6">
                {{ $supervisors->links() }}
            </div>
        </div>
    </div>

    <!-- Bulk Assign Modal -->
    @if($showBulkAssignModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Assign Supervisor to Selected Students</h3>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Select Supervisor
                        </label>
                        <select wire:model="selectedSupervisor" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Choose a supervisor...</option>
                            @foreach($allSupervisors as $supervisor)
                                <option value="{{ $supervisor->id }}">
                                    {{ $supervisor->name }} ({{ $supervisor->students()->count() }} students)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <p class="text-sm text-gray-600">
                            You are about to assign a supervisor to {{ count($selectedStudents) }} selected students.
                        </p>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button wire:click="closeBulkAssignModal" 
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            Cancel
                        </button>
                        <button wire:click="bulkAssignSupervisor" 
                                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700">
                            Assign Supervisor
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>