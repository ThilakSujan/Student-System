'use strict';
const path = require('path');
require('dotenv').config({ path: path.join(__dirname, '../../.env') });
const { getPool, closePool } = require('./config/mysql');
const { connectMongo, closeMongo } = require('./config/mongodb');
const logger = require('./utils/logger');

// Models
const User = require('../../models/User');
const Student = require('../../models/Student');
const Subject = require('../../models/Subject');
const Class = require('../../models/Class');
const Attendance = require('../../models/Attendance');
const Mark = require('../../models/Mark');
const FeeCategory = require('../../models/FeeCategory');
const FeeStructure = require('../../models/FeeStructure');
const FeePayment = require('../../models/FeePayment');
const ExamSchedule = require('../../models/ExamSchedule');
const Notification = require('../../models/Notification');
const EmailLog = require('../../models/EmailLog');
const InstituteProfile = require('../../models/InstituteProfile');

async function validateMigrations() {
  logger.separator('VALIDATION PHASE: MySQL vs MongoDB counts');
  const pool = getPool();
  await connectMongo();

  const collections = [
    { mysqlTable: 'users', Model: User, name: 'Users' },
    { mysqlTable: 'students', Model: Student, name: 'Students' },
    { mysqlTable: 'subjects', Model: Subject, name: 'Subjects' },
    { mysqlTable: 'classes', Model: Class, name: 'Classes' },
    { mysqlTable: 'attendance', Model: Attendance, name: 'Attendance' },
    { mysqlTable: 'marks', Model: Mark, name: 'Marks' },
    { mysqlTable: 'fee_categories', Model: FeeCategory, name: 'Fee Categories' },
    { mysqlTable: 'fee_structures', Model: FeeStructure, name: 'Fee Structures' },
    { mysqlTable: 'fee_payments', Model: FeePayment, name: 'Fee Payments' },
    { mysqlTable: 'exam_schedule', Model: ExamSchedule, name: 'Exam Schedule' },
    { mysqlTable: 'notifications', Model: Notification, name: 'Notifications' },
    { mysqlTable: 'email_logs', Model: EmailLog, name: 'Email Logs' },
    { mysqlTable: 'institute_profile', Model: InstituteProfile, name: 'Institute Profile' },
  ];

  let totalErrors = 0;

  try {
    for (const coll of collections) {
      try {
        const [rows] = await pool.query(`SELECT COUNT(*) AS cnt FROM ${coll.mysqlTable}`);
        const mysqlCount = rows[0].cnt;
        const mongoCount = await coll.Model.countDocuments();
        
        if (mongoCount >= mysqlCount) { // Mongo can have more if duplicate runs occurred
          logger.success(`[${coll.name}] PASS: MySQL=${mysqlCount}, Mongo=${mongoCount}`);
        } else {
          logger.error(`[${coll.name}] FAIL: MySQL=${mysqlCount}, Mongo=${mongoCount}`);
          totalErrors++;
        }
      } catch (err) {
        logger.error(`Failed to validate ${coll.name}: ${err.message}`);
        totalErrors++;
      }
    }

    logger.separator('VALIDATION SUMMARY');
    if (totalErrors === 0) {
      logger.success('✅ All collections passed validation checks.');
    } else {
      logger.error(`❌ ${totalErrors} collection(s) failed validation checks.`);
    }

  } finally {
    await closePool();
    await closeMongo();
  }
}

if (require.main === module) {
  validateMigrations().catch(err => {
    logger.error(`validate fatal: ${err.message}`);
    process.exit(1);
  });
}
module.exports = validateMigrations;
