const Subject = require('../models/Subject');
const AppError = require('../utils/AppError');

exports.getAllSubjects = async (req, res, next) => {
  try {
    const subjects = await Subject.find();
    res.status(200).json({ status: 'success', data: subjects });
  } catch (error) { next(error); }
};

exports.getSubject = async (req, res, next) => {
  try {
    const subject = await Subject.findById(req.params.id);
    if (!subject) throw new AppError('Subject not found', 404);
    res.status(200).json({ status: 'success', data: subject });
  } catch (error) { next(error); }
};

exports.createSubject = async (req, res, next) => {
  try {
    const newSubject = await Subject.create(req.body);
    res.status(201).json({ status: 'success', data: newSubject });
  } catch (error) { next(error); }
};

exports.updateSubject = async (req, res, next) => {
  try {
    const updatedSubject = await Subject.findByIdAndUpdate(req.params.id, req.body, { new: true, runValidators: true });
    if (!updatedSubject) throw new AppError('Subject not found', 404);
    res.status(200).json({ status: 'success', data: updatedSubject });
  } catch (error) { next(error); }
};

exports.deleteSubject = async (req, res, next) => {
  try {
    await Subject.findByIdAndDelete(req.params.id);
    res.status(200).json({ status: 'success', message: 'Subject deleted' });
  } catch (error) { next(error); }
};
