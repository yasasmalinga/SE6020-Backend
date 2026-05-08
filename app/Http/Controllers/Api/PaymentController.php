<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $payments = DB::table('payments')
            ->join('bookings', 'bookings.id', '=', 'payments.booking_id')
            ->join('candidates', 'candidates.id', '=', 'bookings.candidate_id')
            ->where('candidates.user_id', $user->id)
            ->select('payments.*')
            ->orderByDesc('payments.created_at')
            ->paginate((int) $request->input('per_page', 10));

        return response()->json($payments);
    }

    public function initiate(Request $request): JsonResponse
    {
        $user = $request->user();
        $candidate = $user->candidate;

        abort_unless($candidate, 403, 'Only candidates can initiate payments.');

        $validated = $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'amount' => 'required|numeric|min:1',
            'currency' => 'nullable|string|size:3',
        ]);

        $booking = Booking::query()->findOrFail($validated['booking_id']);
        abort_unless($booking->candidate_id === $candidate->id, 403, 'You can only pay for your own booking.');

        $paymentId = DB::table('payments')->insertGetId([
            'booking_id' => $booking->id,
            'candidate_id' => $candidate->id,
            'amount' => $validated['amount'],
            'currency' => strtoupper($validated['currency'] ?? 'USD'),
            'status' => 'initiated',
            'provider' => 'mock-gateway',
            'provider_reference' => 'mock_'.Str::uuid(),
            'metadata' => json_encode(['note' => 'Mock payment for assignment demo']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payment = DB::table('payments')->where('id', $paymentId)->first();

        return response()->json($payment, 201);
    }

    public function updateStatus(Request $request, int $paymentId): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:authorized,captured,failed,refunded',
        ]);

        DB::table('payments')
            ->where('id', $paymentId)
            ->update([
                'status' => $validated['status'],
                'updated_at' => now(),
            ]);

        $payment = DB::table('payments')->where('id', $paymentId)->first();
        if (! $payment) {
            abort(404, 'Payment not found.');
        }

        return response()->json($payment);
    }
}
