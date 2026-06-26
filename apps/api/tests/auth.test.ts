import { describe, expect, it } from 'vitest';import request from 'supertest';import { createApp } from '../src/app.js';
describe('auth controller',()=>{it('rejects invalid login payload',async()=>{const res=await request(createApp()).post('/api/auth/login').send({email:'bad',password:'x'});expect(res.status).toBe(400);expect(res.body.success).toBe(false);});});
