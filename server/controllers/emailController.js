const EmailLog = require('../models/EmailLog');
const AppError = require('../utils/AppError');

exports.getEmailLogs = async (req, res, next) => {
  try {
    const logs = await EmailLog.find().sort({ createdAt: -1 });
    res.status(200).json({ status: 'success', data: logs });
  } catch (error) { next(error); }
};

exports.deleteEmailLog = async (req, res, next) => {
  try {
    await EmailLog.findByIdAndDelete(req.params.id);
    res.status(200).json({ status: 'success', message: 'Log deleted' });
  } catch (error) { next(error); }
};
