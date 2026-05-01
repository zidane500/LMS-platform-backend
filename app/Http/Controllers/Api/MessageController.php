<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\MessageReaction;
use App\Models\MessageBlock;
use App\Models\Formation;
use App\Models\Inscription;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MessageController extends Controller
{
    // ── Vérifier accès ──────────────────────────────────────
    private function checkAcces($user, $formation): bool
    {
        return $user->role === 'admin'
            || $formation->formateur_id === $user->id
            || Inscription::where('user_id', $user->id)
                ->where('formation_id', $formation->id)->exists();
    }

    // ── Formater un message ─────────────────────────────────
    private function formatMessage(Message $m, int $currentUserId): array
    {
        if ($m->is_retracted) {
            return [
                'id'           => $m->id,
                'is_retracted' => true,
                'contenu'      => '',
                'type'         => 'retracted',
                'sender'       => [
                    'id'     => (string) $m->sender_id,
                    'nom'    => $m->sender ? ($m->sender->prenom . ' ' . $m->sender->nom) : 'Utilisateur',
                    'role'   => $m->sender->role ?? '',
                    'avatar' => null,
                ],
                'reply_to'       => null,
                'reactions'      => [],
                'created_at'     => $m->created_at->diffForHumans(),
                'created_at_raw' => $m->created_at->toISOString(),
            ];
        }

        return [
            'id'           => $m->id,
            'is_retracted' => false,
            'contenu'      => $m->contenu,
            'type'         => $m->type ?? 'text',
            'media_url'    => $m->media_url,
            'media_nom'    => $m->media_nom,
            'media_mime'   => $m->media_mime,
            'sender'       => [
                'id'     => (string) $m->sender->id,
                'nom'    => $m->sender->prenom . ' ' . $m->sender->nom,
                'avatar' => $m->sender->photo_profil
                    ? asset('storage/' . $m->sender->photo_profil)
                    : null,
                'role'   => $m->sender->role,
            ],
            'reply_to'  => $m->replyTo ? [
                'id'         => $m->replyTo->id,
                'contenu'    => $m->replyTo->is_retracted
                    ? 'Message supprimé'
                    : $m->replyTo->contenu,
                'type'       => $m->replyTo->type,
                'media_url'  => $m->replyTo->media_url,
                'sender_nom' => $m->replyTo->sender
                    ? $m->replyTo->sender->prenom . ' ' . $m->replyTo->sender->nom
                    : '',
            ] : null,
            'reactions' => $m->reactions
                ->groupBy('emoji')
                ->map(fn($r, $emoji) => [
                    'emoji' => $emoji,
                    'count' => $r->count(),
                    'mine'  => $r->pluck('user_id')->contains($currentUserId),
                    'users' => $r->map(fn($rx) => $rx->user
                        ? $rx->user->prenom . ' ' . $rx->user->nom
                        : '')->values(),
                ])->values(),
            'created_at'     => $m->created_at->diffForHumans(),
            'created_at_raw' => $m->created_at->toISOString(),
        ];
    }

    // ── GET /api/formations/{id}/messages ──────────────────
    // Apprenant  : sa conversation privée avec l'instructeur
    // Formateur  : ?user_id=X → conversation avec cet apprenant
    public function index(Request $request, $formationId)
    {
        $user      = $request->user();
        $formation = Formation::findOrFail($formationId);

        if (!$this->checkAcces($user, $formation)) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $instructorId = $formation->formateur_id;

        // Déterminer les deux participants
        if ($user->id === $instructorId || $user->role === 'admin') {
            $otherUserId = (int) $request->query('user_id');
            if (!$otherUserId) {
                return response()->json(['message' => 'user_id requis'], 422);
            }
            $userA = $user->id;
            $userB = $otherUserId;
        } else {
            $userA = $user->id;
            $userB = $instructorId;
        }

        // Marquer lu si formateur consulte
        if ($user->id === $instructorId) {
            Message::where('formation_id', $formationId)
                ->where('sender_id', $userB)
                ->where('receiver_id', $userA)
                ->where('lu_formateur', false)
                ->update(['lu_formateur' => true]);
        }

        $messages = Message::where('formation_id', $formationId)
            ->where(function ($q) use ($userA, $userB) {
                $q->where(function ($q2) use ($userA, $userB) {
                    $q2->where('sender_id', $userA)
                       ->where('receiver_id', $userB);
                })->orWhere(function ($q2) use ($userA, $userB) {
                    $q2->where('sender_id', $userB)
                       ->where('receiver_id', $userA);
                });
            })
            ->with([
                'sender:id,prenom,nom,photo_profil,role',
                'replyTo.sender:id,prenom,nom',
                'reactions.user:id,prenom,nom',
            ])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($m) => $this->formatMessage($m, $user->id));

        return response()->json($messages);
    }

    // ── POST /api/formations/{id}/messages ─────────────────
    public function store(Request $request, $formationId)
{
    $user = $request->user();
    
    // ✅ Admin peut envoyer dans n'importe quelle formation
    $formation = Formation::findOrFail($formationId);
    $estProprietaire = $formation->formateur_id === $user->id;
    $estInscrit = \App\Models\Inscription::where('user_id', $user->id)
        ->where('formation_id', $formationId)->exists();
    
    // ✅ Autorisation : apprenant inscrit, formateur propriétaire, ou ADMIN
    if (!$estInscrit && !$estProprietaire && $user->role !== 'admin') {
        return response()->json(['message' => 'Non autorisé'], 403);
    }

        $instructorId = $formation->formateur_id;

        // Déterminer receiver_id
        if ($user->id === $instructorId || $user->role === 'admin') {
            $receiverId = (int) $request->input('receiver_id');
            if (!$receiverId) {
                return response()->json(['message' => 'receiver_id requis'], 422);
            }
        } else {
            $receiverId = $instructorId;
        }

        // Vérifier si bloqué
        $isBlocked = MessageBlock::where('formation_id', $formationId)
            ->where('blocked_user_id', $user->id)->exists();

        if ($isBlocked && $user->id !== $instructorId && $user->role !== 'admin') {
            return response()->json([
                'message' => 'Vous avez été bloqué dans cette discussion.',
                'blocked' => true,
            ], 403);
        }

        $request->validate([
            'contenu'     => 'nullable|string|max:5000',
            'media'       => 'nullable|file|max:51200',
            'reply_to_id' => 'nullable|exists:messages,id',
            'receiver_id' => 'nullable|integer',
        ]);

        // Upload média
        $type = 'text'; $mediaUrl = $mediaNom = $mediaMime = null;

        if ($request->hasFile('media')) {
            $file      = $request->file('media');
            $mime      = $file->getMimeType();
            $mediaMime = $mime;
            $mediaNom  = $file->getClientOriginalName();

            if (str_starts_with($mime, 'image/'))     $type = 'image';
            elseif (str_starts_with($mime, 'video/')) $type = 'video';
            elseif (str_starts_with($mime, 'audio/')) $type = 'audio';
            else                                       $type = 'file';

            $chemin   = $file->store("messages/{$formationId}", 'public');
            $mediaUrl = asset('storage/' . $chemin);
        }

        if (!$request->filled('contenu') && !$mediaUrl) {
            return response()->json(['message' => 'Contenu ou fichier requis'], 422);
        }

        $message = Message::create([
            'formation_id'  => $formationId,
            'sender_id'     => $user->id,
            'receiver_id'   => $receiverId,
            'contenu'       => $request->contenu ?? '',
            'lu_formateur'  => ($user->id === $instructorId),
            'type'          => $type,
            'media_url'     => $mediaUrl,
            'media_nom'     => $mediaNom,
            'media_mime'    => $mediaMime,
            'reply_to_id'   => $request->reply_to_id,
            'is_retracted'  => false,
        ]);

        $message->load([
            'sender:id,prenom,nom,photo_profil,role',
            'replyTo.sender:id,prenom,nom',
            'reactions.user:id,prenom,nom',
        ]);

        // Notifications
        if ($user->id !== $instructorId) {
            NotificationService::send(
                $instructorId,
                "💬 {$user->prenom} {$user->nom} vous a envoyé un message dans \"{$formation->titre}\"",
                'info'
            );
        } else {
            NotificationService::send(
                $receiverId,
                "💬 Le formateur a répondu dans \"{$formation->titre}\"",
                'info'
            );
        }

        return response()->json($this->formatMessage($message, $user->id), 201);
    }

    // ── DELETE /api/messages/{id} — Retirer son message ────
    public function destroy(Request $request, $messageId)
    {
        $user    = $request->user();
        $message = Message::findOrFail($messageId);

        if ((int) $message->sender_id !== $user->id) {
            return response()->json(['message' => 'Vous ne pouvez retirer que vos propres messages.'], 403);
        }

        // Supprimer fichier si présent
        if ($message->media_url) {
            $relativePath = str_replace(asset('storage') . '/', '', $message->media_url);
            Storage::disk('public')->delete($relativePath);
        }

        $message->update([
            'is_retracted' => true,
            'contenu'      => '',
            'media_url'    => null,
            'media_nom'    => null,
            'media_mime'   => null,
        ]);

        return response()->json(['message' => 'Message retiré', 'id' => $messageId]);
    }

    // ── POST /api/messages/{id}/react ──────────────────────
    public function react(Request $request, $messageId)
    {
        $request->validate(['emoji' => 'required|string|max:10']);
        $user    = $request->user();
        $message = Message::findOrFail($messageId);

        $existing = MessageReaction::where('message_id', $messageId)
            ->where('user_id', $user->id)->first();

        if ($existing) {
            if ($existing->emoji === $request->emoji) {
                $existing->delete();
                return response()->json(['removed' => true]);
            }
            $existing->update(['emoji' => $request->emoji]);
        } else {
            MessageReaction::create([
                'message_id' => $messageId,
                'user_id'    => $user->id,
                'emoji'      => $request->emoji,
            ]);
        }

        return response()->json(['added' => true]);
    }

    // ── POST /api/formations/{id}/messages/block ───────────
    public function blockUser(Request $request, $formationId)
    {
        $request->validate(['user_id' => 'required|exists:users,id']);
        $user      = $request->user();
        $formation = Formation::findOrFail($formationId);

        if ($user->role !== 'admin' && $formation->formateur_id !== $user->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        MessageBlock::updateOrCreate(
            ['formation_id' => $formationId, 'blocked_user_id' => $request->user_id],
            ['blocked_by' => $user->id]
        );

        return response()->json(['message' => 'Utilisateur bloqué', 'blocked' => true]);
    }

    // ── DELETE /api/formations/{id}/messages/unblock ───────
    public function unblockUser(Request $request, $formationId)
    {
        $request->validate(['user_id' => 'required|exists:users,id']);
        $user      = $request->user();
        $formation = Formation::findOrFail($formationId);

        if ($user->role !== 'admin' && $formation->formateur_id !== $user->id) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        MessageBlock::where('formation_id', $formationId)
            ->where('blocked_user_id', $request->user_id)
            ->delete();

        return response()->json(['message' => 'Utilisateur débloqué', 'blocked' => false]);
    }

    // ── GET /api/messages/inbox ────────────────────────────
    public function inbox(Request $request)
    {
        $user = $request->user();

        if (!in_array($user->role, ['formateur', 'admin'])) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $formations = Formation::where('formateur_id', $user->id)->get();

        $result = $formations->map(function ($f) use ($user) {
            // Apprenants qui ont envoyé un message dans cette formation
            $senderIds = Message::where('formation_id', $f->id)
                ->where('sender_id', '!=', $user->id)
                ->distinct()
                ->pluck('sender_id');

            if ($senderIds->isEmpty()) return null;

            $senders = $senderIds->map(function ($senderId) use ($f, $user) {
                $sender = User::find($senderId);
                if (!$sender) return null;

                $lastMsg = Message::where('formation_id', $f->id)
                    ->where(function ($q) use ($senderId, $user) {
                        $q->where(function ($q2) use ($senderId, $user) {
                            $q2->where('sender_id', $senderId)
                               ->where('receiver_id', $user->id);
                        })->orWhere(function ($q2) use ($senderId, $user) {
                            $q2->where('sender_id', $user->id)
                               ->where('receiver_id', $senderId);
                        });
                    })
                    ->orderByDesc('created_at')
                    ->first();

                // Fallback si pas encore de receiver_id (anciens messages)
                if (!$lastMsg) {
                    $lastMsg = Message::where('formation_id', $f->id)
                        ->where('sender_id', $senderId)
                        ->orderByDesc('created_at')
                        ->first();
                }

                if (!$lastMsg) return null;

                $isBlocked = MessageBlock::where('formation_id', $f->id)
                    ->where('blocked_user_id', $senderId)->exists();

                $nbNonLus = Message::where('formation_id', $f->id)
                    ->where('sender_id', $senderId)
                    ->where('lu_formateur', false)
                    ->count();

                $contenuAffiche = $lastMsg->is_retracted
                    ? '🚫 Message supprimé'
                    : ($lastMsg->type !== 'text'
                        ? '📎 ' . ($lastMsg->media_nom ?? 'Fichier')
                        : $lastMsg->contenu);

                return [
                    'user_id'  => (string) $senderId,
                    'nom'      => $sender->prenom . ' ' . $sender->nom,
                    'avatar'   => $sender->photo_profil
                        ? asset('storage/' . $sender->photo_profil)
                        : null,
                    'dernier_message' => [
                        'contenu'    => $contenuAffiche,
                        'created_at' => $lastMsg->created_at->diffForHumans(),
                    ],
                    'nb_non_lus' => $nbNonLus,
                    'is_blocked' => $isBlocked,
                ];
            })->filter()->values();

            if ($senders->isEmpty()) return null;

            return [
                'formation_id'     => (string) $f->id,
                'formation_titre'  => $f->titre,
                'miniature'        => $f->miniature
                    ? asset('storage/' . $f->miniature)
                    : null,
                'senders'          => $senders,
                'nb_non_lus_total' => $senders->sum('nb_non_lus'),
            ];
        })->filter()->values();

        return response()->json($result);
    }
}