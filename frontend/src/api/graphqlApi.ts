/**
 * API layer dùng GraphQL. Mutations theo mô hình request → notification:
 * gửi mutation → nhận request_id → chờ notification trùng request_id → trả về data hoặc throw.
 */
import { graphqlRequest } from '../lib/graphql';
import { getCurrentUserId, waitForRequestResult } from '../lib/echo';
import type { RequestCompletedPayload } from '../lib/echo';
import * as ops from '../graphql/operations';
import type {
    User,
    Agent,
    Transaction,
    PaginatedResponse,
    Config,
    Statistics,
    SummaryItem,
    MoMoQRResponse,
    CCCDScanResult,
} from '../types';
import { api } from '../lib/api';
interface QueueStatus {
    connection: string;
    driver: string;
    pending_count: number | null;
    message?: string;
}
interface CloneLastRun {
    status?: 'running' | 'success' | 'failed';
    message?: string;
    started_at?: string;
    finished_at?: string;
    exit_code?: number;
}
interface EodSettlementRecord {
    id: number;
    agent_id: number;
    settlement_date: string;
    agent?: { id: number; name: string };
    settler?: { id: number; name: string };
    [key: string]: unknown;
}

const NOTIFY_TIMEOUT = 25000;

// --- Auth / Config (vẫn dùng REST cho login/register để lấy token)
export async function getMe(): Promise<User> {
    const res = await graphqlRequest<{ me: User }>(ops.QUERY_ME);
    return res.me;
}

export async function getConfig(): Promise<Config> {
    const res = await graphqlRequest<{ config: Config }>(ops.QUERY_CONFIG);
    return res.config;
}

// --- Agents
export async function getAgents(): Promise<Agent[]> {
    const res = await graphqlRequest<{ agents: Agent[] }>(ops.QUERY_AGENTS);
    return res.agents ?? [];
}

export async function getUserAgents(): Promise<Agent[]> {
    const res = await graphqlRequest<{ userAgents: Agent[] }>(ops.QUERY_USER_AGENTS);
    return Array.isArray(res.userAgents) ? res.userAgents : [];
}

export async function getAgent(id: number): Promise<Agent> {
    const res = await graphqlRequest<{ agent: Agent }>(
        `query Agent($id: Int!) { agent(id: $id) { id agent_id name status allowed_users } }`,
        { id }
    );
    return res.agent;
}

interface RequestAck {
    accepted: boolean;
    request_id: string;
    message?: string;
}

/**
 * Helper: wait for WebSocket notification for a given RequestAck.
 * Throws on timeout or when backend reports error.
 */
async function waitForAckNotification(
    ack: RequestAck,
    timeoutMs?: number
): Promise<RequestCompletedPayload> {
    const userId = getCurrentUserId();
    if (!userId) {
        throw new Error('User not authenticated');
    }

    const notification = await waitForRequestResult(
        ack.request_id,
        userId,
        typeof timeoutMs === 'number' ? timeoutMs : NOTIFY_TIMEOUT
    );

    console.log("notification", notification);

    if (!notification) {
        throw new Error(
            ack.message || 'Không nhận được phản hồi từ hệ thống. Vui lòng thử lại.'
        );
    }

    if (!notification.success) {
        throw new Error(notification.error || 'Xử lý yêu cầu thất bại');
    }

    return notification;
}

export async function createAgent(payload: {
    name: string;
    allowed_users?: string;
}): Promise<void> {
    const res = await graphqlRequest<{ createAgent: RequestAck }>(
        ops.MUTATION_CREATE_AGENT,
        payload
    );
    const ack = res.createAgent;
    await waitForAckNotification(ack);
}

export async function updateAgent(
    id: number,
    payload: { status?: string; allowed_users?: string }
): Promise<void> {
    const res = await graphqlRequest<{ updateAgent: RequestAck }>(ops.MUTATION_UPDATE_AGENT, {
        id,
        ...payload,
    });
    const ack = res.updateAgent;
    await waitForAckNotification(ack);
}

export async function deleteAgent(id: number): Promise<void> {
    const res = await graphqlRequest<{ deleteAgent: RequestAck }>(ops.MUTATION_DELETE_AGENT, { id });
    console.log("agent", res);
    const ack = res.deleteAgent;
    await waitForAckNotification(ack);
}

