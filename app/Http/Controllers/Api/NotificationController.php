<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $notifications = Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get()
            ->map(fn($n) => [
                'id'        => $n->id,
                'message'   => $n->message,
                'type'      => $n->type ?? 'info',
                'lu'        => (bool) $n->lu,
                'created_at' => $n->created_at?->locale('fr')->diffForHumans(),
            ]);

        return response()->json($notifications);
    }

    public function marquerLu(Request $request, $id)
    {
        Notification::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->update(['lu' => true]);

        return response()->json(['message' => 'Notification lue']);
    }

    public function marquerToutLu(Request $request)
    {
        Notification::where('user_id', $request->user()->id)
            ->where('lu', false)
            ->update(['lu' => true]);

        return response()->json(['message' => 'Toutes les notifications lues']);
    }

    public function nonLues(Request $request)
    {
        $count = Notification::where('user_id', $request->user()->id)
            ->where('lu', false)
            ->count();
        return response()->json(['count' => $count]);
    }

    public function destroy(Request $request, $id)
{
    Notification::where('id', $id)
        ->where('user_id', $request->user()->id)
        ->delete();

    return response()->json(['message' => 'Notification supprimée']);
}
}