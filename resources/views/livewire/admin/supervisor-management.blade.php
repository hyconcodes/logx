<?php

use Livewire\Volt\Component;
use App\Models\User;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;
    
    public $showBulkAssignModal = false;
    public $selectedSupervisor = null;
    public $selectedStudents = [];
    public $searchTerm = '';
    public $supervisorFilter = '';
    
    public function mount()
    {
        // Authorization can be added here if needed
    }
    
    public function with()
    {
        $supervisorsQuery = User::whereHas('roles', function ($query) {
            $query->where('name', 'supervisor');
        })->withCount(['students' => function ($query) {
            $query->whereHas('roles', function ($q) {
                $q->where('name', 'student');
            });
        }]);

        if ($this->supervisorFilter) {
            $supervisorsQuery->where('id', $this->supervisorFilter);
        }

        $studentsQuery = User::whereHas('roles', function ($query) {
            $query->where('name', 'student');
        })->with('supervisor');

        if ($this->searchTerm) {
            $studentsQuery->where(function ($query) {
                $query->where('name', 'like', '%' . $this->searchTerm . '%')
                      ->orWhere('email', 'like', '%' . $this->searchTerm . '%')
                      ->orWhere('matric_no', 'like', '%' . $this->searchTerm . '%');
            });
        }

        if ($this->supervisorFilter === 'unassigned') {
            $studentsQuery->whereNull('supervisor_id');
        } elseif ($this->supervisorFilter) {
            $studentsQuery->where('supervisor_id', $this->supervisorFilter);
        }

        // Get all supervisors for stats (not paginated)
        $allSupervisors = $supervisorsQuery->get();

        return [
            'supervisors' => $allSupervisors, // For stats and display
            'paginatedSupervisors' => $supervisorsQuery->paginate(10, ['*'], 'supervisors'), // For table pagination
            'students' => $studentsQuery->paginate(15),
            'unassignedStudents' => User::whereHas('roles', function ($query) {
                $query->where('name', 'student');
            })->whereNull('supervisor_id')->get(),
        ];
    }
    
    public function openBulkAssignModal()
    {
        $this->showBulkAssignModal = true;
        $this->selectedStudents = [];
        $this->selectedSupervisor = null;
    }
    
    public function closeBulkAssignModal()
    {
        $this->showBulkAssignModal = false;
        $this->selectedStudents = [];
        $this->selectedSupervisor = null;
    }
    
    public function bulkAssignStudents()
    {
        $this->validate([
            'selectedSupervisor' => 'required|exists:users,id',
            'selectedStudents' => 'required|array|min:1',
            'selectedStudents.*' => 'exists:users,id',
        ]);
        
        $supervisor = User::findOrFail($this->selectedSupervisor);
        $currentStudentCount = $supervisor->students()->whereHas('roles', function ($query) {
            $query->where('name', 'student');
        })->count();
        
        $availableSlots = 8 - $currentStudentCount;
        
        if (count($this->selectedStudents) > $availableSlots) {
            session()->flash('error', "Supervisor can only take {$availableSlots} more students (currently has {$currentStudentCount}/8).");
            return;
        }
        
        User::whereIn('id', $this->selectedStudents)->update([
            'supervisor_id' => $this->selectedSupervisor
        ]);
        
        $this->closeBulkAssignModal();
        session()->flash('message', 'Students assigned successfully!');
    }
    
    public function assignStudent($studentId, $supervisorId)
    {
        $supervisor = User::findOrFail($supervisorId);
        $currentStudentCount = $supervisor->students()->whereHas('roles', function ($query) {
            $query->where('name', 'student');
        })->count();
        
        if ($currentStudentCount >= 8) {
            session()->flash('error', 'Supervisor already has maximum students (8/8).');
            return;
        }
        
        User::findOrFail($studentId)->update(['supervisor_id' => $supervisorId]);
        session()->flash('message', 'Student assigned successfully!');
    }
    
    public function unassignStudent($studentId)
    {
        User::findOrFail($studentId)->update(['supervisor_id' => null]);
        session()->flash('message', 'Student unassigned successfully!');
    }
    
    public function updatedSearchTerm()
    {
        $this->resetPage();
    }
    
    public function updatedSupervisorFilter()
    {
        $this->resetPage();
    }
}; ?>

