<?php

namespace App\Policies;

use App\Models\KnowledgeTopic;
use App\Models\User;

class KnowledgeTopicPolicy
{
    /**
     * Determine whether the user can view any models.
     * Semua karyawan boleh melihat daftar topik untuk filter pustaka pengetahuan (PRD v2.0 §3.2).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, KnowledgeTopic $knowledgeTopic): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     * Topik dikelola oleh Admin/HRD/Quality (PRD v2.0 §3.2).
     */
    public function create(User $user): bool
    {
        return $this->canManageTopics($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, KnowledgeTopic $knowledgeTopic): bool
    {
        return $this->canManageTopics($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, KnowledgeTopic $knowledgeTopic): bool
    {
        return $this->canManageTopics($user);
    }

    /**
     * Helper to verify if user has authority to manage knowledge topics.
     */
    protected function canManageTopics(User $user): bool
    {
        return $user->can('manage-knowledge-topics');
    }
}
