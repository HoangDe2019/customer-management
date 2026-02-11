<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessQueueJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class QueueController extends Controller
{
    /**
     * Add a job to the queue. Body: type (string), payload (object), queue (optional).
     * Types: sync_to_staging, export_transactions, cccd_scan, or any custom type.
     */
    public function dispatch(Request $request)
    {
        $request->validate([
            'type' => 'required|string|max:64',
            'payload' => 'nullable|array',
            'queue' => 'nullable|string|max:64',
        ]);

        ProcessQueueJob::dispatch(
            $request->input('type'),
            $request->input('payload', []),
            $request->input('queue')
        );

        return response()->json([
            'success' => true,
            'message' => 'Job added to queue',
        ], 202);
    }

    /**
     * Get queue status (pending count). Database driver only.
     */
    public function status(Request $request)
    {
        $connection = $request->get('connection', config('queue.default'));
        $driver = config("queue.connections.{$connection}.driver");

        if ($driver !== 'database') {
            return response()->json([
                'connection' => $connection,
                'driver' => $driver,
                'pending_count' => null,
                'message' => 'Pending count only available for database driver',
            ]);
        }

        $table = config("queue.connections.{$connection}.table", 'jobs');
        $pending = DB::table($table)->count();

        return response()->json([
            'connection' => $connection,
            'driver' => $driver,
            'pending_count' => $pending,
        ]);
    }

    /**
     * Trigger clone-to-staging (dispatches job to queue). Run worker to process.
     */
    public function triggerClone(Request $request)
    {
        ProcessQueueJob::dispatch('sync_to_staging', [], $request->input('queue', 'default'));

        return response()->json([
            'success' => true,
            'message' => 'Clone-to-staging job added to queue. Run queue:work to process.',
        ], 202);
    }

    /**
     * Get last clone-to-staging run status (from cache).
     */
    public function cloneStatus(Request $request)
    {
        $data = Cache::get(ProcessQueueJob::CACHE_KEY_CLONE_LAST);

        return response()->json([
            'last_run' => $data,
        ]);
    }
}
