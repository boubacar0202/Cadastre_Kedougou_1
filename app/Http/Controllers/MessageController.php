<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; 
use App\Models\User;
use Illuminate\Support\Facades\Log;


class MessageController extends Controller
{
    /**
     * Applique le middleware auth sur toutes les méthodes
     */
    // public function __construct()
    // {
    //     $this->middleware('auth');
    // }

    public function create()
    {
        $users = User::where('id', '!=', Auth::id())->get();

        return Inertia::render('message/create', [
            'authUser' => Auth::user(),
            'users' => $users, // <-- ajoute ça
        ]);
    }


    /**
     * Affiche la liste des messages entre l'utilisateur connecté et un autre utilisateur
     */
    public function index(Request $request)
    {
        // Récupère l'utilisateur connecté
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login'); 
        }

        $withUserId = $request->input('with');
        if (!$withUserId) {
            return redirect()->back()->withErrors(['with' => 'Utilisateur à afficher requis']);
        }

        // Vérifie que l'utilisateur existe
        $otherUser = User::find($withUserId);
        if (!$otherUser) {
            return redirect()->back()->withErrors(['with' => 'Utilisateur introuvable']);
        }

        // Récupère les messages entre les deux utilisateurs
        $messages = Message::with(['sender', 'receiver'])
            ->where(function ($q) use ($user, $withUserId) {
                $q->where('sender_id', $user->id)
                  ->where('receiver_id', $withUserId);
            })
            ->orWhere(function ($q) use ($user, $withUserId) {
                $q->where('sender_id', $withUserId)
                  ->where('receiver_id', $user->id);
            })
            ->orderBy('created_at', 'asc')
            ->paginate(20);
 
        // Retourne la page Inertia
        return Inertia::render('message/create', [
            'messages' => $messages,
            'withUser' => $otherUser,
            'authUser' => Auth::user(),

        ]);
    }


    public function apiIndex($withUserId)
    {
        $user = Auth::user();
        $messages = Message::where(function ($q) use ($user, $withUserId) {
                $q->where('sender_id', $user->id)->where('receiver_id', $withUserId);
            })
            ->orWhere(function ($q) use ($user, $withUserId) {
                $q->where('sender_id', $withUserId)->where('receiver_id', $user->id);
            })
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'sender_id' => $m->sender_id,
                'receiver_id' => $m->receiver_id,
                'content' => $m->content,
                'is_received' => (bool) $m->is_received,
                'is_read' => (bool) $m->is_read,
                'created_at' => $m->created_at,
            ]);

        return response()->json($messages);
    }


        
    public function show($userId)
    {
        $messages = Message::where('receiver_id', Auth::user()->id)
                        ->where('sender_id', $userId)
                        ->orWhere(function($q) use ($userId) {
                            $q->where('receiver_id', $userId)
                                ->where('sender_id', Auth::user()->id);
                        })
                        ->orderBy('created_at', 'asc')
                        ->get();

        return response()->json($messages);
    }

    /**
     * Récupère les IDs des utilisateurs qui ont envoyé des messages non lus à l'utilisateur connecté
     */

    // nouvelle méthode pour le total des messages non lus
    public function unreadTotal()
    {
        $user = Auth::user();

        $count = Message::where('receiver_id', $user->id)
                        ->where('is_read', false)
                        ->count();

        return response()->json(['total' => $count]);
    }
 
    // nouvelle méthode pour supprimer un message
    public function destroy($userId)
    {
        $user = Auth::user();
        $message = Message::find($userId);

        if (!$message || $message->sender_id !== $user->id) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        $message->delete();
        return response()->json(['success' => true]);
    }

    // nouvelle méthode pour modifier un message
    public function update(Request $request, $id)
    {
        $user = Auth::user();
 
        $message = Message::findOrFail($id);
        if (!$message || $message->sender_id !== $user->id) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }
        
        $message->update([
            'content' => $request->input('content'),
            'is_read' => $request->input('is_read', $message->is_read),
        ]); 

        return response()->json(['success' => true, 'message' => $message]);
    }

    // Nombre de non lus par utilisateur + infos user
    public function unreadPerUser()
    {
        $authUserId = Auth::id();

        $users = User::where('id', '!=', $authUserId)
            ->get()
            ->map(function($user) use ($authUserId) {
                $user->unread_count = Message::where('sender_id', $user->id)
                                            ->where('receiver_id', $authUserId)
                                            ->where('is_read', false) // ou read_at
                                            ->count();
                return $user;
            });

        return response()->json($users);
    }
 
    // nouvelle méthode pour récupérer les messages entre deux utilisateurs
    public function messages($receiverId)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Non authentifié'], 401);
        }

        $messages = Message::where(function($q) use ($user, $receiverId) {
            $q->where('sender_id', $user->id)->where('receiver_id', $receiverId);
        })->orWhere(function($q) use ($user, $receiverId) {
            $q->where('sender_id', $receiverId)->where('receiver_id', $user->id);
        })->orderBy('created_at', 'asc')->get();

        return response()->json($messages);
    }
  
    // nouvelle méthode pour marquer comme reçu
    public function markReceived($senderId)
    {
        $user = Auth::user();

        Message::where('sender_id', $senderId)
            ->where('receiver_id', $user->id)
            ->update(['is_received' => true]);

        return response()->json(['success' => true]);
    }
 

    // public function unreadByUser()
    // {
    //     // Récupère l'ID de l'utilisateur connecté
    //     $userId = Auth::id(); // juste l'ID

    //     // Compte les messages non lus reçus par cet utilisateur, groupés par expéditeur
    //     $unread = DB::table('messages')
    //         ->select('sender_id', DB::raw('COUNT(*) as unread_count'))
    //         ->where('receiver_id', $userId) // messages reçus par l'utilisateur connecté
    //         ->where(function($q) {
    //             $q->where('is_read', false)
    //             ->orWhereNull('is_read'); // inclut NULL
    //         })
    //         ->groupBy('sender_id')
    //         ->get();

    //     return response()->json($unread);
    // }
    public function unreadByUser()
    {
        try {
            $userId = Auth::id();
            if (!$userId) {
                return response()->json(['error' => 'Non authentifié'], 401);
            }

            $messages = Message::select('sender_id', DB::raw('COUNT(*) as unread_count'))
                ->where('receiver_id', $userId)
                ->where('is_read', false)
                ->groupBy('sender_id')
                ->get();

            return response()->json($messages);

        } catch (\Exception $e) {
            Log::error('Erreur fetch unread: '.$e->getMessage());
            return response()->json(['error' => 'Erreur serveur'], 500);
        }
    }


    // nouvelle méthode pour marquer comme lu
    public function markRead($senderId)
    {
        $userId = Auth::id();

        DB::table('messages')
            ->where('sender_id', $senderId)
            ->where('receiver_id', $userId)
            ->where(function($q) {
                $q->where('is_read', 0)
                ->orWhereNull('is_read');
            })
            ->update(['is_read' => 1]);

        return response()->json(['status' => 'success']);
    }

        /**
     * Stocke un nouveau message
     */
   
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Utilisateur non authentifié'], 401);
        }

        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'content' => 'required|string|max:1000',
        ]);
 
        $message = Message::create([
            'sender_id'   => $user->id,
            'receiver_id' => $request->receiver_id,
            'content'     => $request->content,
            'is_received' => false,
            'is_read'     => false,
        ]);

        return response()->json([
            'message' => $message->only([
                'id',
                'sender_id',
                'receiver_id',
                'content',
                'is_received',
                'is_read',
                'created_at',
                'updated_at',
            ])
        ]);
    }

        
}

