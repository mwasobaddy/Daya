import React from 'react';
import clsx from 'clsx';

type Role = 'client' | 'dcd' | 'da';

const roles: { id: Role; title: string; desc: string; varName: string }[] = [
  { id: 'client', title: 'Client', desc: 'Create your account and launch your first campaign.', varName: '--role-client' },
  { id: 'dcd', title: 'Digital Content Distributor', desc: 'Share content at your business and earn rewards.', varName: '--role-dcd' },
  { id: 'da', title: 'Digital Ambassador', desc: 'Promote Daya and grow with commission & shares.', varName: '--role-da' }
];

export default function RoleSelector({ value, onChange }: { value?: Role; onChange?: (r: Role) => void }) {
  return (
    <form className="grid grid-cols-1 md:grid-cols-3 gap-4" aria-label="Choose your role">
      {roles.map((r) => (
        <label
          key={r.id}
          className={clsx(
            'relative block rounded-lg border p-4 shadow-sm transition-transform hover:-translate-y-1 focus-within:shadow-outline',
            value === r.id ? 'ring-4 ring-offset-2' : ''
          )}
          style={{ borderColor: 'var(--border)', background: 'var(--card)' }}
        >
          <input
            type="radio"
            name="role"
            value={r.id}
            className="sr-only"
            checked={value === r.id}
            onChange={() => onChange?.(r.id)}
          />

          <div className="flex items-start justify-between gap-3">
            <div>
              <div className="text-base font-semibold text-[color:var(--card-foreground)]">{r.title}</div>
              <div className="mt-1 text-sm text-[color:var(--muted-foreground)]">{r.desc}</div>
            </div>

            <div className="shrink-0">
              <svg width="40" height="40" viewBox="0 0 24 24" fill="none" aria-hidden>
                <rect width="24" height="24" rx="6" fill={`var(${r.varName})`} />
                <path d="M7 12h10M7 16h5" stroke="#fff" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
              </svg>
            </div>
          </div>

          <div className="mt-4 flex justify-end">
            <button
              type="button"
              onClick={() => onChange?.(r.id)}
              className="inline-flex items-center rounded-md px-3 py-1 text-sm font-medium"
              style={{ background: `var(${r.varName})`, color: '#fff' }}
            >
              Choose
            </button>
          </div>
        </label>
      ))}
    </form>
  );
}
