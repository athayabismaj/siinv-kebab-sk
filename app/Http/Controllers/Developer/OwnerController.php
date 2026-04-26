<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class OwnerController extends Controller
{
    public function index()
    {
        $ownerRole = Role::where('name', 'owner')->firstOrFail();
        
        $owners = User::where('role_id', $ownerRole->id)
            ->latest()
            ->get();

        return view('developer.owners.index', compact('owners'));
    }

    public function create()
    {
        return view('developer.owners.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $ownerRole = Role::where('name', 'owner')->firstOrFail();

        User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $ownerRole->id,
        ]);

        return redirect()->route('developer.owners.index')->with('success', 'Akun Owner berhasil dibuat.');
    }
}
