'use strict';
const path = require('path');
require('dotenv').config({ path: path.join(__dirname, '../../.env') });
const { getPool, closePool } = require('./config/mysql');
const { connectMongo, closeMongo } = require('./config/mongodb');
const idMapper = require('./utils/idMapper');
const logger = require('./utils/logger');
const Mark = require('../../models/Mark');

async function migrateMarks() {
  logger.separator('MIGRATE: marks → Mark');
  const pool = getPool();
  await connectMongo();
  const timer = logger.startTimer('marks');
  let extracted = 0, migrated = 0, skipped = 0, failed = 0;

  try {
    const [rows] = await pool.query('SELECT * FROM marks ORDER BY id ASC');
    extracted = rows.length;

    for (let i = 0; i < rows.length; i++) {
      const row = rows[i];
      try {
        const studentMongoId = idMapper.get('students', row.student_id);
        const subjectMongoId = idMapper.get('subjects', row.subject_id);
        if (!studentMongoId || !subjectMongoId) {
          logger.warn(`Skipping mark ${row.id}: student or subject not found`);
          skipped++;
          continue;
        }

        const doc = {
          student: studentMongoId,
          subject: subjectMongoId,
          marks_obtained: parseFloat(row.marks_obtained),
          total_marks: parseFloat(row.total_marks) || 100,
          status: row.status || 'Active',
          published: Boolean(row.published),
          published_at: row.published_at ? new Date(row.published_at) : undefined,
          published_by: row.published_by ? idMapper.get('users', row.published_by) : undefined,
          createdAt: row.created_at ? new Date(row.created_at) : new Date(),
          updatedAt: row.updated_at ? new Date(row.updated_at) : new Date(),
        };

        const created = await Mark.create(doc);
        idMapper.set('marks', row.id, created._id);
        migrated++;
        logger.progress(i + 1, rows.length, `mark: ${row.id}`);
      } catch (err) {
        if (err.code === 11000) {
          skipped++; // Duplicate
        } else {
          logger.error(`Failed to insert mark id=${row.id}: ${err.message}`);
          failed++;
        }
      }
    }
  } finally {
    logger.endTimer(timer, { extracted, migrated, skipped, failed });
    await closePool();
    await closeMongo();
  }
}

if (require.main === module) {
  migrateMarks().catch(err => { process.exit(1); });
}
module.exports = migrateMarks;
