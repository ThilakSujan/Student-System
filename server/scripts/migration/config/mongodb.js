'use strict';

/**
 * config/mongodb.js
 * Mongoose connection manager for standalone migration scripts.
 * Reads MONGO_URI from .env.
 */

require('dotenv').config({ path: require('path').join(__dirname, '../../../.env') });
const mongoose = require('mongoose');

/**
 * Opens the Mongoose connection.
 */
async function connectMongo() {
  const uri = process.env.MONGO_URI || 'mongodb://localhost:27017/student_system';

  await mongoose.connect(uri, {
    serverSelectionTimeoutMS: 10000,
  });

  console.log(`✅ MongoDB connected: ${mongoose.connection.host}/${mongoose.connection.name}`);
}

/**
 * Closes the Mongoose connection gracefully.
 */
async function closeMongo() {
  await mongoose.connection.close();
  console.log('🔌 MongoDB connection closed.');
}

module.exports = { connectMongo, closeMongo };
