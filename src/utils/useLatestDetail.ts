import { useCallback, useEffect, useRef, useState } from 'react';

// Invalidating a request also prevents a closed drawer from reopening.
export function useLatestDetail<T>() {
  const [detail, setDetail] = useState<T>();
  const sequence = useRef(0);
  useEffect(
    () => () => {
      sequence.current += 1;
    },
    [],
  );
  const loadDetail = useCallback(
    async (request: () => Promise<{ data: T }>) => {
      const current = ++sequence.current;
      const result = await request();
      if (current === sequence.current) setDetail(result.data);
    },
    [],
  );
  const closeDetail = useCallback(() => {
    sequence.current += 1;
    setDetail(undefined);
  }, []);
  return { detail, loadDetail, closeDetail };
}
