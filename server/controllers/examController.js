const ExamSchedule = require('../models/ExamSchedule');
const AppError = require('../utils/AppError');

exports.getExams = async (req, res, next) => {
  try {
    const data = await ExamSchedule.find().populate('subject', 'subject_name').populate('class', 'class_name');
    res.status(200).json({ status: 'success', data });
  } catch (error) { next(error); }
};

exports.createExam = async (req, res, next) => {
  try {
    const data = await ExamSchedule.create({ ...req.body, created_by: req.user.id });
    res.status(201).json({ status: 'success', data });
  } catch (error) { next(error); }
};

exports.updateExam = async (req, res, next) => {
  try {
    const data = await ExamSchedule.findByIdAndUpdate(req.params.id, req.body, { new: true });
    if (!data) throw new AppError('Exam not found', 404);
    res.status(200).json({ status: 'success', data });
  } catch (error) { next(error); }
};

exports.deleteExam = async (req, res, next) => {
  try {
    await ExamSchedule.findByIdAndDelete(req.params.id);
    res.status(200).json({ status: 'success', message: 'Exam deleted' });
  } catch (error) { next(error); }
};
