const User = require('../models/User');
const AppError = require('../utils/AppError');
const emailService = require('../services/emailService');

exports.getAllUsers = async (req, res, next) => {
  try {
    const users = await User.find().select('-password');
    res.status(200).json({ status: 'success', data: users });
  } catch (error) { next(error); }
};

exports.getPendingUsers = async (req, res, next) => {
  try {
    const users = await User.find({ account_status: 'Pending' }).select('-password');
    res.status(200).json({ status: 'success', data: users });
  } catch (error) { next(error); }
};

exports.approveUser = async (req, res, next) => {
  try {
    const user = await User.findById(req.params.id);
    if (!user) throw new AppError('User not found', 404);

    user.account_status = 'Approved';
    user.approved_by = req.user.id;
    user.approved_at = Date.now();
    await user.save();

    emailService.sendAccountApproved(user.email, user.full_name || user.username).catch(console.error);

    res.status(200).json({ status: 'success', message: 'User approved successfully', data: user });
  } catch (error) { next(error); }
};

exports.rejectUser = async (req, res, next) => {
  try {
    const { reason } = req.body;
    const user = await User.findById(req.params.id);
    if (!user) throw new AppError('User not found', 404);

    user.account_status = 'Rejected';
    user.rejected_by = req.user.id;
    user.rejected_at = Date.now();
    user.rejection_reason = reason;
    await user.save();

    emailService.sendAccountRejected(user.email, user.full_name || user.username, reason).catch(console.error);

    res.status(200).json({ status: 'success', message: 'User rejected successfully' });
  } catch (error) { next(error); }
};

exports.deleteUser = async (req, res, next) => {
  try {
    await User.findByIdAndDelete(req.params.id);
    res.status(200).json({ status: 'success', message: 'User deleted' });
  } catch (error) { next(error); }
};
