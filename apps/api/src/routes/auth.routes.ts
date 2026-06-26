import { Router } from 'express';import { z } from 'zod';import { authController } from '../controllers/auth.controller.js';import { requireAuth } from '../middleware/auth.js';import { validate } from '../middleware/validate.js';
export const authRoutes=Router();
authRoutes.post('/login',validate(z.object({body:z.object({email:z.string().email(),password:z.string().min(8)})})),authController.login);
authRoutes.get('/me',requireAuth,authController.me);
