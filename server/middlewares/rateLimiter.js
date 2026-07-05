const rateLimit = require('express-rate-limit');

/**
 * Generic rate limiter factory
 */
const createLimiter = (windowMs, max, message) =>
  rateLimit({
    windowMs,
    max,
    message: {
      success: false,
      message,
    },
    standardHeaders: true,
    legacyHeaders: false,
    // Use IP as identifier
    keyGenerator: (req) => req.ip || req.connection.remoteAddress,
    handler: (req, res, next, options) => {
      res.status(429).json(options.message);
    },
  });

/**
 * Login limiter: 5 attempts per 15 minutes per IP
 */
const loginLimiter = createLimiter(
  15 * 60 * 1000, // 15 minutes
  5,
  'Too many login attempts. Please wait 15 minutes before trying again.'
);

/**
 * OTP limiter: 3 sends per hour per IP
 */
const otpLimiter = createLimiter(
  60 * 60 * 1000, // 1 hour
  3,
  'Too many OTP requests. You can request a maximum of 3 OTPs per hour.'
);

/**
 * General API limiter: 100 requests per 15 minutes per IP
 */
const apiLimiter = createLimiter(
  15 * 60 * 1000, // 15 minutes
  100,
  'Too many requests from this IP. Please try again after 15 minutes.'
);

/**
 * Stricter limiter for sensitive operations: 10 requests per 15 min
 */
const strictLimiter = createLimiter(
  15 * 60 * 1000,
  10,
  'Too many requests for this operation. Please try again after 15 minutes.'
);

module.exports = { loginLimiter, otpLimiter, apiLimiter, strictLimiter };
