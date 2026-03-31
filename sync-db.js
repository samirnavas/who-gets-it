const mysql = require('mysql2/promise');
const fs = require('fs');
const env = fs.readFileSync('.env', 'utf-8');
const dbUrlMatch = env.match(/DATABASE_URL="([^"]+)"/);
const dbUrl = dbUrlMatch ? dbUrlMatch[1] : null;

async function sync() {
  const connection = await mysql.createConnection(dbUrl);
  console.log('Connected to MySQL DB.');

  const queries = `
  CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (role)
  );

  CREATE TABLE IF NOT EXISTS items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    image_url VARCHAR(500) DEFAULT 'https://via.placeholder.com/300x200?text=No+Image',
    starting_bid DECIMAL(10,2) NOT NULL,
    current_bid DECIMAL(10,2) NOT NULL,
    highest_bidder_id INT,
    end_time DATETIME NOT NULL,
    status ENUM('active', 'ended', 'cancelled') DEFAULT 'active',
    ended_at TIMESTAMP NULL,
    ended_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (highest_bidder_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (ended_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX (end_time),
    INDEX (user_id),
    INDEX (status)
  );

  CREATE TABLE IF NOT EXISTS bids (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    user_id INT NOT NULL,
    bid_amount DECIMAL(10,2) NOT NULL,
    status ENUM('active', 'stopped') DEFAULT 'active',
    stopped_at TIMESTAMP NULL,
    stopped_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (stopped_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX (item_id),
    INDEX (user_id),
    INDEX (status)
  );

  CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('bid_stopped', 'auction_won', 'auction_lost', 'outbid', 'admin_action', 'auction_ended') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    related_id INT,
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (user_id, is_read)
  );
  `.split(';').filter(q => q.trim().length > 0);

  for (let q of queries) {
    try {
      await connection.query(q);
      console.log('Executed:', q.substring(0, 50).trim().split('\n')[0] + '...');
    } catch (e) {
      console.error('Error executing query:', e.message);
    }
  }
  
  await connection.end();
  console.log('Sync complete');
}

sync().catch(console.error);
