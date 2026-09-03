import { request } from '@umijs/max';

export type GameControlCommand = {
  idempotency_key: string;
  type: string;
  target: { type: 'character'; id: string };
  payload: Record<string, number>;
};

export async function executeGameControlCommand(data: GameControlCommand) {
  return request('/api/operations/game-control/commands', {
    method: 'POST',
    data,
  });
}
