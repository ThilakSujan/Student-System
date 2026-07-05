const Class = require('../models/Class');
const AppError = require('../utils/AppError');

exports.getAllClasses = async (req, res, next) => {
  try {
    const classes = await Class.find().populate('class_teacher', 'username full_name email');
    res.status(200).json({ status: 'success', data: classes });
  } catch (error) { next(error); }
};

exports.getClass = async (req, res, next) => {
  try {
    const classData = await Class.findById(req.params.id)
      .populate('class_teacher', 'username full_name email')
      .populate('students', 'student_name email');
    if (!classData) throw new AppError('Class not found', 404);
    res.status(200).json({ status: 'success', data: classData });
  } catch (error) { next(error); }
};

exports.createClass = async (req, res, next) => {
  try {
    const newClass = await Class.create(req.body);
    res.status(201).json({ status: 'success', data: newClass });
  } catch (error) { next(error); }
};

exports.updateClass = async (req, res, next) => {
  try {
    const updatedClass = await Class.findByIdAndUpdate(req.params.id, req.body, { new: true, runValidators: true });
    if (!updatedClass) throw new AppError('Class not found', 404);
    res.status(200).json({ status: 'success', data: updatedClass });
  } catch (error) { next(error); }
};

exports.deleteClass = async (req, res, next) => {
  try {
    await Class.findByIdAndDelete(req.params.id);
    res.status(200).json({ status: 'success', message: 'Class deleted' });
  } catch (error) { next(error); }
};

exports.assignStudents = async (req, res, next) => {
  try {
    const { studentIds } = req.body; // array of student ids
    const updatedClass = await Class.findByIdAndUpdate(
      req.params.id,
      { $addToSet: { students: { $each: studentIds } } },
      { new: true }
    );
    res.status(200).json({ status: 'success', data: updatedClass });
  } catch (error) { next(error); }
};

exports.removeStudent = async (req, res, next) => {
  try {
    const { studentId } = req.params;
    const updatedClass = await Class.findByIdAndUpdate(
      req.params.id,
      { $pull: { students: studentId } },
      { new: true }
    );
    res.status(200).json({ status: 'success', data: updatedClass });
  } catch (error) { next(error); }
};
