import { describe, expect, it } from 'vitest';import { detectSeverity, scoreMention } from '../src/services/scoring.service.js';
describe('scoring',()=>{it('scores risky language',()=>{expect(scoreMention('corruption fraud arrest')).toBe(75);expect(detectSeverity(75)).toBe('HIGH');});});
