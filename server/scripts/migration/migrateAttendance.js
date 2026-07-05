'use strict';
const path = require('path');
require('dotenv').config({ path: path.join(__dirname, '../../.env') });
const { getPool, closePool } = require('./config/mysql');
const { connectMongo, closeMongo } = require('./config/mongodb');
const idMapper = require('./utils/idMapper');
const logger = require('./utils/logger');
const Attendance = require('../../models/Attendance');

async function migrateAttendance() {
  logger.separator('MIGRATE: attendance → Attendance');
  const pool = getPool();
  await connectMongo();
  const timer = logger.startTimer('attendance');
  let extracted = 0, migrated = 0, skipped = 0, failed = 0;

  try {
    const [rows] = await pool.query('SELECT * FROM attendance ORDER BY id ASC');
    extracted = rows.length;

    for (let i = 0; i < rows.length; i++) {
      const row = rows[i];
      try {
        const studentMongoId = idMapper.get('students', row.student_id);
        if (!studentMongoId) {
          logger.warn(`Skipping attendance ${row.id}: student not found`);
          skipped++;
          continue;
        }

        const doc = {
          student: studentMongoId,
          date: new Date(row.date),
          status: row.status || 'Present',
          marked_by: row.marked_by ? idMapper.get('users', row.marked_by) : undefined,
          createdAt: row.created_at ? new Date(row.created_at) : new Date(),
          updatedAt: row.updated_at ? new Date(row.updated_at) : new Date(),
        };

        const created = await Attendance.create(doc);
        idMapper.set('attendance', row.id, created._id);
        migrated++;
        logger.progress(i + 1, rows.length, `attendance: ${row.id}`);
      } catch (err) {
        if (err.code === 11000) {
          skipped++; // Duplicate
        } else {
          logger.error(`Failed to insert attendance id=${row.id}: ${err.message}`);
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
  migrateAttendance().catch(err => { process.exit(1); });
}
module.exports = migrateAttendance;
