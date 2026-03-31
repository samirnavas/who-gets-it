const { PrismaClient } = require('@prisma/client');
const prisma = new PrismaClient();

async function main() {
  const user = await prisma.user.upsert({
    where: { username: 'admin' },
    update: {},
    create: {
      username: 'admin',
      passwordHash: 'notarealhash',
      role: 'admin',
    },
  });

  const auction = await prisma.item.create({
    data: {
      id: 1,
      userId: user.id,
      title: 'Legendary Golden Gavel',
      description: 'The golden gavel used by the original creator of Who Gets It.',
      startingBid: 100.0,
      currentBid: 100.0,
      endTime: new Date(Date.now() + 1000 * 60 * 60 * 24 * 7), // 7 days from now
      imageUrl: 'https://images.unsplash.com/photo-1589829085413-56de8ae18c73?auto=format&fit=crop&q=80&w=600',
    },
  });

  console.log('Database seeded with test item!');
}

main()
  .catch((e) => {
    console.error(e);
    process.exit(1);
  })
  .finally(async () => {
    await prisma.$disconnect();
  });
