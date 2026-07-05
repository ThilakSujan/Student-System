'use strict';
const path = require('path');
require('dotenv').config({ path: path.join(__dirname, '../../.env') });
const { getPool, closePool } = require('./config/mysql');
const { connectMongo, closeMongo } = require('./config/mongodb');
const idMapper = require('./utils/idMapper');
const logger = require('./utils/logger');
const Student = require('../../models/Student');

async function migrateStudents() {
  logger.separator('MIGRATE: students → Student');
  const pool = getPool();
  await connectMongo();
  const timer = logger.startTimer('students');
  let extracted = 0, migrated = 0, skipped = 0, failed = 0;

  try {
    logger.info('Extracting students from MySQL...');
    const [rows] = await pool.query('SELECT * FROM students ORDER BY id ASC');
    extracted = rows.length;

    for (let i = 0; i < rows.length; i++) {
      const row = rows[i];
      try {
        const existing = await Student.findOne({ email: row.email.toLowerCase() });
        if (existing) {
          logger.warn(`Student email "${row.email}" exists → skipping.`);
          idMapper.set('students', row.id, existing._id);
          skipped++;
          continue;
        }

        const doc = {
          student_name: row.student_name,
          email: row.email.toLowerCase().trim(),
          phone: row.phone || undefined,
          gender: row.gender || undefined,
          dob: row.dob ? new Date(row.dob) : undefined,
          department: row.department || undefined,
          skills: row.skills ? row.skills.split(',').map(s => s.trim()) : [],
          parent_name: row.parent_name || undefined,
          parent_email: row.parent_email ? row.parent_email.toLowerCase().trim() : undefined,
          status: row.status || 'Active',
          password: null, // To be set on first login
          isFirstLogin: true,
          createdAt: row.created_at ? new Date(row.created_at) : new Date(),
          updatedAt: new Date(),
        };

        const created = await Student.create(doc);
        idMapper.set('students', row.id, created._id);
        migrated++;
        logger.progress(i + 1, rows.length, `student: ${row.student_name}`);
      } catch (err) {
        logger.error(`Failed to insert student id=${row.id}: ${err.message}`);
        failed++;
      }
    }

    const [countRes] = await pool.query('SELECT COUNT(*) AS cnt FROM students');
    const mysqlCount = countRes[0].cnt;
    const mongoCount = await Student.countDocuments();
    if (mongoCount >= mysqlCount - skipped) {
      logger.success(`VALIDATE PASS — MySQL: ${mysqlCount}, MongoDB: ${mongoCount}`);
    } else {
      logger.warn(`VALIDATE MISMATCH — MySQL: ${mysqlCount}, MongoDB: ${mongoCount}`);
    }
  } finally {
    logger.endTimer(timer, { extracted, migrated, skipped, failed });
    await closePool();
    await closeMongo();
  }
}

if (require.main === module) {
  migrateStudents().catch(err => {
    logger.error(`migrateStudents fatal: ${err.message}`);
    process.exit(1);
  });
}
module.exports = migrateStudents;
