import bcrypt from 'bcrypt';
import { PrismaClient } from '@prisma/client';
const prisma = new PrismaClient();
async function main(){const passwordHash=await bcrypt.hash('ChangeMe123!',12);await prisma.user.upsert({where:{email:'admin@nazar.local'},update:{},create:{email:'admin@nazar.local',name:'NAZAR Admin',passwordHash,role:'ADMIN'}});}
main().finally(()=>prisma.$disconnect());
