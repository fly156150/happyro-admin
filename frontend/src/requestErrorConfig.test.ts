import { beforeEach, describe, expect, it, vi } from 'vitest';
import { errorConfig, setRequestFeedback } from './requestErrorConfig';

const feedback = {
  warning: vi.fn(),
  error: vi.fn(),
  notify: vi.fn(),
};

vi.mock('@umijs/max', () => ({
  getIntl: vi.fn(() => ({
    formatMessage: vi.fn(({ defaultMessage }) => defaultMessage),
  })),
}));

// biome-ignore lint/style/noNonNullAssertion: config handlers are always defined
const errorThrower = errorConfig.errorConfig!.errorThrower!;
// biome-ignore lint/style/noNonNullAssertion: config handlers are always defined
const errorHandler = errorConfig.errorConfig!.errorHandler!;

beforeEach(() => {
  vi.clearAllMocks();
  vi.restoreAllMocks();
  setRequestFeedback(feedback);
});

describe('errorThrower', () => {
  it('should throw error when success is false', () => {
    const response = {
      success: false,
      data: null,
      errorCode: 400,
      errorMessage: 'Bad Request',
      showType: 2,
    };

    expect(() => {
      errorThrower(response);
    }).toThrow('Bad Request');
  });

  it('should not throw error when success is true', () => {
    const response = {
      success: true,
      data: { id: 1 },
    };

    expect(() => {
      errorThrower(response);
    }).not.toThrow();
  });

  it('should throw BizError with correct info', () => {
    const response = {
      success: false,
      data: { detail: 'more info' },
      errorCode: 403,
      errorMessage: 'Forbidden',
      showType: 3,
    };

    expect.assertions(5);
    try {
      errorThrower(response);
    } catch (error: any) {
      expect(error.name).toBe('BizError');
      expect(error.info.errorCode).toBe(403);
      expect(error.info.errorMessage).toBe('Forbidden');
      expect(error.info.showType).toBe(3);
      expect(error.info.data).toEqual({ detail: 'more info' });
    }
  });
});

describe('business error handler', () => {
  it('should rethrow error when skipErrorHandler is true', () => {
    const error = new Error('Test error');
    const opts = { skipErrorHandler: true };

    expect(() => {
      errorHandler(error, opts);
    }).toThrow('Test error');
  });

  it('should handle SILENT showType', () => {
    const error: any = new Error('Silent error');
    error.name = 'BizError';
    error.info = {
      errorCode: 1001,
      errorMessage: 'Silent error',
      showType: 0,
    };

    errorHandler(error, {});

    expect(feedback.warning).not.toHaveBeenCalled();
    expect(feedback.error).not.toHaveBeenCalled();
    expect(feedback.notify).not.toHaveBeenCalled();
  });

  it('should handle WARN_MESSAGE showType', () => {
    const error: any = new Error('Warning');
    error.name = 'BizError';
    error.info = {
      errorCode: 1002,
      errorMessage: 'This is a warning',
      showType: 1,
    };

    errorHandler(error, {});

    expect(feedback.warning).toHaveBeenCalledWith('This is a warning');
  });

  it('should handle ERROR_MESSAGE showType', () => {
    const error: any = new Error('Error message');
    error.name = 'BizError';
    error.info = {
      errorCode: 1003,
      errorMessage: 'This is an error',
      showType: 2,
    };

    errorHandler(error, {});

    expect(feedback.error).toHaveBeenCalledWith('This is an error');
  });

  it('should handle NOTIFICATION showType', () => {
    const error: any = new Error('Notification');
    error.name = 'BizError';
    error.info = {
      errorCode: 1004,
      errorMessage: 'This is a notification',
      showType: 3,
    };

    errorHandler(error, {});

    expect(feedback.notify).toHaveBeenCalledWith({
      title: 1004,
      description: 'This is a notification',
    });
  });

  it('should handle REDIRECT showType', () => {
    const error: any = new Error('Redirect');
    error.name = 'BizError';
    error.info = {
      errorCode: 401,
      errorMessage: 'Unauthorized',
      showType: 9,
    };

    errorHandler(error, {});

    // REDIRECT 分支不应触发任何消息/通知提示
    expect(feedback.warning).not.toHaveBeenCalled();
    expect(feedback.error).not.toHaveBeenCalled();
    expect(feedback.notify).not.toHaveBeenCalled();
  });

  it('should handle default case for unknown showType', () => {
    const error: any = new Error('Unknown type');
    error.name = 'BizError';
    error.info = {
      errorCode: 1005,
      errorMessage: 'Unknown error type',
      showType: 99,
    };

    errorHandler(error, {});

    expect(feedback.error).toHaveBeenCalledWith('Unknown error type');
  });
});

