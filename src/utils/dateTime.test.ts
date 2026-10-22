import { afterEach, expect, it } from 'vitest';
import { configureTimeZone, formatDateTime } from './dateTime';

afterEach(() => configureTimeZone('Asia/Shanghai'));
it('converts UTC and explicit offsets without adding the offset twice', () => {
  expect(formatDateTime('2026-10-22T20:01:02.123456Z')).toBe(
    '2026-10-23 04:01:02',
  );
  expect(formatDateTime('2026-10-23T04:01:02+08:00')).toBe(
    '2026-10-23 04:01:02',
  );
  expect(formatDateTime('2026-10-22T16:00:00Z')).toBe('2026-10-23 00:00:00');
});
it('uses the configured zone including daylight saving transitions', () => {
  configureTimeZone('America/New_York');
  expect(formatDateTime('2026-03-08T06:59:59Z')).toBe('2026-03-08 01:59:59');
  expect(formatDateTime('2026-03-08T07:00:00Z')).toBe('2026-03-08 03:00:00');
  configureTimeZone('UTC');
  expect(formatDateTime('2026-10-22T16:00:00Z')).toBe('2026-10-22 16:00:00');
});
it('rejects ambiguous timestamps, empty values and invalid configuration', () => {
  for (const value of [null, undefined, '', '2026-10-22 07:17:36', 'invalidZ'])
    expect(formatDateTime(value)).toBe('—');
  expect(() => configureTimeZone('Invalid/Zone')).toThrow();
});
