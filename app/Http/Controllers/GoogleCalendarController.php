<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GoogleCalendarController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        $connection = $request->user()->googleCalendarConnection;

        return response()->json([
            'connected' => $connection !== null && $connection->sync_status === 'CONNECTED',
            'data' => $connection ? [
                'google_account_email' => $connection->google_account_email,
                'calendar_id' => $connection->calendar_id,
                'sync_status' => $connection->sync_status,
                'connected_at' => $connection->connected_at,
            ] : null,
        ]);
    }

    public function disconnect(Request $request): JsonResponse
    {
        $connection = $request->user()->googleCalendarConnection;

        if ($connection) {
            $connection->update([
                'refresh_token' => null,
                'sync_status' => 'DISCONNECTED',
                'connected_at' => null,
            ]);
        }

        return response()->json(['message' => 'Koneksi Google Calendar diputus.']);
    }
}
