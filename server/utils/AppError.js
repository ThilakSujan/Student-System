/**
 * Custom application error class
 * Extends native Error with HTTP status code and operational flag
 */
class AppError extends Error {
  constructor(message, statusCode, errors = null) {
    super(message);
    this.statusCode = statusCode;
    this.status = `${statusCode}`.startsWith('4') ? 'fail' : 'error';
    this.isOperational = true;
    this.errors = errors; // validation errors array

    Error.captureStackTrace(this, this.constructor);
  }
}

module.exports = AppError;
