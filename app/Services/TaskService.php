<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Notification;
use App\Models\ReferralEarning;
use App\Models\Task;
use App\Models\TaskSubmission;
use App\Models\Wallet;
use Exception;

class TaskService
{
    public static function approveSubmission(int $submissionId): void
    {
        $submission = TaskSubmission::find($submissionId);
        if (!$submission || $submission['status'] !== 'pending') {
            throw new Exception('This submission has already been processed.');
        }

        $task = Task::find((int) $submission['task_id']);
        $reward = (float) $task['reward_amount'];

        TaskSubmission::updateById($submissionId, ['status' => 'approved', 'reward_paid' => $reward]);
        Wallet::credit((int) $submission['user_id'], 'task', $reward, 'task_reward', 'Reward for task: ' . $task['title']);
        ReferralEarning::distribute((int) $submission['user_id'], 'task', $reward);
        Notification::send((int) $submission['user_id'], 'Task Approved', "Your submission for \"{$task['title']}\" was approved. You earned " . money($reward) . ".", 'success');
    }

    public static function rejectSubmission(int $submissionId, string $note): void
    {
        $submission = TaskSubmission::find($submissionId);
        if (!$submission || $submission['status'] !== 'pending') {
            throw new Exception('This submission has already been processed.');
        }

        $task = Task::find((int) $submission['task_id']);
        TaskSubmission::updateById($submissionId, ['status' => 'rejected', 'admin_note' => $note]);
        Notification::send((int) $submission['user_id'], 'Task Rejected', "Your submission for \"{$task['title']}\" was rejected: {$note}", 'error');
    }
}
