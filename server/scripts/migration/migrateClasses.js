'use strict';
const path = require('path');
require('dotenv').config({ path: path.join(__dirname, '../../.env') });
const { getPool, closePool } = require('./config/mysql');
const { connectMongo, closeMongo } = require('./config/mongodb');
const idMapper = require('./utils/idMapper');
const logger = require('./utils/logger');
const Class = require('../../models/Class');

async function migrateClasses() {
  logger.separator('MIGRATE: classes + class_students → Class');
  const pool = getPool();
  await connectMongo();
  const timer = logger.startTimer('classes');
  let extracted = 0, migrated = 0, skipped = 0, failed = 0;

  try {
    const [rows] = await pool.query('SELECT * FROM classes ORDER BY id ASC');
    extracted = rows.length;

    for (let i = 0; i < rows.length; i++) {
      const row = rows[i];
      try {
        const existing = await Class.findOne({ class_name: row.class_name, section: row.section, academic_year: row.academic_year });
        if (existing) {
          idMapper.set('classes', row.id, existing._id);
          skipped++;
          continue;
        }

        // Get class_students
        const [studentRows] = await pool.query('SELECT student_id FROM class_students WHERE class_id = ?', [row.id]);
        const studentIds = [];
        for (const sr of studentRows) {
          const mId = idMapper.get('students', sr.student_id);
          if (mId) studentIds.push(mId);
        }

        const doc = {
          class_name: row.class_name,
          section: row.section || '',
          academic_year: row.academic_year || '',
          class_teacher: row.class_teacher_id ? idMapper.get('users', row.class_teacher_id) : undefined,
          description: row.description || '',
          status: row.status || 'Active',
          students: studentIds,
          createdAt: row.created_at ? new Date(row.created_at) : new Date(),
          updatedAt: row.updated_at ? new Date(row.updated_at) : new Date(),
        };

        const created = await Class.create(doc);
        idMapper.set('classes', row.id, created._id);
        migrated++;
        logger.progress(i + 1, rows.length, `class: ${row.class_name} ${row.section}`);
      } catch (err) {
        logger.error(`Failed to insert class id=${row.id}: ${err.message}`);
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
  migrateClasses().catch(err => { process.exit(1); });
}
module.exports = migrateClasses;
