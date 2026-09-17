<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PointTransaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UserXpAdjustmentController extends Controller
{
    /**
     * Koreksi XP manual karyawan oleh Admin.
     * PRD v2.0 §2.2 & prompt: HANYA role Admin yang berwenang.
     * WAJIB selalu mencatat baris baru ke point_transactions dengan source_type='admin_adjustment'
     * dan ledger_type='xp', TIDAK PERNAH mengedit kolom users.xp secara langsung.
     */
    public function adjustXp(Request $request, string|int $id): JsonResponse
    {
        Gate::authorize('adjust-xp-manual');

        $validated = $request->validate([
            'points' => ['required', 'integer', 'not_in:0'],
            'description' => ['required', 'string', 'max:255'],
        ], [
            'points.required' => 'Nilai koreksi poin XP wajib diisi.',
            'points.not_in' => 'Nilai koreksi tidak boleh 0.',
            'description.required' => 'Catatan / alasan koreksi manual wajib dicantumkan.',
        ]);

        $user = User::findOrFail($id);
        $previousXp = (int) $user->xp;

        $transaction = PointTransaction::create([
            'user_id' => $user->id,
            'ledger_type' => PointTransaction::LEDGER_XP,
            'points' => (int) $validated['points'],
            'source_type' => 'admin_adjustment',
            'description' => trim($validated['description']),
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Koreksi XP berhasil dicatat ke buku besar.',
            'data' => [
                'transaction_id' => $transaction->id,
                'user_id' => $user->id,
                'previous_xp' => $previousXp,
                'current_xp' => (int) $user->fresh()->xp,
                'points_adjusted' => (int) $transaction->points,
                'description' => $transaction->description,
            ],
        ]);
    }
}
