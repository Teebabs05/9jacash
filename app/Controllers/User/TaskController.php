<?php

declare(strict_types=1);

namespace App\Controllers\User;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Upload;
use App\Models\ActivityLog;
use App\Models\Task;
use App\Models\TaskSubmission;
use Exception;

class TaskController extends Controller
{
    public function index(): void
    {
        $userId = (int) current_user()['id'];
        $this->view('user/tasks', [
            'title' => 'Tasks',
            'tasks' => Task::availableForUser($userId),
        ], 'dashboard');
    }

    public function submit(int $id): void
    {
        $this->verifyCsrf();
        $userId = (int) current_user()['id'];
        $task = Task::find($id);

        if (!$task || $task['status'] !== 'active') {
            Session::flash('error', 'This task is not available.');
            $this->redirect('tasks');
        }

        try {
            $proofFile = null;
            if ((bool) $task['requires_proof']) {
                if (empty($_FILES['proof']) || $_FILES['proof']['error'] === UPLOAD_ERR_NO_FILE) {
                    throw new Exception('Please upload proof of completion.');
                }
                $proofFile = Upload::image($_FILES['proof'], 'proofs');
            }

            TaskSubmission::create([
                'task_id' => $id,
                'user_id' => $userId,
                'proof_text' => sanitize($this->post('proof_text', '')),
                'proof_file' => $proofFile,
                'status' => $task['requires_proof'] ? 'pending' : 'approved',
            ]);

            if (!$task['requires_proof']) {
                \App\Models\Wallet::credit($userId, 'task', (float) $task['reward_amount'], 'task_reward', 'Reward for task: ' . $task['title']);
                \App\Models\ReferralEarning::distribute($userId, 'task', (float) $task['reward_amount']);
            }

            ActivityLog::log($userId, 'task_submitted', "Submitted task '{$task['title']}'.");
            Session::flash('success', $task['requires_proof'] ? 'Submission received and pending review.' : 'Task completed! Reward credited.');
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());
        }

        $this->redirect('tasks');
    }

    public function history(): void
    {
        $userId = (int) current_user()['id'];
        $this->view('user/task-history', [
            'title' => 'Task History',
            'submissions' => TaskSubmission::forUser($userId),
        ], 'dashboard');
    }
}
