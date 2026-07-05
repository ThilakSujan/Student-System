const jwt = require('jsonwebtoken');
const User = require('../models/User');
const Student = require('../models/Student');
const AppError = require('../utils/AppError');
const logger = require('../utils/logger');

/**
 * Protect routes - verify JWT and attach req.user
 */
const protect = async (req, res, next) => {
  try {
    let token;

    // Extract token from Authorization header
    if (req.headers.authorization && req.headers.authorization.startsWith('Bearer ')) {
      token = req.headers.authorization.split(' ')[1];
    }

    if (!token) {
      return next(new AppError('Authentication required. Please log in.', 401));
    }

    // Verify token
    let decoded;
    try {
      decoded = jwt.verify(token, process.env.JWT_SECRET);
    } catch (err) {
      if (err.name === 'TokenExpiredError') {
        return next(new AppError('Your session has expired. Please log in again.', 401));
      }
      if (err.name === 'JsonWebTokenError') {
        return next(new AppError('Invalid token. Please log in again.', 401));
      }
      return next(new AppError('Token verification failed.', 401));
    }

    const { id, role } = decoded;

    let user;

    if (role === 'student') {
      // Look up student
      user = await Student.findById(id).select('-reset_otp');
      if (!user) {
        return next(new AppError('Student account not found.', 401));
      }
      if (user.status === 'Inactive' || user.status === 'Suspended') {
        return next(new AppError('Your account is inactive. Contact admin.', 403));
      }
    } else {
      // Look up admin/staff
      user = await User.findById(id).select('-reset_otp -password');
      if (!user) {
        return next(new AppError('User account not found.', 401));
      }
      if (user.account_status !== 'Approved') {
        const statusMessages = {
          Pending: 'Your account is pending approval.',
          Rejected: 'Your account has been rejected.',
          Suspended: 'Your account has been suspended.',
        };
        return next(
          new AppError(statusMessages[user.account_status] || 'Account not approved.', 403)
        );
      }
    }

    req.user = user;
    req.userId = user._id;
    req.userRole = role || user.role;

    next();
  } catch (error) {
    logger.error('Auth middleware error:', error);
    next(new AppError('Authentication failed.', 500));
  }
};

/**
 * Optional auth - attach user if token provided, don't fail if not
 */
const optionalAuth = async (req, res, next) => {
  try {
    let token;
    if (req.headers.authorization && req.headers.authorization.startsWith('Bearer ')) {
      token = req.headers.authorization.split(' ')[1];
    }
    if (!token) return next();

    const decoded = jwt.verify(token, process.env.JWT_SECRET);
    const { id, role } = decoded;

    if (role === 'student') {
      req.user = await Student.findById(id).select('-reset_otp');
    } else {
      req.user = await User.findById(id).select('-reset_otp -password');
    }

    if (req.user) {
      req.userId = req.user._id;
      req.userRole = role || req.user.role;
    }
    next();
  } catch {
    next(); // ignore auth errors for optional auth
  }
};

module.exports = { protect, optionalAuth };
