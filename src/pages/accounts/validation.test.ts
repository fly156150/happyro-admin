import { describe, expect, it } from 'vitest';
import { credentialError } from './validation';

describe('game account wire validation', () => {
  it('counts UTF-8 bytes with separate username and password bounds', () => {
    expect(credentialError('short', 'account')).toBe('账号最少 6 个字节');
    expect(credentialError('中文', 'account')).toBe('');
    expect(credentialError('中'.repeat(7), 'account')).toBe('');
    expect(credentialError('中'.repeat(8), 'account')).toBe(
      '账号最多 21 个字节',
    );
    expect(credentialError('a'.repeat(23), 'password')).toBe('');
    expect(credentialError('a'.repeat(24), 'password')).toBe(
      '密码最多 23 个字节',
    );
  });
  it('rejects control characters, broken surrogates and username edge spaces', () => {
    expect(credentialError('abc\0def', 'password')).toBe('密码包含无效字符。');
    expect(credentialError('abcdef\ud800', 'password')).toBe(
      '密码包含无效字符。',
    );
    expect(credentialError(' abcdef', 'account')).toBe(
      '账号首尾不能包含空格。',
    );
    expect(credentialError(' abcdef', 'password')).toBe('');
    expect(credentialError('player_M', 'account')).toBe(
      '账号不能以 _M 或 _F 结尾。',
    );
  });
});
