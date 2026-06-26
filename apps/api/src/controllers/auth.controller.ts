import type { Request,Response } from 'express';import { authService } from '../services/auth.service.js';import { ok } from '../utils/api-response.js';
export const authController={login:async(req:Request,res:Response)=>ok(res,await authService.login(req.body.email,req.body.password)),me:async(req:Request,res:Response)=>ok(res,{user:req.user})};
