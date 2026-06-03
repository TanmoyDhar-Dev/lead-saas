<?php

namespace App\Http\Controllers;

use App\Models\ConnectedMailbox;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntegrationController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        $microsoft = ConnectedMailbox::where('user_id', $user->id)
            ->where('provider', 'microsoft')
            ->first();

        return response()->json([
            'integrations' => [
                'microsoft' => [
                    'connected' => $microsoft !== null,
                    'email' => $microsoft?->email_address,
                    'provider_label' => 'Microsoft Outlook',
                ],
            ],
        ]);
    }

    public function disconnect(Request $request, string $provider): JsonResponse
    {
        $deleted = ConnectedMailbox::where('user_id', $request->user()->id)
            ->where('provider', $provider)
            ->delete();

        if (! $deleted) {
            return response()->json(['message' => 'Integration not found.'], 404);
        }

        return response()->json(['message' => 'Disconnected successfully.']);
    }
}
