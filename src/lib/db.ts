import mysql from "mysql2/promise";

const poolConfig = {
  uri: process.env.DATABASE_URL,
  waitForConnections: true,
  connectionLimit: 10,
  maxIdle: 10,
  idleTimeout: 60000,
  queueLimit: 0,
  enableKeepAlive: true,
  keepAliveInitialDelay: 0,
};

const globalForDb = globalThis as unknown as {
  mysqlPool: mysql.Pool | undefined;
};

const pool = globalForDb.mysqlPool ?? mysql.createPool(poolConfig);

if (process.env.NODE_ENV !== "production") globalForDb.mysqlPool = pool;

export default pool;
