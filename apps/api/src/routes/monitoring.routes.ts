import { Router } from 'express';import { z } from 'zod';import { monitoringController } from '../controllers/monitoring.controller.js';import { requireAuth, requireRole } from '../middleware/auth.js';import { validate } from '../middleware/validate.js';
export const monitoringRoutes=Router();
monitoringRoutes.use(requireAuth);
monitoringRoutes.get('/dashboard',monitoringController.dashboard);
monitoringRoutes.get('/mentions',validate(z.object({query:z.object({page:z.coerce.number().min(1).optional(),limit:z.coerce.number().min(1).max(100).optional(),search:z.string().optional()})})),monitoringController.mentions);
monitoringRoutes.post('/mentions',requireRole(['ADMIN','ANALYST']),validate(z.object({body:z.object({source:z.string(),sourceUrl:z.string().url(),title:z.string(),content:z.string(),individualId:z.string().optional()})})),monitoringController.ingest);
