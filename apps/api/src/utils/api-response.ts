import type { Response } from 'express';
export function ok<T>(res:Response,data:T,meta:Record<string,unknown>={}){return res.json({success:true,data,error:null,meta});}
export function created<T>(res:Response,data:T){return res.status(201).json({success:true,data,error:null,meta:{}});}
