<?php
namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSentEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        $this->message = $message->load(['sender:id,prenom,nom,photo_profil,role']);
    }

    public function broadcastOn()
    {
        $participants = [$this->message->sender_id, $this->message->receiver_id];
        sort($participants);
        return new PrivateChannel("conversation.{$this->message->formation_id}." . implode('.', $participants));
    }

    public function broadcastAs()
    {
        return 'message.sent';
    }

    public function broadcastWith()
    {
        $controller = new \App\Http\Controllers\Api\MessageController();
        return [
            'message' => $controller->formatMessage($this->message, $this->message->sender_id)
        ];
    }
}