const FeeCategory = require('../models/FeeCategory');
const FeeStructure = require('../models/FeeStructure');
const FeePayment = require('../models/FeePayment');
const AppError = require('../utils/AppError');
const helpers = require('../utils/helpers');

// Categories
exports.getCategories = async (req, res, next) => {
  try {
    const data = await FeeCategory.find();
    res.status(200).json({ status: 'success', data });
  } catch (error) { next(error); }
};

exports.createCategory = async (req, res, next) => {
  try {
    const data = await FeeCategory.create({ ...req.body, created_by: req.user.id });
    res.status(201).json({ status: 'success', data });
  } catch (error) { next(error); }
};

// Structures
exports.getStructures = async (req, res, next) => {
  try {
    const data = await FeeStructure.find().populate('category', 'name').populate('class', 'class_name section');
    res.status(200).json({ status: 'success', data });
  } catch (error) { next(error); }
};

exports.createStructure = async (req, res, next) => {
  try {
    const data = await FeeStructure.create({ ...req.body, created_by: req.user.id });
    res.status(201).json({ status: 'success', data });
  } catch (error) { next(error); }
};

// Payments
exports.getPayments = async (req, res, next) => {
  try {
    const role = req.userRole;
    let query = {};
    if (role === 'student') query.student = req.user.id;
    const data = await FeePayment.find(query)
      .populate('student', 'student_name')
      .populate({ path: 'feeStructure', populate: { path: 'category' } });
    res.status(200).json({ status: 'success', data });
  } catch (error) { next(error); }
};

exports.createPayment = async (req, res, next) => {
  try {
    const payload = {
      ...req.body,
      recorded_by: req.user.id,
      receipt_no: helpers.generateReceiptNo()
    };
    const data = await FeePayment.create(payload);
    res.status(201).json({ status: 'success', data });
  } catch (error) { next(error); }
};
