'use strict';
const path = require('path');
require('dotenv').config({ path: path.join(__dirname, '../../.env') });
const { getPool, closePool } = require('./config/mysql');
const { connectMongo, closeMongo } = require('./config/mongodb');
const idMapper = require('./utils/idMapper');
const logger = require('./utils/logger');
const FeePayment = require('../../models/FeePayment');

async function migrateFeePayments() {
  logger.separator('MIGRATE: fee_payments → FeePayment');
  const pool = getPool();
  await connectMongo();
  const timer = logger.startTimer('feePayments');
  let extracted = 0, migrated = 0, skipped = 0, failed = 0;

  try {
    const [rows] = await pool.query('SELECT * FROM fee_payments ORDER BY id ASC');
    extracted = rows.length;

    for (let i = 0; i < rows.length; i++) {
      const row = rows[i];
      try {
        const studentMongoId = idMapper.get('students', row.student_id);
        // Note: MySQL column is fee_assignment_id, but it actually points to fee_structures
        const feeStructureMongoId = idMapper.get('fee_structures', row.fee_assignment_id);
        
        if (!studentMongoId || !feeStructureMongoId) {
          logger.warn(`Skipping fee payment ${row.id}: student or fee structure not found`);
          skipped++;
          continue;
        }

        const doc = {
          student: studentMongoId,
          feeStructure: feeStructureMongoId,
          amount_paid: parseFloat(row.amount_paid),
          payment_date: new Date(row.payment_date),
          payment_mode: row.payment_mode || 'Cash',
          receipt_no: row.receipt_no || '',
          remarks: row.remarks || '',
          recorded_by: row.recorded_by ? idMapper.get('users', row.recorded_by) : undefined,
          createdAt: row.created_at ? new Date(row.created_at) : new Date(),
          updatedAt: row.updated_at ? new Date(row.updated_at) : new Date(),
        };

        const created = await FeePayment.create(doc);
        idMapper.set('fee_payments', row.id, created._id);
        migrated++;
        logger.progress(i + 1, rows.length, `fee payment: ${row.id}`);
      } catch (err) {
        logger.error(`Failed to insert fee payment id=${row.id}: ${err.message}`);
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
  migrateFeePayments().catch(err => { process.exit(1); });
}
module.exports = migrateFeePayments;
