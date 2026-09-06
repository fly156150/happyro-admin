import type { RequestOptions } from '@@/plugin-request/request';
import type { RequestConfig } from '@umijs/max';
import { getIntl } from '@umijs/max';

export type RequestFeedback = {
  warning: (content?: string) => void;
  error: (content?: string) => void;
  notify: (options: { title?: number; description?: string }) => void;
};

let requestFeedback: RequestFeedback | undefined;

export function setRequestFeedback(feedback?: RequestFeedback) {
  requestFeedback = feedback;
}

// 错误处理方案： 错误类型
enum ErrorShowType {
  SILENT = 0,
  WARN_MESSAGE = 1,
  ERROR_MESSAGE = 2,
  NOTIFICATION = 3,
  REDIRECT = 9,
}
// 与后端约定的响应数据格式
interface ResponseStructure {
  success: boolean;
  data: unknown;
  errorCode?: number;
  errorMessage?: string;
  showType?: ErrorShowType;
}

const csrfProtectedMethods = new Set(['POST', 'PUT', 'PATCH', 'DELETE']);
let csrfRefreshPromise: Promise<void> | undefined;

function refreshCsrfCookie() {
  csrfRefreshPromise ??= fetch('/sanctum/csrf-cookie', {
    method: 'GET',
    credentials: 'include',
    cache: 'no-store',
    headers: { Accept: 'application/json' },
  })
    .then((response) => {
      if (!response.ok) {
        throw new Error(`Unable to refresh CSRF cookie (${response.status})`);
      }
    })
    .finally(() => {
      csrfRefreshPromise = undefined;
    });

  return csrfRefreshPromise;
}

function currentXsrfToken() {
  const cookie = document.cookie
    .split('; ')
    .find((entry) => entry.startsWith('XSRF-TOKEN='));

  return cookie ? decodeURIComponent(cookie.slice('XSRF-TOKEN='.length)) : null;
}

/**
 * @name 错误处理
 * pro 自带的错误处理， 可以在这里做自己的改动
 * @doc https://umijs.org/docs/max/request#配置
 */
export const errorConfig: RequestConfig = {
  // 错误处理： umi@3 的错误处理方案。
  errorConfig: {
    // 错误抛出
    errorThrower: (res) => {
      const { success, data, errorCode, errorMessage, showType } =
        res as unknown as ResponseStructure;
      if (!success) {
        const error: any = new Error(errorMessage);
        error.name = 'BizError';
        error.info = { errorCode, errorMessage, showType, data };
        throw error; // 抛出自制的错误
      }
    },
    // 错误接收及处理
    errorHandler: (error: any, opts: any) => {
      if (opts?.skipErrorHandler) throw error;
      // 我们的 errorThrower 抛出的错误。
      if (error.name === 'BizError') {
        const errorInfo: ResponseStructure | undefined = error.info;
        if (errorInfo) {
          const { errorMessage, errorCode } = errorInfo;
          switch (errorInfo.showType) {
            case ErrorShowType.SILENT:
              // do nothing
              break;
            case ErrorShowType.WARN_MESSAGE:
              requestFeedback?.warning(errorMessage);
              break;
            case ErrorShowType.ERROR_MESSAGE:
              requestFeedback?.error(errorMessage);
              break;
            case ErrorShowType.NOTIFICATION:
              requestFeedback?.notify({
                title: errorCode,
                description: errorMessage,
              });
              break;
            case ErrorShowType.REDIRECT:
              window.location.href = '/user/login';
              break;
            default:
              requestFeedback?.error(errorMessage);
          }
        }
      } else if (error.response) {
        // Axios 的错误
        // 请求成功发出且服务器也响应了状态码，但状态代码超出了 2xx 的范围
        requestFeedback?.error(`Response status:${error.response.status}`);
      } else if (typeof navigator !== 'undefined' && !navigator.onLine) {
        requestFeedback?.error(
          getIntl().formatMessage({
            id: 'app.request.offline',
            defaultMessage:
              'Network unavailable. Please check your connection and try again.',
          }),
        );
      } else if (error.request) {
        requestFeedback?.error('None response! Please retry.');
      } else {
        requestFeedback?.error('Request error, please retry.');
      }
    },
  },

  // 请求拦截器
  requestInterceptors: [
    (config: RequestOptions) => {
      if (csrfProtectedMethods.has((config.method ?? 'GET').toUpperCase())) {
        return refreshCsrfCookie().then(() => {
          const token = currentXsrfToken();
          if (!token) {
            throw new Error('CSRF cookie was not issued');
          }

          return {
            ...config,
            headers: { ...config.headers, 'X-XSRF-TOKEN': token },
          };
        });
      }

      return config;
    },
  ],

  // 响应拦截器
  responseInterceptors: [],
};
