import { describe, expect, it, vi } from 'vitest';
import { createRetryableMutation } from './retryableMutation';

describe('retryable grants', () => {
  it('reuses a failed request key even after submitting different input', async () => {
    const send = vi.fn(
      async (_: { amount: number; idempotency_key: string }) => {
        throw new Error('response lost');
      },
    );
    const submit = createRetryableMutation<{ amount: number }, void>(send);
    await expect(submit({ amount: 1 })).rejects.toThrow('response lost');
    await expect(submit({ amount: 2 })).rejects.toThrow('response lost');
    await expect(submit({ amount: 1 })).rejects.toThrow('response lost');
    expect(send.mock.calls[0][0].idempotency_key).toBe(
      send.mock.calls[2][0].idempotency_key,
    );
    expect(send.mock.calls[0][0].idempotency_key).not.toBe(
      send.mock.calls[1][0].idempotency_key,
    );
  });
  it('coalesces concurrent clicks and permits a new grant after confirmed success', async () => {
    const send = vi.fn(
      async (_: { amount: number; idempotency_key: string }) => 123,
    );
    const submit = createRetryableMutation<{ amount: number }, number>(send);
    const first = submit({ amount: 1 });
    expect(submit({ amount: 1 })).toBe(first);
    expect(await first).toBe(123);
    await submit({ amount: 1 });
    expect(send).toHaveBeenCalledTimes(2);
    expect(send.mock.calls[0][0].idempotency_key).not.toBe(
      send.mock.calls[1][0].idempotency_key,
    );
  });
});
