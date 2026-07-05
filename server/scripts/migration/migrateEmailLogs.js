'use strict';
const path = require('path');
require('dotenv').config({ path: path.join(__dirname, '../../.env') });
const { getPool, closePool } = require('./config/mysql');
const { connectMongo, closeMongo } = require('./config/mongodb');
const idMapper = require('./utils/idMapper');
const logger = require('./utils/logger');
const EmailLog = require('../../models/EmailLog');

async function migrateEmailLogs() {
  logger.separator('MIGRATE: email_logs → EmailLog');
  const pool = getPool();
  await connectMongo();
  const timer = logger.startTimer('emailLogs');
  let extracted = 0, migrated = 0, skipped = 0, failed = 0;

  try {
    const [rows] = await pool.query('SELECT * FROM email_logs ORDER BY id ASC');
    extracted = rows.length;

    for (let i = 0; i < rows.length; i++) {
      const row = rows[i];
      try {
        const doc = {
          recipient_email: row.recipient_email,
          subject: row.subject,
          email_type: row.email_type || 'custom',
          status: row.status || 'failed',
          sent_at: row.sent_at ? new Date(row.sent_at) : new Date(),
          error_message: row.error_message || '',
          related_id: row.related_id ? row.related_id.toString() : null,
          related_type: row.related_type || null,
          created_by: row.created_by ? idMapper.get('users', row.created_by) : undefined,
        };

        const created = await EmailLog.create(doc);
        idMapper.set('email_logs', row.id, created._id);
        migrated++;
        logger.progress(i + 1, rows.length, `email log: ${row.id}`);
      } catch (err) {
        logger.error(`Failed to insert email log id=${row.id}: ${err.message}`);
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
  migrateEmailLogs().catch(err => { process.exit(1); });
}
module.exports = migrateEmailLogs;
