'use strict';

/**
 * utils/idMapper.js
 * In-memory bidirectional mapping of MySQL integer IDs → MongoDB ObjectIds.
 *
 * Usage:
 *   const idMapper = require('./utils/idMapper');
 *
 *   // After inserting a doc:
 *   idMapper.set('users', mysqlRow.id, mongoDoc._id);
 *
 *   // When building a related doc:
 *   const mongoUserId = idMapper.get('users', row.created_by_mysql_id);
 */

const mongoose = require('mongoose');

// Master store: Map<collectionName, Map<mysqlId, ObjectId>>
const _store = new Map();

/**
 * Ensures a collection bucket exists in the store.
 * @param {string} collection
 */
function _ensure(collection) {
  if (!_store.has(collection)) {
    _store.set(collection, new Map());
  }
}

/**
 * Registers a MySQL ID → MongoDB ObjectId mapping.
 * @param {string}          collection - logical collection name, e.g. 'users'
 * @param {number|string}   mysqlId    - MySQL integer primary key
 * @param {mongoose.Types.ObjectId|string} mongoId - the inserted MongoDB _id
 */
function set(collection, mysqlId, mongoId) {
  _ensure(collection);
  const id = mongoId instanceof mongoose.Types.ObjectId
    ? mongoId
    : new mongoose.Types.ObjectId(String(mongoId));
  _store.get(collection).set(Number(mysqlId), id);
}

/**
 * Retrieves the MongoDB ObjectId for a given MySQL ID.
 * Returns null (not undefined) when the mapping is missing so callers
 * can use null-checks safely.
 *
 * @param {string}        collection
 * @param {number|string} mysqlId
 * @returns {mongoose.Types.ObjectId|null}
 */
function get(collection, mysqlId) {
  if (mysqlId === null || mysqlId === undefined) return null;
  const bucket = _store.get(collection);
  if (!bucket) return null;
  return bucket.get(Number(mysqlId)) ?? null;
}

/**
 * Returns the entire Map<mysqlId, ObjectId> for a collection.
 * Returns an empty Map when no entries have been recorded.
 *
 * @param {string} collection
 * @returns {Map<number, mongoose.Types.ObjectId>}
 */
function getAll(collection) {
  return _store.get(collection) ?? new Map();
}

/**
 * Returns the total number of mapped IDs across all collections.
 * Useful for logging.
 */
function totalMappings() {
  let total = 0;
  for (const bucket of _store.values()) {
    total += bucket.size;
  }
  return total;
}

/**
 * Clears all mappings (useful between test runs).
 */
function clear() {
  _store.clear();
}

module.exports = { set, get, getAll, totalMappings, clear };
