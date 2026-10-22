let formatter: Intl.DateTimeFormat;

export function configureTimeZone(value: string) {
  formatter = new Intl.DateTimeFormat('sv-SE', {
    timeZone: value,
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hourCycle: 'h23',
  });
}

configureTimeZone('Asia/Shanghai');

/** API dates must identify an instant with Z or a numeric offset. */
export function formatDateTime(value: unknown): string {
  if (typeof value !== 'string' && typeof value !== 'number') return '—';
  if (typeof value === 'string' && !/(Z|[+-]\d{2}:\d{2})$/.test(value))
    return '—';
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? '—' : formatter.format(date);
}
