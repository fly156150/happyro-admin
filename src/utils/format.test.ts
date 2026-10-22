import { describe, expect, it } from 'vitest';
import { toPlainRagnarokText } from './format';

describe('toPlainRagnarokText', () => {
  it('keeps NAVI display names and hides navigation markup', () => {
    expect(
      toPlainRagnarokText(
        '^ffffff<NAVI>^4D4DFF[海达姆伙伴]^000000<INFO>mal_in01,20,124,0,100,0,0</INFO></NAVI>^000000',
      ),
    ).toBe('[海达姆伙伴]');
  });
});
