const crypto = require('crypto');

/**
 * Generate a unique receipt number
 * Format: RCP-YYYYMMDD-XXXXXX (X = random alphanumeric)
 */
const generateReceiptNo = () => {
  const date = new Date();
  const dateStr = date.getFullYear().toString() +
    String(date.getMonth() + 1).padStart(2, '0') +
    String(date.getDate()).padStart(2, '0');
  const random = crypto.randomBytes(3).toString('hex').toUpperCase();
  return `RCP-${dateStr}-${random}`;
};

/**
 * Calculate grade based on percentage
 * @param {number} obtained - marks obtained
 * @param {number} total - total marks
 * @returns {string} grade letter
 */
const calculateGrade = (obtained, total) => {
  if (!total || total === 0) return 'N/A';
  const percentage = (obtained / total) * 100;
  if (percentage >= 90) return 'A+';
  if (percentage >= 80) return 'A';
  if (percentage >= 70) return 'B';
  if (percentage >= 60) return 'C';
  if (percentage >= 50) return 'D';
  return 'F';
};

/**
 * Calculate percentage
 * @param {number} obtained - obtained marks
 * @param {number} total - total marks
 * @returns {string} percentage string with 2 decimals
 */
const calculatePercentage = (obtained, total) => {
  if (!total || total === 0) return '0.00';
  return ((obtained / total) * 100).toFixed(2);
};

/**
 * Format a date to readable string
 * @param {Date|string} date
 * @param {string} format - 'short' | 'long' | 'iso'
 * @returns {string}
 */
const formatDate = (date, format = 'short') => {
  if (!date) return '';
  const d = new Date(date);
  if (isNaN(d.getTime())) return '';

  switch (format) {
    case 'long':
      return d.toLocaleDateString('en-IN', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
      });
    case 'iso':
      return d.toISOString().split('T')[0];
    case 'short':
    default:
      return d.toLocaleDateString('en-IN', {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
      });
  }
};

/**
 * Generate a 6-digit OTP string
 * @returns {string}
 */
const generateOTP = () => {
  return Math.floor(100000 + Math.random() * 900000).toString();
};

/**
 * Paginate a mongoose query
 * @param {number} page
 * @param {number} limit
 * @returns {{ skip: number, limit: number }}
 */
const getPagination = (page = 1, limit = 10) => {
  const parsedPage = Math.max(1, parseInt(page));
  const parsedLimit = Math.min(100, Math.max(1, parseInt(limit)));
  const skip = (parsedPage - 1) * parsedLimit;
  return { skip, limit: parsedLimit, page: parsedPage };
};

/**
 * Build pagination meta for response
 */
const buildPaginationMeta = (total, page, limit) => {
  const totalPages = Math.ceil(total / limit);
  return {
    total,
    page,
    limit,
    totalPages,
    hasNextPage: page < totalPages,
    hasPrevPage: page > 1,
  };
};

/**
 * Sanitize filename for uploads
 */
const sanitizeFilename = (filename) => {
  return filename
    .replace(/[^a-zA-Z0-9._-]/g, '_')
    .replace(/_{2,}/g, '_')
    .toLowerCase();
};

/**
 * Build success response object
 */
const successResponse = (data = {}, message = 'Success', pagination = null) => {
  const response = { success: true, message, data };
  if (pagination) response.pagination = pagination;
  return response;
};

/**
 * Build error response object
 */
const errorResponse = (message = 'Error', errors = null) => {
  const response = { success: false, message };
  if (errors) response.errors = errors;
  return response;
};

/**
 * Convert CSV row values to proper types
 */
const parseCSVValue = (value, type = 'string') => {
  if (value === undefined || value === null || value === '') return null;
  switch (type) {
    case 'number':
      return isNaN(Number(value)) ? null : Number(value);
    case 'boolean':
      return ['true', '1', 'yes'].includes(value.toString().toLowerCase());
    case 'date':
      return new Date(value) || null;
    default:
      return value.toString().trim();
  }
};

/**
 * Generate initials from name
 */
const getInitials = (name) => {
  if (!name) return '?';
  return name
    .split(' ')
    .map((n) => n[0])
    .join('')
    .toUpperCase()
    .substring(0, 2);
};

module.exports = {
  generateReceiptNo,
  calculateGrade,
  calculatePercentage,
  formatDate,
  generateOTP,
  getPagination,
  buildPaginationMeta,
  sanitizeFilename,
  successResponse,
  errorResponse,
  parseCSVValue,
  getInitials,
};
