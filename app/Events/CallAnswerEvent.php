<?php
namespace App\Events;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallAnswerEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $formationId,
        public int $callerId,
        public int $answererId,
        public string $callId,
        public array $answer
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        // Notifie l'appelant original
        return new PrivateChannel('user.' . $this->callerId);
    }

    public function broadcastAs(): string { return 'call.answer'; }

    public function broadcastWith(): array
    {
        return [
            'call_id'    => $this->callId,
            'answerer_id'=> $this->answererId,
            'answer'     => $this->answer,
        ];
    }
}