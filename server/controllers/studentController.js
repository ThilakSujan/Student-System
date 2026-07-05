const Student = require('../models/Student');
const AppError = require('../utils/AppError');
const fs = require('fs');
const csv = require('csv-parser');
const reportService = require('../services/reportService');

exports.getAllStudents = async (req, res, next) => {
  try {
    const students = await Student.find();
    res.status(200).json({ status: 'success', data: students });
  } catch (error) { next(error); }
};

exports.getStudent = async (req, res, next) => {
  try {
    const student = await Student.findById(req.params.id);
    if (!student) throw new AppError('Student not found', 404);
    res.status(200).json({ status: 'success', data: student });
  } catch (error) { next(error); }
};

exports.createStudent = async (req, res, next) => {
  try {
    const student = await Student.create(req.body);
    res.status(201).json({ status: 'success', data: student });
  } catch (error) { next(error); }
};

exports.updateStudent = async (req, res, next) => {
  try {
    const student = await Student.findByIdAndUpdate(req.params.id, req.body, { new: true, runValidators: true });
    if (!student) throw new AppError('Student not found', 404);
    res.status(200).json({ status: 'success', data: student });
  } catch (error) { next(error); }
};

exports.deleteStudent = async (req, res, next) => {
  try {
    await Student.findByIdAndDelete(req.params.id);
    res.status(200).json({ status: 'success', message: 'Student deleted' });
  } catch (error) { next(error); }
};

exports.importStudents = async (req, res, next) => {
  try {
    if (!req.file) throw new AppError('No CSV file provided', 400);
    const results = [];
    fs.createReadStream(req.file.path)
      .pipe(csv())
      .on('data', (data) => results.push(data))
      .on('end', async () => {
        try {
          await Student.insertMany(results);
          res.status(200).json({ status: 'success', message: `${results.length} students imported` });
        } catch (err) {
          next(err);
        }
      });
  } catch (error) { next(error); }
};
