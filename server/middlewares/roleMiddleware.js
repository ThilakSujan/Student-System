const AppError = require('../utils/AppError');

const requireRole = (...roles) => {
  return (req, res, next) => {
    if (!req.user) return next(new AppError('Authentication required.', 401));
    const userRole = req.userRole || req.user.role;
    if (!roles.includes(userRole)) {
      return next(new AppError(`Access denied. Roles allowed: ${roles.join(', ')}.`, 403));
    }
    next();
  };
};

const requireAdmin = requireRole('admin');
const requireAdminOrStaff = requireRole('admin', 'staff');
const requireStudent = requireRole('student');

module.exports = {
  requireRole,
  requireAdmin,
  requireAdminOrStaff,
  requireStudent,
};
