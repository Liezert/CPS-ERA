<?php

namespace App\Observers;

use App\Models\KnowledgeDocument;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Str;

class KnowledgeDocumentObserver
{
    /**
     * Handle the KnowledgeDocument "created" event.
     */
    public function created(KnowledgeDocument $document): void
    {
        if ($document->status === 'published') {
            $this->notifyUsers($document);
        }
    }

    /**
     * Handle the KnowledgeDocument "updated" event.
     */
    public function updated(KnowledgeDocument $document): void
    {
        if ($document->wasChanged('status') && $document->status === 'published') {
            $this->notifyUsers($document);
        }
    }

    /**
     * Send notification to all other users about the newly published document.
     */
    protected function notifyUsers(KnowledgeDocument $document): void
    {
        $users = User::query()
            ->when($document->created_by, fn ($q) => $q->where('id', '!=', $document->created_by))
            ->get();

        foreach ($users as $user) {
            Notification::create([
                'user_id' => $user->id,
                'type' => 'knowledge_baru',
                'title' => 'Knowledge Baru: '.Str::limit($document->title, 100),
                'message' => 'Dokumen pengetahuan baru telah diterbitkan: '.Str::limit($document->title, 150),
                'related_type' => 'knowledge_document',
                'related_id' => (string) $document->id,
                'read_at' => null,
            ]);
        }
    }
}
