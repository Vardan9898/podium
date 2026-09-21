import { ProposalStatus } from '@/types/api';

const STATUS_LABELS: Record<ProposalStatus, string> = {
    [ProposalStatus.Pending]: 'Pending',
    [ProposalStatus.Approved]: 'Approved',
    [ProposalStatus.Rejected]: 'Rejected',
};

export const STATUS_OPTIONS = Object.values(ProposalStatus).map((value) => ({ value, label: STATUS_LABELS[value] }));

export function statusLabel(status: ProposalStatus): string {
    return STATUS_LABELS[status];
}

const dateFormat = new Intl.DateTimeFormat(undefined, { day: 'numeric', month: 'short', year: 'numeric' });

export function formatDate(iso: string): string {
    return dateFormat.format(new Date(iso));
}

const relative = new Intl.RelativeTimeFormat(undefined, { numeric: 'auto' });

export function timeAgo(iso: string, now = Date.now()): string {
    const seconds = Math.round((new Date(iso).getTime() - now) / 1000);
    const units: [Intl.RelativeTimeFormatUnit, number][] = [
        ['day', 86400],
        ['hour', 3600],
        ['minute', 60],
    ];

    for (const [unit, size] of units) {
        if (Math.abs(seconds) >= size) {
            return relative.format(Math.round(seconds / size), unit);
        }
    }

    return 'just now';
}

/** Ticket-style reference, e.g. #0042. */
export function reference(id: number): string {
    return `#${String(id).padStart(4, '0')}`;
}
