'use strict';

/**
 * config/mysql.js
 * MySQL2 promise-based connection pool for migration scripts.
 * Reads credentials from .env via MYSQL_* variables.
 */

require('dotenv').config({ path: require('path').join(__dirname, '../../../.env') });
const mysql = require('mysql2/promise');

let pool = null;

/**
 * Returns the shared connection pool, creating it on first call.
 * @returns {mysql.Pool}
 */
function getPool() {
  if (!pool) {
    pool = mysql.createPool({
      host:               process.env.MYSQL_HOST     || 'localhost',
      port:               parseInt(process.env.MYSQL_PORT || '3306', 10),
      user:               process.env.MYSQL_USER     || 'root',
      password:           process.env.MYSQL_PASSWORD || '',
      database:           process.env.MYSQL_DATABASE || 'student1_db',
      waitForConnections: true,
      connectionLimit:    10,
      queueLimit:         0,
      timezone:           '+00:00',        // store as UTC
      decimalNumbers:     true,            // return DECIMAL as JS number
      dateStrings:        false,           // return DATE/DATETIME as JS Date
    });
  }
  return pool;
}

/**
 * Gracefully closes the connection pool.
 */
async function closePool() {
  if (pool) {
    await pool.end();
    pool = null;
  }
}

module.exports = { getPool, closePool };
