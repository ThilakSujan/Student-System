'use strict';

/**
 * migrateUsers.js
 * Migrates MySQL `users` + `user_profiles` → MongoDB `users` collection.
 *
 * Steps:
 *  1. Connect MySQL pool
 *  2. Connect MongoDB
 *  3. EXTRACT: SELECT users LEFT JOIN user_profiles
 *  4. TRANSFORM: map fields, keep PHP $2y$ bcrypt hash as-is
 *  5. LOAD: insertOne with duplicate-skip
 *  6. Update idMapper
 *  7. VALIDATE: count check
 *  8. Disconnect
 */

'use strict';
const path = require('path');
require('dotenv').config({ path: path.join(__dirname, '../../.env') });

const { getPool, closePool }    = require('./config/mysql');
const { connectMongo, closeMongo } = require('./config/mongodb');
const idMapper                  = require('./utils/idMapper');
const logger                    = require('./utils/logger');
const User                      = require('../../models/User');

async function migrateUsers() {
  logger.separator('MIGRATE: users + user_profiles → User');

  // ── Connect ────────────────────────────────────────────────────────────────
  const pool = getPool();
  await connectMongo();

  const timer = logger.startTimer('users');
  let extracted = 0, migrated = 0, skipped = 0, failed = 0;

  try {
    // ── EXTRACT ───────────────────────────────────────────────────────────────
    logger.info('Extracting users with profiles from MySQL...');
    const [rows] = await pool.query(`
      SELECT
        u.id,
        u.username,
        u.email,
        u.password,
        u.role,
        u.account_status,
        u.reset_otp,
        u.otp_expiry,
        u.otp_verified,
        u.otp_attempts,
        u.otp_last_sent,
        u.otp_send_count,
        u.approved_by,
        u.approved_at,
        u.rejected_by,
        u.rejected_at,
        u.rejection_reason,
        u.created_at,
        up.full_name,
        up.phone       AS profile_phone,
        up.profile_text
      FROM users u
      LEFT JOIN user_profiles up ON up.user_id = u.id
      ORDER BY u.id ASC
    `);
    extracted = rows.length;
    logger.info(`Extracted ${extracted} user(s) from MySQL.`);

    // ── TRANSFORM + LOAD ───────────────────────────────────────────────────────
    for (let i = 0; i < rows.length; i++) {
      const row = rows[i];

      try {
        // Duplicate check
        const existing = await User.findOne({ email: row.email.toLowerCase() });
        if (existing) {
          logger.warn(`User email "${row.email}" already exists → skipping (MySQL id=${row.id}).`);
          idMapper.set('users', row.id, existing._id);
          skipped++;
          continue;
        }

        // TRANSFORM
        const doc = {
          username:         row.username,
          email:            row.email.toLowerCase().trim(),
          // Keep PHP-generated $2y$ hash — bcryptjs accepts $2y$ as $2b$
          password:         row.password.replace(/^\$2y\$/, '$2b$'),
          role:             row.role,
          account_status:   row.account_status,
          reset_otp:        row.reset_otp    || undefined,
          otp_expiry:       row.otp_expiry   ? new Date(row.otp_expiry)   : undefined,
          otp_verified:     Boolean(row.otp_verified),
          otp_attempts:     row.otp_attempts  || 0,
          otp_last_sent:    row.otp_last_sent ? new Date(row.otp_last_sent) : undefined,
          otp_send_count:   row.otp_send_count || 0,
          // approved_by / rejected_by must be resolved AFTER all users loaded
          // Store as null now; a second pass will resolve self-references if needed
          approved_by:      null,
          approved_at:      row.approved_at  ? new Date(row.approved_at)  : null,
          rejected_by:      null,
          rejected_at:      row.rejected_at  ? new Date(row.rejected_at)  : null,
          rejection_reason: row.rejection_reason || null,
          full_name:        row.full_name    || null,
          phone:            row.profile_phone || null,
          profile_text:     row.profile_text || null,
          createdAt:        row.created_at   ? new Date(row.created_at)   : new Date(),
          updatedAt:        new Date(),
        };

        // LOAD using insertOne to bypass Mongoose save middleware (prevents double hashing)
        const result = await User.collection.insertOne(doc);
        idMapper.set('users', row.id, result.insertedId);
        migrated++;

        logger.progress(i + 1, rows.length, `user: ${row.username}`);
      } catch (err) {
        logger.error(`Failed to insert user id=${row.id} (${row.email}): ${err.message}`);
        failed++;
      }
    }

    // ── SECOND PASS: resolve approved_by / rejected_by self-references ────────
    logger.info('Resolving self-referencing approved_by / rejected_by...');
    const [refRows] = await pool.query(
      'SELECT id, approved_by, rejected_by FROM users WHERE approved_by IS NOT NULL OR rejected_by IS NOT NULL'
    );
    for (const r of refRows) {
      const mongoId = idMapper.get('users', r.id);
      if (!mongoId) continue;
      const update = {};
      if (r.approved_by) {
        const approvedMongoId = idMapper.get('users', r.approved_by);
        if (approvedMongoId) update.approved_by = approvedMongoId;
      }
      if (r.rejected_by) {
        const rejectedMongoId = idMapper.get('users', r.rejected_by);
        if (rejectedMongoId) update.rejected_by = rejectedMongoId;
      }
      if (Object.keys(update).length) {
        await User.findByIdAndUpdate(mongoId, { $set: update });
      }
    }

    // ── VALIDATE ──────────────────────────────────────────────────────────────
    const [countRes] = await pool.query('SELECT COUNT(*) AS cnt FROM users');
    const mysqlCount = countRes[0].cnt;
    const mongoCount = await User.countDocuments();
    if (mongoCount >= mysqlCount - skipped) {
      logger.success(`VALIDATE PASS — MySQL: ${mysqlCount}, MongoDB: ${mongoCount}, Skipped: ${skipped}`);
    } else {
      logger.warn(`VALIDATE MISMATCH — MySQL: ${mysqlCount}, MongoDB: ${mongoCount}, Skipped: ${skipped}`);
    }

  } finally {
    logger.endTimer(timer, { extracted, migrated, skipped, failed });
    await closePool();
    await closeMongo();
  }
}

// ── Run standalone ─────────────────────────────────────────────────────────────
if (require.main === module) {
  migrateUsers().catch(err => {
    logger.error(`migrateUsers fatal: ${err.message}`);
    process.exit(1);
  });
}

module.exports = migrateUsers;
