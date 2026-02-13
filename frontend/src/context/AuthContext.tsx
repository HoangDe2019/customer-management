import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
  type ReactNode,
} from 'react';
import { getMe } from '../api/auth';
import { getStoredUser } from '../lib/api';
import type { User } from '../types';

interface AuthContextValue {
  user: User | null;
  loading: boolean;
  setUser: (u: User | null) => void;
  refreshUser: () => Promise<void>;
}

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUserState] = useState<User | null>(() => getStoredUser());
  const [loading, setLoading] = useState(!!sessionStorage.getItem('access_token'));

  const refreshUser = useCallback(async () => {
    if (!sessionStorage.getItem('access_token')) {
      setUserState(null);
      setLoading(false);
      return;
    }
    try {
      const u = await getMe();
      setUserState(u);
    } catch {
      setUserState(null);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    if (sessionStorage.getItem('access_token') && !user?.agents) {
      refreshUser();
    } else {
      setLoading(false);
    }
  }, [refreshUser]);

  const setUser = useCallback((u: User | null) => {
    setUserState(u);
  }, []);

  const value = useMemo(
    () => ({ user, loading, setUser, refreshUser }),
    [user, loading, setUser, refreshUser]
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used within AuthProvider');
  return ctx;
}