<div class="p-6">
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-base font-semibold leading-6 text-gray-900">Supervisor Management</h1>
            <p class="mt-2 text-sm text-gray-700">Manage supervisor assignments and view student distributions.</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
            <button wire:click="openBulkAssignModal" type="button" class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                Bulk Assign Students
            </button>
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('message'))
        <div class="mt-4 rounded-md bg-green-50 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800">{{ session('message') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mt-4 rounded-md bg-red-50 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Supervisors Overview -->
    <div class="mt-8">
        <div class="sm:flex sm:items-center">
            <div class="sm:flex-auto">
                <h2 class="text-lg font-medium text-gray-900">Supervisors Overview</h2>
                <p class="mt-1 text-sm text-gray-700">{{ $supervisors->count() }} total supervisors</p>
            </div>
        </div>

        <!-- Summary Stats -->
        <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-4">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Available</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ $supervisors->where('students_count', '<', 6)->count() }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Almost Full</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ $supervisors->whereBetween('students_count', [6, 7])->count() }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Full</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ $supervisors->where('students_count', '>=', 8)->count() }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Students</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ $supervisors->sum('students_count') }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Supervisors Table -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Supervisors</h3>
                <p class="text-sm text-gray-600 mt-1">Manage supervisor assignments and view capacity</p>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Supervisor
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Email
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Students
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Capacity
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($paginatedSupervisors as $supervisor)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                                <span class="text-sm font-medium text-indigo-800">
                                                    {{ strtoupper(substr($supervisor->name, 0, 2)) }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">{{ $supervisor->name }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $supervisor->email }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $supervisor->students_count }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                            <div class="bg-indigo-600 h-2 rounded-full" 
                                                 style="width: {{ ($supervisor->students_count / 8) * 100 }}%"></div>
                                        </div>
                                        <span class="text-sm text-gray-600">{{ $supervisor->students_count }}/8</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($supervisor->students_count >= 8)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            Full
                                        </span>
                                    @elseif($supervisor->students_count >= 6)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            Near Full
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Available
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button wire:click="$set('supervisorFilter', {{ $supervisor->id }})" 
                                            class="text-indigo-600 hover:text-indigo-900 mr-3">
                                        View Students
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                    No supervisors found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $paginatedSupervisors->links() }}
            </div>
        </div>
    </div>

    <!-- Students Management -->
    <div class="mt-8">
        <div class="sm:flex sm:items-center">
            <div class="sm:flex-auto">
                <h2 class="text-lg font-medium text-gray-900">Students Management</h2>
                <p class="mt-1 text-sm text-gray-700">{{ $unassignedStudents->count() }} unassigned students</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="search" class="block text-sm font-medium text-gray-700">Search Students</label>
                <flux:input wire:model.live="searchTerm" type="text" id="search" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Search by name, email, or matric number..."/>
            </div>
            <div>
                <label for="supervisor-filter" class="block text-sm font-medium text-gray-700">Filter by Supervisor</label>
                <flux:select wire:model.live="supervisorFilter" id="supervisor-filter" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">All Students</option>
                    <option value="unassigned">Unassigned Only</option>
                    @foreach ($supervisors as $supervisor)
                        <option value="{{ $supervisor->id }}">{{ $supervisor->name }}</option>
                    @endforeach
                </flux:select>
            </div>
        </div>

        <!-- Students Table -->
        <div class="mt-6 flow-root">
            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead>
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-0">Student</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Matric Number</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Current Supervisor</th>
                                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($students as $student)
                                <tr>
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-0">
                                        <div class="flex items-center">
                                            <div class="h-8 w-8 flex-shrink-0">
                                                <div class="h-8 w-8 rounded-full bg-gray-300 flex items-center justify-center">
                                                    <span class="text-xs font-medium text-gray-700">{{ substr($student->name, 0, 2) }}</span>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="font-medium text-gray-900">{{ $student->name }}</div>
                                                <div class="text-gray-500">{{ $student->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        {{ $student->matric_no ?? 'N/A' }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                        @if($student->supervisor)
                                            <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800">
                                                {{ $student->supervisor->name }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-800">
                                                Unassigned
                                            </span>
                                        @endif
                                    </td>
                                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                                        @if($student->supervisor)
                                            <button wire:click="unassignStudent({{ $student->id }})" class="text-red-600 hover:text-red-900 mr-3">
                                                Unassign
                                            </button>
                                        @endif
                                        <div class="inline-block">
                                            <select wire:change="assignStudent({{ $student->id }}, $event.target.value)" class="rounded-md border-gray-300 text-sm">
                                                <option value="">Assign to...</option>
                                                @foreach ($supervisors->where('students_count', '<', 8) as $supervisor)
                                                    <option value="{{ $supervisor->id }}">
                                                        {{ $supervisor->name }} ({{ $supervisor->students_count }}/8)
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-3 py-4 text-sm text-gray-500 text-center">No students found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $students->links() }}
        </div>
    </div>

    <!-- Bulk Assignment Modal -->
    @if($showBulkAssignModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="relative inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Bulk Assign Students
                        </h3>
                        <div class="mt-4">
                            <label for="supervisor-select" class="block text-sm font-medium text-gray-700">Select Supervisor</label>
                            <select wire:model="selectedSupervisor" id="supervisor-select" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Choose a supervisor...</option>
                                @foreach ($supervisors->where('students_count', '<', 8) as $supervisor)
                                    <option value="{{ $supervisor->id }}">
                                        {{ $supervisor->name }} ({{ $supervisor->students_count }}/8 students)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700">Select Unassigned Students</label>
                            <div class="mt-2 max-h-60 overflow-y-auto border border-gray-300 rounded-md">
                                @foreach ($unassignedStudents as $student)
                                    <label class="flex items-center p-3 hover:bg-gray-50">
                                        <input wire:model="selectedStudents" type="checkbox" value="{{ $student->id }}" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                        <div class="ml-3">
                                            <div class="text-sm font-medium text-gray-900">{{ $student->name }}</div>
                                            <div class="text-sm text-gray-500">{{ $student->email }}</div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                        <button wire:click="bulkAssignStudents" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:col-start-2 sm:text-sm">
                            Assign Students
                        </button>
                        <button wire:click="closeBulkAssignModal" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:col-start-1 sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>