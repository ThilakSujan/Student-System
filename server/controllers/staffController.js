const User = require('../models/User');
const AppError = require('../utils/AppError');
const emailService = require('../services/emailService');

exports.getStaff = async (req, res, next) => {
  try {
    const staff = await User.find({ role: 'staff' }).select('-password');
    res.status(200).json({ status: 'success', data: staff });
  } catch (error) { next(error); }
};

exports.getStaffMember = async (req, res, next) => {
  try {
    const staff = await User.findOne({ _id: req.params.id, role: 'staff' }).select('-password');
    if (!staff) throw new AppError('Staff member not found', 404);
    res.status(200).json({ status: 'success', data: staff });
  } catch (error) { next(error); }
};

exports.createStaff = async (req, res, next) => {
  try {
    const data = await User.create({ ...req.body, role: 'staff', account_status: 'Approved' });
    res.status(201).json({ status: 'success', data });
  } catch (error) { next(error); }
};

exports.updateStaff = async (req, res, next) => {
  try {
    const data = await User.findOneAndUpdate(
      { _id: req.params.id, role: 'staff' },
      req.body,
      { new: true, runValidators: true }
    ).select('-password');
    if (!data) throw new AppError('Staff member not found', 404);
    res.status(200).json({ status: 'success', data });
  } catch (error) { next(error); }
};

exports.deleteStaff = async (req, res, next) => {
  try {
    const staff = await User.findOneAndDelete({ _id: req.params.id, role: 'staff' });
    if (!staff) throw new AppError('Staff member not found', 404);
    res.status(200).json({ status: 'success', message: 'Staff member deleted' });
  } catch (error) { next(error); }
};
