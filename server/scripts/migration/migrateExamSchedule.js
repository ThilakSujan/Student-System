'use strict';
const path = require('path');
require('dotenv').config({ path: path.join(__dirname, '../../.env') });
const { getPool, closePool } = require('./config/mysql');
const { connectMongo, closeMongo } = require('./config/mongodb');
const idMapper = require('./utils/idMapper');
const logger = require('./utils/logger');
const ExamSchedule = require('../../models/ExamSchedule');

async function migrateExamSchedule() {
  logger.separator('MIGRATE: exam_schedule → ExamSchedule');
  const pool = getPool();
  await connectMongo();
  const timer = logger.startTimer('examSchedule');
  let extracted = 0, migrated = 0, skipped = 0, failed = 0;

  try {
    const [rows] = await pool.query('SELECT * FROM exam_schedule ORDER BY id ASC');
    extracted = rows.length;

    for (let i = 0; i < rows.length; i++) {
      const row = rows[i];
      try {
        const existing = await ExamSchedule.findOne({ exam_title: row.exam_title, exam_date: new Date(row.exam_date) });
        if (existing) {
          idMapper.set('exam_schedule', row.id, existing._id);
          skipped++;
          continue;
        }

        const doc = {
          exam_title: row.exam_title,
          subject: row.subject_id ? idMapper.get('subjects', row.subject_id) : undefined,
          class: row.class_id ? idMapper.get('classes', row.class_id) : undefined,
          exam_date: new Date(row.exam_date),
          start_time: row.start_time || '',
          end_time: row.end_time || '',
          venue: row.venue || '',
          exam_type: row.exam_type || 'Internal',
          description: row.description || '',
          created_by: row.created_by ? idMapper.get('users', row.created_by) : undefined,
          status: row.status || 'Scheduled',
          createdAt: row.created_at ? new Date(row.created_at) : new Date(),
          updatedAt: row.updated_at ? new Date(row.updated_at) : new Date(),
        };

        const created = await ExamSchedule.create(doc);
        idMapper.set('exam_schedule', row.id, created._id);
        migrated++;
        logger.progress(i + 1, rows.length, `exam schedule: ${row.exam_title}`);
      } catch (err) {
        logger.error(`Failed to insert exam schedule id=${row.id}: ${err.message}`);
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
  migrateExamSchedule().catch(err => { process.exit(1); });
}
module.exports = migrateExamSchedule;
