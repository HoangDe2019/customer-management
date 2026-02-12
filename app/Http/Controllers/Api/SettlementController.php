<?php
// app/Http/Controllers/Api/SettlementController.php

namespace App\Http\Controllers\Api;

use App\Events\DataUpdated;
use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\EodSettlement;
use App\Services\SettlementService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SettlementController extends Controller
{
    protected SettlementService $settlementService;

    public function __construct(SettlementService $settlementService)
    {
        $this->settlementService = $settlementService;
    }

    public function getEndOfDaySettlement(Request $request)
    {
        $request->validate([
            'agent_id' => 'required|exists:agents,id',
            'date' => 'required|date_format:d/m/Y',
        ]);

        $agent = Agent::findOrFail($request->agent_id);

        // Check access
        if (!$request->user()->hasAgentAccess($agent)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $date = Carbon::createFromFormat('d/m/Y', $request->date)->format('Y-m-d');
        $settlement = $this->settlementService->calculateEndOfDaySettlement($agent, $date);

        return response()->json($settlement);
    }

    public function saveEndOfDaySettlement(Request $request)
    {
        $request->validate([
            'agent_id' => 'required|exists:agents,id',
            'date' => 'required|date_format:d/m/Y',
            'settlement_data' => 'required|array',
        ]);

        $agent = Agent::findOrFail($request->agent_id);

        // Check access (admin or agent owner)
        if (!$request->user()->isAdmin() && !$request->user()->hasAgentAccess($agent)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $date = Carbon::createFromFormat('d/m/Y', $request->date)->format('Y-m-d');

        $settlement = $this->settlementService->saveEndOfDaySettlement(
            $agent,
            $date,
            $request->settlement_data,
            $request->user()
        );

        event(new DataUpdated('settlements', 'saved'));

        return response()->json($settlement, 201);
    }

    public function getDailyAdvances(Request $request)
    {
        $request->validate([
            'agent_id' => 'required|exists:agents,id',
            'date' => 'required|date_format:d/m/Y',
        ]);

        $agent = Agent::findOrFail($request->agent_id);

        // Check access
        if (!$request->user()->hasAgentAccess($agent)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $date = Carbon::createFromFormat('d/m/Y', $request->date)->format('Y-m-d');
        $advances = $this->settlementService->getDailyAdvanceSummary($agent, $date);

        return response()->json($advances);
    }

    public function settleDailyAdvances(Request $request)
    {
        $request->validate([
            'agent_id' => 'required|exists:agents,id',
            'date' => 'required|date_format:d/m/Y',
        ]);

        $agent = Agent::findOrFail($request->agent_id);

        // Check access
        if (!$request->user()->isAdmin() && !$request->user()->hasAgentAccess($agent)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $date = Carbon::createFromFormat('d/m/Y', $request->date)->format('Y-m-d');
        $result = $this->settlementService->settleDailyAdvances($agent, $date);

        event(new DataUpdated('settlements', 'settled'));

        return response()->json($result);
    }

    public function getSettlementHistory(Request $request)
    {
        $query = EodSettlement::with(['agent', 'settler']);

        if ($request->has('agent_id')) {
            $agent = Agent::findOrFail($request->agent_id);

            if (!$request->user()->hasAgentAccess($agent)) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $query->where('agent_id', $agent->id);
        } else {
            if (!$request->user()->isAdmin()) {
                $agentIds = $request->user()->agents()->pluck('agents.id');
                $query->whereIn('agent_id', $agentIds);
            }
        }

        $settlements = $query->latest('settlement_date')
            ->paginate($request->get('per_page', 15));

        return response()->json($settlements);
    }
}
