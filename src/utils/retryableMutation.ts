import { createIdempotencyKey } from './idempotency';

/** Keep failed attempts identifiable until the form is unmounted. */
export function createRetryableMutation<Payload extends object, Result>(
  send: (payload: Payload & { idempotency_key: string }) => Promise<Result>,
  isFinalFailure?: (error: unknown) => boolean,
) {
  const attempts = new Map<
    string,
    { key: string; running?: Promise<Result> }
  >();
  return (payload: Payload): Promise<Result> => {
    // Form payloads contain JSON values; sort fields so insertion order is irrelevant.
    const fingerprint = JSON.stringify(
      Object.fromEntries(
        Object.entries(payload).sort(([a], [b]) => a.localeCompare(b)),
      ),
    );
    let attempt = attempts.get(fingerprint);
    if (!attempt) {
      attempt = { key: createIdempotencyKey() };
      attempts.set(fingerprint, attempt);
    }
    if (attempt.running) return attempt.running;
    const current = attempt;
    current.running = Promise.resolve()
      .then(() => send({ ...payload, idempotency_key: current.key }))
      .then((result) => {
        attempts.delete(fingerprint);
        return result;
      })
      .catch((error: unknown) => {
        if (isFinalFailure?.(error)) attempts.delete(fingerprint);
        throw error;
      })
      .finally(() => {
        current.running = undefined;
      });
    return current.running;
  };
}
