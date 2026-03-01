<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MenuController extends Controller
{
    // ================= INDEX =================
    public function index()
    {
        $menus = Menu::latest()->paginate(10);
        return view('admin.menus.index', compact('menus'));
    }

    // ================= CREATE =================
    public function create()
    {
        return view('admin.menus.create');
    }

    // ================= STORE =================
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|max:150|unique:menus,name',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $imagePath = null;

        if ($request->hasFile('image')) {

            $file = $request->file('image');

            // Rename random + aman
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

            $imagePath = $file->storeAs('menus', $filename, 'public');
        }

        Menu::create([
            'name' => $request->name,
            'price' => $request->price,
            'description' => $request->description,
            'image_path' => $imagePath,
        ]);

        return redirect()
            ->route('admin.menus.index')
            ->with('success', 'Menu berhasil ditambahkan.');
    }

    // ================= EDIT =================
    public function edit(Menu $menu)
    {
        return view('admin.menus.edit', compact('menu'));
    }

    // ================= UPDATE =================
    public function update(Request $request, Menu $menu)
    {
        $request->validate([
            'name' => 'required|max:150|unique:menus,name,' . $menu->id,
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $data = [
            'name' => $request->name,
            'price' => $request->price,
            'description' => $request->description,
        ];

        if ($request->hasFile('image')) {

            // Hapus gambar lama
            if ($menu->image_path &&
                Storage::disk('public')->exists($menu->image_path)) {

                Storage::disk('public')->delete($menu->image_path);
            }

            $file = $request->file('image');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

            $data['image_path'] = $file->storeAs('menus', $filename, 'public');
        }

        $menu->update($data);

        return redirect()
            ->route('admin.menus.index')
            ->with('success', 'Menu berhasil diperbarui.');
    }

    // ================= DESTROY =================
    public function destroy(Menu $menu)
    {
        // Jika pernah dipakai transaksi → hanya soft delete
        if (method_exists($menu, 'orderItems') && $menu->orderItems()->exists()) {

            $menu->delete();

            return redirect()
                ->route('admin.menus.index')
                ->with('success', 'Menu diarsipkan karena memiliki riwayat transaksi.');
        }

        // Jika belum pernah dipakai → hapus permanen + file
        if ($menu->image_path &&
            Storage::disk('public')->exists($menu->image_path)) {

            Storage::disk('public')->delete($menu->image_path);
        }

        $menu->forceDelete();

        return redirect()
            ->route('admin.menus.index')
            ->with('success', 'Menu berhasil dihapus permanen.');
    }

    // ================= ARCHIVE =================
    public function archive()
    {
        $menus = Menu::onlyTrashed()
            ->latest()
            ->paginate(10);

        return view('admin.menus.archive', compact('menus'));
    }

    // ================= RESTORE =================
    public function restore($id)
    {
        $menu = Menu::withTrashed()->findOrFail($id);

        $menu->restore();

        return redirect()
            ->route('admin.menus.archive')
            ->with('success', 'Menu berhasil diaktifkan kembali.');
    }
}