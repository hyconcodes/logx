<?php

use Livewire\Volt\Component;
use App\Models\Department;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;
    
    public $showCreateModal = false;
    public $showEditModal = false;
    public $selectedDepartment = null;
    public $name = '';
    public $description = '';
    public $status = true;
    public $searchTerm = '';
    
    public function mount()
    {
        // Authorization can be added here if needed
    }
    
    public function with()
    {
        $query = Department::withCount(['students', 'supervisors']);
        
        if ($this->searchTerm) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->searchTerm . '%')
                  ->orWhere('description', 'like', '%' . $this->searchTerm . '%');
            });
        }
        
        return [
            'departments' => $query->paginate(10),
            'totalDepartments' => Department::count(),
            'activeDepartments' => Department::where('status', true)->count(),
            'inactiveDepartments' => Department::where('status', false)->count(),
        ];
    }
    
    public function createDepartment()
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:departments,name',
            'description' => 'nullable|string|max:1000',
            'status' => 'boolean',
        ]);
        
        Department::create([
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
        ]);
        
        $this->reset(['name', 'description', 'status', 'showCreateModal']);
        $this->status = true; // Reset to default
        session()->flash('message', 'Department created successfully!');
    }
    
    public function editDepartment($departmentId)
    {
        $this->selectedDepartment = Department::findOrFail($departmentId);
        $this->name = $this->selectedDepartment->name;
        $this->description = $this->selectedDepartment->description;
        $this->status = $this->selectedDepartment->status;
        $this->showEditModal = true;
    }
    
    public function updateDepartment()
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:departments,name,' . $this->selectedDepartment->id,
            'description' => 'nullable|string|max:1000',
            'status' => 'boolean',
        ]);
        
        $this->selectedDepartment->update([
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
        ]);
        
        $this->reset(['name', 'description', 'status', 'showEditModal', 'selectedDepartment']);
        session()->flash('message', 'Department updated successfully!');
    }
    
    public function deleteDepartment($departmentId)
    {
        $department = Department::withCount(['students', 'supervisors'])->findOrFail($departmentId);
        
        if ($department->students_count > 0 || $department->supervisors_count > 0) {
            session()->flash('error', 'Cannot delete department with assigned users!');
            return;
        }
        
        $department->delete();
        session()->flash('message', 'Department deleted successfully!');
    }
    
    public function toggleStatus($departmentId)
    {
        $department = Department::findOrFail($departmentId);
        $department->update(['status' => !$department->status]);
        
        $status = $department->status ? 'activated' : 'deactivated';
        session()->flash('message', "Department {$status} successfully!");
    }
    
    public function updatedSearchTerm()
    {
        $this->resetPage();
    }
};

?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Department Management</h1>
            <p class="text-gray-600">Manage departments and their settings</p>
        </div>
        <button wire:click="$set('showCreateModal', true)" 
                class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Add Department
        </button>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('message'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="p-2 bg-indigo-100 rounded-lg">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Departments</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $totalDepartments }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Active Departments</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $activeDepartments }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="p-2 bg-red-100 rounded-lg">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Inactive Departments</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $inactiveDepartments }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <input wire:model.live="searchTerm" 
                       type="text" 
                       placeholder="Search departments..." 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
        </div>
    </div>

    <!-- Departments Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Departments</h3>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Department
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Description
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Students
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Supervisors
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
                    @forelse($departments as $department)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                            <span class="text-sm font-medium text-indigo-800">
                                                {{ strtoupper(substr($department->name, 0, 2)) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <a href="{{ route('admin.department.detail', $department->id) }}" 
                                           class="text-sm font-medium text-indigo-600 hover:text-indigo-900 hover:underline">
                                            {{ $department->name }}
                                        </a>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 max-w-xs truncate">
                                    {{ $department->description ?: 'No description' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $department->students_count }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $department->supervisors_count }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($department->status)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                <button wire:click="editDepartment({{ $department->id }})" 
                                        class="text-indigo-600 hover:text-indigo-900">
                                    Edit
                                </button>
                                <button wire:click="toggleStatus({{ $department->id }})" 
                                        class="text-yellow-600 hover:text-yellow-900">
                                    {{ $department->status ? 'Deactivate' : 'Activate' }}
                                </button>
                                @if($department->students_count == 0 && $department->supervisors_count == 0)
                                    <button wire:click="deleteDepartment({{ $department->id }})" 
                                            wire:confirm="Are you sure you want to delete this department?"
                                            class="text-red-600 hover:text-red-900">
                                        Delete
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                No departments found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $departments->links() }}
        </div>
    </div>

    <!-- Create Department Modal -->
    @if($showCreateModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Create New Department</h3>
                    
                    <form wire:submit="createDepartment">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Department Name</label>
                            <input wire:model="name" 
                                   type="text" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                   required>
                            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea wire:model="description" 
                                      rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                            @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        
                        <div class="mb-4">
                            <label class="flex items-center">
                                <input wire:model="status" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-700">Active</span>
                            </label>
                        </div>
                        
                        <div class="flex justify-end space-x-3">
                            <button type="button" 
                                    wire:click="$set('showCreateModal', false)"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">
                                Cancel
                            </button>
                            <button type="submit" 
                                    class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                                Create Department
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Edit Department Modal -->
    @if($showEditModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Edit Department</h3>
                    
                    <form wire:submit="updateDepartment">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Department Name</label>
                            <input wire:model="name" 
                                   type="text" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                   required>
                            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea wire:model="description" 
                                      rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                            @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        
                        <div class="mb-4">
                            <label class="flex items-center">
                                <input wire:model="status" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-700">Active</span>
                            </label>
                        </div>
                        
                        <div class="flex justify-end space-x-3">
                            <button type="button" 
                                    wire:click="$set('showEditModal', false)"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">
                                Cancel
                            </button>
                            <button type="submit" 
                                    class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                                Update Department
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>