'use strict';
const path = require('path');
require('dotenv').config({ path: path.join(__dirname, '../../.env') });
const { getPool, closePool } = require('./config/mysql');
const { connectMongo, closeMongo } = require('./config/mongodb');
const idMapper = require('./utils/idMapper');
const logger = require('./utils/logger');
const FeeCategory = require('../../models/FeeCategory');

async function migrateFeeCategories() {
  logger.separator('MIGRATE: fee_categories → FeeCategory');
  const pool = getPool();
  await connectMongo();
  const timer = logger.startTimer('feeCategories');
  let extracted = 0, migrated = 0, skipped = 0, failed = 0;

  try {
    const [rows] = await pool.query('SELECT * FROM fee_categories ORDER BY id ASC');
    extracted = rows.length;

    for (let i = 0; i < rows.length; i++) {
      const row = rows[i];
      try {
        const existing = await FeeCategory.findOne({ name: row.name });
        if (existing) {
          idMapper.set('fee_categories', row.id, existing._id);
          skipped++;
          continue;
        }

        const doc = {
          name: row.name,
          description: row.description || '',
          is_permanent: Boolean(row.is_permanent),
          status: row.status || 'Active',
          created_by: row.created_by ? idMapper.get('users', row.created_by) : undefined,
          createdAt: row.created_at && row.created_at !== '0000-00-00 00:00:00' ? new Date(row.created_at) : new Date(),
          updatedAt: row.updated_at && row.updated_at !== '0000-00-00 00:00:00' ? new Date(row.updated_at) : new Date(),
        };

        const created = await FeeCategory.create(doc);
        idMapper.set('fee_categories', row.id, created._id);
        migrated++;
        logger.progress(i + 1, rows.length, `fee category: ${row.name}`);
      } catch (err) {
        logger.error(`Failed to insert fee category id=${row.id}: ${err.message}`);
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
  migrateFeeCategories().catch(err => { process.exit(1); });
}
module.exports = migrateFeeCategories;
