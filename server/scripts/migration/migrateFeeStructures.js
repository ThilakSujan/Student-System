'use strict';
const path = require('path');
require('dotenv').config({ path: path.join(__dirname, '../../.env') });
const { getPool, closePool } = require('./config/mysql');
const { connectMongo, closeMongo } = require('./config/mongodb');
const idMapper = require('./utils/idMapper');
const logger = require('./utils/logger');
const FeeStructure = require('../../models/FeeStructure');

async function migrateFeeStructures() {
  logger.separator('MIGRATE: fee_structures → FeeStructure');
  const pool = getPool();
  await connectMongo();
  const timer = logger.startTimer('feeStructures');
  let extracted = 0, migrated = 0, skipped = 0, failed = 0;

  try {
    const [rows] = await pool.query('SELECT * FROM fee_structures ORDER BY id ASC');
    extracted = rows.length;

    for (let i = 0; i < rows.length; i++) {
      const row = rows[i];
      try {
        const categoryMongoId = idMapper.get('fee_categories', row.category_id);
        if (!categoryMongoId) {
          logger.warn(`Skipping fee structure ${row.id}: category not found`);
          skipped++;
          continue;
        }

        const doc = {
          category: categoryMongoId,
          class: row.class_id ? idMapper.get('classes', row.class_id) : undefined,
          academic_year: row.academic_year,
          amount: parseFloat(row.amount),
          due_date: row.due_date ? new Date(row.due_date) : undefined,
          description: row.description || '',
          status: row.status || 'Active',
          created_by: row.created_by ? idMapper.get('users', row.created_by) : undefined,
          createdAt: row.created_at ? new Date(row.created_at) : new Date(),
          updatedAt: row.updated_at ? new Date(row.updated_at) : new Date(),
        };

        const created = await FeeStructure.create(doc);
        idMapper.set('fee_structures', row.id, created._id);
        migrated++;
        logger.progress(i + 1, rows.length, `fee structure: ${row.id}`);
      } catch (err) {
        logger.error(`Failed to insert fee structure id=${row.id}: ${err.message}`);
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
  migrateFeeStructures().catch(err => { process.exit(1); });
}
module.exports = migrateFeeStructures;
