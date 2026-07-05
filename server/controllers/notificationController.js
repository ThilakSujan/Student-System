const Notification = require('../models/Notification');
const AppError = require('../utils/AppError');

exports.getNotifications = async (req, res, next) => {
  try {
    const role = req.userRole;
    let query = { status: 'Active', expiry_date: { $gte: new Date() } };
    if (role === 'student') query.target_audience = { $in: ['Student', 'Both'] };
    else if (role === 'staff' || role === 'admin') query.target_audience = { $in: ['Staff', 'Both'] };
    
    // Admin gets everything regardless of expiry
    if (role === 'admin') query = {};

    const data = await Notification.find(query).sort({ createdAt: -1 });
    res.status(200).json({ status: 'success', data });
  } catch (error) { next(error); }
};

exports.createNotification = async (req, res, next) => {
  try {
    const data = await Notification.create({ ...req.body, created_by: req.user.id });
    res.status(201).json({ status: 'success', data });
  } catch (error) { next(error); }
};

exports.deleteNotification = async (req, res, next) => {
  try {
    await Notification.findByIdAndDelete(req.params.id);
    res.status(200).json({ status: 'success', message: 'Notification deleted' });
  } catch (error) { next(error); }
};

exports.updateNotification = async (req, res, next) => {
  try {
    const data = await Notification.findByIdAndUpdate(req.params.id, req.body, { new: true, runValidators: true });
    if (!data) throw new AppError('Notification not found', 404);
    res.status(200).json({ status: 'success', data });
  } catch (error) { next(error); }
};
