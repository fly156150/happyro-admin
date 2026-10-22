const numberFormatter = new Intl.NumberFormat('en-US');

/**
 * Format a number with thousand separators.
 * Replaces numeral(val).format('0,0')
 */
export const formatNumber = (val: number | string): string => {
  const parsed = Number(val);
  return Number.isFinite(parsed) ? numberFormatter.format(parsed) : '';
};

/**
 * Format a number as yuan currency string.
 * Replaces `¥ ${numeral(val).format('0,0')}`
 */
export const formatYuan = (val: number | string) => `¥ ${formatNumber(val)}`;

export function toPlainRagnarokText(value: string | undefined): string {
  return String(value ?? '')
    .replace(
      /<(ITEMLINK|ITEM|NAVI)>([\s\S]*?)<INFO>[\s\S]*?<\/INFO><\/\1>/gi,
      '$2',
    )
    .replace(/\^[0-9a-f]{6}/gi, '')
    .replace(/(?:\\n|\^n)/gi, '\n');
}
