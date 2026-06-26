import Anthropic from '@anthropic-ai/sdk';import { z } from 'zod';import { env } from '../config/env.js';
const analysisSchema=z.object({summary:z.string(),sentiment:z.enum(['POSITIVE','NEUTRAL','NEGATIVE']),riskScore:z.number().int().min(0).max(100),topics:z.array(z.string())});
export type ClaudeAnalysis=z.infer<typeof analysisSchema>;
export class ClaudeService{private client=env.ANTHROPIC_API_KEY?new Anthropic({apiKey:env.ANTHROPIC_API_KEY}):null;async analyzeMention(text:string):Promise<ClaudeAnalysis>{if(!this.client)return {summary:text.slice(0,240),sentiment:'NEUTRAL',riskScore:0,topics:[]};const response=await this.client.messages.create({model:env.CLAUDE_MODEL,max_tokens:600,temperature:0,system:'Return only JSON with summary, sentiment, riskScore, topics.',messages:[{role:'user',content:text}]});const raw=response.content[0]?.type==='text'?response.content[0].text:'{}';return analysisSchema.parse(JSON.parse(raw));}}
export const claudeService=new ClaudeService();