// --- Transactions
export interface TransactionFilters {
    agent_id?: number;
    status?: string;
    date_from?: string;
    date_to?: string;
    transaction_type?: string;
    page?: number;
    per_page?: number;
}

export async function getTransactions(
    params?: TransactionFilters
): Promise<PaginatedResponse<Transaction>> {
    const res = await graphqlRequest<{ transactions: PaginatedResponse<Transaction> }>(
        ops.QUERY_TRANSACTIONS,
        params as Record<string, unknown>
    );
    return res.transactions;
}

export async function getTransaction(id: number): Promise<Transaction> {
    const res = await graphqlRequest<{ transaction: Transaction }>(ops.QUERY_TRANSACTION, { id });
    return res.transaction;
}

export async function createTransaction(payload: {
    agent_id: number;
    customer_name?: string;
    cccd_number?: string;
    total_amount: number;
    transaction_type: 'Đáo' | 'Rút';
    pos_fee_percent?: number;
    agent_fee_percent?: number;
    agent_advance?: number;
}): Promise<Transaction> {
    const res = await graphqlRequest<{ createTransaction: RequestAck }>(
        ops.MUTATION_CREATE_TRANSACTION,
        payload as Record<string, unknown>
    );
    const ack = res.createTransaction;
    const notification = await waitForAckNotification(ack);
    return notification.data as unknown as Transaction;
}

export async function updateTransactionStatus(
    id: number,
    status: string
): Promise<void> {
    const res = await graphqlRequest<{ updateTransactionStatus: RequestAck }>(
        ops.MUTATION_UPDATE_TRANSACTION_STATUS,
        { id, status }
    );
    const ack = res.updateTransactionStatus;
    await waitForAckNotification(ack);
}

export async function scanCCCD(imageBase64: string): Promise<CCCDScanResult> {
    const res = await graphqlRequest<{ scanCCCD: RequestAck }>(ops.MUTATION_SCAN_CCCD, {
        image: imageBase64,
    });

    const ack = res.scanCCCD;
    const notification = await waitForAckNotification(ack, 60000);
    return (notification.data ?? null) as CCCDScanResult;
}

// --- Settlements
export async function getDailyAdvances(
    agentId: number,
    date: string
): Promise<Record<string, unknown>> {
    const res = await graphqlRequest<{ dailyAdvances: { value: string } }>(
        ops.QUERY_DAILY_ADVANCES,
        { agent_id: agentId, date }
    );
    try {
        return JSON.parse(res.dailyAdvances?.value ?? '{}');
    } catch {
        return {};
    }
}

export async function settleDailyAdvances(
    agentId: number,
    date: string
): Promise<{ success?: boolean; message?: string }> {
    const res = await graphqlRequest<{ settleDailyAdvances: RequestAck }>(
        ops.MUTATION_SETTLE_DAILY_ADVANCES,
        { agent_id: agentId, date }
    );
    const ack = res.settleDailyAdvances;
    await waitForAckNotification(ack);
    return { success: true };
}

export async function getEodSettlement(
    agentId: number,
    date: string
): Promise<Record<string, unknown>> {
    const res = await graphqlRequest<{ eodSettlement: { value: string } }>(
        ops.QUERY_EOD_SETTLEMENT,
        { agent_id: agentId, date }
    );
    try {
        return JSON.parse(res.eodSettlement?.value ?? '{}');
    } catch {
        return {};
    }
}

export async function saveEodSettlement(
    agentId: number,
    date: string,
    settlementData: Record<string, unknown>
): Promise<void> {
    const res = await graphqlRequest<{ saveEodSettlement: RequestAck }>(
        ops.MUTATION_SAVE_EOD_SETTLEMENT,
        {
            agent_id: agentId,
            date,
            settlement_data: JSON.stringify(settlementData),
        }
    );
    const ack = res.saveEodSettlement;
    await waitForAckNotification(ack);
}

