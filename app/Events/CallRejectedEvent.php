<?php
namespace App\Events;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallRejectedEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $formationId,
        public int $callerId,
        public int $rejecterId,
        public string $callId
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        // Notifie l'appelant que son appel a été refusé
        return new PrivateChannel('user.' . $this->callerId);
    }

    public function broadcastAs(): string { return 'call.rejected'; }

    public function broadcastWith(): array
    {
        return ['call_id' => $this->callId];
    }
}