// Source: happyro-client/src/Engine/Auth/RegistrationValidation.js,
// PACKETVER=20211103. Registration reserves two username bytes for _M/_F.
export function credentialError(value: string, kind: 'account' | 'password') {
  const label = kind === 'account' ? '账号' : '密码';
  const maximum = kind === 'account' ? 21 : 23;
  if (/\p{Cc}/u.test(value) || !value.isWellFormed())
    return `${label}包含无效字符。`;
  if (kind === 'account' && value.trim() !== value)
    return '账号首尾不能包含空格。';
  if (kind === 'account' && /_[mf]$/i.test(value))
    return '账号不能以 _M 或 _F 结尾。';
  const length = new TextEncoder().encode(value).length;
  if (length < 6) return `${label}最少 6 个字节`;
  if (length > maximum) return `${label}最多 ${maximum} 个字节`;
  return '';
}

export function credentialRule(kind: 'account' | 'password') {
  return {
    validator: async (_rule: unknown, value?: string) => {
      const error = credentialError(value ?? '', kind);
      if (error) throw new Error(error);
    },
  };
}
