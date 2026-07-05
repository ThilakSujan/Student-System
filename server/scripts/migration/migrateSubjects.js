'use strict';
const path = require('path');
require('dotenv').config({ path: path.join(__dirname, '../../.env') });
const { getPool, closePool } = require('./config/mysql');
const { connectMongo, closeMongo } = require('./config/mongodb');
const idMapper = require('./utils/idMapper');
const logger = require('./utils/logger');
const Subject = require('../../models/Subject');

async function migrateSubjects() {
  logger.separator('MIGRATE: subjects → Subject');
  const pool = getPool();
  await connectMongo();
  const timer = logger.startTimer('subjects');
  let extracted = 0, migrated = 0, skipped = 0, failed = 0;

  try {
    const [rows] = await pool.query('SELECT * FROM subjects ORDER BY id ASC');
    extracted = rows.length;

    for (let i = 0; i < rows.length; i++) {
      const row = rows[i];
      try {
        const existing = await Subject.findOne({ subject_code: row.subject_code });
        if (existing) {
          idMapper.set('subjects', row.id, existing._id);
          skipped++;
          continue;
        }

        const doc = {
          subject_code: row.subject_code,
          subject_name: row.subject_name,
          credit_hours: row.credit_hours || 3,
          status: row.status || 'Active',
          createdAt: row.created_at ? new Date(row.created_at) : new Date(),
          updatedAt: new Date(),
        };

        const created = await Subject.create(doc);
        idMapper.set('subjects', row.id, created._id);
        migrated++;
        logger.progress(i + 1, rows.length, `subject: ${row.subject_code}`);
      } catch (err) {
        logger.error(`Failed to insert subject id=${row.id}: ${err.message}`);
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
  migrateSubjects().catch(err => { process.exit(1); });
}
module.exports = migrateSubjects;