export async function getSettlementHistory(params?: {
    agent_id?: number;
    page?: number;
    per_page?: number;
}): Promise<PaginatedResponse<EodSettlementRecord>> {
    const res = await graphqlRequest<{
        settlementHistory: PaginatedResponse<EodSettlementRecord>;
    }>(ops.QUERY_SETTLEMENT_HISTORY, params as Record<string, unknown>);
    return res.settlementHistory;
}

// --- Statistics
export async function getStatistics(
    agentId: number,
    period?: 'all' | 'today' | '3days' | 'week' | 'month'
): Promise<Statistics> {
    const res = await graphqlRequest<{ statistics: Statistics }>(ops.QUERY_STATISTICS, {
        agent_id: agentId,
        period: period ?? 'all',
    });
    const s = res.statistics;
    if (s && typeof s.by_status === 'string') {
        try {
            (s as Statistics & { by_status: unknown }).by_status = JSON.parse(s.by_status);
        } catch {
            /* keep string */
            throw new Error('Lỗi parse JSON');
        }
    }
    return s;
}

export async function getSummary(): Promise<SummaryItem[]> {
    const res = await graphqlRequest<{ summary: SummaryItem[] }>(ops.QUERY_SUMMARY);
    return Array.isArray(res.summary) ? res.summary : [];
}

// --- Queue
export async function getQueueStatus(): Promise<QueueStatus> {
    const res = await graphqlRequest<{ queueStatus: QueueStatus }>(ops.QUERY_QUEUE_STATUS);
    return res.queueStatus;
}

export async function getCloneStatus(): Promise<{ last_run: CloneLastRun | null }> {
    const res = await graphqlRequest<{ cloneStatus: { last_run: CloneLastRun | null } }>(
        ops.QUERY_CLONE_STATUS
    );
    return { last_run: res.cloneStatus?.last_run ?? null };
}

export async function dispatchJob(payload: {
    type: string;
    payload?: Record<string, unknown>;
    queue?: string;
}): Promise<{ success: boolean; message: string }> {
    const res = await graphqlRequest<{ queueDispatch: RequestAck }>(ops.MUTATION_QUEUE_DISPATCH, {
        type: payload.type,
        payload: payload.payload ? JSON.stringify(payload.payload) : undefined,
        queue: payload.queue,
    });
    return { success: res.queueDispatch.accepted, message: res.queueDispatch.message ?? 'OK' };
}

export async function triggerCloneToStaging(): Promise<{ success: boolean; message: string }> {
    const res = await graphqlRequest<{ triggerClone: RequestAck }>(ops.MUTATION_TRIGGER_CLONE);
    return { success: res.triggerClone.accepted, message: res.triggerClone.message ?? 'OK' };
}

// --- MoMo
export async function generateMoMoQR(payload: {
    customer_name: string;
    total_amount: number;
    transaction_type: 'Đáo' | 'Rút';
}): Promise<MoMoQRResponse> {
    // Dùng GraphQL + WebSocket notification thay vì REST thuần
    const res = await graphqlRequest<{ generateMoMoQR: RequestAck }>(
        ops.MUTATION_GENERATE_MOMO_QR,
        payload
    );
    const ack = res.generateMoMoQR;
    const notification = await waitForAckNotification(ack);

    const raw = (notification.data ?? {}) as {
        payment_id?: number | null;
        order_id?: string | null;
        qr_code_url?: string | null;
        pay_url?: string | null;
        deeplink?: string | null;
        status?: string;
        result_code?: number | string;
        message?: string;
    };

    return {
        paymentId: raw.payment_id ?? null,
        orderId: raw.order_id ?? null,
        qrCodeUrl: raw.qr_code_url ?? null,
        payUrl: raw.pay_url ?? null,
        deeplink: raw.deeplink ?? null,
        status: (raw.status as MoMoQRResponse['status']) ?? 'failed',
        resultCode: raw.result_code ?? -1,
        message: raw.message ?? 'Unknown error',
    };
}

export async function checkMoMoStatus(orderId: string): Promise<{
    status: string;
    resultCode?: number;
    message?: string;
}> {
    const res = await graphqlRequest<{
        momoCheckStatus: { status: string; resultCode?: number; message?: string };
    }>(ops.QUERY_MOMO_CHECK_STATUS, { orderId });
    return res.momoCheckStatus;
}
