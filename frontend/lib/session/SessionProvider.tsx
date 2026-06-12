'use client';

import {
  createContext,
  useContext,
  useMemo,
  useState,
  type ReactNode,
} from 'react';
import type { User } from '@/lib/types/user';

interface SessionContextValue {
  user: User | null;
  setUser: (user: User | null) => void;
}

const SessionContext = createContext<SessionContextValue | null>(null);

interface SessionProviderProps {
  initialUser: User | null;
  children: ReactNode;
}

export function SessionProvider({
  initialUser,
  children,
}: SessionProviderProps) {
  const [user, setUser] = useState<User | null>(initialUser);

  const value = useMemo(() => ({ user, setUser }), [user]);

  return (
    <SessionContext.Provider value={value}>{children}</SessionContext.Provider>
  );
}

export function useSession(): SessionContextValue {
  const context = useContext(SessionContext);
  if (!context) {
    throw new Error('useSession must be used within a <SessionProvider>');
  }
  return context;
}
