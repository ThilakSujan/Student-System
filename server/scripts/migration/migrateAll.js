'use strict';
const logger = require('./utils/logger');

// Import all migration scripts
const migrateUsers = require('./migrateUsers');
const migrateStudents = require('./migrateStudents');
const migrateSubjects = require('./migrateSubjects');
const migrateClasses = require('./migrateClasses');
const migrateAttendance = require('./migrateAttendance');
const migrateMarks = require('./migrateMarks');
const migrateFeeCategories = require('./migrateFeeCategories');
const migrateFeeStructures = require('./migrateFeeStructures');
const migrateFeePayments = require('./migrateFeePayments');
const migrateExamSchedule = require('./migrateExamSchedule');
const migrateNotifications = require('./migrateNotifications');
const migrateEmailLogs = require('./migrateEmailLogs');
const migrateInstituteProfile = require('./migrateInstituteProfile');
const validateMigrations = require('./validate');

async function runAllMigrations() {
  console.clear();
  logger.separator('🚀 MERN STUDENT SYSTEM - FULL MIGRATION PIPELINE', 'cyan');
  const startTime = Date.now();

  try {
    // Run in strict dependency order
    await migrateUsers();
    await migrateStudents();
    await migrateSubjects();
    await migrateClasses();
    await migrateAttendance();
    await migrateMarks();
    await migrateFeeCategories();
    await migrateFeeStructures();
    await migrateFeePayments();
    await migrateExamSchedule();
    await migrateNotifications();
    await migrateEmailLogs();
    await migrateInstituteProfile();

    // Run Validation
    await validateMigrations();

    const totalTime = ((Date.now() - startTime) / 1000).toFixed(2);
    logger.separator(`✨ MIGRATION COMPLETE (${totalTime}s) ✨`, 'green');
    
  } catch (err) {
    logger.separator('❌ MIGRATION PIPELINE FAILED', 'red');
    logger.error(err.message);
    process.exit(1);
  }
}

runAllMigrations();
