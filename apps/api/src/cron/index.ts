import cron from 'node-cron';import { monitoringQueue } from '../queues/monitoring.queue.js';import { logger } from '../lib/logger.js';
export function startCron(){cron.schedule('*/15 * * * *',async()=>{await monitoringQueue.add('collect-news',{scheduledAt:new Date().toISOString()},{jobId:`collect-news-${Math.floor(Date.now()/900000)}`});logger.info('cron_collect_news_enqueued');});}
