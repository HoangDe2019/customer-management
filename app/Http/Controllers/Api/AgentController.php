<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AgentController extends Controller
{
    /**
     * List agents: admin sees all active; others see only allowed.
     */
    public function index(Request $request)
    {
        if ($request->user()->is_admin) {
            $agents = Agent::active()->orderBy('name')->get();
        } else {
            $agents = $request->user()->getAccessibleAgents();
        }

        $list = $agents->map(fn ($a) => [
            'id' => $a->id,
            'agent_id' => $a->agent_id,
            'name' => $a->name,
            'status' => $a->status,
            'allowed_users' => $a->allowed_users,
            'created_date' => $a->created_at?->format('d/m/Y H:i:s'),
        ])->toArray();

        return response()->json(['agents' => $list]);
    }

    /**
     * Get user's agents for dropdown (like getUserAgents in GAS).
     */
    public function userAgents(Request $request)
    {
        $agents = $request->user()->getAccessibleAgents();
        $list = $agents->map(fn ($a) => [
            'id' => $a->id,
            'agent_id' => $a->agent_id,
            'name' => $a->name,
            'status' => $a->status,
        ])->values()->toArray();
        return response()->json($list);
    }

    /**
     * Admin: create agent.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'allowed_users' => 'nullable|string', // comma-separated emails
        ]);

        if (!$request->user()->is_admin) {
            return response()->json(['error' => 'Chỉ admin mới có quyền tạo đại lý'], 403);
        }

        $name = trim($request->name);
        if (Agent::where('agent_id', $name)->orWhere('name', $name)->exists()) {
            return response()->json(['error' => 'Tên đại lý đã tồn tại. Vui lòng chọn tên khác.'], 422);
        }

        $allowedUsers = $request->allowed_users
            ? array_map('trim', array_filter(explode(',', $request->allowed_users)))
            : [];

        $agent = Agent::create([
            'agent_id' => $name,
            'name' => $name,
            'status' => 'Active',
            'allowed_users' => $allowedUsers,
            'created_by' => $request->user()->id,
        ]);

        foreach ($allowedUsers as $email) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $agent->users()->syncWithoutDetaching([$user->id]);
            }
        }

        return response()->json([
            'success' => true,
            'agent_id' => $agent->agent_id,
            'message' => 'Đã tạo đại lý thành công',
            'agent' => $agent,
        ], 201);
    }

    /**
     * Show one agent (if user has access).
     */
    public function show(Request $request, Agent $agent)
    {
        if (!$request->user()->hasAgentAccess($agent)) {
            return response()->json(['error' => 'Bạn không có quyền truy cập đại lý này'], 403);
        }
        return response()->json($agent->load('creator:id,name,email'));
    }

    /**
     * Admin: update agent.
     */
    public function update(Request $request, Agent $agent)
    {
        if (!$request->user()->is_admin) {
            return response()->json(['error' => 'Chỉ admin mới có quyền cập nhật đại lý'], 403);
        }

        $request->validate([
            'status' => ['nullable', Rule::in(['Active', 'Inactive', 'Deleted'])],
            'allowed_users' => 'nullable|string',
        ]);

        if ($request->has('status')) {
            $agent->status = $request->status;
        }
        if ($request->has('allowed_users')) {
            $emails = array_map('trim', array_filter(explode(',', $request->allowed_users)));
            $agent->allowed_users = $emails;
            $userIds = User::whereIn('email', $emails)->pluck('id');
            $agent->users()->sync($userIds);
        }
        $agent->save();

        return response()->json(['success' => true, 'message' => 'Đã cập nhật thành công', 'agent' => $agent]);
    }

    /**
     * Admin: soft-delete / deactivate agent.
     */
    public function destroy(Request $request, Agent $agent)
    {
        if (!$request->user()->is_admin) {
            return response()->json(['error' => 'Chỉ admin mới có quyền xóa đại lý'], 403);
        }
        if (strtolower($agent->agent_id) === 'khách hàng') {
            return response()->json(['error' => 'Không thể xóa đại lý mặc định'], 422);
        }
        $agent->status = 'Deleted';
        $agent->save();
        return response()->json(['success' => true, 'message' => 'Đã xóa đại lý']);
    }
}
