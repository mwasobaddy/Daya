import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import RoleSelector from '@/components/RoleSelector';

export default function RoleDemo() {
  const [role, setRole] = useState<'client' | 'dcd' | 'da' | undefined>(undefined);

  return (
    <>
      <Head title="Role selector demo" />
      <div className="min-h-screen flex items-start justify-center bg-[color:var(--background)] p-6">
        <div className="w-full max-w-4xl">
          <h1 className="mb-4 text-2xl font-semibold text-[color:var(--foreground)]">Role selector demo</h1>
          <p className="mb-6 text-sm text-[color:var(--muted-foreground)]">Pick a role to continue — this demo shows our 60/30/10 accents in light & dark modes.</p>

          <RoleSelector value={role} onChange={(r) => setRole(r)} />

          <div className="mt-6 text-sm text-[color:var(--foreground)]">
            Selected role: <strong>{role ?? 'none'}</strong>
          </div>
        </div>
      </div>
    </>
  );
}
