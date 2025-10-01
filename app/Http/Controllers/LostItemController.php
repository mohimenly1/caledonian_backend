<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LostItemTicket;
use App\Models\Student;
use App\Models\User;
use App\Notifications\LostItemNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class LostItemController extends Controller
{
    // For Parents (Flutter App)
    public function getParentTickets()
    {
        $tickets = LostItemTicket::where('parent_user_id', Auth::id())->with('student:id,name')->latest()->get();
        return response()->json($tickets);
    }

    public function showTicketForParent(LostItemTicket $ticket)
    {
        // $user = Auth::user();
        // $isParent = $ticket->parent_user_id === $user->id;
        // $isAdmin = $user->user_type === 'moder'; // Or whatever your admin user_type is

        // Authorization check
      

        // Load all necessary relationships
        return response()->json($ticket->load([
            'student:id,name',
            'parent:id,name',
            'messages.sender:id,name,user_type'
        ]));
    }

    public function storeTicket(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'attachment' => 'nullable|image|max:2048',
        ]);

        $student = Student::findOrFail($validated['student_id']);
        if ($student->parent->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $ticket = LostItemTicket::create([
            'student_id' => $validated['student_id'],
            'parent_user_id' => Auth::id(),
            'subject' => $validated['subject'],
        ]);

        $attachmentPath = $request->hasFile('attachment') ? $request->file('attachment')->store('lost_items', 'public') : null;

        $ticket->messages()->create([
            'user_id' => Auth::id(),
            'message' => $validated['message'],
            'attachment_path' => $attachmentPath,
        ]);

        // Notify admins
        $admins = User::where('user_type', 'moder')->get();
        Notification::send($admins, new LostItemNotification($ticket, "New lost item ticket created for student: {$student->name}"));

        return response()->json($ticket, 201);
    }

    public function replyToTicket(Request $request, LostItemTicket $ticket)
    {
        $user = Auth::user();
        $isParent = $ticket->parent_user_id === $user->id;
        $isAdmin = $user->user_type === 'moder';

        // if ((!$isParent && !$isAdmin) || $ticket->status === 'closed') {
        //     return response()->json(['message' => 'Unauthorized or ticket is closed'], 403);
        // }
        
        $validated = $request->validate([
            'message' => 'required|string',
            'attachment' => 'nullable|image|max:2048',
        ]);

        $attachmentPath = $request->hasFile('attachment') ? $request->file('attachment')->store('lost_items', 'public') : null;

        $ticket->messages()->create([
            'user_id' => $user->id,
            'message' => $validated['message'],
            'attachment_path' => $attachmentPath,
        ]);
        
        // Notify the other party
        if ($isParent) {
            $admins = User::where('user_type', 'moder')->get();
            Notification::send($admins, new LostItemNotification($ticket, "Parent replied to ticket #{$ticket->id}"));
        } else { // Is Admin
            Notification::send($ticket->parent, new LostItemNotification($ticket, "You have a new reply on your ticket #{$ticket->id}"));
        }

        return response()->json($ticket->messages()->latest()->first()->load('sender:id,name'));
    }

    // For Admins (Vue Dashboard)
    public function getAllTickets()
    {
        $tickets = LostItemTicket::with('student:id,name', 'parent:id,name')->latest()->get();
        return response()->json($tickets);
    }
    
    public function closeTicket(LostItemTicket $ticket)
    {
        $ticket->update(['status' => 'closed']);
        // Optionally, notify the parent that the ticket has been closed
        Notification::send($ticket->parent, new LostItemNotification($ticket, "Your ticket #{$ticket->id} has been closed."));
        return response()->json(['message' => 'Ticket closed successfully.']);
    }
}