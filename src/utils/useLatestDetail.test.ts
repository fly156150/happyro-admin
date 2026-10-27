import { act, renderHook } from '@testing-library/react';
import { expect, it } from 'vitest';
import { useLatestDetail } from './useLatestDetail';

function deferred() {
  let resolve!: (result: { data: string }) => void;
  const promise = new Promise<{ data: string }>((done) => {
    resolve = done;
  });
  return { promise, resolve };
}
it('keeps the last selected detail when responses arrive out of order', async () => {
  const { result } = renderHook(() => useLatestDetail<string>());
  const first = deferred();
  const second = deferred();
  let a!: Promise<void>;
  let b!: Promise<void>;
  act(() => {
    a = result.current.loadDetail(() => first.promise);
    b = result.current.loadDetail(() => second.promise);
  });
  await act(async () => {
    second.resolve({ data: 'B' });
    await b;
  });
  expect(result.current.detail).toBe('B');
  await act(async () => {
    first.resolve({ data: 'A' });
    await a;
  });
  expect(result.current.detail).toBe('B');
});
it('does not reopen a closed detail when a pending request finishes', async () => {
  const { result } = renderHook(() => useLatestDetail<string>());
  await act(async () => {
    await result.current.loadDetail(async () => ({ data: 'A' }));
  });
  const next = deferred();
  const loading = result.current.loadDetail(() => next.promise);
  act(() => result.current.closeDetail());
  await act(async () => {
    next.resolve({ data: 'B' });
    await loading;
  });
  expect(result.current.detail).toBeUndefined();
});
