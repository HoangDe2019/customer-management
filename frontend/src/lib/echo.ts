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
const apiUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000';

let echo: Echo | null = null;

/**
 * Initialize Echo with authentication support for private channels
 */
export function getEcho(): Echo | null {
  if (!key || !host) {
    //console.warn('Echo: Missing REVERB_APP_KEY or REVERB_HOST');
    return null;
  }

  if (echo) return echo;

  try {
    const token = sessionStorage.getItem('access_token');

    echo = new Echo({
      broadcaster: 'reverb',
      key,
      wsHost: host,
      wsPort: port || 8080,
      wssPort: port || 443,
      forceTLS: scheme === 'wss',
      enabledTransports: ['ws', 'wss'],
      disableStats: true,

      // ✅ Add auth for private channels
      authEndpoint: `${apiUrl}/broadcasting/auth`,
      auth: {
        headers: {
          Authorization: token ? `Bearer ${token}` : '',
          Accept: 'application/json',
        },
      },
    });

    //console.log('Echo initialized successfully');
    return echo;
  } catch (error) {
    //console.error('Echo initialization failed:', error);
    return null;
  }
}

/**
 * Disconnect Echo (useful for cleanup on logout)
 */
export function disconnectEcho(): void {
  if (echo) {
    echo.disconnect();
    echo = null;
    //console.log('Echo disconnected');
  }
}

// ==================== Public Channels ====================

export type DataUpdatedPayload = {
  entity: string;
  action: string;
};

export function subscribeDataUpdates(
  onUpdate: (payload: DataUpdatedPayload) => void
): (() => void) | null {
  const instance = getEcho();
  if (!instance) return null;

  const channel = instance.channel('data-updates');
  channel.listen('.data.updated', (e: DataUpdatedPayload) => {
    onUpdate(e);
  });

  return () => {
    instance.leave('data-updates');
  };
}

export type JobCompletedPayload = {
  job_type: string;
  status: string;
  payload?: Record<string, unknown>;
};

export function subscribeJobUpdates(
  onUpdate: (payload: JobCompletedPayload) => void
): (() => void) | null {
  const instance = getEcho();
  if (!instance) return null;

  const channel = instance.channel('job-updates');
  channel.listen('.job.completed', (e: JobCompletedPayload) => {
    onUpdate(e);
  });

  return () => {
    instance.leave('job-updates');
  };
}

// ==================== Private Channels (User-specific) ====================

export type RequestCompletedPayload = {
  request_id: string;
  success: boolean;
  resource_type: string; // Changed from 'entity' to match backend
  action: string;
  data?: Record<string, unknown> | unknown[] | null;
  error?: string | null;
  timestamp?: string;
};

/**
 * Subscribe to user-specific request results on private channel
 * ✅ Now uses private channel: user.{userId}
 */
export function subscribeRequestResults(
  userId: number | string,
  onResult: (payload: RequestCompletedPayload) => void
): (() => void) | null {
  const instance = getEcho();
  if (!instance) {
    //console.warn('Echo instance not available');
    return null;
  }

  try {
    // ✅ Listen on private channel
    const channelName = `user.${userId}`;
    //console.log(`Subscribing to private channel: ${channelName}`);

    const channel = instance.private(channelName);

    channel.listen('.request.completed', (e: RequestCompletedPayload) => {
      //console.log('📨 Request completed notification received:', e);
      onResult(e);
    });

    // Handle subscription success
    channel.subscribed(() => {
     // console.log(`✅ Successfully subscribed to ${channelName}`);
    });

    // Handle subscription errors
    channel.error((error: Error) => {
      console.error(`❌ Failed to subscribe to ${channelName}:`, error);
    });

    return () => {
      //console.log(`Leaving channel: ${channelName}`);
      instance.leave(channelName);
    };
  } catch (error) {
    console.error('Error subscribing to request results:', error);
    return null;
  }
}

/**
 * Wait for a specific request result notification
 * ✅ Now uses private channel
 */
export function waitForRequestResult(
    requestId: string,
    userId: number | string,
    timeoutMs: number = 30000
  ): Promise<RequestCompletedPayload | null> {
    return new Promise((resolve) => {
      //console.log(`[Echo] ⏳ Waiting for request: ${requestId}`);
      //console.log(`[Echo] 👤 User ID: ${userId}`);
      //console.log(`[Echo] ⏰ Timeout: ${timeoutMs}ms`);

      let resolved = false;
      const safeResolve = (value: RequestCompletedPayload | null) => {
        if (!resolved) {
          resolved = true;
          console.log(`[Echo] ✅ Resolving with:`, value);
          resolve(value);
        }
      };

      const unsub = subscribeRequestResults(userId, (payload) => {
        if (payload.request_id === requestId) {
          //console.log(`[Echo] ✅ Match! Resolving...`);
          unsub?.();
          safeResolve(payload);
        } else {
          console.log(`[Echo] ⚠️ Request ID mismatch, ignoring...`);
        }
      });

      if (!unsub) {
        //console.error('[Echo] ❌ Failed to subscribe');
        safeResolve(null);
        return;
      }

      // Timeout handler
      const timeoutId = setTimeout(() => {
        unsub();
        safeResolve(null);
      }, timeoutMs);

      // Clear timeout on early resolution
      const originalResolve = resolve;
      resolve = (value) => {
        clearTimeout(timeoutId);
        originalResolve(value);
      };
    });
  }

// ==================== Convenience Function ====================

/**
 * Get current user ID from sessionStorage
 */
export function getCurrentUserId(): number | null {
  const userStr = sessionStorage.getItem('user');
  if (!userStr) return null;

  try {
    const user = JSON.parse(userStr);
    return user?.id || null;
  } catch {
    return null;
  }
}
