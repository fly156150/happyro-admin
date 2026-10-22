// Source: happyro-server/src/common/mmo.hpp; reserve the terminating NUL.
export function mailTextError(value: string, maximum: number, label: string) {
  if (value.includes('\0')) return `${label}包含无效字符。`;
  if (new TextEncoder().encode(value).length > maximum)
    return `${label}最多 ${maximum} 个 UTF-8 字节（汉字通常占 3 字节）。`;
  return '';
}

export function mailTextRule(maximum: number, label: string) {
  return {
    validator: async (_rule: unknown, value?: string) => {
      const error = mailTextError(value ?? '', maximum, label);
      if (error) throw new Error(error);
    },
  };
}
