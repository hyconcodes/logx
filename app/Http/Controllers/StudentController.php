<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class StudentController extends Controller
{
    /**
     * Show the student registration form.
     */
    public function showRegisterForm()
    {
        // Redirect authenticated users to their dashboard
        if (auth()->check()) {
            $user = auth()->user();
            if ($user->hasRole('superadmin')) {
                return redirect()->route('superadmin.dashboard');
            } elseif ($user->hasRole('supervisor')) {
                return redirect()->route('supervisor.dashboard');
            } else {
                return redirect()->route('student.dashboard');
            }
        }

        $departments = Department::active()->orderBy('name')->get();
        return view('auth.student-register', compact('departments'));
    }

    /**
     * Handle student registration.
     */
    public function register(Request $request)
    {
        // Redirect authenticated users to their dashboard
        if (auth()->check()) {
            $user = auth()->user();
            if ($user->hasRole('superadmin')) {
                return redirect()->route('superadmin.dashboard');
            } elseif ($user->hasRole('supervisor')) {
                return redirect()->route('supervisor.dashboard');
            } else {
                return redirect()->route('student.dashboard');
            }
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 
                'string', 
                'email', 
                'max:255', 
                'unique:users',
                'regex:/^[a-zA-Z]+\.[0-9]+@bouesti\.edu\.ng$/'
            ],
            'password' => ['required', 'confirmed', Password::defaults()],
            'department_id' => ['required', 'exists:departments,id'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Extract matric_no from email address
        $emailParts = explode('@', $request->email);
        $localPart = $emailParts[0]; // lastname.matric_no
        $nameParts = explode('.', $localPart);
        $matric_no = end($nameParts); // Get the last part which is the matric number

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'matric_no' => $matric_no,
            'password' => Hash::make($request->password),
            'department_id' => $request->department_id,
        ]);

        // Assign student role
        $user->assignRole('student');

        // Log the user in
        auth()->login($user);

        return redirect()->route('student.dashboard');
    }
}