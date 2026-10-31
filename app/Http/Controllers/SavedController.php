<?php

namespace App\Http\Controllers;

use App\Models\CarModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Daftar mobil yang disimpan pengguna (tabel `favorites`). */
class SavedController extends Controller
{
    public function index(Request $request)
    {
        $cars = $request->user()->favorites()
            ->with('brand')
            ->withMax('engines', 'power_hp')
            ->orderByPivot('created_at', 'desc')
            ->get();

        return view('saved.index', compact('cars'));
    }

    /** Simpan kalau belum disimpan, hapus kalau sudah. */
    public function toggle(Request $request, CarModel $carModel): RedirectResponse|JsonResponse
    {
        $changes = $request->user()->favorites()->toggle($carModel->id);
        $saved = ! empty($changes['attached']);
        $message = $saved
            ? $carModel->full_name.' saved.'
            : $carModel->full_name.' removed from your saved cars.';

        // Dari garasi tombol diperbarui di tempat (tanpa memuat ulang halaman): cukup status baru dan pesannya.
        if ($request->expectsJson()) {
            return response()->json(['saved' => $saved, 'message' => $message]);
        }

        return back()->with('status', $message);
    }
}
