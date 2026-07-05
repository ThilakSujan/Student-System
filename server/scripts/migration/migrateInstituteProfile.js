'use strict';
const path = require('path');
require('dotenv').config({ path: path.join(__dirname, '../../.env') });
const { getPool, closePool } = require('./config/mysql');
const { connectMongo, closeMongo } = require('./config/mongodb');
const logger = require('./utils/logger');
const InstituteProfile = require('../../models/InstituteProfile');

async function migrateInstituteProfile() {
  logger.separator('MIGRATE: institute_profile → InstituteProfile');
  const pool = getPool();
  await connectMongo();
  const timer = logger.startTimer('instituteProfile');
  let extracted = 0, migrated = 0, skipped = 0, failed = 0;

  try {
    const [rows] = await pool.query('SELECT * FROM institute_profile ORDER BY id ASC LIMIT 1');
    extracted = rows.length;

    if (rows.length > 0) {
      const row = rows[0];
      try {
        const existing = await InstituteProfile.findOne();
        if (existing) {
          logger.warn(`Institute profile already exists → skipping.`);
          skipped++;
        } else {
          const doc = {
            institute_name: row.institute_name || '',
            address: row.address || '',
            phone: row.phone || '',
            email: row.email || '',
            principal_name: row.principal_name || '',
            logo: row.logo || '',
            other_details: row.other_details || '',
            createdAt: row.created_at ? new Date(row.created_at) : new Date(),
            updatedAt: row.updated_at ? new Date(row.updated_at) : new Date(),
          };

          await InstituteProfile.create(doc);
          migrated++;
          logger.progress(1, 1, `institute profile: ${row.institute_name}`);
        }
      } catch (err) {
        logger.error(`Failed to insert institute profile: ${err.message}`);
        failed++;
      }
    } else {
      logger.warn('No institute profile found in MySQL.');
    }
  } finally {
    logger.endTimer(timer, { extracted, migrated, skipped, failed });
    await closePool();
    await closeMongo();
  }
}

if (require.main === module) {
  migrateInstituteProfile().catch(err => { process.exit(1); });
}
module.exports = migrateInstituteProfile;
