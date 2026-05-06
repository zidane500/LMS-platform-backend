<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Formation;
use App\Models\Inscription;
use App\Events\VoiceOfferEvent;
use App\Events\VideoOfferEvent;
use App\Events\CallAnswerEvent;
use App\Events\IceCandidateEvent;
use App\Events\CallEndedEvent;
use App\Events\CallRejectedEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CallController extends Controller
{
    /**
     * Check if user has access to formation
     */
    private function checkFormationAccess($user, $formationId): bool
    {
        $formation = Formation::find($formationId);
        if (!$formation) {
            return false;
        }

        return $user->role === 'admin'
            || $formation->formateur_id === $user->id
            || Inscription::where('user_id', $user->id)
                ->where('formation_id', $formationId)->exists();
    }

    /**
     * Initiate voice call
     */
    public function voiceOffer(Request $request)
    {
        try {
            $data = $request->validate([
                'formation_id' => 'required|integer',
                'recipient_id' => 'required|integer',
                'offer' => 'required|array',
                'call_id' => 'required|string',
                'caller_nom' => 'required|string',
            ]);

            $user = $request->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $formationId = (int) $data['formation_id'];
            $recipientId = (int) $data['recipient_id'];

            // Verify access
            if (!$this->checkFormationAccess($user, $formationId)) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Broadcast voice offer event
            VoiceOfferEvent::dispatch(
                $formationId,
                $user->id,
                $recipientId,
                $data['caller_nom'],
                $data['call_id'],
                $data['offer']
            );

            return response()->json(['status' => 'offer_sent']);
        } catch (\Exception $e) {
            Log::error('Voice offer error: ' . $e->getMessage());
            return response()->json(['error' => 'Server error'], 500);
        }
    }

    /**
     * Initiate video call
     */
    public function videoOffer(Request $request)
    {
        try {
            $data = $request->validate([
                'formation_id' => 'required|integer',
                'recipient_id' => 'required|integer',
                'offer' => 'required|array',
                'call_id' => 'required|string',
                'caller_nom' => 'required|string',
            ]);

            $user = $request->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $formationId = (int) $data['formation_id'];
            $recipientId = (int) $data['recipient_id'];

            // Verify access
            if (!$this->checkFormationAccess($user, $formationId)) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Broadcast video offer event
            VideoOfferEvent::dispatch(
                $formationId,
                $user->id,
                $recipientId,
                $data['caller_nom'],
                $data['call_id'],
                $data['offer']
            );

            return response()->json(['status' => 'offer_sent']);
        } catch (\Exception $e) {
            Log::error('Video offer error: ' . $e->getMessage());
            return response()->json(['error' => 'Server error'], 500);
        }
    }

    /**
     * Accept call (send answer)
     */
    public function answer(Request $request)
    {
        try {
            $data = $request->validate([
                'formation_id' => 'required|integer',
                'caller_id' => 'required|integer',
                'answer' => 'required|array',
                'call_id' => 'required|string',
            ]);

            $user = $request->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $formationId = (int) $data['formation_id'];
            $callerId = (int) $data['caller_id'];

            // Verify access
            if (!$this->checkFormationAccess($user, $formationId)) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Broadcast answer event
            CallAnswerEvent::dispatch(
                $formationId,
                $callerId,
                $user->id,
                $data['call_id'],
                $data['answer']
            );

            return response()->json(['status' => 'answer_sent']);
        } catch (\Exception $e) {
            Log::error('Call answer error: ' . $e->getMessage());
            return response()->json(['error' => 'Server error'], 500);
        }
    }

    /**
     * Send ICE candidate
     */
    public function iceCandidate(Request $request)
    {
        try {
            $data = $request->validate([
                'formation_id' => 'required|integer',
                'recipient_id' => 'required|integer',
                'candidate' => 'required|array',
                'call_id' => 'required|string',
            ]);

            $user = $request->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $formationId = (int) $data['formation_id'];
            $recipientId = (int) $data['recipient_id'];

            // Verify access
            if (!$this->checkFormationAccess($user, $formationId)) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Broadcast ICE candidate event
            IceCandidateEvent::dispatch(
                $formationId,
                $user->id,
                $recipientId,
                $data['call_id'],
                $data['candidate']
            );

            return response()->json(['status' => 'candidate_sent']);
        } catch (\Exception $e) {
            Log::error('ICE candidate error: ' . $e->getMessage());
            return response()->json(['error' => 'Server error'], 500);
        }
    }

    /**
     * End call
     */
    public function endCall(Request $request)
    {
        try {
            $data = $request->validate([
                'formation_id' => 'required|integer',
                'recipient_id' => 'required|integer',
                'call_id' => 'required|string',
            ]);

            $user = $request->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $formationId = (int) $data['formation_id'];
            $recipientId = (int) $data['recipient_id'];

            // Verify access
            if (!$this->checkFormationAccess($user, $formationId)) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Broadcast call ended event
            CallEndedEvent::dispatch(
                $formationId,
                $user->id,
                $recipientId,
                $data['call_id']
            );

            return response()->json(['status' => 'call_ended']);
        } catch (\Exception $e) {
            Log::error('Call end error: ' . $e->getMessage());
            return response()->json(['error' => 'Server error'], 500);
        }
    }

    /**
     * Reject call
     */
    public function rejectCall(Request $request)
    {
        try {
            $data = $request->validate([
                'formation_id' => 'required|integer',
                'caller_id' => 'required|integer',
                'call_id' => 'required|string',
            ]);

            $user = $request->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $formationId = (int) $data['formation_id'];
            $callerId = (int) $data['caller_id'];

            // Verify access
            if (!$this->checkFormationAccess($user, $formationId)) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Broadcast call rejected event
            CallRejectedEvent::dispatch(
                $formationId,
                $callerId,
                $user->id,
                $data['call_id']
            );

            return response()->json(['status' => 'call_rejected']);
        } catch (\Exception $e) {
            Log::error('Call reject error: ' . $e->getMessage());
            return response()->json(['error' => 'Server error'], 500);
        }
    }
}