describe('transport error handler', () => {
  it('should handle axios response error', () => {
    const error: any = new Error('Axios error');
    error.response = {
      status: 500,
      data: {},
    };

    errorHandler(error, {});

    expect(feedback.error).toHaveBeenCalledWith('Response status:500');
  });

  it('should handle offline error', () => {
    const error: any = new Error('Network error');
    error.request = {};

    const originalOnLine = navigator.onLine;
    Object.defineProperty(navigator, 'onLine', {
      writable: true,
      value: false,
    });

    try {
      errorHandler(error, {});

      expect(feedback.error).toHaveBeenCalledWith(
        'Network unavailable. Please check your connection and try again.',
      );
    } finally {
      Object.defineProperty(navigator, 'onLine', {
        writable: true,
        value: originalOnLine,
      });
    }
  });

  it('should handle request error with no response', () => {
    const error: any = new Error('Request error');
    error.request = {};

    errorHandler(error, {});

    expect(feedback.error).toHaveBeenCalledWith('None response! Please retry.');
  });

  it('should handle generic error', () => {
    const error: any = new Error('Generic error');

    errorHandler(error, {});

    expect(feedback.error).toHaveBeenCalledWith('Request error, please retry.');
  });
});

describe('requestInterceptors', () => {
  // The interceptor is registered as a plain function (not a tuple),
  // so narrow the union type to a callable for the test.
  const interceptor = errorConfig.requestInterceptors?.[0] as (config: {
    url?: string;
    method?: string;
  }) =>
    | { url?: string; headers?: Record<string, string> }
    | Promise<{ url?: string; headers?: Record<string, string> }>;

  it('should pass through config without modification', () => {
    const config = {
      url: 'https://api.example.com/users',
      method: 'GET',
    };

    const result = interceptor(config) as { url?: string };

    expect(result.url).toBe('https://api.example.com/users');
  });

  it('should handle URL without config', () => {
    const config = {};

    const result = interceptor(config) as { url?: string };

    expect(result.url).toBeUndefined();
  });

  it.each([
    'POST',
    'put',
    'PATCH',
    'DELETE',
  ])('should refresh the CSRF cookie before %s requests', async (method) => {
    const fetchMock = vi
      .spyOn(globalThis, 'fetch')
      .mockResolvedValue({ ok: true } as Response);
    vi.spyOn(document, 'cookie', 'get').mockReturnValue(
      'theme=dark; XSRF-TOKEN=refreshed%3Dtoken',
    );
    const config = { url: '/api/resource', method };

    const result = await interceptor(config);

    expect(fetchMock).toHaveBeenCalledWith('/sanctum/csrf-cookie', {
      method: 'GET',
      credentials: 'include',
      cache: 'no-store',
      headers: { Accept: 'application/json' },
    });
    expect(result.headers).toEqual({ 'X-XSRF-TOKEN': 'refreshed=token' });
  });

  it('should share one CSRF refresh across concurrent mutations', async () => {
    let resolveRefresh: ((response: Response) => void) | undefined;
    const fetchMock = vi.spyOn(globalThis, 'fetch').mockReturnValue(
      new Promise((resolve) => {
        resolveRefresh = resolve;
      }),
    );
    vi.spyOn(document, 'cookie', 'get').mockReturnValue(
      'XSRF-TOKEN=shared-token',
    );

    const first = interceptor({ url: '/api/first', method: 'POST' });
    const second = interceptor({ url: '/api/second', method: 'PATCH' });
    resolveRefresh?.({ ok: true } as Response);
    await Promise.all([first, second]);

    expect(fetchMock).toHaveBeenCalledTimes(1);
  });
});
