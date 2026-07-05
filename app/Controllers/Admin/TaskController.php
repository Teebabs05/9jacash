<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Models\ActivityLog;
use App\Models\Task;
use App\Models\TaskSubmission;
use App\Services\TaskService;
use Exception;

class TaskController extends Controller
{
    public function index(): void
    {
        if ($this->isPost()) {
            $this->verifyCsrf();
            Task::create([
                'title' => sanitize($this->post('title')),
                'description' => sanitize($this->post('description')),
                'category' => sanitize($this->post('category')),
                'link' => sanitize($this->post('link', '')),
                'reward_amount' => (float) $this->post('reward_amount'),
                'requires_proof' => $this->post('requires_proof') ? 1 : 0,
                'repeatable' => sanitize($this->post('repeatable', 'once')),
                'status' => 'active',
            ]);
            Session::flash('success', 'Task created.');
            $this->redirect('admin/tasks');
        }

        $this->view('admin/tasks/index', [
            'title' => 'Tasks',
            'tasks' => Task::all('created_at DESC'),
        ], 'admin');
    }

    public function update(int $id): void
    {
        $this->verifyCsrf();
        Task::updateById($id, [
            'title' => sanitize($this->post('title')),
            'description' => sanitize($this->post('description')),
            'reward_amount' => (float) $this->post('reward_amount'),
            'status' => $this->post('status') === 'active' ? 'active' : 'inactive',
        ]);
        Session::flash('success', 'Task updated.');
        $this->redirect('admin/tasks');
    }

    public function delete(int $id): void
    {
        $this->verifyCsrf();
        Task::deleteById($id);
        Session::flash('success', 'Task deleted.');
        $this->redirect('admin/tasks');
    }

    public function submissions(): void
    {
        $page = max(1, (int) $this->input('page', 1));
        $status = $this->input('status') ?: null;
        $result = TaskSubmission::paginatedPending($page, 20, $status);

        $this->view('admin/tasks/submissions', [
            'title' => 'Task Submissions',
            'rows' => $result['rows'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => 20,
            'status' => $status,
        ], 'admin');
    }

    public function approveSubmission(int $id): void
    {
        $this->verifyCsrf();
        try {
            TaskService::approveSubmission($id);
            ActivityLog::log((int) current_user()['id'], 'task_submission_approved', "Approved task submission #{$id}.");
            Session::flash('success', 'Submission approved and reward paid.');
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());
        }
        $this->redirect('admin/tasks-submissions');
    }

    public function rejectSubmission(int $id): void
    {
        $this->verifyCsrf();
        try {
            TaskService::rejectSubmission($id, sanitize($this->post('note', 'Rejected by admin.')) ?: 'Rejected by admin.');
            Session::flash('success', 'Submission rejected.');
        } catch (Exception $e) {
            Session::flash('error', $e->getMessage());
        }
        $this->redirect('admin/tasks-submissions');
    }
}
