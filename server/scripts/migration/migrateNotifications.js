'use strict';
const path = require('path');
require('dotenv').config({ path: path.join(__dirname, '../../.env') });
const { getPool, closePool } = require('./config/mysql');
const { connectMongo, closeMongo } = require('./config/mongodb');
const idMapper = require('./utils/idMapper');
const logger = require('./utils/logger');
const Notification = require('../../models/Notification');

async function migrateNotifications() {
  logger.separator('MIGRATE: notifications → Notification');
  const pool = getPool();
  await connectMongo();
  const timer = logger.startTimer('notifications');
  let extracted = 0, migrated = 0, skipped = 0, failed = 0;

  try {
    const [rows] = await pool.query('SELECT * FROM notifications ORDER BY id ASC');
    extracted = rows.length;

    for (let i = 0; i < rows.length; i++) {
      const row = rows[i];
      try {
        const doc = {
          title: row.title,
          message: row.message,
          target_audience: row.target_audience || 'Both',
          expiry_date: new Date(row.expiry_date),
          status: row.status || 'Active',
          created_by: row.created_by ? idMapper.get('users', row.created_by) : undefined,
          createdAt: row.created_at ? new Date(row.created_at) : new Date(),
          updatedAt: new Date(),
        };

        const created = await Notification.create(doc);
        idMapper.set('notifications', row.id, created._id);
        migrated++;
        logger.progress(i + 1, rows.length, `notification: ${row.title}`);
      } catch (err) {
        logger.error(`Failed to insert notification id=${row.id}: ${err.message}`);
        failed++;
      }
    }
  } finally {
    logger.endTimer(timer, { extracted, migrated, skipped, failed });
    await closePool();
    await closeMongo();
  }
}

if (require.main === module) {
  migrateNotifications().catch(err => { process.exit(1); });
}
module.exports = migrateNotifications;
