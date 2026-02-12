import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

declare global {
  interface Window {
    Pusher: typeof Pusher;
  }
}

window.Pusher = Pusher;

const key = import.meta.env.VITE_REVERB_APP_KEY;
const host = import.meta.env.VITE_REVERB_HOST;
const port = import.meta.env.VITE_REVERB_PORT;
const scheme = import.meta.env.VITE_REVERB_SCHEME || 'ws';

// eslint-disable-next-line @typescript-eslint/no-explicit-any
let echo: any = null;

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export function getEcho(): any {
  if (!key || !host) return null;
  if (echo) return echo;
  try {
    echo = new Echo({
      broadcaster: 'reverb',
      key,
      wsHost: host,
      wsPort: port || 8080,
      wssPort: port || 443,
      forceTLS: scheme === 'wss',
      enabledTransports: ['ws', 'wss'],
      disableStats: true,
    });
    return echo;
  } catch {
    return null;
  }
}

export type DataUpdatedPayload = { entity: string; action: string };

export function subscribeDataUpdates(onUpdate: (payload: DataUpdatedPayload) => void): (() => void) | null {
  const instance = getEcho();
  if (!instance) return null;
  instance.channel('data-updates').listen('.data.updated', (e: DataUpdatedPayload) => {
    onUpdate(e);
  });
  return () => {
    instance.leave('data-updates');
  };
}

export type JobCompletedPayload = { job_type: string; status: string; payload?: Record<string, unknown> };

export function subscribeJobUpdates(onUpdate: (payload: JobCompletedPayload) => void): (() => void) | null {
  const instance = getEcho();
  if (!instance) return null;
  instance.channel('job-updates').listen('.job.completed', (e: JobCompletedPayload) => {
    onUpdate(e);
  });
  return () => {
    instance.leave('job-updates');
  };
}

/** Payload khi server broadcast kết quả request (request → notification) */
export type RequestCompletedPayload = {
  request_id: string;
  success: boolean;
  entity: string;
  action: string;
  data?: Record<string, unknown> | unknown[] | null;
  error?: string | null;
};

export function subscribeRequestResults(onResult: (payload: RequestCompletedPayload) => void): (() => void) | null {
  const instance = getEcho();
  if (!instance) return null;
  instance.channel('request-results').listen('.request.completed', (e: RequestCompletedPayload) => {
    onResult(e);
  });
  return () => {
    instance.leave('request-results');
  };
}

/** Chờ tối đa ms để nhận notification có request_id trùng; trả về payload hoặc null nếu hết giờ. */
export function waitForRequestResult(
  requestId: string,
  timeoutMs: number = 30000
): Promise<RequestCompletedPayload | null> {
  return new Promise((resolve) => {
    const unsub = subscribeRequestResults((payload) => {
      if (payload.request_id === requestId) {
        unsub?.();
        resolve(payload);
      }
    });
    if (!unsub) {
      resolve(null);
      return;
    }
    setTimeout(() => {
      unsub();
      resolve(null);
    }, timeoutMs);
  });
}
