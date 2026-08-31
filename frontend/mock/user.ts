import type { Request, Response } from 'express';

export default {
  'GET /api/users': [
    { key: '1', name: 'John Brown', age: 32, address: 'New York' },
    { key: '2', name: 'Jim Green', age: 42, address: 'London' },
  ],
  'GET /api/500': (_req: Request, res: Response) => {
    res.status(500).send({ message: 'Internal Server Error' });
  },
  'GET /api/404': (_req: Request, res: Response) => {
    res.status(404).send({ message: 'Not Found' });
  },
  'GET /api/403': (_req: Request, res: Response) => {
    res.status(403).send({ message: 'Forbidden' });
  },
  'GET /api/401': (_req: Request, res: Response) => {
    res.status(401).send({ message: 'Unauthorized' });
  },
};
