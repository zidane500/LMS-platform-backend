<?php
namespace App\Events;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VideoOfferEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $formationId,
        public int $callerId,
        public int $recipientId,
        public string $callerNom,
        public string $callId,
        public array $offer
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('user.' . $this->recipientId);
    }

    public function broadcastAs(): string { return 'call.video-offer'; }

    public function broadcastWith(): array
    {
        return [
            'call_id'      => $this->callId,
            'caller_id'    => $this->callerId,
            'caller_nom'   => $this->callerNom,
            'formation_id' => $this->formationId,
            'offer'        => $this->offer,
        ];
    }
}