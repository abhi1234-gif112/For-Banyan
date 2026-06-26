import { Queue } from 'bullmq';import { redis } from '../lib/redis.js';
export const monitoringQueue=new Queue('monitoring',{connection:redis,defaultJobOptions:{attempts:3,backoff:{type:'exponential',delay:5000},removeOnComplete:100,removeOnFail:500}});
